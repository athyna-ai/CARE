from django.shortcuts import render, redirect
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_http_methods
from django.utils.decorators import method_decorator
from django.views import View
from django.core.exceptions import ValidationError
from django.db import transaction
from django.utils import timezone
from datetime import datetime, timedelta
import json
import logging

from .models import Report, AnalyticsCache
from patients.models import Student, Faculty
from admin_panel.models import User
from logs.models import ActivityLog, VisitationLog
from medical.models import MedicalRecord
from core.helpers import sanitize_string, log_activity

logger = logging.getLogger(__name__)


class AnalyticsView(View):
    """Analytics and reports - matches PHP reports/analytics.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        # Get date range from request
        start_date_str = request.GET.get('start_date', '')
        end_date_str = request.GET.get('end_date', '')
        
        # Default to current month if no dates provided
        if not start_date_str or not end_date_str:
            today = timezone.now().date()
            start_date = today.replace(day=1)  # First day of current month
            end_date = today
        else:
            try:
                start_date = datetime.strptime(start_date_str, '%Y-%m-%d').date()
                end_date = datetime.strptime(end_date_str, '%Y-%m-%d').date()
            except ValueError:
                messages.error(request, 'Invalid date format.')
                return redirect('reports:analytics')
        
        # Get statistics
        stats = self._get_statistics(start_date, end_date)
        
        context = {
            'page_title': 'Reports & Analytics',
            'show_top_nav': True,
            'show_sidebar': True,
            'stats': stats,
            'start_date': start_date,
            'end_date': end_date,
            'start_date_str': start_date_str,
            'end_date_str': end_date_str,
        }
        
        return render(request, 'reports/analytics.html', context)
    
    def _get_statistics(self, start_date, end_date):
        """Get analytics statistics"""
        stats = {}
        
        try:
            # Basic counts
            stats['total_students'] = Student.objects.filter(status='Active').count()
            stats['total_faculty'] = Faculty.objects.filter(status='Active').count()
            
            # Visitation statistics
            visitation_stats = VisitationLog.objects.filter(
                visit_date__date__range=[start_date, end_date]
            ).aggregate(
                total_visits=models.Count('id'),
                total_medications=models.Count('id', filter=models.Q(medication_given=True)),
                total_injuries=models.Count('id', filter=models.Q(injury=True)),
            )
            
            stats.update(visitation_stats)
            
            # Top reasons for visits
            top_reasons = VisitationLog.objects.filter(
                visit_date__date__range=[start_date, end_date]
            ).values('reason').annotate(
                count=models.Count('id')
            ).order_by('-count')[:10]
            
            stats['top_reasons'] = list(top_reasons)
            
            # Most common medications
            common_medications = VisitationLog.objects.filter(
                visit_date__date__range=[start_date, end_date],
                medication_given=True
            ).values('medication_name').annotate(
                count=models.Count('id')
            ).order_by('-count')[:10]
            
            stats['common_medications'] = list(common_medications)
            
            # Age group analysis
            age_groups = Student.objects.filter(status='Active').extra(
                select={
                    'age_group': "CASE "
                                "WHEN age < 6 THEN 'Pre-school' "
                                "WHEN age BETWEEN 6 AND 11 THEN 'Elementary' "
                                "WHEN age BETWEEN 12 AND 17 THEN 'High School' "
                                "WHEN age BETWEEN 18 AND 21 THEN 'Senior High School' "
                                "ELSE 'College' END"
                }
            ).values('age_group').annotate(
                count=models.Count('id')
            ).order_by('age_group')
            
            stats['age_groups'] = list(age_groups)
            
            # Daily visit trends
            daily_trends = VisitationLog.objects.filter(
                visit_date__date__range=[start_date, end_date]
            ).extra(
                select={'visit_date': 'DATE(visit_date)'}
            ).values('visit_date').annotate(
                count=models.Count('id')
            ).order_by('visit_date')
            
            stats['daily_trends'] = list(daily_trends)
            
            # Security monitoring
            security_logs = ActivityLog.objects.filter(
                timestamp__date__range=[start_date, end_date],
                action__in=['login_failed', 'rfid_verification_failed', 'unauthorized_access']
            ).count()
            
            stats['security_incidents'] = security_logs
            
            # Medical records count
            medical_records_count = MedicalRecord.objects.filter(
                created_at__date__range=[start_date, end_date]
            ).count()
            
            stats['medical_records_created'] = medical_records_count
            
        except Exception as e:
            logger.error(f"Analytics error: {str(e)}")
            stats = {
                'total_students': 0,
                'total_faculty': 0,
                'total_visits': 0,
                'total_medications': 0,
                'total_injuries': 0,
                'top_reasons': [],
                'common_medications': [],
                'age_groups': [],
                'daily_trends': [],
                'security_incidents': 0,
                'medical_records_created': 0,
            }
        
        return stats


class GenerateReportView(View):
    """Generate custom reports"""
    
    @method_decorator(login_required)
    def post(self, request):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            report_type = request.POST.get('report_type', '')
            title = request.POST.get('title', '')
            start_date = request.POST.get('start_date', '')
            end_date = request.POST.get('end_date', '')
            
            if not report_type or not title:
                messages.error(request, 'Report type and title are required.')
                return redirect('reports:analytics')
            
            # Create report record
            report = Report.objects.create(
                report_type=report_type,
                title=title,
                start_date=start_date if start_date else None,
                end_date=end_date if end_date else None,
                status='generating',
                generated_by=request.user,
            )
            
            # Generate report data based on type
            if report_type == 'daily':
                report_data = self._generate_daily_report(start_date, end_date)
            elif report_type == 'weekly':
                report_data = self._generate_weekly_report(start_date, end_date)
            elif report_type == 'monthly':
                report_data = self._generate_monthly_report(start_date, end_date)
            elif report_type == 'security':
                report_data = self._generate_security_report(start_date, end_date)
            else:
                report_data = self._generate_custom_report(start_date, end_date)
            
            # Update report with data
            report.data = report_data
            report.status = 'completed'
            report.completed_at = timezone.now()
            report.save()
            
            # Log activity
            log_activity(request.user, 'report_generated', 
                        f'Generated {report_type} report: {title}', 
                        'reports/generate')
            
            messages.success(request, f'{title} report generated successfully.')
            
            return redirect('reports:analytics')
            
        except Exception as e:
            logger.error(f"Generate report error: {str(e)}")
            messages.error(request, 'An error occurred while generating the report.')
            return redirect('reports:analytics')
    
    def _generate_daily_report(self, start_date, end_date):
        """Generate daily report data"""
        # Implementation for daily report
        return {'message': 'Daily report data'}
    
    def _generate_weekly_report(self, start_date, end_date):
        """Generate weekly report data"""
        # Implementation for weekly report
        return {'message': 'Weekly report data'}
    
    def _generate_monthly_report(self, start_date, end_date):
        """Generate monthly report data"""
        # Implementation for monthly report
        return {'message': 'Monthly report data'}
    
    def _generate_security_report(self, start_date, end_date):
        """Generate security report data"""
        # Implementation for security report
        return {'message': 'Security report data'}
    
    def _generate_custom_report(self, start_date, end_date):
        """Generate custom report data"""
        # Implementation for custom report
        return {'message': 'Custom report data'}