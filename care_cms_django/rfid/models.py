from django.db import models
from django.contrib.auth.models import User
from patients.models import Student, Faculty


class RFIDDevice(models.Model):
    DEVICE_TYPE_CHOICES = [
        ('reader', 'RFID Reader'),
        ('writer', 'RFID Writer'),
        ('scanner', 'RFID Scanner'),
    ]
    
    STATUS_CHOICES = [
        ('active', 'Active'),
        ('inactive', 'Inactive'),
        ('maintenance', 'Maintenance'),
    ]
    
    device_id = models.CharField(max_length=50, unique=True)
    device_name = models.CharField(max_length=100)
    device_type = models.CharField(max_length=10, choices=DEVICE_TYPE_CHOICES)
    location = models.CharField(max_length=100)
    status = models.CharField(max_length=15, choices=STATUS_CHOICES, default='active')
    last_maintenance = models.DateTimeField(blank=True, null=True)
    next_maintenance = models.DateTimeField(blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'rfid_devices'
        ordering = ['device_name']
    
    def __str__(self):
        return f"{self.device_name} ({self.location})"


class RFIDScan(models.Model):
    SCAN_TYPE_CHOICES = [
        ('login', 'Login'),
        ('visitation', 'Visitation'),
        ('registration', 'Registration'),
        ('checkup', 'Checkup'),
        ('other', 'Other'),
    ]
    
    rfid_number = models.CharField(max_length=50)
    scan_type = models.CharField(max_length=15, choices=SCAN_TYPE_CHOICES)
    device = models.ForeignKey(RFIDDevice, on_delete=models.SET_NULL, null=True, blank=True)
    student = models.ForeignKey(Student, on_delete=models.SET_NULL, null=True, blank=True)
    faculty = models.ForeignKey(Faculty, on_delete=models.SET_NULL, null=True, blank=True)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    success = models.BooleanField(default=True)
    error_message = models.TextField(blank=True, null=True)
    ip_address = models.GenericIPAddressField()
    user_agent = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        db_table = 'rfid_scans'
        ordering = ['-created_at']
    
    def __str__(self):
        patient_name = self.student.name if self.student else self.faculty.name if self.faculty else 'Unknown'
        return f"RFID Scan - {patient_name} ({self.scan_type})"
    
    @property
    def patient(self):
        """Return the patient object (student or faculty)"""
        return self.student or self.faculty


class RFIDPortalSession(models.Model):
    session_id = models.CharField(max_length=128, unique=True)
    rfid_number = models.CharField(max_length=50, blank=True, null=True)
    search_performed = models.BooleanField(default=False)
    student_found = models.ForeignKey(Student, on_delete=models.SET_NULL, null=True, blank=True)
    faculty_found = models.ForeignKey(Faculty, on_delete=models.SET_NULL, null=True, blank=True)
    registration_started = models.BooleanField(default=False)
    registration_type = models.CharField(max_length=10, choices=[('student', 'Student'), ('faculty', 'Faculty')], blank=True, null=True)
    ip_address = models.GenericIPAddressField()
    user_agent = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'rfid_portal_sessions'
        ordering = ['-created_at']
    
    def __str__(self):
        return f"Portal Session - {self.session_id[:8]}... ({self.created_at})"
