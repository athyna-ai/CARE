from django.db import models
from django.contrib.auth.models import User


class AdminProfile(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE, related_name='admin_profile')
    rfid = models.CharField(max_length=50, unique=True)
    department = models.CharField(max_length=100, blank=True, null=True)
    position = models.CharField(max_length=100, blank=True, null=True)
    phone_number = models.CharField(max_length=20, blank=True, null=True)
    is_active = models.BooleanField(default=True)
    last_login_rfid = models.DateTimeField(blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'admin_profiles'
    
    def __str__(self):
        return f"{self.user.get_full_name() or self.user.username} - {self.position or 'Admin'}"


class SystemSettings(models.Model):
    SETTING_TYPE_CHOICES = [
        ('general', 'General'),
        ('rfid', 'RFID'),
        ('medical', 'Medical'),
        ('security', 'Security'),
        ('notification', 'Notification'),
    ]
    
    key = models.CharField(max_length=100, unique=True)
    value = models.TextField()
    setting_type = models.CharField(max_length=20, choices=SETTING_TYPE_CHOICES, default='general')
    description = models.TextField(blank=True, null=True)
    is_active = models.BooleanField(default=True)
    updated_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'system_settings'
        ordering = ['setting_type', 'key']
    
    def __str__(self):
        return f"{self.key} ({self.setting_type})"


class BackupLog(models.Model):
    BACKUP_TYPE_CHOICES = [
        ('full', 'Full Backup'),
        ('incremental', 'Incremental Backup'),
        ('manual', 'Manual Backup'),
        ('scheduled', 'Scheduled Backup'),
    ]
    
    STATUS_CHOICES = [
        ('pending', 'Pending'),
        ('in_progress', 'In Progress'),
        ('completed', 'Completed'),
        ('failed', 'Failed'),
    ]
    
    backup_type = models.CharField(max_length=15, choices=BACKUP_TYPE_CHOICES)
    status = models.CharField(max_length=15, choices=STATUS_CHOICES, default='pending')
    file_path = models.CharField(max_length=255, blank=True, null=True)
    file_size = models.BigIntegerField(blank=True, null=True)  # in bytes
    tables_included = models.JSONField(default=list, blank=True)
    error_message = models.TextField(blank=True, null=True)
    started_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    started_at = models.DateTimeField(auto_now_add=True)
    completed_at = models.DateTimeField(blank=True, null=True)
    
    class Meta:
        db_table = 'backup_logs'
        ordering = ['-started_at']
    
    def __str__(self):
        return f"{self.backup_type} - {self.status} ({self.started_at})"


class AuditLog(models.Model):
    ACTION_CHOICES = [
        ('create', 'Create'),
        ('read', 'Read'),
        ('update', 'Update'),
        ('delete', 'Delete'),
        ('login', 'Login'),
        ('logout', 'Logout'),
        ('export', 'Export'),
        ('import', 'Import'),
    ]
    
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    action = models.CharField(max_length=10, choices=ACTION_CHOICES)
    model_name = models.CharField(max_length=50)
    object_id = models.CharField(max_length=50, blank=True, null=True)
    object_repr = models.CharField(max_length=255, blank=True, null=True)
    changes = models.JSONField(default=dict, blank=True)
    ip_address = models.GenericIPAddressField()
    user_agent = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        db_table = 'audit_logs'
        ordering = ['-created_at']
    
    def __str__(self):
        return f"{self.action} {self.model_name} - {self.user or 'System'} ({self.created_at})"
