# Care CMS - Complete Data Flow Diagram (DFD) Documentation

## **ZERO LEVEL DFD (Context Diagram)**

### External Entities:
1. **Admin Users** - Clinic staff and administrators
2. **Students** - School students (patients)
3. **Faculty** - School faculty members (patients)
4. **RFID Reader** - Hardware authentication device
5. **System Administrator** - Technical maintenance personnel

### Main Process:
- **CARE CMS SYSTEM** (Process 0)

### Data Flows:
- **FROM Admin Users TO System**: Login Credentials, Patient Data Inputs, Medical Records, Visitation Logs, Archive Requests, System Configuration
- **FROM Students TO System**: Student Information, RFID Data
- **FROM Faculty TO System**: Faculty Information, RFID Data  
- **FROM RFID Reader TO System**: RFID Card Data, Authentication Requests
- **FROM System Administrator TO System**: Admin Accounts, System Settings, Maintenance Commands
- **FROM System TO Admin Users**: System Reports, Patient Records, Activity Logs, Analytics, Dashboards
- **FROM System TO Students**: Patient Records (when requested)
- **FROM System TO Faculty**: Patient Records (when requested)
- **FROM System TO RFID Reader**: Authentication Status, Access Control

---

## **LEVEL 1 DFD - Main Processes**

### External Entities:
1. **Admin Users**
2. **Students**
3. **Faculty**
4. **RFID Reader**
5. **System Administrator**

### Processes:
1. **1.0 - User Authentication & Authorization**
2. **2.0 - Patient Management**
3. **3.0 - Medical Records Management**
4. **4.0 - Visitation Management**
5. **5.0 - RFID System Management**
6. **6.0 - Reporting & Analytics**
7. **7.0 - Archive & Backup Management**
8. **8.0 - System Administration**

### Data Stores:
- **D1 - Users Database**
- **D2 - Students Database**
- **D3 - Faculty Database**
- **D4 - Medical Records Database**
- **D5 - Visitation Logs Database**
- **D6 - Activity Logs Database**
- **D7 - Archive Database**
- **D8 - Daily Logs Database**

### Complete Data Flows:

#### External Entities to Processes:
- Admin Users → 1.0: Login Credentials (Username, Email, Password)
- Admin Users → 2.0: Patient Registration Data
- Admin Users → 3.0: Medical Form Data
- Admin Users → 4.0: Visitation Records
- Admin Users → 6.0: Report Requests
- Admin Users → 7.0: Archive Requests
- Admin Users → 8.0: System Configuration
- Students → 2.0: Student Information
- Faculty → 2.0: Faculty Information
- RFID Reader → 1.0: RFID Card Data
- RFID Reader → 5.0: RFID Scan Data
- System Administrator → 8.0: Admin Commands

#### Processes to External Entities:
- 1.0 → Admin Users: Authentication Status, Session Token
- 2.0 → Admin Users: Patient Records, Registration Confirmation
- 3.0 → Admin Users: Medical Records, Form Confirmation
- 4.0 → Admin Users: Visitation Records, Visit Confirmation
- 5.0 → Admin Users: Patient Search Results
- 5.0 → RFID Reader: Authentication Response
- 6.0 → Admin Users: Reports, Analytics, Statistics
- 7.0 → Admin Users: Archive Status, Restore Confirmation
- 8.0 → Admin Users: System Status, Configuration Updates

#### Processes to Data Stores:
- 1.0 → D1: User Session Data, Login Attempts
- 1.0 → D6: Authentication Logs
- 2.0 → D2: Student Records
- 2.0 → D3: Faculty Records
- 2.0 → D6: Patient Management Logs
- 3.0 → D4: Medical Records, Medical Forms
- 3.0 → D6: Medical Record Logs
- 4.0 → D5: Visitation Logs, Treatment Records
- 4.0 → D6: Visitation Activity Logs
- 5.0 → D2: Student RFID Updates
- 5.0 → D3: Faculty RFID Updates
- 5.0 → D1: Admin RFID Updates
- 5.0 → D6: RFID Activity Logs
- 6.0 → D6: Activity Logs
- 6.0 → D8: Daily Log Summaries
- 7.0 → D7: Archived Records
- 7.0 → D6: Archive Activity Logs
- 8.0 → D1: User Accounts, Permission Updates
- 8.0 → D6: System Administration Logs

