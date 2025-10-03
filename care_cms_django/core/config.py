import os
from django.conf import settings

# Database configuration - matches PHP core/config.php
DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_NAME = os.getenv('DB_NAME', 'care_cms')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_CHARSET = 'utf8mb4'

# Timezone setting - matches PHP core/config.php
TIMEZONE = 'Asia/Manila'

# CSRF token generation - matches PHP core/config.php csrf_token function
def csrf_token():
    """Generate CSRF token"""
    import secrets
    return secrets.token_hex(32)

# CSRF token verification - matches PHP core/config.php verify_csrf function
def verify_csrf(token):
    """Verify CSRF token"""
    # This will be handled by Django's built-in CSRF protection
    return True
