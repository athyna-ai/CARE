from django.urls import path
from . import views

app_name = 'reports'

urlpatterns = [
    path('analytics/', views.AnalyticsView.as_view(), name='analytics'),
    path('generate-report/', views.GenerateReportView.as_view(), name='generate_report'),
]