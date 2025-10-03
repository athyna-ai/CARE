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

from .models import RFIDLog
from patients.models import Student, Faculty
from admin_panel.models import User
from logs.models import ActivityLog
from core.helpers import sanitize_string, log_activity

logger = logging.getLogger(__name__)


class RFIDPortalView(View):
    """RFID search portal - matches PHP rfid/rfid_portal.php exactly"""
    
    def get(self, request):
        context = {
            'page_title': 'RFID Portal',
            'show_top_nav': True,
            'show_sidebar': True,
        }
        return render(request, 'rfid/rfid_portal.html', context)
    
    def post(self, request):
        rfid = sanitize_string(request.POST.get('rfid', ''))
        
        if not rfid:
            messages.error(request, 'Please enter an RFID number.')
            return render(request, 'rfid/rfid_portal.html', {
                'page_title': 'RFID Portal',
                'show_top_nav': True,
                'show_sidebar': True,
            })
        
        try:
            # Search for student with this RFID
            student = Student.objects.filter(rfid=rfid).first()
            
            # Search for faculty with this RFID
            faculty = Faculty.objects.filter(rfid=rfid).first()
            
            if student:
                # Log RFID access
                RFIDLog.objects.create(
                    rfid=rfid,
                    user=request.user if request.user.is_authenticated else None,
                    action='student_access',
                    success=True,
                    ip_address=self._get_client_ip(request),
                    user_agent=request.META.get('HTTP_USER_AGENT', ''),
                    details=f'Accessed student: {student.name}',
                )
                
                # Log activity if user is authenticated
                if request.user.is_authenticated:
                    log_activity(request.user, 'rfid_student_access', f'Accessed student via RFID: {student.name}', 'rfid/portal')
                
                return redirect('patients:patient_view', patient_id=student.id, patient_type='student')
            
            elif faculty:
                # Log RFID access
                RFIDLog.objects.create(
                    rfid=rfid,
                    user=request.user if request.user.is_authenticated else None,
                    action='faculty_access',
                    success=True,
                    ip_address=self._get_client_ip(request),
                    user_agent=request.META.get('HTTP_USER_AGENT', ''),
                    details=f'Accessed faculty: {faculty.name}',
                )
                
                # Log activity if user is authenticated
                if request.user.is_authenticated:
                    log_activity(request.user, 'rfid_faculty_access', f'Accessed faculty via RFID: {faculty.name}', 'rfid/portal')
                
                return redirect('patients:patient_view', patient_id=faculty.id, patient_type='faculty')
            
            else:
                # No record found
                RFIDLog.objects.create(
                    rfid=rfid,
                    user=request.user if request.user.is_authenticated else None,
                    action='no_record_found',
                    success=False,
                    ip_address=self._get_client_ip(request),
                    user_agent=request.META.get('HTTP_USER_AGENT', ''),
                    details='No record found for RFID',
                )
                
                # Log activity if user is authenticated
                if request.user.is_authenticated:
                    log_activity(request.user, 'rfid_no_record', f'No record found for RFID: {rfid}', 'rfid/portal')
                
                context = {
                    'page_title': 'RFID Portal',
                    'show_top_nav': True,
                    'show_sidebar': True,
                    'no_record_found': True,
                    'rfid_searched': rfid,
                }
                return render(request, 'rfid/rfid_portal.html', context)
                
        except Exception as e:
            logger.error(f"RFID portal error: {str(e)}")
            messages.error(request, 'An error occurred while searching for RFID.')
            return render(request, 'rfid/rfid_portal.html', {
                'page_title': 'RFID Portal',
                'show_top_nav': True,
                'show_sidebar': True,
            })
    
    def _get_client_ip(self, request):
        """Get client IP address"""
        x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
        if x_forwarded_for:
            ip = x_forwarded_for.split(',')[0]
        else:
            ip = request.META.get('REMOTE_ADDR')
        return ip