from django.db import models
from django.conf import settings


class Report(models.Model):
    """Generated reports and analytics"""
    REPORT_TYPE_CHOICES = [
        ('daily', 'Daily Report'),
        ('weekly', 'Weekly Report'),
        ('monthly', 'Monthly Report'),
        ('yearly', 'Yearly Report'),
        ('custom', 'Custom Report'),
        ('analytics', 'Analytics Report'),
        ('security', 'Security Report'),
    ]
    
    STATUS_CHOICES = [
        ('pending', 'Pending'),
        ('generating', 'Generating'),
        ('completed', 'Completed'),
        ('failed', 'Failed'),
    ]
    
    report_type = models.CharField(max_length=20, choices=REPORT_TYPE_CHOICES)
    title = models.CharField(max_length=255)
    description = models.TextField(blank=True, null=True)
    status = models.CharField(max_length=15, choices=STATUS_CHOICES, default='pending')
    
    # Date range for the report
    start_date = models.DateField(blank=True, null=True)
    end_date = models.DateField(blank=True, null=True)
    
    # Report data and filters
    filters = models.JSONField(default=dict, blank=True)
    data = models.JSONField(default=dict, blank=True)
    
    # File information
    file_path = models.CharField(max_length=500, blank=True, null=True)
    file_size = models.BigIntegerField(blank=True, null=True)
    
    # Metadata
    generated_by = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True)
    generated_at = models.DateTimeField(auto_now_add=True)
    completed_at = models.DateTimeField(blank=True, null=True)
    error_message = models.TextField(blank=True, null=True)
    
    class Meta:
        db_table = 'reports'
        indexes = [
            models.Index(fields=['report_type']),
            models.Index(fields=['status']),
            models.Index(fields=['generated_at']),
            models.Index(fields=['start_date', 'end_date']),
        ]
        
    def __str__(self):
        return f"{self.title} ({self.report_type})"


class AnalyticsCache(models.Model):
    """Cache for analytics data to improve performance"""
    cache_key = models.CharField(max_length=255, unique=True)
    data = models.JSONField()
    expires_at = models.DateTimeField()
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        db_table = 'analytics_cache'
        indexes = [
            models.Index(fields=['cache_key']),
            models.Index(fields=['expires_at']),
        ]
        
    def __str__(self):
        return f"Cache: {self.cache_key}"