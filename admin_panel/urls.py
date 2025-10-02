from django.urls import path
from . import views

urlpatterns = [
    path('', views.dashboard, name='dashboard'),
    path('admins/', views.admin_list, name='admin_list'),
    path('admins/create/', views.admin_create, name='admin_create'),
    path('admins/<int:admin_id>/', views.admin_detail, name='admin_detail'),
    path('admins/<int:admin_id>/edit/', views.admin_edit, name='admin_edit'),
    path('admins/<int:admin_id>/delete/', views.admin_delete, name='admin_delete'),
    path('settings/', views.settings, name='settings'),
    path('settings/create/', views.setting_create, name='setting_create'),
    path('settings/<int:setting_id>/edit/', views.setting_edit, name='setting_edit'),
    path('settings/<int:setting_id>/delete/', views.setting_delete, name='setting_delete'),
    path('backups/', views.backup_list, name='backup_list'),
    path('backups/create/', views.backup_create, name='backup_create'),
    path('backups/<int:backup_id>/', views.backup_detail, name='backup_detail'),
    path('backups/<int:backup_id>/download/', views.backup_download, name='backup_download'),
    path('backups/<int:backup_id>/delete/', views.backup_delete, name='backup_delete'),
    path('activity-logs/', views.activity_log_list, name='activity_log_list'),
    path('activity-logs/<int:log_id>/', views.activity_log_detail, name='activity_log_detail'),
    path('activity-logs/<int:log_id>/archive/', views.activity_log_archive, name='activity_log_archive'),
    path('audit-logs/', views.audit_log_list, name='audit_log_list'),
    path('audit-logs/<int:log_id>/', views.audit_log_detail, name='audit_log_detail'),
    path('security-logs/', views.security_logs, name='security_logs'),
    path('clear-failed-attempts/', views.clear_failed_attempts, name='clear_failed_attempts'),
]
