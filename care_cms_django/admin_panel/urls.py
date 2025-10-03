from django.urls import path
from . import views

app_name = 'admin'

urlpatterns = [
    path('dashboard/', views.DashboardView.as_view(), name='dashboard'),
    path('settings/', views.SettingsView.as_view(), name='settings'),
    path('archive/students/', views.ArchiveStudentsView.as_view(), name='archive_students'),
    path('archive/faculty/', views.ArchiveFacultyView.as_view(), name='archive_faculty'),
    path('security-logs/', views.SecurityLogsView.as_view(), name='security_logs'),
    path('backup/', views.BackupView.as_view(), name='backup'),
]