#### Data Stores to Processes:
- D1 → 1.0: User Credentials, RFID Data, Account Status
- D1 → 8.0: User Information
- D2 → 2.0: Student Records
- D2 → 3.0: Student Patient Data
- D2 → 4.0: Student Information
- D2 → 5.0: Student RFID Data
- D2 → 6.0: Student Statistics
- D2 → 7.0: Student Records for Archiving
- D3 → 2.0: Faculty Records
- D3 → 3.0: Faculty Patient Data
- D3 → 4.0: Faculty Information
- D3 → 5.0: Faculty RFID Data
- D3 → 6.0: Faculty Statistics
- D3 → 7.0: Faculty Records for Archiving
- D4 → 3.0: Medical Records
- D4 → 6.0: Medical Statistics
- D4 → 7.0: Medical Records for Archiving
- D5 → 4.0: Visitation History
- D5 → 6.0: Visitation Statistics
- D5 → 7.0: Visitation Logs for Archiving
- D6 → 6.0: Activity Logs, Security Logs
- D6 → 8.0: System Activity Data
- D7 → 7.0: Archived Records
- D8 → 6.0: Daily Summaries

#### Process to Process:
- 1.0 → 2.0: Authenticated User Session
- 1.0 → 3.0: Authenticated User Session
- 1.0 → 4.0: Authenticated User Session
- 1.0 → 5.0: Authenticated User Session
- 1.0 → 6.0: Authenticated User Session
- 1.0 → 7.0: Authenticated User Session
- 1.0 → 8.0: Authenticated User Session
- 2.0 → 3.0: Patient ID, Patient Type
- 2.0 → 4.0: Patient ID, Patient Type
- 2.0 → 5.0: Patient ID for RFID Assignment
- 3.0 → 4.0: Medical History Data
- 5.0 → 2.0: RFID Search Results
- 6.0 → ALL: Logging Requests
- 7.0 → 2.0: Restored Patient Records
- 7.0 → 3.0: Restored Medical Records
- 7.0 → 4.0: Restored Visitation Logs

---

## **LEVEL 2 DFD - Process 1.0 (User Authentication & Authorization)**

### Sub-Processes:
- **1.1 - Login Verification**
- **1.2 - RFID Authentication**
- **1.3 - Session Management**
- **1.4 - Account Management**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 1.1: Username/Email, Password
- RFID Reader → 1.2: RFID Card Number

#### Sub-Process to External:
- 1.3 → Admin Users: Session Token, User Info
- 1.4 → Admin Users: Account Status

#### Sub-Processes to Data Stores:
- 1.1 → D1: Failed Login Attempts, Account Lockouts
- 1.2 → D1: RFID Verification Status
- 1.3 → D1: Session Data, Last Login Timestamp
- 1.4 → D1: New User Accounts, Password Updates, RFID Updates
- ALL → D6: Authentication Activity Logs

#### Data Stores to Sub-Processes:
- D1 → 1.1: User Credentials, Account Status, Failed Attempts Count
- D1 → 1.2: Stored RFID Hash
- D1 → 1.3: User Session Data
- D1 → 1.4: User Account Information

#### Between Sub-Processes:
- 1.1 → 1.2: Validated User ID, Pending Login Status
- 1.2 → 1.3: Authenticated User Data
- 1.3 → 1.4: Session Validation Requests

---

## **LEVEL 2 DFD - Process 2.0 (Patient Management)**

