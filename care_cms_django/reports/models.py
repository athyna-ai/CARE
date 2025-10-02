from django.db import models
from django.contrib.auth.models import User
from patients.models import Student, Faculty
from medical.models import MedicalRecord, Visitation, CheckupForm


class ReportTemplate(models.Model):
    REPORT_TYPE_CHOICES = [
        ('student_list', 'Student List'),
        ('faculty_list', 'Faculty List'),
        ('medical_records', 'Medical Records'),
        ('visitations', 'Visitations'),
        ('daily_summary', 'Daily Summary'),
        ('monthly_summary', 'Monthly Summary'),
        ('custom', 'Custom Report'),
    ]
    
    name = models.CharField(max_length=100)
    report_type = models.CharField(max_length=20, choices=REPORT_TYPE_CHOICES)
    description = models.TextField(blank=True, null=True)
    template_data = models.JSONField(default=dict)  # Store report configuration
    is_active = models.BooleanField(default=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'report_templates'
        ordering = ['name']
    
    def __str__(self):
        return f"{self.name} ({self.report_type})"


class GeneratedReport(models.Model):
    STATUS_CHOICES = [
        ('pending', 'Pending'),
        ('generating', 'Generating'),
        ('completed', 'Completed'),
        ('failed', 'Failed'),
    ]
    
    template = models.ForeignKey(ReportTemplate, on_delete=models.CASCADE)
    name = models.CharField(max_length=100)
    status = models.CharField(max_length=15, choices=STATUS_CHOICES, default='pending')
    file_path = models.CharField(max_length=255, blank=True, null=True)
    file_format = models.CharField(max_length=10, choices=[('pdf', 'PDF'), ('excel', 'Excel'), ('csv', 'CSV')])
    parameters = models.JSONField(default=dict)  # Store report parameters
    record_count = models.IntegerField(default=0)
    error_message = models.TextField(blank=True, null=True)
    generated_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    completed_at = models.DateTimeField(blank=True, null=True)
    
    class Meta:
        db_table = 'generated_reports'
        ordering = ['-created_at']
    
    def __str__(self):
        return f"{self.name} - {self.status} ({self.created_at})"


class DashboardWidget(models.Model):
    WIDGET_TYPE_CHOICES = [
        ('chart', 'Chart'),
        ('table', 'Table'),
        ('metric', 'Metric'),
        ('list', 'List'),
    ]
    
    name = models.CharField(max_length=100)
    widget_type = models.CharField(max_length=10, choices=WIDGET_TYPE_CHOICES)
    description = models.TextField(blank=True, null=True)
    configuration = models.JSONField(default=dict)  # Store widget configuration
    position_x = models.IntegerField(default=0)
    position_y = models.IntegerField(default=0)
    width = models.IntegerField(default=4)
    height = models.IntegerField(default=3)
    is_active = models.BooleanField(default=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'dashboard_widgets'
        ordering = ['position_y', 'position_x']
    
    def __str__(self):
        return f"{self.name} ({self.widget_type})"


class DataExport(models.Model):
    EXPORT_TYPE_CHOICES = [
        ('students', 'Students'),
        ('faculty', 'Faculty'),
        ('medical_records', 'Medical Records'),
        ('visitations', 'Visitations'),
        ('activity_logs', 'Activity Logs'),
        ('custom', 'Custom Export'),
    ]
    
    FORMAT_CHOICES = [
        ('csv', 'CSV'),
        ('excel', 'Excel'),
        ('json', 'JSON'),
    ]
    
    export_type = models.CharField(max_length=20, choices=EXPORT_TYPE_CHOICES)
    format = models.CharField(max_length=10, choices=FORMAT_CHOICES)
    file_path = models.CharField(max_length=255, blank=True, null=True)
    file_size = models.BigIntegerField(blank=True, null=True)  # in bytes
    record_count = models.IntegerField(default=0)
    filters = models.JSONField(default=dict)  # Store export filters
    status = models.CharField(max_length=15, choices=[
        ('pending', 'Pending'),
        ('processing', 'Processing'),
        ('completed', 'Completed'),
        ('failed', 'Failed'),
    ], default='pending')
    error_message = models.TextField(blank=True, null=True)
    exported_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    completed_at = models.DateTimeField(blank=True, null=True)
    
    class Meta:
        db_table = 'data_exports'
        ordering = ['-created_at']
    
    def __str__(self):
        return f"{self.export_type} - {self.format} ({self.status})"
