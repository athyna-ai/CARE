from django.urls import path
from . import views

urlpatterns = [
    path('', views.dashboard_widgets, name='dashboard_widgets'),
    path('analytics/', views.analytics, name='analytics'),
    path('export/', views.data_export, name='data_export'),
    path('export/<int:export_id>/', views.export_detail, name='export_detail'),
    path('templates/', views.report_templates, name='report_templates'),
    path('templates/<int:template_id>/generate/', views.generate_report, name='generate_report'),
    path('generated/<int:report_id>/', views.generated_report_detail, name='generated_report_detail'),
]