### Sub-Processes:
- **2.1 - Student Registration**
- **2.2 - Faculty Registration**
- **2.3 - Patient Information Updates**
- **2.4 - Patient Status Management**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 2.1: Student Info (Name, Level, Course, Section, RFID, Address, Age, DOB, Religion, Guardian, Allergies, Contacts, Emergency Contact, Medical Notes)
- Admin Users → 2.2: Faculty Info (Name, Department, Gender, RFID, Address, Age, Senior Status, DOB, Religion, Emergency Contact, Allergies, Medical Notes)
- Admin Users → 2.3: Update Requests (Personal Info, Academic Details, Contact Updates)
- Admin Users → 2.4: Status Change Requests (Active, Inactive, Graduated, Transferred)
- Students → 2.1: Self-Registration Data
- Faculty → 2.2: Self-Registration Data

#### Sub-Process to External:
- 2.1 → Admin Users: Registration Confirmation, Student ID
- 2.2 → Admin Users: Registration Confirmation, Faculty ID
- 2.3 → Admin Users: Update Confirmation
- 2.4 → Admin Users: Status Change Confirmation

#### Sub-Processes to Data Stores:
- 2.1 → D2: New Student Records
- 2.2 → D3: New Faculty Records
- 2.3 → D2: Updated Student Information
- 2.3 → D3: Updated Faculty Information
- 2.4 → D2: Student Status Changes
- 2.4 → D3: Faculty Status Changes
- ALL → D6: Patient Management Activity Logs

#### Data Stores to Sub-Processes:
- D2 → 2.1: Existing Student Records (for duplicate check)
- D2 → 2.3: Current Student Information
- D2 → 2.4: Student Status
- D3 → 2.2: Existing Faculty Records (for duplicate check)
- D3 → 2.3: Current Faculty Information
- D3 → 2.4: Faculty Status

#### Between Sub-Processes:
- 2.1 → 2.3: New Student ID for Updates
- 2.2 → 2.3: New Faculty ID for Updates
- 2.3 → 2.4: Updated Patient ID for Status Management

---

## **LEVEL 2 DFD - Process 3.0 (Medical Records Management)**

### Sub-Processes:
- **3.1 - Medical Form Creation**
- **3.2 - Medical Record Storage**
- **3.3 - Medical Record Updates**
- **3.4 - Medical Record Retrieval**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 3.1: Medical Form Data (Form Type: Athlete/General/Emergency/Medical History, Form Data: Sport, Position, Height, Weight, Medical History, Physical Exam, Recommendations, Symptoms, Allergies, Medications)
- Admin Users → 3.3: Update Requests (Record ID, Updated Data)
- Admin Users → 3.4: Retrieval Requests (Patient ID, Patient Type, Date Range)

#### Sub-Process to External:
- 3.1 → Admin Users: Form Creation Confirmation, Record ID
- 3.3 → Admin Users: Update Confirmation
- 3.4 → Admin Users: Medical Records, Medical History, Reports

#### Sub-Processes to Data Stores:
- 3.1 → D4: New Medical Records
- 3.2 → D4: Validated Medical Records
- 3.3 → D4: Updated Medical Records
- ALL → D6: Medical Record Activity Logs

#### Data Stores to Sub-Processes:
- D2 → 3.1: Student Patient Data (for form creation)
- D3 → 3.1: Faculty Patient Data (for form creation)
- D4 → 3.2: Medical Records (for validation)
- D4 → 3.3: Existing Medical Records
- D4 → 3.4: Medical Records for Retrieval

#### Between Sub-Processes:
- 3.1 → 3.2: New Form Data for Storage
- 3.2 → 3.3: Stored Record ID for Updates
- 3.2 → 3.4: Record ID for Retrieval
- 3.3 → 3.4: Updated Records for Display

---

## **LEVEL 2 DFD - Process 4.0 (Visitation Management)**

### Sub-Processes:
- **4.1 - Visit Registration**
- **4.2 - Treatment Recording**
- **4.3 - Medication Management**
- **4.4 - Visit Completion**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 4.1: Visit Info (Patient ID, Visit Reason, Symptoms, Visit Date)
- Admin Users → 4.2: Treatment Data (Symptoms, Heart Rate, Blood Pressure, Temperature, Other Notes)
- Admin Users → 4.3: Medication Data (Medication Given, Medication Name, Other Medication, Medication Notes, Other Treatment)
- Admin Users → 4.4: Completion Data (Follow-up Instructions, Discharge Notes)

