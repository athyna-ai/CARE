from django.db import models
from django.conf import settings


class RFIDLog(models.Model):
    """RFID access logs for tracking RFID usage"""
    rfid = models.CharField(max_length=50)
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True)
    action = models.CharField(max_length=50)  # login, access, etc.
    success = models.BooleanField(default=True)
    ip_address = models.CharField(max_length=45)
    user_agent = models.CharField(max_length=255)
    timestamp = models.DateTimeField(auto_now_add=True)
    details = models.TextField(blank=True, null=True)
    
    class Meta:
        db_table = 'rfid_logs'
        indexes = [
            models.Index(fields=['rfid']),
            models.Index(fields=['timestamp']),
            models.Index(fields=['success']),
        ]
        
    def __str__(self):
        return f"RFID {self.rfid} - {self.action} ({self.timestamp})"