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

from admin_panel.models import User
from logs.models import ActivityLog
from core.helpers import sanitize_string, log_activity

logger = logging.getLogger(__name__)


class IndexView(View):
    """Main index page - matches PHP index.php exactly"""
    
    def get(self, request):
        # Check if user is already logged in
        if request.user.is_authenticated:
            return redirect('admin:dashboard')
        
        context = {
            'page_title': 'Welcome',
            'show_top_nav': False,
            'show_sidebar': False,
        }
        
        return render(request, 'index.html', context)
