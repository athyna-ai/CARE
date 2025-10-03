from django.db import models
from django.contrib.auth.models import AbstractUser
from django.core.validators import RegexValidator
import json


class User(AbstractUser):
    """Admin users with RFID support - matches PHP users table"""
    rfid = models.CharField(max_length=50, blank=True, null=True, unique=True)
    is_admin = models.BooleanField(default=False)
    is_active = models.BooleanField(default=True)
    verified = models.BooleanField(default=False)
    verify_token = models.CharField(max_length=100, blank=True, null=True)
    reset_token = models.CharField(max_length=100, blank=True, null=True)
    reset_expiry = models.DateTimeField(blank=True, null=True)
    last_login = models.DateTimeField(blank=True, null=True)
    failed_attempts = models.IntegerField(default=0)
    locked_until = models.DateTimeField(blank=True, null=True)
    
    class Meta:
        db_table = 'users'
        
    def __str__(self):
        return f"{self.username} ({self.email})"


# Models moved to their respective apps to avoid duplication


# Archive Models - These match the PHP archive tables exactly

class StudentArchive(models.Model):
    """Archived student records"""
    original_id = models.IntegerField()
    name = models.CharField(max_length=255)
    level = models.CharField(max_length=50)
    year_grade = models.CharField(max_length=20, blank=True, null=True)
    section = models.CharField(max_length=100, blank=True, null=True)
    strand = models.CharField(max_length=100, blank=True, null=True)
    course = models.CharField(max_length=100, blank=True, null=True)
    rfid = models.CharField(max_length=50, blank=True, null=True)
    address = models.TextField(blank=True, null=True)
    guardian = models.CharField(max_length=255, blank=True, null=True)
    emergency_contact = models.CharField(max_length=20, blank=True, null=True)
    dob = models.DateField(blank=True, null=True)
    age = models.IntegerField(blank=True, null=True)
    religion = models.CharField(max_length=100, blank=True, null=True)
    allergies = models.TextField(blank=True, null=True)
    contacts = models.JSONField(blank=True, null=True)
    medical_notes = models.TextField(blank=True, null=True)
    archived_at = models.DateTimeField(auto_now_add=True)
    archived_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    
    class Meta:
        db_table = 'students_archive'
        indexes = [
            models.Index(fields=['original_id']),
            models.Index(fields=['name']),
            models.Index(fields=['level']),
            models.Index(fields=['archived_at']),
        ]
        
    def __str__(self):
        return f"Archived: {self.name}"


class FacultyArchive(models.Model):
    """Archived faculty records"""
    GENDER_CHOICES = [
        ('Male', 'Male'),
        ('Female', 'Female'),
        ('Other', 'Other'),
    ]
    
    original_id = models.IntegerField()
    name = models.CharField(max_length=255)
    department = models.CharField(max_length=100, blank=True, null=True)
    address = models.TextField(blank=True, null=True)
    age = models.IntegerField(blank=True, null=True)
    sr = models.BooleanField(default=False)
    dob = models.DateField(blank=True, null=True)
    allergies = models.TextField(blank=True, null=True)
    religion = models.CharField(max_length=100, blank=True, null=True)
    emergency_contact = models.CharField(max_length=20, blank=True, null=True)
    gender = models.CharField(max_length=6, choices=GENDER_CHOICES, blank=True, null=True)
    rfid = models.CharField(max_length=50, blank=True, null=True)
    employee_id = models.CharField(max_length=50, blank=True, null=True)
    position = models.CharField(max_length=100, blank=True, null=True)
    medical_notes = models.TextField(blank=True, null=True)
    archived_at = models.DateTimeField(auto_now_add=True)
    archived_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    
    class Meta:
        db_table = 'faculty_archive'
        indexes = [
            models.Index(fields=['original_id']),
            models.Index(fields=['name']),
            models.Index(fields=['department']),
            models.Index(fields=['archived_at']),
        ]
        
    def __str__(self):
        return f"Archived: {self.name}"


