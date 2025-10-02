from django.db import models
from django.contrib.auth.models import User
import json


class Student(models.Model):
    GENDER_CHOICES = [
        ('Male', 'Male'),
        ('Female', 'Female'),
        ('Other', 'Other'),
    ]
    
    LEVEL_CHOICES = [
        ('Pre-school', 'Pre-school'),
        ('Elementary', 'Elementary'),
        ('High School', 'High School'),
        ('Senior High School', 'Senior High School'),
        ('College', 'College'),
    ]
    
    STATUS_CHOICES = [
        ('Active', 'Active'),
        ('Inactive', 'Inactive'),
        ('Graduated', 'Graduated'),
        ('Transferred', 'Transferred'),
    ]
    
    name = models.CharField(max_length=100)
    gender = models.CharField(max_length=10, choices=GENDER_CHOICES, blank=True, null=True)
    level = models.CharField(max_length=20, choices=LEVEL_CHOICES)
    course = models.CharField(max_length=120, blank=True, null=True)
    block = models.CharField(max_length=10, blank=True, null=True)
    section = models.CharField(max_length=50, blank=True, null=True)
    strand = models.CharField(max_length=50, blank=True, null=True)
    year_grade = models.CharField(max_length=40, blank=True, null=True)
    rfid = models.CharField(max_length=50, unique=True)
    address = models.CharField(max_length=255, blank=True, null=True)
    age = models.IntegerField(blank=True, null=True)
    dob = models.DateField(blank=True, null=True)
    religion = models.CharField(max_length=80, blank=True, null=True)
    guardian = models.CharField(max_length=120, blank=True, null=True)
    allergies = models.TextField(blank=True, null=True)
    contacts = models.JSONField(default=list, blank=True)
    emergency_contact = models.CharField(max_length=120, blank=True, null=True)
    medical_notes = models.TextField(blank=True, null=True)
    status = models.CharField(max_length=15, choices=STATUS_CHOICES, default='Active')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'students'
        indexes = [
            models.Index(fields=['level']),
            models.Index(fields=['status']),
            models.Index(fields=['rfid']),
            models.Index(fields=['name']),
        ]
    
    def __str__(self):
        return f"{self.name} ({self.level})"
    
    def get_contacts_list(self):
        """Return contacts as a list"""
        if isinstance(self.contacts, str):
            try:
                return json.loads(self.contacts)
            except json.JSONDecodeError:
                return []
        return self.contacts or []
    
    def set_contacts_list(self, contacts_list):
        """Set contacts from a list"""
        self.contacts = contacts_list


class Faculty(models.Model):
    GENDER_CHOICES = [
        ('Male', 'Male'),
        ('Female', 'Female'),
        ('Other', 'Other'),
    ]
    
    STATUS_CHOICES = [
        ('Active', 'Active'),
        ('Inactive', 'Inactive'),
        ('Retired', 'Retired'),
        ('Resigned', 'Resigned'),
    ]
    
    name = models.CharField(max_length=100)
    department = models.CharField(max_length=100, blank=True, null=True)
    gender = models.CharField(max_length=10, choices=GENDER_CHOICES, blank=True, null=True)
    rfid = models.CharField(max_length=50, unique=True)
    address = models.CharField(max_length=255, blank=True, null=True)
    age = models.IntegerField(blank=True, null=True)
    sr = models.BooleanField(default=False)  # Senior citizen flag
    dob = models.DateField(blank=True, null=True)
    religion = models.CharField(max_length=80, blank=True, null=True)
    emergency_contact = models.CharField(max_length=120, blank=True, null=True)
    allergies = models.TextField(blank=True, null=True)
    contacts = models.JSONField(default=list, blank=True)
    medical_notes = models.TextField(blank=True, null=True)
    employee_id = models.CharField(max_length=50, blank=True, null=True)
    position = models.CharField(max_length=100, blank=True, null=True)
    status = models.CharField(max_length=15, choices=STATUS_CHOICES, default='Active')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'faculty'
        indexes = [
            models.Index(fields=['department']),
            models.Index(fields=['status']),
            models.Index(fields=['rfid']),
            models.Index(fields=['name']),
        ]
    
    def __str__(self):
        return f"{self.name} ({self.department or 'No Department'})"
    
    def get_contacts_list(self):
        """Return contacts as a list"""
        if isinstance(self.contacts, str):
            try:
                return json.loads(self.contacts)
            except json.JSONDecodeError:
                return []
        return self.contacts or []
    
    def set_contacts_list(self, contacts_list):
        """Set contacts from a list"""
        self.contacts = contacts_list


class EnrollmentHistory(models.Model):
    ENROLLMENT_TYPE_CHOICES = [
        ('initial', 'Initial Enrollment'),
        ('re_enrollment', 'Re-enrollment'),
        ('transfer', 'Transfer'),
        ('promotion', 'Promotion'),
    ]
    
    student = models.ForeignKey(Student, on_delete=models.CASCADE, related_name='enrollment_history')
    enrollment_type = models.CharField(max_length=20, choices=ENROLLMENT_TYPE_CHOICES)
    previous_level = models.CharField(max_length=20, blank=True, null=True)
    new_level = models.CharField(max_length=20, blank=True, null=True)
    previous_status = models.CharField(max_length=15, blank=True, null=True)
    new_status = models.CharField(max_length=15, blank=True, null=True)
    previous_year_grade = models.CharField(max_length=40, blank=True, null=True)
    new_year_grade = models.CharField(max_length=40, blank=True, null=True)
    previous_section = models.CharField(max_length=50, blank=True, null=True)
    new_section = models.CharField(max_length=50, blank=True, null=True)
    previous_strand = models.CharField(max_length=50, blank=True, null=True)
    new_strand = models.CharField(max_length=50, blank=True, null=True)
    previous_course = models.CharField(max_length=120, blank=True, null=True)
    new_course = models.CharField(max_length=120, blank=True, null=True)
    previous_block = models.CharField(max_length=10, blank=True, null=True)
    new_block = models.CharField(max_length=10, blank=True, null=True)
    enrollment_year = models.CharField(max_length=4)
    notes = models.TextField(blank=True, null=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        db_table = 'enrollment_history'
        verbose_name_plural = 'Enrollment Histories'
    
    def __str__(self):
        return f"{self.student.name} - {self.enrollment_type} ({self.enrollment_year})"


class ActivityLog(models.Model):
    USER_TYPE_CHOICES = [
        ('admin', 'Admin'),
        ('student', 'Student'),
        ('faculty', 'Faculty'),
        ('system', 'System'),
    ]
    
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    user_type = models.CharField(max_length=10, choices=USER_TYPE_CHOICES, default='admin')
    action = models.CharField(max_length=64)
    description = models.TextField(blank=True, null=True)
    action_description = models.TextField(blank=True, null=True)
    location = models.CharField(max_length=100)
    rfid_used = models.CharField(max_length=50, blank=True, null=True)
    success = models.BooleanField(default=True)
    error_message = models.TextField(blank=True, null=True)
    session_id = models.CharField(max_length=128, blank=True, null=True)
    ip_address = models.GenericIPAddressField()
    user_agent = models.CharField(max_length=255)
    archived = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        db_table = 'activity_logs'
        ordering = ['-created_at']
    
    def __str__(self):
        return f"{self.action} - {self.user or 'System'} ({self.created_at})"
