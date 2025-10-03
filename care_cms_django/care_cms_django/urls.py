from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path('admin/', admin.site.urls),
    path('', include('core.urls')),
    path('auth/', include('admin_panel.auth_urls')),
    path('admin/', include('admin_panel.urls')),
    path('patients/', include('patients.urls')),
    path('medical/', include('medical.urls')),
    path('rfid/', include('rfid.urls')),
    path('reports/', include('reports.urls')),
    path('logs/', include('logs.urls')),
]

if settings.DEBUG:
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)