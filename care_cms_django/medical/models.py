from django.db import models
from django.conf import settings


class MedicalRecord(models.Model):
    """Medical records - matches PHP medical_records table exactly"""
    PATIENT_TYPE_CHOICES = [
        ('student', 'student'),
        ('faculty', 'faculty'),
    ]
    
    patient_id = models.PositiveIntegerField()  # References either Student or Faculty ID
    patient_type = models.CharField(max_length=7, choices=PATIENT_TYPE_CHOICES)
    form_type = models.CharField(max_length=100)
    form_data = models.JSONField()  # JSON field for form data
    created_by = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'medical_records'
        indexes = [
            models.Index(fields=['patient_id', 'patient_type']),
            models.Index(fields=['form_type']),
            models.Index(fields=['created_at']),
        ]
        
    def __str__(self):
        return f"{self.form_type} - Patient {self.patient_id}"