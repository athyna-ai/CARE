from django.shortcuts import render, redirect, get_object_or_404
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

from .models import MedicalRecord
from patients.models import Student, Faculty
from admin_panel.models import User
from logs.models import ActivityLog
from core.helpers import sanitize_string, log_activity

logger = logging.getLogger(__name__)


class SaveMedicalFormView(View):
    """Save medical forms - matches PHP medical/save_medical_form.php exactly"""
    
    @method_decorator(login_required)
    def post(self, request):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            patient_id = int(request.POST.get('patient_id', 0))
            patient_type = request.POST.get('patient_type', '')
            form_type = request.POST.get('form_type', '')
            
            if not patient_id or not patient_type or not form_type:
                messages.error(request, 'Missing required parameters.')
                return redirect('admin:dashboard')
            
            # Validate patient exists
            if patient_type == 'student':
                patient = get_object_or_404(Student, id=patient_id)
            elif patient_type == 'faculty':
                patient = get_object_or_404(Faculty, id=patient_id)
            else:
                messages.error(request, 'Invalid patient type.')
                return redirect('admin:dashboard')
            
            # Build form data based on form type
            form_data = {}
            
            if form_type == 'athlete':
                form_data = self._build_athlete_form_data(request)
            elif form_type == 'general':
                form_data = self._build_general_form_data(request)
            elif form_type == 'emergency':
                form_data = self._build_emergency_form_data(request)
            elif form_type == 'medical_history':
                form_data = self._build_medical_history_form_data(request)
            else:
                messages.error(request, 'Invalid form type.')
                return redirect('admin:dashboard')
            
            # Create medical record
            medical_record = MedicalRecord.objects.create(
                patient_id=patient_id,
                patient_type=patient_type,
                form_type=form_type,
                form_data=form_data,
                created_by=request.user,
            )
            
            # Log activity
            log_activity(request.user, 'medical_form_created', 
                        f'Created {form_type} medical form for {patient_type} ID {patient_id}', 
                        'medical/forms')
            
            messages.success(request, f'{form_type.title()} form saved successfully.')
            
            return redirect('patients:patient_view', patient_id=patient_id, patient_type=patient_type)
            
        except Exception as e:
            logger.error(f"Save medical form error: {str(e)}")
            messages.error(request, 'An error occurred while saving the medical form.')
            return redirect('admin:dashboard')


class MedicalFormView(View):
    """Display medical form - matches PHP medical/medical_form.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request, patient_id, patient_type):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            # Get patient
            if patient_type == 'student':
                patient = get_object_or_404(Student, id=patient_id)
            elif patient_type == 'faculty':
                patient = get_object_or_404(Faculty, id=patient_id)
            else:
                messages.error(request, 'Invalid patient type.')
                return redirect('admin:dashboard')
            
            # Get existing medical records
            medical_records = MedicalRecord.objects.filter(
                patient_id=patient_id,
                patient_type=patient_type
            ).order_by('-created_at')
            
            context = {
                'page_title': 'Medical Form',
                'show_top_nav': True,
                'show_sidebar': True,
                'patient': patient,
                'patient_type': patient_type,
                'medical_records': medical_records,
            }
            
            return render(request, 'medical/medical_form.html', context)
            
        except Exception as e:
            logger.error(f"Medical form view error: {str(e)}")
            messages.error(request, 'An error occurred while loading the medical form.')
            return redirect('admin:dashboard')


class EditMedicalRecordView(View):
    """Edit medical record - matches PHP medical/edit_medical_record.php"""
    
    @method_decorator(login_required)
    def get(self, request, record_id):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            # Get medical record
            record = get_object_or_404(MedicalRecord, id=record_id)
            
            # Get patient info
            if record.patient_type == 'student':
                from patients.models import Student
                patient = get_object_or_404(Student, id=record.patient_id)
            else:
                from patients.models import Faculty
                patient = get_object_or_404(Faculty, id=record.patient_id)
            
            context = {
                'page_title': 'Edit Medical Record',
                'show_top_nav': True,
                'show_sidebar': True,
                'record': record,
                'patient': patient,
                'patient_type': record.patient_type,
            }
            
            return render(request, 'medical/edit_medical_record.html', context)
            
        except Exception as e:
            logger.error(f"Edit medical record view error: {str(e)}")
            messages.error(request, 'An error occurred while loading the medical record.')
            return redirect('admin:dashboard')
    
    @method_decorator(login_required)
    def post(self, request, record_id):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            # Get medical record
            record = get_object_or_404(MedicalRecord, id=record_id)
            
            # Update form data
            form_data = {}
            for key, value in request.POST.items():
                if key not in ['csrfmiddlewaretoken', 'csrf_token']:
                    form_data[key] = value
            
            record.form_data = form_data
            record.save()
            
            messages.success(request, 'Medical record updated successfully.')
            return redirect('patients:patient_view', record.patient_id, record.patient_type)
            
        except Exception as e:
            logger.error(f"Edit medical record error: {str(e)}")
            messages.error(request, 'An error occurred while updating the medical record.')
            return redirect('admin:dashboard')


@login_required
def delete_medical_record(request, record_id):
    """Delete medical record - matches PHP medical/delete_medical_record.php"""
    # Ensure user is admin
    if not request.user.is_admin:
        messages.error(request, 'Access denied. Admin privileges required.')
        return redirect('auth:login')
    
    try:
        # Get medical record
        record = get_object_or_404(MedicalRecord, id=record_id)
        patient_id = record.patient_id
        patient_type = record.patient_type
        
        # Delete record
        record.delete()
        
        messages.success(request, 'Medical record deleted successfully.')
        return redirect('patients:patient_view', patient_id, patient_type)
        
    except Exception as e:
        logger.error(f"Delete medical record error: {str(e)}")
        messages.error(request, 'An error occurred while deleting the medical record.')
        return redirect('admin:dashboard')