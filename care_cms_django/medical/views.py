from django.shortcuts import render, redirect, get_object_or_404
from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.http import JsonResponse
from django.utils import timezone
from django.db.models import Q
import json

from .models import MedicalRecord, Visitation, CheckupForm, DailyLog
from patients.models import Student, Faculty, ActivityLog


def log_activity(user_id, user_type, action, description, location, rfid_used=None, success=True, error_message=None):
    """Log activity - matches PHP log_activity function"""
    ActivityLog.objects.create(
        user_id=user_id,
        user_type=user_type,
        action=action,
        description=description,
        location=location,
        rfid_used=rfid_used,
        success=success,
        error_message=error_message,
        ip_address="127.0.0.1",  # Placeholder
        user_agent="Django App"  # Placeholder
    )


@login_required
def medical_history(request):
    """Medical history management page"""
    context = {
        'page_title': 'Medical History Management',
    }
    return render(request, 'medical/medical_history.html', context)


@login_required
def create_medical_record(request):
    """Create medical record - matches PHP save_medical_form.php"""
    if request.method == 'POST':
        try:
            patient_id = int(request.POST.get('patient_id'))
            patient_type = request.POST.get('patient_type')
            form_type = request.POST.get('form_type')
            
            # Validate patient type
            if patient_type not in ['student', 'faculty']:
                raise ValueError('Invalid patient type')
            
            # Validate form type
            if form_type not in ['athlete', 'general', 'emergency', 'medical_history']:
                raise ValueError('Invalid form type')
            
            # Prepare form data based on type
            form_data = {}
            
            if form_type == 'athlete':
                form_data = {
                    'sport': request.POST.get('sport', ''),
                    'position': request.POST.get('position', ''),
                    'height': request.POST.get('height', ''),
                    'weight': request.POST.get('weight', ''),
                    'medical_history': request.POST.get('medical_history', ''),
                    'physical_exam': request.POST.get('physical_exam', ''),
                    'recommendations': request.POST.get('recommendations', '')
                }
            elif form_type == 'general':
                form_data = {
                    'assessment_plan': request.POST.get('assessment_plan', 'N/A'),
                    'height': request.POST.get('height', ''),
                    'weight': request.POST.get('weight', ''),
                    'bmi': request.POST.get('bmi', ''),
                    'bmi_status': request.POST.get('bmi_status', ''),
                    'heart_rate': request.POST.get('heart_rate', ''),
                    'heart_rate_status': request.POST.get('heart_rate_status', ''),
                    'temperature': request.POST.get('temperature', ''),
                    'temperature_status': request.POST.get('temperature_status', ''),
                    'blood_pressure': request.POST.get('blood_pressure', ''),
                    'blood_pressure_status': request.POST.get('blood_pressure_status', '')
                }
            elif form_type == 'emergency':
                form_data = {
                    'emergency_type': request.POST.get('emergency_type', ''),
                    'severity': request.POST.get('severity', ''),
                    'emergency_description': request.POST.get('emergency_description', ''),
                    'immediate_actions': request.POST.get('immediate_actions', ''),
                    'heart_rate': request.POST.get('heart_rate', ''),
                    'blood_pressure': request.POST.get('blood_pressure', ''),
                    'temperature': request.POST.get('temperature', ''),
                    'follow_up': request.POST.get('follow_up', '')
                }
            elif form_type == 'medical_history':
                form_data = {
                    'assessment_plan': request.POST.get('assessment_plan', 'N/A'),
                    'height': request.POST.get('height', ''),
                    'weight': request.POST.get('weight', ''),
                    'bmi': request.POST.get('bmi', ''),
                    'bmi_status': request.POST.get('bmi_status', ''),
                    'heart_rate': request.POST.get('heart_rate', ''),
                    'heart_rate_status': request.POST.get('heart_rate_status', ''),
                    'temperature': request.POST.get('temperature', ''),
                    'temperature_status': request.POST.get('temperature_status', ''),
                    'blood_pressure': request.POST.get('blood_pressure', ''),
                    'blood_pressure_status': request.POST.get('blood_pressure_status', '')
                }
            
            # Remove empty values
            form_data = {k: v for k, v in form_data.items() if v != ''}
            
            # Create medical record
            medical_record = MedicalRecord.objects.create(
                patient_id=patient_id,
                patient_type=patient_type,
                form_type=form_type,
                form_data=form_data,
                created_by=request.user
            )
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action='medical_form_created',
                description=f"Created {form_type} medical form for patient ID {patient_id}",
                location='medical/forms'
            )
            
            messages.success(request, "Medical form saved successfully!")
            return redirect('patients:patient_detail', patient_id=patient_id, patient_type=patient_type)
            
        except Exception as e:
            messages.error(request, f"Error saving medical form: {str(e)}")
            return redirect('patients:patient_detail', patient_id=patient_id, patient_type=patient_type)
    
    return render(request, 'medical/create_medical_record.html', {'page_title': 'Create Medical Record'})


