from django.db import models
from django.contrib.auth.models import User
from patients.models import Student, Faculty
import json


class MedicalRecord(models.Model):
    FORM_TYPE_CHOICES = [
        ('athlete', 'Athlete Form'),
        ('general', 'General Checkup'),
        ('general_checkup', 'General Checkup'),
        ('emergency', 'Emergency Form'),
        ('medical_history', 'Medical History'),
    ]
    
    patient_id = models.IntegerField()
    patient_type = models.CharField(max_length=10, choices=[('student', 'Student'), ('faculty', 'Faculty')])
    form_type = models.CharField(max_length=20, choices=FORM_TYPE_CHOICES)
    form_data = models.JSONField(default=dict, blank=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    archived = models.BooleanField(default=False)
    
    class Meta:
        db_table = 'medical_records'
        ordering = ['-created_at']
    
    def __str__(self):
        return f"{self.patient_type.title()} {self.patient_id} - {self.form_type}"


class Visitation(models.Model):
    VISITATION_TYPE_CHOICES = [
        ('routine', 'Routine Checkup'),
        ('emergency', 'Emergency'),
        ('follow_up', 'Follow-up'),
        ('vaccination', 'Vaccination'),
        ('injury', 'Injury'),
        ('illness', 'Illness'),
    ]
    
    student = models.ForeignKey(Student, on_delete=models.CASCADE, null=True, blank=True, related_name='visitations')
    faculty = models.ForeignKey(Faculty, on_delete=models.CASCADE, null=True, blank=True, related_name='visitations')
    visitation_type = models.CharField(max_length=20, choices=VISITATION_TYPE_CHOICES)
    date_visited = models.DateTimeField()
    symptoms = models.TextField(blank=True, null=True)
    diagnosis = models.TextField(blank=True, null=True)
    treatment = models.TextField(blank=True, null=True)
    medication_prescribed = models.TextField(blank=True, null=True)
    follow_up_required = models.BooleanField(default=False)
    follow_up_date = models.DateField(blank=True, null=True)
    notes = models.TextField(blank=True, null=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    archived = models.BooleanField(default=False)
    
    class Meta:
        db_table = 'visitations'
        ordering = ['-created_at']
    
    def __str__(self):
        patient_name = self.student.name if self.student else self.faculty.name
        return f"{patient_name} - {self.visitation_type} ({self.date_visited})"
    
    @property
    def patient(self):
        """Return the patient object (student or faculty)"""
        return self.student or self.faculty


class CheckupForm(models.Model):
    student = models.ForeignKey(Student, on_delete=models.CASCADE, null=True, blank=True, related_name='checkup_forms')
    faculty = models.ForeignKey(Faculty, on_delete=models.CASCADE, null=True, blank=True, related_name='checkup_forms')
    form_type = models.CharField(max_length=50)
    form_data = models.JSONField(default=dict, blank=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    archived = models.BooleanField(default=False)
    
    class Meta:
        db_table = 'checkup_forms'
        ordering = ['-created_at']
    
    def __str__(self):
        patient_name = self.student.name if self.student else self.faculty.name
        return f"{patient_name} - {self.form_type}"
    
    @property
    def patient(self):
        """Return the patient object (student or faculty)"""
        return self.student or self.faculty


class DailyLog(models.Model):
    date = models.DateField()
    total_visits = models.IntegerField(default=0)
    emergency_visits = models.IntegerField(default=0)
    routine_visits = models.IntegerField(default=0)
    notes = models.TextField(blank=True, null=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'daily_logs'
        ordering = ['-date']
        unique_together = ['date']
    
    def __str__(self):
        return f"Daily Log - {self.date}"