class StudentVisitationArchive(models.Model):
    """Archived student visitation records"""
    PATIENT_TYPE_CHOICES = [
        ('student', 'student'),
        ('faculty', 'faculty'),
    ]
    
    original_id = models.PositiveIntegerField()
    patient_id = models.PositiveIntegerField()
    patient_type = models.CharField(max_length=7, choices=PATIENT_TYPE_CHOICES)
    reason = models.CharField(max_length=255)
    visit_date = models.DateTimeField()
    symptoms = models.TextField(blank=True, null=True)
    other_notes = models.TextField(blank=True, null=True)
    heart_rate = models.IntegerField(blank=True, null=True)
    blood_pressure = models.CharField(max_length=50, blank=True, null=True)
    temperature = models.DecimalField(max_digits=4, decimal_places=2, blank=True, null=True)
    medication_given = models.BooleanField(default=False)
    medication_name = models.CharField(max_length=255, blank=True, null=True)
    other_treatment = models.CharField(max_length=255, blank=True, null=True)
    medication_notes = models.TextField(blank=True, null=True)
    injury = models.BooleanField(default=False)
    first_aid_given = models.BooleanField(default=False)
    first_aid_type = models.CharField(max_length=255, blank=True, null=True)
    nurse_name = models.CharField(max_length=100, blank=True, null=True)
    archived_at = models.DateTimeField(auto_now_add=True)
    archived_by = models.ForeignKey(User, on_delete=models.CASCADE)
    
    class Meta:
        db_table = 'student_visitation_archive'
        indexes = [
            models.Index(fields=['patient_id']),
            models.Index(fields=['original_id']),
            models.Index(fields=['archived_at']),
            models.Index(fields=['patient_type']),
        ]
        
    def __str__(self):
        return f"Archived Visit #{self.original_id}"


class FacultyVisitationArchive(models.Model):
    """Archived faculty visitation records"""
    PATIENT_TYPE_CHOICES = [
        ('student', 'student'),
        ('faculty', 'faculty'),
    ]
    
    original_id = models.PositiveIntegerField()
    patient_id = models.PositiveIntegerField()
    patient_type = models.CharField(max_length=7, choices=PATIENT_TYPE_CHOICES)
    reason = models.CharField(max_length=255)
    visit_date = models.DateTimeField()
    symptoms = models.TextField(blank=True, null=True)
    other_notes = models.TextField(blank=True, null=True)
    heart_rate = models.IntegerField(blank=True, null=True)
    blood_pressure = models.CharField(max_length=50, blank=True, null=True)
    temperature = models.DecimalField(max_digits=4, decimal_places=2, blank=True, null=True)
    medication_given = models.BooleanField(default=False)
    medication_name = models.CharField(max_length=255, blank=True, null=True)
    other_treatment = models.CharField(max_length=255, blank=True, null=True)
    medication_notes = models.TextField(blank=True, null=True)
    injury = models.BooleanField(default=False)
    first_aid_given = models.BooleanField(default=False)
    first_aid_type = models.CharField(max_length=255, blank=True, null=True)
    nurse_name = models.CharField(max_length=100, blank=True, null=True)
    archived_at = models.DateTimeField(auto_now_add=True)
    archived_by = models.ForeignKey(User, on_delete=models.CASCADE)
    
    class Meta:
        db_table = 'faculty_visitation_archive'
        indexes = [
            models.Index(fields=['patient_id']),
            models.Index(fields=['original_id']),
            models.Index(fields=['archived_at']),
            models.Index(fields=['patient_type']),
        ]
        
    def __str__(self):
        return f"Archived Visit #{self.original_id}"


