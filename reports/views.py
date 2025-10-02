from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.http import JsonResponse
from django.core.paginator import Paginator
from django.db.models import Q, Count, Sum, Avg, Case, When, IntegerField
from django.utils import timezone
from datetime import datetime, timedelta
import json
from .models import DashboardWidget, DataExport, ReportTemplate, GeneratedReport
from patients.models import Student, Faculty, ActivityLog
from medical.models import MedicalRecord, Visitation


def log_activity(user_id, user_type, action, description, location, rfid_used=None, success=True, error_message=None):
    """Log activity - matches PHP log_activity function"""
    try:
        ActivityLog.objects.create(
            user_id=user_id,
            user_type=user_type,
            action=action,
            description=description,
            action_description=description,
            location=location,
            rfid_used=rfid_used,
            success=success,
            error_message=error_message,
            ip_address='127.0.0.1',  # Default for now
            user_agent='Django System'
        )
    except Exception as e:
        print(f"Error logging activity: {e}")


@login_required
def analytics(request):
    """Reports & Analytics - EXACT same functionality as PHP analytics.php"""
    # Get date range from URL parameters
    start_date = request.GET.get('start_date', timezone.now().replace(day=1).strftime('%Y-%m-%d'))
    end_date = request.GET.get('end_date', timezone.now().strftime('%Y-%m-%d'))
    
    # Validate dates
    try:
        start_date = datetime.strptime(start_date, '%Y-%m-%d').date()
        end_date = datetime.strptime(end_date, '%Y-%m-%d').date()
    except ValueError:
        start_date = timezone.now().replace(day=1).date()
        end_date = timezone.now().date()
    
    # Initialize stats
    stats = {
        'total_students': 0,
        'total_faculty': 0,
        'total_visits': 0,
        'student_visits': 0,
        'faculty_visits': 0,
        'medication_given': 0,
        'injuries': 0,
        'top_reasons': [],
        'daily_trends': [],
        'monthly_trends': [],
        'medications': [],
        'age_groups': []
    }
    
    try:
        # Total students and faculty
        stats['total_students'] = Student.objects.filter(archived=False).count()
        stats['total_faculty'] = Faculty.objects.filter(archived=False).count()
        
        # Visitation statistics for date range
        visitations = Visitation.objects.filter(
            created_at__date__range=[start_date, end_date],
            archived=False
        )
        
        stats['total_visits'] = visitations.count()
        stats['student_visits'] = visitations.filter(patient_type='student').count()
        stats['faculty_visits'] = visitations.filter(patient_type='faculty').count()
        
        # Count medications and injuries from description field
        medication_count = 0
        injury_count = 0
        for visit in visitations:
            if 'Medication: ' in visit.description and 'Medication: None' not in visit.description:
                medication_count += 1
            if 'Injury: Yes' in visit.description:
                injury_count += 1
        
        stats['medication_given'] = medication_count
        stats['injuries'] = injury_count
        
        # Top reasons for visits
        top_reasons = visitations.values('reason').annotate(
            count=Count('reason')
        ).order_by('-count')[:5]
        
        stats['top_reasons'] = list(top_reasons)
        
        # Daily visit trends (last 30 days)
        daily_start = end_date - timedelta(days=30)
        daily_visits = Visitation.objects.filter(
            created_at__date__range=[daily_start, end_date],
            archived=False
        ).extra(
            select={'date': 'DATE(created_at)'}
        ).values('date').annotate(
            visits=Count('id')
        ).order_by('date')
        
        stats['daily_trends'] = list(daily_visits)
        
        # Monthly visit trends (last 12 months)
        monthly_start = end_date - timedelta(days=365)
        monthly_visits = Visitation.objects.filter(
            created_at__date__range=[monthly_start, end_date],
            archived=False
        ).extra(
            select={'month': "DATE_FORMAT(created_at, '%%Y-%%m')"}
        ).values('month').annotate(
            visits=Count('id'),
            student_visits=Count('id', filter=Q(patient_type='student')),
            faculty_visits=Count('id', filter=Q(patient_type='faculty'))
        ).order_by('month')
        
        stats['monthly_trends'] = list(monthly_visits)
        
        # Most common medications (extract from description)
        medication_stats = {}
        for visit in visitations:
            if 'Medication: ' in visit.description and 'Medication: None' not in visit.description:
                # Extract medication name from description
                desc_parts = visit.description.split('\n')
                for part in desc_parts:
                    if part.startswith('Medication: '):
                        med_name = part.replace('Medication: ', '').strip()
                        if med_name and med_name != 'None':
                            medication_stats[med_name] = medication_stats.get(med_name, 0) + 1
                        break
        
        # Convert to list and sort
        stats['medications'] = [
            {'medication_name': name, 'count': count}
            for name, count in sorted(medication_stats.items(), key=lambda x: x[1], reverse=True)[:10]
        ]
        
        # Age group analysis (students only)
        student_visits = visitations.filter(patient_type='student')
        age_groups = {}
        
        for visit in student_visits:
            if visit.student and visit.student.age:
                age = visit.student.age
                if 16 <= age <= 18:
                    group = '16-18'
                elif 19 <= age <= 21:
                    group = '19-21'
                elif 22 <= age <= 24:
                    group = '22-24'
                elif age >= 25:
                    group = '25+'
                else:
                    group = 'Under 16'
                
                age_groups[group] = age_groups.get(group, 0) + 1
        
        stats['age_groups'] = [
            {'age_group': group, 'count': count}
            for group, count in sorted(age_groups.items())
        ]
        
    except Exception as e:
        print(f"Analytics error: {e}")
        log_activity(
            user_id=request.user.id if request.user.is_authenticated else 0,
            user_type='admin' if request.user.is_authenticated else 'system',
            action='analytics_error',
            description=f"Analytics error: {e}",
            location='analytics',
            success=False,
            error_message=str(e)
        )
    
    # Debug information
    debug_info = {
        'start_date': start_date.strftime('%Y-%m-%d'),
        'end_date': end_date.strftime('%Y-%m-%d'),
        'daily_trends_count': len(stats['daily_trends']),
        'total_visits': stats['total_visits']
    }
    
    context = {
        'stats': stats,
        'debug_info': debug_info,
        'start_date': start_date.strftime('%Y-%m-%d'),
        'end_date': end_date.strftime('%Y-%m-%d'),
        'page_title': 'Reports & Analytics',
    }
    return render(request, 'reports/analytics.html', context)


