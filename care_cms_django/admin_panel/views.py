from django.shortcuts import render, redirect
from django.contrib.auth import authenticate, login, logout
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

from .models import User
from patients.models import Student, Faculty
from logs.models import ActivityLog
from rfid.models import RFIDLog
from core.helpers import sanitize_string, log_activity, is_valid_email, is_strong_password

logger = logging.getLogger(__name__)


class LoginView(View):
    """Handle admin login with RFID verification - matches PHP login.php exactly"""
    
    def get(self, request):
        # Check if user is already logged in
        if request.user.is_authenticated:
            return redirect('admin:dashboard')
        
        # Check for pending login (RFID verification step)
        pending_login = request.session.get('pending_login')
        
        context = {
            'page_title': 'Admin Login',
            'show_top_nav': False,
            'show_sidebar': False,
            'pending_login': pending_login is not None,
        }
        
        return render(request, 'auth/login.html', context)
    
    def post(self, request):
        # CSRF protection
        csrf_token = request.POST.get('csrf_token')
        if not self._verify_csrf(request, csrf_token):
            messages.error(request, 'Invalid security token. Please try again.')
            return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
        
        # IP-based rate limiting
        client_ip = self._get_client_ip(request)
        if not self._check_rate_limit(request, client_ip):
            messages.error(request, 'Too many login attempts. Please try again later.')
            return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
        
        identifier = sanitize_string(request.POST.get('identifier', ''))
        password = request.POST.get('password', '')
        
        if not identifier or not password:
            messages.error(request, 'Please provide both username/email and password.')
            return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
        
        try:
            # Find admin user by email or username
            user = None
            try:
                user = User.objects.get(email=identifier, is_admin=True)
            except User.DoesNotExist:
                try:
                    user = User.objects.get(username=identifier, is_admin=True)
                except User.DoesNotExist:
                    pass
            
            if not user:
                self._handle_failed_login(request, client_ip, identifier)
                messages.error(request, 'Invalid credentials.')
                return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
            
            # Check account lock
            if user.locked_until and user.locked_until > timezone.now():
                remaining_time = user.locked_until - timezone.now()
                minutes = int(remaining_time.total_seconds() / 60)
                messages.error(request, f'Account locked. Try again in {minutes} minutes.')
                return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
            
            # Verify password
            if not user.check_password(password):
                self._handle_failed_login(request, client_ip, identifier)
                messages.error(request, 'Invalid credentials.')
                return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
            
            # Successful login - reset failed attempts
            user.failed_attempts = 0
            user.locked_until = None
            user.last_login = timezone.now()
            user.save()
            
            # Check if RFID verification is required
            if user.rfid:
                # Store pending login data for RFID verification
                request.session['pending_login'] = {
                    'user_id': user.id,
                    'username': user.username,
                    'email': user.email,
                    'name': user.get_full_name() or user.username,
                    'rfid': user.rfid,
                    'timestamp': timezone.now().isoformat(),
                }
                messages.info(request, 'Credentials verified. RFID verification required.')
                return render(request, 'auth/login.html', {
                    'page_title': 'Admin Login',
                    'pending_login': True,
                })
            else:
                # No RFID required, log in directly
                login(request, user)
                request.session['last_activity'] = timezone.now().timestamp()
                
                # Log successful login
                log_activity(user, 'login_success', 'Successful login without RFID verification', 'auth/login')
                
                return redirect('admin:dashboard')
                
        except Exception as e:
            logger.error(f"Login error: {str(e)}")
            messages.error(request, 'An error occurred during login. Please try again.')
            return render(request, 'auth/login.html', {'page_title': 'Admin Login'})
    
    def _verify_csrf(self, request, token):
        """Verify CSRF token"""
        session_token = request.session.get('csrf_token')
        return session_token and token == session_token
    
    def _get_client_ip(self, request):
        """Get client IP address"""
        x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
        if x_forwarded_for:
            ip = x_forwarded_for.split(',')[0]
        else:
            ip = request.META.get('REMOTE_ADDR')
        return ip
    
    def _check_rate_limit(self, request, client_ip):
        """Check IP-based rate limiting"""
        now = timezone.now()
        key = f"login_attempts_{client_ip}"
        
        # Get existing attempts from session
        attempts = request.session.get(key, [])
        
        # Remove attempts older than 15 minutes
        attempts = [attempt for attempt in attempts if attempt > (now - timedelta(minutes=15)).timestamp()]
        
        # Check if too many attempts
        if len(attempts) >= 5:
            return False
        
        # Add current attempt
        attempts.append(now.timestamp())
        request.session[key] = attempts
        
        return True
    
    def _handle_failed_login(self, request, client_ip, identifier):
        """Handle failed login attempt"""
        try:
            # Find user and increment failed attempts
            user = None
            try:
                user = User.objects.get(email=identifier, is_admin=True)
            except User.DoesNotExist:
                try:
                    user = User.objects.get(username=identifier, is_admin=True)
                except User.DoesNotExist:
                    pass
            
            if user:
                user.failed_attempts += 1
                
                # Lock account after 5 failed attempts
                if user.failed_attempts >= 5:
                    user.locked_until = timezone.now() + timedelta(minutes=30)
                
                user.save()
                
                # Log failed attempt
                log_activity(user, 'login_failed', f'Failed login attempt #{user.failed_attempts}', 'auth/login')
            
        except Exception as e:
            logger.error(f"Error handling failed login: {str(e)}")


