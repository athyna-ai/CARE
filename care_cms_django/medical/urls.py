from django.urls import path
from . import views

app_name = 'medical'

urlpatterns = [
    path('save-form/', views.SaveMedicalFormView.as_view(), name='save_form'),
    path('medical-form/<int:patient_id>/<str:patient_type>/', views.MedicalFormView.as_view(), name='medical_form'),
    path('edit-record/<int:record_id>/', views.EditMedicalRecordView.as_view(), name='edit_record'),
    path('delete-record/<int:record_id>/', views.delete_medical_record, name='delete_record'),
]