#### Sub-Process to External:
- 4.1 → Admin Users: Check-in Confirmation, Visit ID
- 4.2 → Admin Users: Treatment Recording Confirmation
- 4.3 → Admin Users: Medication Dispensing Confirmation
- 4.4 → Admin Users: Visit Summary, Discharge Instructions

#### Sub-Processes to Data Stores:
- 4.1 → D5: Visit Check-in Records
- 4.2 → D5: Treatment Details
- 4.3 → D5: Medication Records
- 4.4 → D5: Complete Visitation Logs
- ALL → D6: Visitation Activity Logs

#### Data Stores to Sub-Processes:
- D2 → 4.1: Student Patient Information
- D3 → 4.1: Faculty Patient Information
- D4 → 4.2: Medical History (for treatment reference)
- D5 → 4.2: Current Visit Data
- D5 → 4.3: Visit and Treatment Data
- D5 → 4.4: Complete Visit Information

#### Between Sub-Processes:
- 4.1 → 4.2: Visit ID, Patient Info
- 4.2 → 4.3: Visit ID, Treatment Data
- 4.3 → 4.4: Visit ID, Complete Visit Data

---

## **LEVEL 2 DFD - Process 5.0 (RFID System Management)**

### Sub-Processes:
- **5.1 - RFID Registration**
- **5.2 - RFID Search**
- **5.3 - RFID Authentication**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 5.1: RFID Assignment (Patient ID, RFID Number)
- RFID Reader → 5.2: RFID Scan Data
- RFID Reader → 5.3: RFID Authentication Request

#### Sub-Process to External:
- 5.1 → Admin Users: RFID Assignment Confirmation
- 5.2 → Admin Users: Patient Search Results
- 5.3 → RFID Reader: Authentication Status

#### Sub-Processes to Data Stores:
- 5.1 → D1: Admin RFID Assignments
- 5.1 → D2: Student RFID Assignments
- 5.1 → D3: Faculty RFID Assignments
- ALL → D6: RFID Activity Logs

#### Data Stores to Sub-Processes:
- D1 → 5.2: Admin User RFID Data
- D1 → 5.3: Admin RFID for Authentication
- D2 → 5.2: Student RFID Data
- D2 → 5.3: Student RFID for Authentication
- D3 → 5.2: Faculty RFID Data
- D3 → 5.3: Faculty RFID for Authentication

#### Between Sub-Processes:
- 5.1 → 5.2: Newly Assigned RFID
- 5.2 → 5.3: Found Patient/User Data
- 5.3 → 5.1: Authentication Results for RFID Updates

---

## **LEVEL 2 DFD - Process 6.0 (Reporting & Analytics)**

### Sub-Processes:
- **6.1 - Activity Logging**
- **6.2 - Report Generation**
- **6.3 - Analytics Processing**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 6.2: Report Requests (Report Type, Date Range, Filter Criteria)
- Admin Users → 6.3: Analytics Requests (Trend Analysis, Statistics)
- ALL PROCESSES → 6.1: Activity Data (User Actions, System Events)

#### Sub-Process to External:
- 6.2 → Admin Users: Generated Reports
- 6.3 → Admin Users: Analytics Results, Charts, Statistics

#### Sub-Processes to Data Stores:
- 6.1 → D6: Activity Logs, Security Logs
- 6.1 → D8: Daily Log Summaries
- 6.2 → D8: Archived Daily Logs