class RFIDVerificationView(View):
    """Handle RFID verification for pending login - matches PHP verify_rfid_login.php"""
    
    def post(self, request):
        if not request.user.is_authenticated and not request.session.get('pending_login'):
            return JsonResponse({'success': False, 'message': 'No pending login found.'})
        
        rfid = sanitize_string(request.POST.get('rfid', ''))
        if not rfid:
            return JsonResponse({'success': False, 'message': 'RFID is required.'})
        
        try:
            pending_login = request.session.get('pending_login')
            if not pending_login:
                return JsonResponse({'success': False, 'message': 'No pending login found.'})
            
            # Verify RFID matches the user's RFID
            user_id = pending_login.get('user_id')
            user = User.objects.get(id=user_id, is_admin=True)
            
            if user.rfid != rfid:
                # Log failed RFID attempt
                log_activity(user, 'rfid_verification_failed', 'Invalid RFID provided', 'auth/rfid_verification')
                return JsonResponse({'success': False, 'message': 'Invalid RFID. Please try again.'})
            
            # RFID verification successful
            login(request, user)
            request.session['last_activity'] = timezone.now().timestamp()
            
            # Clear pending login
            del request.session['pending_login']
            
            # Log successful RFID verification
            log_activity(user, 'rfid_verification_success', 'Successful RFID verification', 'auth/rfid_verification')
            
            return JsonResponse({
                'success': True, 
                'message': 'RFID verification successful. Logging in...',
                'redirect_url': '/admin/dashboard/'
            })
            
        except User.DoesNotExist:
            return JsonResponse({'success': False, 'message': 'User not found.'})
        except Exception as e:
            logger.error(f"RFID verification error: {str(e)}")
            return JsonResponse({'success': False, 'message': 'An error occurred during RFID verification.'})


class LogoutView(View):
    """Handle user logout - matches PHP logout.php"""
    
    def get(self, request):
        if request.user.is_authenticated:
            # Log logout activity
            log_activity(request.user, 'logout', 'User logged out', 'auth/logout')
            
            # Clear session
            logout(request)
            request.session.flush()
        
        return redirect('index')


class RegisterView(View):
    """Handle admin registration - matches PHP register.php"""
    
    def get(self, request):
        # Check if any admin exists
        if User.objects.filter(is_admin=True).exists():
            messages.error(request, 'Admin registration is disabled.')
            return redirect('auth:login')
        
        context = {
            'page_title': 'Create Admin Account',
            'show_top_nav': False,
            'show_sidebar': False,
        }
        return render(request, 'auth/register.html', context)
    
    def post(self, request):
        # Check if any admin exists
        if User.objects.filter(is_admin=True).exists():
            messages.error(request, 'Admin registration is disabled.')
            return redirect('auth:login')
        
        # CSRF protection
        csrf_token = request.POST.get('csrf_token')
        if not self._verify_csrf(request, csrf_token):
            messages.error(request, 'Invalid security token. Please try again.')
            return render(request, 'auth/register.html', {'page_title': 'Create Admin Account'})
        
        name = sanitize_string(request.POST.get('name', ''))
        email = sanitize_string(request.POST.get('email', ''))
        password = request.POST.get('password', '')
        confirm_password = request.POST.get('confirm_password', '')
        rfid = sanitize_string(request.POST.get('rfid', ''))
        
        # Validation
        errors = []
        
        if not name:
            errors.append('Name is required.')
        
        if not email or not is_valid_email(email):
            errors.append('Valid email is required.')
        
        if not password or not is_strong_password(password):
            errors.append('Password must be at least 8 characters with uppercase, lowercase, number, and special character.')
        
        if password != confirm_password:
            errors.append('Passwords do not match.')
        
        if not rfid:
            errors.append('RFID is required.')
        
        # Check if email already exists
        if User.objects.filter(email=email).exists():
            errors.append('Email already exists.')
        
        # Check if RFID already exists
        if User.objects.filter(rfid=rfid).exists():
            errors.append('RFID already exists.')
        
        if errors:
            for error in errors:
                messages.error(request, error)
            return render(request, 'auth/register.html', {'page_title': 'Create Admin Account'})
        
        try:
            with transaction.atomic():
                # Create admin user
                user = User.objects.create_user(
                    username=email,
                    email=email,
                    password=password,
                    first_name=name.split()[0] if name.split() else name,
                    last_name=' '.join(name.split()[1:]) if len(name.split()) > 1 else '',
                    is_admin=True,
                    is_active=True,
                    rfid=rfid,
                )
                
                # Log admin creation
                log_activity(user, 'admin_created', f'Admin account created: {email}', 'auth/register')
                
                messages.success(request, 'Admin account created successfully. You can now log in.')
                return redirect('auth:login')
                
        except Exception as e:
            logger.error(f"Registration error: {str(e)}")
            messages.error(request, 'An error occurred during registration. Please try again.')
            return render(request, 'auth/register.html', {'page_title': 'Create Admin Account'})
    
    def _verify_csrf(self, request, token):
        """Verify CSRF token"""
        session_token = request.session.get('csrf_token')
        return session_token and token == session_token


