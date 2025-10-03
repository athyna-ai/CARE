from django.urls import path
from . import views

app_name = 'rfid'

urlpatterns = [
    path('portal/', views.RFIDPortalView.as_view(), name='portal'),
]