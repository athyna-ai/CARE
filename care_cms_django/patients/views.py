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
from django.core.paginator import Paginator
from datetime import datetime, timedelta
import json
import logging

from .models import Student, Faculty, EnrollmentHistory
from medical.models import MedicalRecord
from logs.models import VisitationLog
from admin_panel.models import User, StudentArchive, FacultyArchive
from logs.models import ActivityLog
from core.helpers import sanitize_string, log_activity, is_valid_email, is_strong_password

logger = logging.getLogger(__name__)


class StudentFormView(View):
    """Student registration and editing form - matches PHP patients/student_form.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request, student_id=None):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        student = None
        if student_id:
            try:
                student = Student.objects.get(id=student_id)
            except Student.DoesNotExist:
                messages.error(request, 'Student not found.')
                return redirect('patients:school_listing')
        
        # Pre-fill RFID if provided
        prefill_rfid = request.GET.get('rfid', '')
        
        context = {
            'page_title': 'Student Registration' if not student else 'Edit Student',
            'show_top_nav': True,
            'show_sidebar': True,
            'student': student,
            'prefill_rfid': prefill_rfid,
            'csrf_token': request.session.get('csrf_token', ''),
        }
        
        return render(request, 'patients/student_form.html', context)
    
    @method_decorator(login_required)
    def post(self, request, student_id=None):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        # CSRF protection
        csrf_token = request.POST.get('csrf_token')
        if not self._verify_csrf(request, csrf_token):
            messages.error(request, 'Invalid security token. Please try again.')
            return redirect('patients:student_form')
        
        # Get form data
        name = sanitize_string(request.POST.get('name', ''))
        gender = request.POST.get('gender', '')
        level = request.POST.get('level', '')
        course = sanitize_string(request.POST.get('course', ''))
        block = sanitize_string(request.POST.get('block', ''))
        section = sanitize_string(request.POST.get('section', ''))
        strand = sanitize_string(request.POST.get('strand', ''))
        year_grade = sanitize_string(request.POST.get('year_grade', ''))
        rfid = sanitize_string(request.POST.get('rfid', ''))
        address = sanitize_string(request.POST.get('address', ''))
        dob_str = request.POST.get('dob', '')
        religion = sanitize_string(request.POST.get('religion', ''))
        guardian = sanitize_string(request.POST.get('guardian', ''))
        allergies = sanitize_string(request.POST.get('allergies', ''))
        emergency_contact = sanitize_string(request.POST.get('emergency_contact', ''))
        medical_notes = sanitize_string(request.POST.get('medical_notes', ''))
        data_privacy_consent = request.POST.get('data_privacy_consent') == 'on'
        
        # Parse emergency contacts
        contacts = []
        for i in range(1, 4):  # Support up to 3 emergency contacts
            contact_name = sanitize_string(request.POST.get(f'contact_{i}_name', ''))
            contact_phone = sanitize_string(request.POST.get(f'contact_{i}_phone', ''))
            contact_relation = sanitize_string(request.POST.get(f'contact_{i}_relation', ''))
            
            if contact_name and contact_phone:
                contacts.append({
                    'name': contact_name,
                    'phone': contact_phone,
                    'relation': contact_relation,
                })
        
        # Validation
        errors = []
        
        if not name:
            errors.append('Full name is required.')
        
        if not gender:
            errors.append('Gender is required.')
        
        if not level:
            errors.append('Education level is required.')
        
        if not rfid:
            errors.append('RFID number is required.')
        
        if not dob_str:
            errors.append('Date of birth is required.')
        
        if not data_privacy_consent:
            errors.append('Data privacy consent is required.')
        
        # Parse date of birth
        dob = None
        if dob_str:
            try:
                dob = datetime.strptime(dob_str, '%Y-%m-%d').date()
                # Calculate age
                today = timezone.now().date()
                age = today.year - dob.year - ((today.month, today.day) < (dob.month, dob.day))
            except ValueError:
                errors.append('Invalid date of birth format.')
        
        # Check RFID uniqueness
        if rfid:
            existing_student = Student.objects.filter(rfid=rfid).exclude(id=student_id).first()
            if existing_student:
                errors.append('RFID number already exists.')
        
        if errors:
            for error in errors:
                messages.error(request, error)
            return redirect('patients:student_form', student_id=student_id)
        
        try:
            with transaction.atomic():
                if student_id:
                    # Update existing student
                    student = Student.objects.get(id=student_id)
                    student.name = name
                    student.gender = gender
                    student.level = level
                    student.course = course
                    student.block = block
                    student.section = section
                    student.strand = strand
                    student.year_grade = year_grade
                    student.rfid = rfid
                    student.address = address
                    student.dob = dob
                    student.age = age
                    student.religion = religion
                    student.guardian = guardian
                    student.allergies = allergies
                    student.contacts = contacts
                    student.emergency_contact = emergency_contact
                    student.medical_notes = medical_notes
                    student.save()
                    
                    log_activity(request.user, 'student_updated', f'Updated student: {name}', 'patients/student_form')
                    messages.success(request, 'Student updated successfully.')
                    
                else:
                    # Check for re-enrollment (graduated student with same RFID)
                    graduated_student = Student.objects.filter(rfid=rfid, status='Graduated').first()
                    
                    if graduated_student:
                        # Re-enroll graduated student
                        graduated_student.name = name
                        graduated_student.gender = gender
                        graduated_student.level = level
                        graduated_student.course = course
                        graduated_student.block = block
                        graduated_student.section = section
                        graduated_student.strand = strand
                        graduated_student.year_grade = year_grade
                        graduated_student.address = address
                        graduated_student.dob = dob
                        graduated_student.age = age
                        graduated_student.religion = religion
                        graduated_student.guardian = guardian
                        graduated_student.allergies = allergies
                        graduated_student.contacts = contacts
                        graduated_student.emergency_contact = emergency_contact
                        graduated_student.medical_notes = medical_notes
                        graduated_student.status = 'Active'
                        graduated_student.save()
                        
                        # Create enrollment history record
                        EnrollmentHistory.objects.create(
                            student=graduated_student,
                            enrollment_type='re_enrollment',
                            previous_level=graduated_student.level,
                            previous_status='Graduated',
                            new_level=level,
                            new_status='Active',
                            enrollment_year=str(timezone.now().year),
                            notes='Re-enrolled after graduation',
                            created_by=request.user,
                        )
                        
                        log_activity(request.user, 'student_re_enrolled', f'Re-enrolled student: {name}', 'patients/student_form')
                        messages.success(request, 'Student re-enrolled successfully.')
                        
                    else:
                        # Create new student
                        student = Student.objects.create(
                            name=name,
                            gender=gender,
                            level=level,
                            course=course,
                            block=block,
                            section=section,
                            strand=strand,
                            year_grade=year_grade,
                            rfid=rfid,
                            address=address,
                            dob=dob,
                            age=age,
                            religion=religion,
                            guardian=guardian,
                            allergies=allergies,
                            contacts=contacts,
                            emergency_contact=emergency_contact,
                            medical_notes=medical_notes,
                            status='Active',
                        )
                        
                        # Create enrollment history record
                        EnrollmentHistory.objects.create(
                            student=student,
                            enrollment_type='initial',
                            new_level=level,
                            new_status='Active',
                            enrollment_year=str(timezone.now().year),
                            notes='Initial enrollment',
                            created_by=request.user,
                        )
                        
                        log_activity(request.user, 'student_created', f'Created new student: {name}', 'patients/student_form')
                        messages.success(request, 'Student registered successfully.')
                
                return redirect('patients:school_listing')
                
        except Exception as e:
            logger.error(f"Student form error: {str(e)}")
            messages.error(request, 'An error occurred while saving student data.')
            return redirect('patients:student_form', student_id=student_id)


class FacultyFormView(View):
    """Faculty registration/edit form - matches PHP patients/faculty_form.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request, faculty_id=None):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        faculty = None
        if faculty_id:
            try:
                faculty = Faculty.objects.get(id=faculty_id)
            except Faculty.DoesNotExist:
                messages.error(request, 'Faculty not found.')
                return redirect('patients:faculty_listing')
        
        context = {
            'page_title': 'Faculty Registration' if not faculty else 'Edit Faculty',
            'show_top_nav': True,
            'show_sidebar': True,
            'faculty': faculty,
        }
        return render(request, 'patients/faculty_form.html', context)
    
    @method_decorator(login_required)
    def post(self, request, faculty_id=None):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        # CSRF protection
        csrf_token = request.POST.get('csrf_token')
        if not self._verify_csrf(request, csrf_token):
            messages.error(request, 'Invalid security token. Please try again.')
            return redirect('patients:faculty_form', faculty_id=faculty_id)
        
        # Get form data
        name = sanitize_string(request.POST.get('name', ''))
        department = sanitize_string(request.POST.get('department', ''))
        position = sanitize_string(request.POST.get('position', ''))
        employee_id = sanitize_string(request.POST.get('employee_id', ''))
        gender = sanitize_string(request.POST.get('gender', ''))
        rfid = sanitize_string(request.POST.get('rfid', ''))
        address = sanitize_string(request.POST.get('address', ''))
        phone = sanitize_string(request.POST.get('phone', ''))
        email = sanitize_string(request.POST.get('email', ''))
        date_of_birth = request.POST.get('date_of_birth', '')
        emergency_contact = sanitize_string(request.POST.get('emergency_contact', ''))
        emergency_phone = sanitize_string(request.POST.get('emergency_phone', ''))
        data_privacy_consent = request.POST.get('data_privacy_consent') == 'on'
        
        # Validation
        errors = []
        if not name:
            errors.append('Name is required.')
        if not department:
            errors.append('Department is required.')
        if not position:
            errors.append('Position is required.')
        if not employee_id:
            errors.append('Employee ID is required.')
        if not gender:
            errors.append('Gender is required.')
        if not rfid:
            errors.append('RFID is required.')
        if not date_of_birth:
            errors.append('Date of birth is required.')
        if not data_privacy_consent:
            errors.append('Data privacy consent is required.')
        
        # Check for duplicate RFID
        existing_faculty = Faculty.objects.filter(rfid=rfid)
        if faculty_id:
            existing_faculty = existing_faculty.exclude(id=faculty_id)
        if existing_faculty.exists():
            errors.append('RFID already exists.')
        
        if errors:
            for error in errors:
                messages.error(request, error)
            return redirect('patients:faculty_form', faculty_id=faculty_id)
        
        try:
            with transaction.atomic():
                if faculty_id:
                    # Update existing faculty
                    faculty = Faculty.objects.get(id=faculty_id)
                    faculty.name = name
                    faculty.department = department
                    faculty.position = position
                    faculty.employee_id = employee_id
                    faculty.gender = gender
                    faculty.rfid = rfid
                    faculty.address = address
                    faculty.phone = phone
                    faculty.email = email
                    faculty.date_of_birth = date_of_birth
                    faculty.emergency_contact = emergency_contact
                    faculty.emergency_phone = emergency_phone
                    faculty.data_privacy_consent = data_privacy_consent
                    faculty.save()
                    
                    log_activity(request.user, 'faculty_updated', f'Updated faculty: {name}', 'patients/faculty_form')
                    messages.success(request, 'Faculty updated successfully.')
                else:
                    # Create new faculty
                    faculty = Faculty.objects.create(
                        name=name,
                        department=department,
                        position=position,
                        employee_id=employee_id,
                        gender=gender,
                        rfid=rfid,
                        address=address,
                        phone=phone,
                        email=email,
                        date_of_birth=date_of_birth,
                        emergency_contact=emergency_contact,
                        emergency_phone=emergency_phone,
                        data_privacy_consent=data_privacy_consent,
                        status='Active',
                    )
                    
                    log_activity(request.user, 'faculty_created', f'Created new faculty: {name}', 'patients/faculty_form')
                    messages.success(request, 'Faculty registered successfully.')
                
                return redirect('patients:faculty_listing')
                
        except Exception as e:
            logger.error(f"Faculty form error: {str(e)}")
            messages.error(request, 'An error occurred while saving faculty data.')
            return redirect('patients:faculty_form', faculty_id=faculty_id)
    
    def _verify_csrf(self, request, token):
        """Verify CSRF token"""
        session_token = request.session.get('csrf_token')
        return session_token and token == session_token


