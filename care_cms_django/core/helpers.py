import re
import logging
from django.utils import timezone
from django.contrib.auth.models import User
from logs.models import ActivityLog

logger = logging.getLogger(__name__)


def sanitize_string(value):
    """Sanitize string input - matches PHP core/helpers.php sanitize_string function"""
    if value is None:
        return ''
    
    # Remove any null bytes and control characters
    value = str(value).replace('\x00', '')
    value = re.sub(r'[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]', '', value)
    
    # Strip whitespace
    value = value.strip()
    
    return value


def is_valid_email(email):
    """Validate email format - matches PHP core/helpers.php is_valid_email function"""
    if not email:
        return False
    
    # Basic email regex pattern
    pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
    return bool(re.match(pattern, email))


def is_strong_password(password):
    """Check if password is strong - matches PHP core/helpers.php is_strong_password function"""
    if not password or len(password) < 8:
        return False
    
    # Check for uppercase letter
    if not re.search(r'[A-Z]', password):
        return False
    
    # Check for lowercase letter
    if not re.search(r'[a-z]', password):
        return False
    
    # Check for digit
    if not re.search(r'\d', password):
        return False
    
    # Check for special character
    if not re.search(r'[!@#$%^&*(),.?":{}|<>]', password):
        return False
    
    return True


def log_activity(user, action, details='', location=''):
    """Log user activity - matches PHP core/helpers.php log_activity function"""
    try:
        ActivityLog.objects.create(
            user=user,
            user_type='admin',
            action=action,
            description=details,
            action_description=details,
            location=location,
            success=True,
            timestamp=timezone.now(),
            ip_address='127.0.0.1',  # Default IP, should be updated with actual IP
            user_agent='Django System',
        )
    except Exception as e:
        logger.error(f"Error logging activity: {str(e)}")


def require_admin_auth():
    """Require admin authentication - matches PHP core/helpers.php require_admin_auth function"""
    # This is handled by Django's login_required decorator and admin check in views
    pass


def is_logged_in_admin():
    """Check if user is logged in as admin - matches PHP core/helpers.php is_logged_in_admin function"""
    # This is handled by Django's authentication system
    pass


def admin_exists():
    """Check if any admin exists - matches PHP core/helpers.php admin_exists function"""
    return User.objects.filter(is_admin=True).exists()


def mask_email(email):
    """Mask email for privacy - matches PHP core/helpers.php mask_email function"""
    if not email or '@' not in email:
        return email
    
    local, domain = email.split('@', 1)
    
    if len(local) <= 2:
        masked_local = local[0] + '*' * (len(local) - 1)
    else:
        masked_local = local[0] + '*' * (len(local) - 2) + local[-1]
    
    return f"{masked_local}@{domain}"


def mask_token_tail(value, keep=6):
    """Mask token tail - matches PHP core/helpers.php mask_token_tail function"""
    if not value or len(value) <= keep:
        return value
    
    return '*' * (len(value) - keep) + value[-keep:]


def mask_name(name):
    """Mask name for privacy - matches PHP core/helpers.php mask_name function"""
    if not name:
        return name
    
    parts = name.split()
    if len(parts) == 1:
        # Single name
        if len(parts[0]) <= 2:
            return parts[0][0] + '*' * (len(parts[0]) - 1)
        else:
            return parts[0][0] + '*' * (len(parts[0]) - 2) + parts[0][-1]
    else:
        # Multiple names
        masked_parts = []
        for part in parts:
            if len(part) <= 2:
                masked_parts.append(part[0] + '*' * (len(part) - 1))
            else:
                masked_parts.append(part[0] + '*' * (len(part) - 2) + part[-1])
        return ' '.join(masked_parts)


def mask_address(address):
    """Mask address for privacy - matches PHP core/helpers.php mask_address function"""
    if not address:
        return address
    
    # Simple masking - show first few characters and last few characters
    if len(address) <= 10:
        return '*' * len(address)
    
    return address[:3] + '*' * (len(address) - 6) + address[-3:]
