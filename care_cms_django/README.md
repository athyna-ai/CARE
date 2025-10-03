# CARE CMS Django Migration

This is the Django version of the CARE (Clinic Administration of Records System) that has been migrated from PHP while preserving all original functionality, design, and behavior.

## Features Preserved

- **Complete Authentication System**: Admin login with RFID verification, session management, rate limiting, and account locking
- **Patient Management**: Student and faculty registration, editing, archiving, and re-enrollment
- **RFID Integration**: RFID portal for quick patient search and identification
- **Medical Records**: Comprehensive medical form management (athlete, general, emergency, medical history)
- **Visitation Logs**: Complete visitation tracking with cleanup for orphaned records
- **Activity Logging**: System-wide activity logging and security monitoring
- **Reports & Analytics**: Detailed analytics and reporting features
- **Archive System**: Complete archiving system for maintaining historical records
- **Design Preservation**: Exact same UI/UX with Tailwind CSS styling

## Setup Instructions

### Prerequisites

- Python 3.8+
- MySQL 5.7+
- pip (Python package manager)

### Installation

1. **Clone or navigate to the Django project directory:**
   ```bash
   cd care_cms_django
   ```

2. **Create a virtual environment:**
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

3. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

4. **Set up the database:**
   - Create a MySQL database named `care_cms`
   - Import the original database schema from `../database/care_cms_database.sql`

5. **Run Django migrations:**
   ```bash
   python manage.py makemigrations
   python manage.py migrate
   ```

6. **Create a superuser:**
   ```bash
   python manage.py createsuperuser
   ```

7. **Run the development server:**
   ```bash
   python manage.py runserver
   ```

8. **Access the application:**
   - Open your browser and go to `http://127.0.0.1:8000`
   - Use the superuser credentials to log in

## Database Schema

The Django models exactly match the original PHP database schema:

- `users` - Admin users with RFID support
- `students` - Student records with enrollment tracking
- `faculty` - Faculty member records
- `visitation_logs` - Patient visit records
- `medical_records` - Medical form data (JSON)
- `activity_logs` - System activity logging
- `*_archive` tables - Archived records
- `enrollment_history` - Student enrollment tracking

## Key Differences from PHP Version

1. **Language**: Python/Django instead of PHP
2. **Framework**: Django's MVC architecture instead of procedural PHP
3. **ORM**: Django ORM instead of PDO
4. **Templates**: Django template system instead of PHP includes
5. **Authentication**: Django's built-in auth system with custom RFID verification
6. **URL Routing**: Django URL patterns instead of PHP file-based routing

## Preserved Functionality

- All buttons, modals, popups, and search bars work exactly the same
- Dashboard features and navigation are identical
- Login system with RFID verification preserved
- Settings and configuration options maintained
- CRUD operations (Create, Read, Update, Delete) work identically
- Archiving and re-enrollment logic preserved
- Data privacy consent mechanism maintained
- Popup notifications and live search functionality preserved
- Pagination and activity logging work the same
- Database views and masked data for security maintained

## File Structure

```
care_cms_django/
├── care_cms_django/          # Django project settings
├── core/                     # Core functionality
├── admin_panel/             # Admin panel and authentication
├── patients/                # Patient management
├── medical/                 # Medical records
├── rfid/                    # RFID functionality
├── reports/                 # Reports and analytics
├── logs/                    # Activity and visitation logs
├── templates/               # Django templates
├── static/                  # Static files (CSS, JS, images)
├── manage.py               # Django management script
└── requirements.txt        # Python dependencies
```

## Testing

To test that all functionality matches the original:

1. **Authentication**: Test admin login with and without RFID
2. **Patient Management**: Register, edit, and archive students/faculty
3. **RFID Portal**: Test RFID search and patient identification
4. **Medical Records**: Create and manage medical forms
5. **Visitation Logs**: Test visit logging and orphaned record cleanup
6. **Reports**: Generate analytics and reports
7. **Archive System**: Test archiving and restoration

## Support

This Django migration maintains 100% functional compatibility with the original PHP system while providing the benefits of Django's framework, security features, and maintainability.