class SchoolListingView(View):
    """School listing - matches PHP patients/school_listing.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request, level=None):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            # Get level from URL parameter or default to 'Elementary'
            if not level:
                level = request.GET.get('level', 'Elementary')
            
            # Get students for the specified level
            students = Student.objects.filter(level=level).order_by('name')
            
            # Pagination
            paginator = Paginator(students, 20)
            page_number = request.GET.get('page')
            students = paginator.get_page(page_number)
            
            context = {
                'page_title': f'{level} Students',
                'show_top_nav': True,
                'show_sidebar': True,
                'students': students,
                'level': level,
                'total_students': Student.objects.filter(level=level).count(),
            }
            
            return render(request, 'patients/school_listing.html', context)
            
        except Exception as e:
            logger.error(f"School listing error: {str(e)}")
            messages.error(request, 'An error occurred while loading student list.')
            return redirect('admin:dashboard')


class FacultyListingView(View):
    """Faculty listing - matches PHP patients/faculty_listing.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            # Get all faculty
            faculty = Faculty.objects.all().order_by('name')
            
            # Pagination
            paginator = Paginator(faculty, 20)
            page_number = request.GET.get('page')
            faculty = paginator.get_page(page_number)
            
            context = {
                'page_title': 'Faculty Members',
                'show_top_nav': True,
                'show_sidebar': True,
                'faculty': faculty,
                'total_faculty': Faculty.objects.count(),
            }
            
            return render(request, 'patients/faculty_listing.html', context)
            
        except Exception as e:
            logger.error(f"Faculty listing error: {str(e)}")
            messages.error(request, 'An error occurred while loading faculty list.')
            return redirect('admin:dashboard')