@login_required
def save_medical_history(request):
    """Save medical history - matches PHP save_medical_history.php"""
    if request.method == 'POST':
        try:
            patient_id = int(request.POST.get('patient_id'))
            patient_type = request.POST.get('patient_type')
            form_type = request.POST.get('form_type', 'medical_history')
            
            # Validate patient type
            if patient_type not in ['student', 'faculty']:
                raise ValueError('Invalid patient type')
            
            # Prepare form data based on type
            if form_type == 'general_checkup':
                form_data = {
                    'assessment_plan': request.POST.get('assessment_plan', 'N/A'),
                    'height': request.POST.get('height', ''),
                    'weight': request.POST.get('weight', ''),
                    'bmi': request.POST.get('bmi', ''),
                    'bmi_status': request.POST.get('bmi_status', ''),
                    'heart_rate': request.POST.get('heart_rate', ''),
                    'heart_rate_status': request.POST.get('heart_rate_status', ''),
                    'temperature': request.POST.get('temperature', ''),
                    'temperature_status': request.POST.get('temperature_status', ''),
                    'blood_pressure': request.POST.get('blood_pressure', ''),
                    'blood_pressure_status': request.POST.get('blood_pressure_status', '')
                }
                form_type_value = 'general_checkup'
                success_message = "General CheckUp saved successfully!"
            else:
                # Complex medical history form data
                form_data = {
                    'ongoing_conditions': request.POST.getlist('ongoing_conditions'),
                    'ongoing_conditions_other': request.POST.get('ongoing_conditions_other', ''),
                    'surgery_status': request.POST.get('surgery_status', 'no'),
                    'surgery_details': request.POST.get('surgery_details', ''),
                    'family_conditions': request.POST.getlist('family_conditions'),
                    'family_conditions_other': request.POST.get('family_conditions_other', ''),
                    'smoke_exposure': request.POST.get('smoke_exposure', 'no'),
                    'immunization': request.POST.getlist('immunization'),
                    'covid_vaccine': request.POST.getlist('covid_vaccine'),
                    'covid_positive': request.POST.get('covid_positive', 'no'),
                    'covid_details': request.POST.get('covid_details', ''),
                    'allergies': request.POST.get('allergies', '')
                }
                form_type_value = 'medical_history'
                success_message = "Medical history saved successfully!"
            
            # Create medical record
            medical_record = MedicalRecord.objects.create(
                patient_id=patient_id,
                patient_type=patient_type,
                form_type=form_type_value,
                form_data=form_data,
                created_by=request.user
            )
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action=f'{form_type_value}_created',
                description=f"Created {form_type_value} form for patient ID {patient_id}",
                location='medical/history'
            )
            
            messages.success(request, success_message)
            return redirect('patients:patient_detail', patient_id=patient_id, patient_type=patient_type)
            
        except Exception as e:
            messages.error(request, f"Error saving medical history: {str(e)}")
            return redirect('patients:patient_detail', patient_id=patient_id, patient_type=patient_type)
    
    return render(request, 'medical/save_medical_history.html', {'page_title': 'Save Medical History'})