class StudentMedicalArchive(models.Model):
    """Archived student medical records"""
    PATIENT_TYPE_CHOICES = [
        ('student', 'student'),
        ('faculty', 'faculty'),
    ]
    
    original_id = models.PositiveIntegerField()
    patient_id = models.PositiveIntegerField()
    patient_type = models.CharField(max_length=7, choices=PATIENT_TYPE_CHOICES)
    form_type = models.CharField(max_length=100)
    form_data = models.JSONField()
    archived_at = models.DateTimeField(auto_now_add=True)
    archived_by = models.ForeignKey(User, on_delete=models.CASCADE)
    
    class Meta:
        db_table = 'student_medical_archive'
        indexes = [
            models.Index(fields=['patient_id']),
            models.Index(fields=['original_id']),
            models.Index(fields=['archived_at']),
            models.Index(fields=['patient_type']),
        ]
        
    def __str__(self):
        return f"Archived Medical Record #{self.original_id}"


class FacultyMedicalArchive(models.Model):
    """Archived faculty medical records"""
    PATIENT_TYPE_CHOICES = [
        ('student', 'student'),
        ('faculty', 'faculty'),
    ]
    
    original_id = models.PositiveIntegerField()
    patient_id = models.PositiveIntegerField()
    patient_type = models.CharField(max_length=7, choices=PATIENT_TYPE_CHOICES)
    form_type = models.CharField(max_length=100)
    form_data = models.JSONField()
    archived_at = models.DateTimeField(auto_now_add=True)
    archived_by = models.ForeignKey(User, on_delete=models.CASCADE)
    
    class Meta:
        db_table = 'faculty_medical_archive'
        indexes = [
            models.Index(fields=['patient_id']),
            models.Index(fields=['original_id']),
            models.Index(fields=['archived_at']),
            models.Index(fields=['patient_type']),
        ]
        
    def __str__(self):
        return f"Archived Medical Record #{self.original_id}"


class OrphanedVisitationLog(models.Model):
    """Orphaned visitation records - for data integrity"""
    PATIENT_TYPE_CHOICES = [
        ('student', 'student'),
        ('faculty', 'faculty'),
    ]
    
    original_id = models.PositiveIntegerField()
    patient_id = models.PositiveIntegerField(blank=True, null=True)
    patient_type = models.CharField(max_length=7, choices=PATIENT_TYPE_CHOICES, blank=True, null=True)
    reason = models.CharField(max_length=255)
    visit_date = models.DateTimeField()
    symptoms = models.TextField(blank=True, null=True)
    other_notes = models.TextField(blank=True, null=True)
    heart_rate = models.IntegerField(blank=True, null=True)
    blood_pressure = models.CharField(max_length=50, blank=True, null=True)
    temperature = models.DecimalField(max_digits=4, decimal_places=2, blank=True, null=True)
    medication_given = models.BooleanField(default=False)
    medication_name = models.CharField(max_length=255, blank=True, null=True)
    other_treatment = models.CharField(max_length=255, blank=True, null=True)
    medication_notes = models.TextField(blank=True, null=True)
    injury = models.BooleanField(default=False)
    first_aid_given = models.BooleanField(default=False)
    first_aid_type = models.CharField(max_length=255, blank=True, null=True)
    nurse_name = models.CharField(max_length=100, blank=True, null=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    moved_at = models.DateTimeField(auto_now_add=True)
    moved_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, related_name='moved_orphaned_logs')
    reason_moved = models.CharField(max_length=255)
    
    class Meta:
        db_table = 'orphaned_visitation_logs'
        indexes = [
            models.Index(fields=['original_id']),
            models.Index(fields=['patient_id']),
            models.Index(fields=['created_at']),
            models.Index(fields=['moved_at']),
        ]
        
    def __str__(self):
        return f"Orphaned Visit #{self.original_id}"


# RFIDLog moved to rfid app


class DailyLog(models.Model):
    """Daily logs for archiving - matches PHP daily_logs table"""
    log_date = models.DateField(unique=True)
    activity_data = models.JSONField()
    visitation_data = models.JSONField()
    total_activities = models.IntegerField(default=0)
    total_visitations = models.IntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'daily_logs'
        indexes = [
            models.Index(fields=['log_date']),
        ]
        
    def __str__(self):
        return f"Daily Log - {self.log_date}"