class PatientViewView(View):
    """Patient view - matches PHP patients/patient_view.php exactly"""
    
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
            
            # Get medical records
            medical_records = MedicalRecord.objects.filter(
                patient_id=patient_id,
                patient_type=patient_type
            ).order_by('-created_at')
            
            # Get visitation history
            visitations = VisitationLog.objects.filter(
                patient_id=patient_id,
                patient_type=patient_type
            ).order_by('-visit_date')[:10]
            
            context = {
                'page_title': 'Patient Details',
                'show_top_nav': True,
                'show_sidebar': True,
                'patient': patient,
                'patient_type': patient_type,
                'medical_records': medical_records,
                'visitations': visitations,
            }
            
            return render(request, 'patients/patient_view.html', context)
            
        except Exception as e:
            logger.error(f"Patient view error: {str(e)}")
            messages.error(request, 'An error occurred while loading patient details.')
            return redirect('admin:dashboard')


def archive_student(request, student_id):
    """Archive student - matches PHP patients/archive_student.php exactly"""
    if not request.user.is_admin:
        messages.error(request, 'Access denied. Admin privileges required.')
        return redirect('auth:login')
    
    try:
        student = get_object_or_404(Student, id=student_id)
        
        with transaction.atomic():
            # Create archive record
            StudentArchive.objects.create(
                original_id=student.id,
                name=student.name,
                level=student.level,
                year_grade=student.year_grade,
                course=student.course,
                section=student.section,
                strand=student.strand,
                gender=student.gender,
                rfid=student.rfid,
                address=student.address,
                phone=student.phone,
                email=student.email,
                date_of_birth=student.date_of_birth,
                emergency_contact=student.emergency_contact,
                emergency_phone=student.emergency_phone,
                data_privacy_consent=student.data_privacy_consent,
                status=student.status,
                archived_by=request.user,
                archived_at=timezone.now(),
            )
            
            # Delete original record
            student.delete()
            
            log_activity(request.user, 'student_archived', f'Archived student: {student.name}', 'patients/archive')
            messages.success(request, 'Student archived successfully.')
            
    except Exception as e:
        logger.error(f"Archive student error: {str(e)}")
        messages.error(request, 'An error occurred while archiving student.')
    
    return redirect('patients:school_listing', level='all')