@login_required
def edit_medical_record(request, record_id):
    """Edit medical record - matches PHP edit_medical_record.php"""
    medical_record = get_object_or_404(MedicalRecord, id=record_id)
    
    if request.method == 'POST':
        try:
            form_data = {}
            
            # Update form data based on form type
            if medical_record.form_type == 'athlete':
                form_data = {
                    'sport': request.POST.get('sport', ''),
                    'position': request.POST.get('position', ''),
                    'height': request.POST.get('height', ''),
                    'weight': request.POST.get('weight', ''),
                    'medical_history': request.POST.get('medical_history', ''),
                    'physical_exam': request.POST.get('physical_exam', ''),
                    'recommendations': request.POST.get('recommendations', '')
                }
            elif medical_record.form_type in ['general', 'general_checkup']:
                form_data = {
                    'assessment_plan': request.POST.get('assessment_plan', 'N/A'),
                    'height': request.POST.get('height', ''),
                    'weight': request.POST.get('weight', ''),
                    'bmi': request.POST.get('bmi', ''),
                    'bmi_status': request.POST.get('bmi_status', ''),
                    'heart_rate': request.POST.get('heart_rate', ''),
                    'heart_rate_status': request.POST.get('heart_rate_status', ''),
                    'temperature': request.POST.get('temperature', ''),
                    'temperature_status': request.POST.get('temperature_status', ''),
                    'blood_pressure': request.POST.get('blood_pressure', ''),
                    'blood_pressure_status': request.POST.get('blood_pressure_status', '')
                }
            elif medical_record.form_type == 'emergency':
                form_data = {
                    'emergency_type': request.POST.get('emergency_type', ''),
                    'severity': request.POST.get('severity', ''),
                    'emergency_description': request.POST.get('emergency_description', ''),
                    'immediate_actions': request.POST.get('immediate_actions', ''),
                    'heart_rate': request.POST.get('heart_rate', ''),
                    'blood_pressure': request.POST.get('blood_pressure', ''),
                    'temperature': request.POST.get('temperature', ''),
                    'follow_up': request.POST.get('follow_up', '')
                }
            elif medical_record.form_type == 'medical_history':
                form_data = {
                    'ongoing_conditions': request.POST.getlist('ongoing_conditions'),
                    'ongoing_conditions_other': request.POST.get('ongoing_conditions_other', ''),
                    'surgery_status': request.POST.get('surgery_status', 'no'),
                    'surgery_details': request.POST.get('surgery_details', ''),
                    'family_conditions': request.POST.getlist('family_conditions'),
                    'family_conditions_other': request.POST.get('family_conditions_other', ''),
                    'smoke_exposure': request.POST.get('smoke_exposure', 'no'),
                    'immunization': request.POST.getlist('immunization'),
                    'covid_vaccine': request.POST.getlist('covid_vaccine'),
                    'covid_positive': request.POST.get('covid_positive', 'no'),
                    'covid_details': request.POST.get('covid_details', ''),
                    'allergies': request.POST.get('allergies', '')
                }
            
            # Remove empty values
            form_data = {k: v for k, v in form_data.items() if v != ''}
            
            # Update medical record
            medical_record.form_data = form_data
            medical_record.save()
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action='medical_record_updated',
                description=f"Updated {medical_record.form_type} medical record for patient ID {medical_record.patient_id}",
                location='medical/records'
            )
            
            messages.success(request, "Medical record updated successfully!")
            return redirect('patients:patient_detail', patient_id=medical_record.patient_id, patient_type=medical_record.patient_type)
            
        except Exception as e:
            messages.error(request, f"Error updating medical record: {str(e)}")
    
    context = {
        'medical_record': medical_record,
        'page_title': f'Edit {medical_record.form_type.title()} Record',
    }
    return render(request, 'medical/edit_medical_record.html', context)


@login_required
def delete_medical_record(request, record_id):
    """Delete medical record - matches PHP delete_medical_record.php"""
    medical_record = get_object_or_404(MedicalRecord, id=record_id)
    
    if request.method == 'POST':
        try:
            patient_id = medical_record.patient_id
            patient_type = medical_record.patient_type
            form_type = medical_record.form_type
            
            # Archive the record instead of deleting
            medical_record.archived = True
            medical_record.save()
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action='medical_record_deleted',
                description=f"Archived {form_type} medical record for patient ID {patient_id}",
                location='medical/records'
            )
            
            messages.success(request, "Medical record deleted successfully!")
            return redirect('patients:patient_detail', patient_id=patient_id, patient_type=patient_type)
            
        except Exception as e:
            messages.error(request, f"Error deleting medical record: {str(e)}")
    
    context = {
        'medical_record': medical_record,
        'page_title': f'Delete {medical_record.form_type.title()} Record',
    }
    return render(request, 'medical/delete_medical_record.html', context)