#### Data Stores to Sub-Processes:
- D2 → 6.2: Student Statistics
- D2 → 6.3: Student Data for Analysis
- D3 → 6.2: Faculty Statistics
- D3 → 6.3: Faculty Data for Analysis
- D4 → 6.2: Medical Record Statistics
- D4 → 6.3: Medical Data for Analysis
- D5 → 6.2: Visitation Statistics
- D5 → 6.3: Visitation Data for Analysis
- D6 → 6.2: Activity Logs
- D6 → 6.3: Historical Activity Data
- D8 → 6.2: Daily Summaries
- D8 → 6.3: Historical Daily Data

#### Between Sub-Processes:
- 6.1 → 6.2: Real-time Activity Data
- 6.1 → 6.3: Activity Patterns
- 6.2 → 6.3: Report Data for Analysis
- 6.3 → 6.2: Analyzed Data for Reports

---

## **LEVEL 2 DFD - Process 7.0 (Archive & Backup Management)**

### Sub-Processes:
- **7.1 - Data Archiving**
- **7.2 - Archive Management**
- **7.3 - Data Restoration**

### Data Flows:

#### External to Sub-Processes:
- Admin Users → 7.1: Archive Requests (Record IDs, Archive Reason)
- Admin Users → 7.2: Archive Search Requests
- Admin Users → 7.3: Restore Requests (Archive ID, Target Table)

#### Sub-Process to External:
- 7.1 → Admin Users: Archive Confirmation
- 7.2 → Admin Users: Archive Search Results
- 7.3 → Admin Users: Restore Confirmation

#### Sub-Processes to Data Stores:
- 7.1 → D7: Archived Patient Records
- 7.1 → D7: Archived Medical Records
- 7.1 → D7: Archived Visitation Logs
- 7.3 → D2: Restored Student Records
- 7.3 → D3: Restored Faculty Records
- 7.3 → D4: Restored Medical Records
- 7.3 → D5: Restored Visitation Logs
- ALL → D6: Archive Activity Logs

#### Data Stores to Sub-Processes:
- D2 → 7.1: Student Records for Archiving
- D3 → 7.1: Faculty Records for Archiving
- D4 → 7.1: Medical Records for Archiving
- D5 → 7.1: Visitation Logs for Archiving
- D7 → 7.2: Archived Records
- D7 → 7.3: Records to Restore

#### Between Sub-Processes:
- 7.1 → 7.2: Newly Archived Records
- 7.2 → 7.3: Found Archive Records
- 7.3 → 7.2: Restoration Status Updates

---

## **LEVEL 2 DFD - Process 8.0 (System Administration)**

### Sub-Processes:
- **8.1 - User Administration**
- **8.2 - System Configuration**
- **8.3 - Maintenance Operations**

### Data Flows:

#### External to Sub-Processes:
- System Administrator → 8.1: Admin Account Creation, Permission Changes
- System Administrator → 8.2: Configuration Changes (Security Settings, System Parameters)
- System Administrator → 8.3: Maintenance Commands (Database Updates, Cleanup Operations)

#### Sub-Process to External:
- 8.1 → System Administrator: User Management Status
- 8.2 → System Administrator: Configuration Status
- 8.3 → System Administrator: Maintenance Results

#### Sub-Processes to Data Stores:
- 8.1 → D1: New Admin Accounts, Permission Updates
- 8.3 → D6: Cleanup Operations (Archive Old Logs)
- 8.3 → D8: Daily Log Archiving
- ALL → D6: System Administration Logs

#### Data Stores to Sub-Processes:
- D1 → 8.1: User Account Information
- D1 → 8.2: Current User Settings
- D6 → 8.3: Log Data for Cleanup
- D8 → 8.3: Daily Logs for Archiving

#### Between Sub-Processes:
- 8.1 → 8.2: User Configuration Updates
- 8.2 → 8.3: Configuration Change Logs
- 8.3 → 8.1: Maintenance Status for User Management

---

## **Summary of All Data Flows**

### Data Flow Types:
1. **Input Data Flows** - From external entities to processes
2. **Output Data Flows** - From processes to external entities
3. **Read Data Flows** - From data stores to processes
4. **Write Data Flows** - From processes to data stores
5. **Inter-Process Data Flows** - Between processes

