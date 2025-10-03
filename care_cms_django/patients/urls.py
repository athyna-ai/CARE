from django.urls import path
from . import views

app_name = 'patients'

urlpatterns = [
    path('student-form/', views.StudentFormView.as_view(), name='student_form'),
    path('student-form/<int:student_id>/', views.StudentFormView.as_view(), name='student_form'),
    path('faculty-form/', views.FacultyFormView.as_view(), name='faculty_form'),
    path('faculty-form/<int:faculty_id>/', views.FacultyFormView.as_view(), name='faculty_form'),
    path('school-listing/', views.SchoolListingView.as_view(), name='school_listing'),
    path('faculty-listing/', views.FacultyListingView.as_view(), name='faculty_listing'),
    path('patient-view/<int:patient_id>/<str:patient_type>/', views.PatientViewView.as_view(), name='patient_view'),
    path('archive-student/<int:student_id>/', views.archive_student, name='archive_student'),
    path('archive-faculty/<int:faculty_id>/', views.archive_faculty, name='archive_faculty'),
]