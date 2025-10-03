from django.db import models
from django.conf import settings


class Student(models.Model):
    """Student records - matches PHP students table exactly"""
    GENDER_CHOICES = [
        ('Male', 'Male'),
        ('Female', 'Female'),
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
    gender = models.CharField(max_length=6, choices=GENDER_CHOICES, blank=True, null=True)
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
    contacts = models.JSONField(blank=True, null=True)  # JSON field for multiple contacts
    emergency_contact = models.CharField(max_length=120, blank=True, null=True)
    medical_notes = models.TextField(blank=True, null=True)
    status = models.CharField(max_length=12, choices=STATUS_CHOICES, default='Active')
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


class Faculty(models.Model):
    """Faculty records - matches PHP faculty table exactly"""
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
    gender = models.CharField(max_length=6, choices=GENDER_CHOICES, blank=True, null=True)
    rfid = models.CharField(max_length=50, unique=True)
    address = models.CharField(max_length=255, blank=True, null=True)
    age = models.IntegerField(blank=True, null=True)
    sr = models.BooleanField(default=False)  # Senior citizen flag
    dob = models.DateField(blank=True, null=True)
    religion = models.CharField(max_length=80, blank=True, null=True)
    emergency_contact = models.CharField(max_length=120, blank=True, null=True)
    allergies = models.TextField(blank=True, null=True)
    medical_notes = models.TextField(blank=True, null=True)
    employee_id = models.CharField(max_length=50, blank=True, null=True)
    position = models.CharField(max_length=100, blank=True, null=True)
    status = models.CharField(max_length=12, choices=STATUS_CHOICES, default='Active')
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
        return f"{self.name} ({self.department})"


class EnrollmentHistory(models.Model):
    """Enrollment history for students - tracks enrollment changes"""
    ENROLLMENT_TYPE_CHOICES = [
        ('initial', 'initial'),
        ('re_enrollment', 're_enrollment'),
        ('transfer', 'transfer'),
    ]
    
    student = models.ForeignKey(Student, on_delete=models.CASCADE)
    enrollment_type = models.CharField(max_length=20, choices=ENROLLMENT_TYPE_CHOICES)
    
    # Previous enrollment data
    previous_level = models.CharField(max_length=20, blank=True, null=True)
    previous_status = models.CharField(max_length=12, blank=True, null=True)
    previous_year_grade = models.CharField(max_length=40, blank=True, null=True)
    previous_section = models.CharField(max_length=50, blank=True, null=True)
    previous_strand = models.CharField(max_length=50, blank=True, null=True)
    previous_course = models.CharField(max_length=120, blank=True, null=True)
    previous_block = models.CharField(max_length=10, blank=True, null=True)
    
    # New enrollment data
    new_level = models.CharField(max_length=20, blank=True, null=True)
    new_status = models.CharField(max_length=12, blank=True, null=True)
    new_year_grade = models.CharField(max_length=40, blank=True, null=True)
    new_section = models.CharField(max_length=50, blank=True, null=True)
    new_strand = models.CharField(max_length=50, blank=True, null=True)
    new_course = models.CharField(max_length=120, blank=True, null=True)
    new_block = models.CharField(max_length=10, blank=True, null=True)
    
    enrollment_year = models.CharField(max_length=4)
    notes = models.TextField(blank=True, null=True)
    created_by = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    
    class Meta:
        db_table = 'enrollment_history'
        
    def __str__(self):
        return f"{self.student.name} - {self.enrollment_type}"