### Key Data Elements:

**User Authentication Data:**
- Username, Email, Password, RFID, Session Token, User ID, Admin Status, Last Login, Failed Attempts

**Patient Data:**
- Patient ID, Name, Gender, Level/Department, Course, Section, Strand, Year/Grade, RFID, Address, Age, DOB, Religion, Guardian, Allergies, Contacts, Emergency Contact, Medical Notes, Status

**Medical Records Data:**
- Record ID, Patient ID, Patient Type, Form Type, Sport, Position, Height, Weight, Medical History, Physical Exam, Recommendations, Symptoms, Medications, Created By, Created Date

**Visitation Data:**
- Visit ID, Patient ID, Patient Type, Visit Date, Reason, Symptoms, Heart Rate, Blood Pressure, Temperature, Medication Given, Medication Name, First Aid Given, First Aid Type, Other Notes, Nurse Name, Created By, Created Date

**RFID Data:**
- RFID Number, Card Type, Patient ID, User ID, Assignment Date

**Activity Log Data:**
- Log ID, User ID, User Type, Action, Description, Location, RFID Used, Success Status, Error Message, Session ID, Timestamp, IP Address, User Agent

**Archive Data:**
- Archive ID, Original ID, Record Type, Archived Data (JSON), Archived By, Archived Date

**Report Data:**
- Report Type, Date Range, Statistics, Charts, Trends, Summaries

---

## **Database Tables (Data Stores)**

### D1 - Users Database
**Table:** `users`
**Fields:** id, name, email, password_hash, rfid, is_admin, is_active, verified, verify_token, reset_token, reset_expiry, last_login, failed_attempts, locked_until, created_at, updated_at

### D2 - Students Database
**Table:** `students`
**Fields:** id, name, gender, level, course, block, section, strand, year_grade, rfid, address, age, dob, religion, guardian, allergies, contacts, emergency_contact, medical_notes, status, created_at, updated_at

### D3 - Faculty Database
**Table:** `faculty`
**Fields:** id, name, department, gender, rfid, address, age, sr, dob, religion, emergency_contact, allergies, medical_notes, created_at, updated_at

### D4 - Medical Records Database
**Table:** `medical_records`
**Fields:** id, patient_id, patient_type, form_type, form_data, created_at, created_by

### D5 - Visitation Logs Database
**Table:** `visitation_logs`
**Fields:** id, patient_id, patient_type, reason, visit_date, symptoms, heart_rate, blood_pressure, temperature, other_notes, medication_given, medication_name, other_treatment, medication_notes, injury, first_aid_given, first_aid_type, nurse_name, created_at, created_by, updated_at, archived

### D6 - Activity Logs Database
**Table:** `activity_logs`
**Fields:** id, user_id, user_type, action, description, action_description, location, rfid_used, success, error_message, session_id, timestamp, ip_address, user_agent, created_timestamp, archived

### D7 - Archive Database
**Tables:** 
- `students_archive`
- `faculty_archive`
- `student_medical_archive`
- `faculty_medical_archive`
- `student_visitation_archive`
- `faculty_visitation_archive`
- `orphaned_visitation_logs`

### D8 - Daily Logs Database
**Table:** `daily_logs`
**Fields:** id, log_date, activity_data, visitation_data, total_activities, total_visitations, created_at, updated_at

---

## **How to Use This Documentation**

This documentation provides complete details for creating DFD diagrams at all levels:

1. **For Context Diagram (Level 0):** Use the external entities and main data flows listed
2. **For Level 1 DFD:** Use all 8 processes, 8 data stores, 5 external entities, and all data flows listed
3. **For Level 2 DFDs:** Expand each process into sub-processes with detailed data flows

**Drawing the Diagrams:**
- Use circles for processes
- Use rectangles for external entities
- Use open rectangles (or parallel lines) for data stores
- Use arrows with labels for data flows
- Show ALL data flows simultaneously in a single diagram for each level

This creates a comprehensive view of the Care CMS system's data flows and processes.