@login_required
def view_medical_record(request, record_id):
    """View medical record - matches PHP medical_record_view.php"""
    medical_record = get_object_or_404(MedicalRecord, id=record_id)
    
    context = {
        'medical_record': medical_record,
        'page_title': f'{medical_record.form_type.title()} Record',
    }
    return render(request, 'medical/view_medical_record.html', context)


@login_required
def save_visitation(request):
    """Save visitation - matches PHP save_visitation.php"""
    if request.method == 'POST':
        try:
            patient_id = int(request.POST.get('patient_id'))
            patient_type = request.POST.get('patient_type')
            
            # Get patient object
            if patient_type == 'student':
                patient = get_object_or_404(Student, id=patient_id)
            else:
                patient = get_object_or_404(Faculty, id=patient_id)
            
            # Create visitation
            visitation = Visitation.objects.create(
                student=patient if patient_type == 'student' else None,
                faculty=patient if patient_type == 'faculty' else None,
                visitation_type=request.POST.get('visitation_type', 'routine'),
                date_visited=timezone.now(),
                symptoms=request.POST.get('symptoms', ''),
                diagnosis=request.POST.get('diagnosis', ''),
                treatment=request.POST.get('treatment', ''),
                medication_prescribed=request.POST.get('medication_prescribed', ''),
                follow_up_required=request.POST.get('follow_up_required') == 'on',
                follow_up_date=request.POST.get('follow_up_date') or None,
                notes=request.POST.get('notes', ''),
                created_by=request.user
            )
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action='visitation_created',
                description=f"Created visitation for {patient_type} ID {patient_id}",
                location='medical/visitation'
            )
            
            messages.success(request, "Visitation saved successfully!")
            return redirect('patients:patient_detail', patient_id=patient_id, patient_type=patient_type)
            
        except Exception as e:
            messages.error(request, f"Error saving visitation: {str(e)}")
    
    return render(request, 'medical/save_visitation.html', {'page_title': 'Save Visitation'})


@login_required
def archive_visitation(request, visitation_id):
    """Archive visitation"""
    visitation = get_object_or_404(Visitation, id=visitation_id)
    
    if request.method == 'POST':
        try:
            visitation.archived = True
            visitation.save()
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action='visitation_archived',
                description=f"Archived visitation ID {visitation_id}",
                location='medical/visitation'
            )
            
            messages.success(request, "Visitation archived successfully!")
            return redirect('patients:patient_detail', 
                          patient_id=visitation.patient.id, 
                          patient_type='student' if visitation.student else 'faculty')
            
        except Exception as e:
            messages.error(request, f"Error archiving visitation: {str(e)}")
    
    context = {
        'visitation': visitation,
        'page_title': 'Archive Visitation',
    }
    return render(request, 'medical/archive_visitation.html', context)


@login_required
def restore_visitation(request, visitation_id):
    """Restore visitation"""
    visitation = get_object_or_404(Visitation, id=visitation_id)
    
    if request.method == 'POST':
        try:
            visitation.archived = False
            visitation.save()
            
            # Log the activity
            log_activity(
                user_id=request.user.id,
                user_type='admin',
                action='visitation_restored',
                description=f"Restored visitation ID {visitation_id}",
                location='medical/visitation'
            )
            
            messages.success(request, "Visitation restored successfully!")
            return redirect('patients:patient_detail', 
                          patient_id=visitation.patient.id, 
                          patient_type='student' if visitation.student else 'faculty')
            
        except Exception as e:
            messages.error(request, f"Error restoring visitation: {str(e)}")
    
    context = {
        'visitation': visitation,
        'page_title': 'Restore Visitation',
    }
    return render(request, 'medical/restore_visitation.html', context)