@login_required
def dashboard_widgets(request):
    """Dashboard widgets management"""
    widgets = DashboardWidget.objects.filter(user=request.user).order_by('position')
    
    context = {
        'widgets': widgets,
        'page_title': 'Dashboard Widgets',
    }
    return render(request, 'reports/dashboard_widgets.html', context)


@login_required
def data_export(request):
    """Data export functionality"""
    if request.method == 'POST':
        export_type = request.POST.get('export_type')
        date_range = request.POST.get('date_range')
        
        try:
            # Create export record
            export = DataExport.objects.create(
                user=request.user,
                export_type=export_type,
                parameters={'date_range': date_range},
                status='processing'
            )
            
            # Process export (simplified)
            export.status = 'completed'
            export.file_path = f'/exports/{export.id}_{export_type}.csv'
            export.save()
            
            messages.success(request, 'Data export completed successfully!')
            return redirect('reports:export_detail', export_id=export.id)
            
        except Exception as e:
            messages.error(request, f'Error creating export: {e}')
    
    context = {
        'page_title': 'Data Export',
    }
    return render(request, 'reports/data_export.html', context)


@login_required
def export_detail(request, export_id):
    """View export details"""
    export = get_object_or_404(DataExport, id=export_id, user=request.user)
    
    context = {
        'export': export,
        'page_title': f'Export: {export.export_type}',
    }
    return render(request, 'reports/export_detail.html', context)


@login_required
def report_templates(request):
    """Report templates management"""
    templates = ReportTemplate.objects.all()
    
    context = {
        'templates': templates,
        'page_title': 'Report Templates',
    }
    return render(request, 'reports/report_templates.html', context)


@login_required
def generate_report(request, template_id):
    """Generate report from template"""
    template = get_object_or_404(ReportTemplate, id=template_id)
    
    if request.method == 'POST':
        try:
            # Generate report
            report = GeneratedReport.objects.create(
                user=request.user,
                template=template,
                parameters=request.POST.dict(),
                status='completed'
            )
            
            messages.success(request, 'Report generated successfully!')
            return redirect('reports:generated_report_detail', report_id=report.id)
            
        except Exception as e:
            messages.error(request, f'Error generating report: {e}')
    
    context = {
        'template': template,
        'page_title': f'Generate Report: {template.name}',
    }
    return render(request, 'reports/generate_report.html', context)


@login_required
def generated_report_detail(request, report_id):
    """View generated report"""
    report = get_object_or_404(GeneratedReport, id=report_id, user=request.user)
    
    context = {
        'report': report,
        'page_title': f'Report: {report.template.name}',
    }
    return render(request, 'reports/generated_report_detail.html', context)