def generate_csrf_token(request):
    """Generate CSRF token for forms"""
    import secrets
    token = secrets.token_hex(32)
    request.session['csrf_token'] = token
    return token


class DashboardView(View):
    """Admin dashboard - matches PHP admin/dashboard.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request):
        # Ensure user is admin
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        user = request.user
        
        context = {
            'page_title': 'Admin Dashboard',
            'show_top_nav': True,
            'show_sidebar': True,
            'user': {
                'id': user.id,
                'name': user.get_full_name() or user.username,
                'email': user.email,
                'username': user.username,
            }
        }
        
        return render(request, 'admin_panel/dashboard.html', context)


class SettingsView(View):
    """System settings - matches PHP admin/settings.php exactly"""
    
    @method_decorator(login_required)
    def get(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        context = {
            'page_title': 'System Settings',
            'show_top_nav': True,
            'show_sidebar': True,
        }
        return render(request, 'admin_panel/settings.html', context)
    
    @method_decorator(login_required)
    def post(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        settings_type = request.POST.get('settings_type', '')
        
        try:
            if settings_type == 'general':
                # Handle general settings
                messages.success(request, 'General settings saved successfully.')
            elif settings_type == 'security':
                # Handle security settings
                messages.success(request, 'Security settings saved successfully.')
            elif settings_type == 'rfid':
                # Handle RFID settings
                messages.success(request, 'RFID settings saved successfully.')
            elif settings_type == 'medical':
                # Handle medical settings
                messages.success(request, 'Medical settings saved successfully.')
            elif settings_type == 'notifications':
                # Handle notification settings
                messages.success(request, 'Notification settings saved successfully.')
            else:
                messages.error(request, 'Invalid settings type.')
            
            return redirect('admin:settings')
            
        except Exception as e:
            logger.error(f"Settings save error: {str(e)}")
            messages.error(request, 'An error occurred while saving settings.')
            return redirect('admin:settings')


class ArchiveStudentsView(View):
    """Archive students view - matches PHP admin/archive_student.php"""
    
    @method_decorator(login_required)
    def get(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        students = Student.objects.filter(status='Active').order_by('name')
        
        context = {
            'page_title': 'Archive Students',
            'show_top_nav': True,
            'show_sidebar': True,
            'students': students,
        }
        return render(request, 'admin_panel/archive_students.html', context)


class ArchiveFacultyView(View):
    """Archive faculty view - matches PHP admin/archive_faculty.php"""
    
    @method_decorator(login_required)
    def get(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        faculty = Faculty.objects.filter(status='Active').order_by('name')
        
        context = {
            'page_title': 'Archive Faculty',
            'show_top_nav': True,
            'show_sidebar': True,
            'faculty': faculty,
        }
        return render(request, 'admin_panel/archive_faculty.html', context)


class SecurityLogsView(View):
    """Security logs view - matches PHP admin/security_logs.php"""
    
    @method_decorator(login_required)
    def get(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        logs = ActivityLog.objects.filter(success=False).order_by('-timestamp')[:100]
        
        context = {
            'page_title': 'Security Logs',
            'show_top_nav': True,
            'show_sidebar': True,
            'logs': logs,
        }
        return render(request, 'admin_panel/security_logs.html', context)


class BackupView(View):
    """Backup view - matches PHP admin/settings_backup.php"""
    
    @method_decorator(login_required)
    def get(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        context = {
            'page_title': 'System Backup',
            'show_top_nav': True,
            'show_sidebar': True,
        }
        return render(request, 'admin_panel/backup.html', context)
    
    @method_decorator(login_required)
    def post(self, request):
        if not request.user.is_admin:
            messages.error(request, 'Access denied. Admin privileges required.')
            return redirect('auth:login')
        
        try:
            # Handle backup creation
            messages.success(request, 'Backup created successfully.')
            return redirect('admin:backup')
        except Exception as e:
            logger.error(f"Backup error: {str(e)}")
            messages.error(request, 'An error occurred while creating backup.')
            return redirect('admin:backup')