def archive_faculty(request, faculty_id):
    """Archive faculty - matches PHP patients/archive_faculty.php exactly"""
    if not request.user.is_admin:
        messages.error(request, 'Access denied. Admin privileges required.')
        return redirect('auth:login')
    
    try:
        faculty = get_object_or_404(Faculty, id=faculty_id)
        
        with transaction.atomic():
            # Create archive record
            FacultyArchive.objects.create(
                original_id=faculty.id,
                name=faculty.name,
                department=faculty.department,
                position=faculty.position,
                employee_id=faculty.employee_id,
                gender=faculty.gender,
                rfid=faculty.rfid,
                address=faculty.address,
                phone=faculty.phone,
                email=faculty.email,
                date_of_birth=faculty.date_of_birth,
                emergency_contact=faculty.emergency_contact,
                emergency_phone=faculty.emergency_phone,
                data_privacy_consent=faculty.data_privacy_consent,
                status=faculty.status,
                archived_by=request.user,
                archived_at=timezone.now(),
            )
            
            # Delete original record
            faculty.delete()
            
            log_activity(request.user, 'faculty_archived', f'Archived faculty: {faculty.name}', 'patients/archive')
            messages.success(request, 'Faculty archived successfully.')
            
    except Exception as e:
        logger.error(f"Archive faculty error: {str(e)}")
        messages.error(request, 'An error occurred while archiving faculty.')
    
    return redirect('patients:faculty_listing')
    
    def _verify_csrf(self, request, token):
        """Verify CSRF token"""
        session_token = request.session.get('csrf_token')
        return session_token and token == session_token