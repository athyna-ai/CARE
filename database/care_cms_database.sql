-- =====================================================
-- CARE CMS - Complete Database Setup
-- =====================================================
-- Main tables store ORIGINAL data
-- Views show MASKED data for security
-- Archive tables store archived patient data

-- Create the new database
CREATE DATABASE IF NOT EXISTS care_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE care_cms;

-- =====================================================
-- DAILY LOGS TABLE (for archiving daily logs)
-- =====================================================
CREATE TABLE IF NOT EXISTS daily_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_date DATE NOT NULL UNIQUE,
    activity_data JSON NOT NULL,
    visitation_data JSON NOT NULL,
    total_activities INT NOT NULL DEFAULT 0,
    total_visitations INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_log_date (log_date)
) ENGINE=InnoDB;

-- =====================================================
-- USERS TABLE (replaces admin table)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rfid VARCHAR(50) NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    verify_token VARCHAR(100) NULL,
    reset_token VARCHAR(100) NULL,
    reset_expiry DATETIME NULL,
    last_login TIMESTAMP NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rfid (rfid),
    INDEX idx_is_admin (is_admin)
) ENGINE=InnoDB;

-- =====================================================
-- STUDENTS TABLE (ORIGINAL data)
-- =====================================================
CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female') NULL,
    level ENUM('Pre-school','Elementary','High School','Senior High School','College') NOT NULL,
    course VARCHAR(120) NULL,
    block VARCHAR(10) NULL,
    section VARCHAR(50) NULL,
    strand VARCHAR(50) NULL,
    year_grade VARCHAR(40) NULL,
    rfid VARCHAR(50) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    age INT NULL,
    dob DATE NULL,
    religion VARCHAR(80) NULL,
    guardian VARCHAR(120) NULL,
    allergies TEXT NULL,
    contacts JSON NULL,
    emergency_contact VARCHAR(120) NULL,
    medical_notes TEXT NULL,
    status ENUM('Active','Inactive','Graduated','Transferred') DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_status (status),
    INDEX idx_rfid (rfid),
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- =====================================================
-- FACULTY TABLE (ORIGINAL data)
-- =====================================================
CREATE TABLE IF NOT EXISTS faculty (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NULL,
    gender ENUM('Male','Female','Other') NULL,
    rfid VARCHAR(50) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    age INT NULL,
    sr TINYINT(1) NOT NULL DEFAULT 0,
    dob DATE NULL,
    religion VARCHAR(80) NULL,
    emergency_contact VARCHAR(120) NULL,
    allergies TEXT NULL,
    medical_notes TEXT NULL,
    employee_id VARCHAR(50) NULL,
    position VARCHAR(100) NULL,
    status ENUM('Active','Inactive','Retired','Resigned') DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_status (status),
    INDEX idx_rfid (rfid),
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- =====================================================
-- ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    user_type ENUM('admin','student','faculty','system') DEFAULT 'admin',
    action VARCHAR(64) NOT NULL,
    description TEXT NULL,
    action_description TEXT NULL,
    location VARCHAR(100) NOT NULL,
    rfid_used VARCHAR(50) NULL,
    success TINYINT(1) DEFAULT 1,
    error_message TEXT NULL,
    session_id VARCHAR(128) NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    created_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp),
    INDEX idx_success (success),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- VISITATION LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS visitation_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    patient_type ENUM('student','faculty') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    visit_date DATETIME NOT NULL,
    symptoms TEXT NULL,
    other_notes TEXT NULL,
    heart_rate INT NULL,
    blood_pressure VARCHAR(50) NULL,
    temperature DECIMAL(4,2) NULL,
    medication_given TINYINT(1) DEFAULT 0,
    medication_name VARCHAR(255) NULL,
    other_treatment VARCHAR(255) NULL,
    medication_notes TEXT NULL,
    injury TINYINT(1) DEFAULT 0,
    first_aid_given TINYINT(1) DEFAULT 0,
    first_aid_type VARCHAR(255) NULL,
    nurse_name VARCHAR(100) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_id (patient_id),
    INDEX idx_patient_type (patient_type),
    INDEX idx_visit_date (visit_date),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MEDICAL RECORDS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS medical_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    patient_type ENUM('student','faculty') NOT NULL,
    form_type VARCHAR(100) NOT NULL,
    form_data JSON NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_id (patient_id),
    INDEX idx_patient_type (patient_type),
    INDEX idx_form_type (form_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ARCHIVE TABLES FOR PATIENTS
-- =====================================================

-- Students Archive Table
CREATE TABLE IF NOT EXISTS students_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    level VARCHAR(50) NOT NULL,
    year_grade VARCHAR(20) NULL,
    section VARCHAR(100) NULL,
    strand VARCHAR(100) NULL,
    course VARCHAR(100) NULL,
    rfid VARCHAR(50) NULL,
    address TEXT NULL,
    guardian VARCHAR(255) NULL,
    emergency_contact VARCHAR(20) NULL,
    dob DATE NULL,
    age INT NULL,
    religion VARCHAR(100) NULL,
    allergies TEXT NULL,
    contacts JSON NULL,
    medical_notes TEXT NULL,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT NULL,
    INDEX idx_original_id (original_id),
    INDEX idx_name (name),
    INDEX idx_level (level),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB;

-- Faculty Archive Table
CREATE TABLE IF NOT EXISTS faculty_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NULL,
    address TEXT NULL,
    age INT NULL,
    sr BOOLEAN DEFAULT FALSE,
    dob DATE NULL,
    allergies TEXT NULL,
    religion VARCHAR(100) NULL,
    emergency_contact VARCHAR(20) NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    rfid VARCHAR(50) NULL,
    employee_id VARCHAR(50) NULL,
    position VARCHAR(100) NULL,
    medical_notes TEXT NULL,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT NULL,
    INDEX idx_original_id (original_id),
    INDEX idx_name (name),
    INDEX idx_department (department),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB;

-- =====================================================
-- VISITATION ARCHIVE TABLES
-- =====================================================

-- Student Visitation Archive
CREATE TABLE IF NOT EXISTS student_visitation_archive (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    patient_type ENUM('student','faculty') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    visit_date DATETIME NOT NULL,
    symptoms TEXT NULL,
    other_notes TEXT NULL,
    heart_rate INT NULL,
    blood_pressure VARCHAR(50) NULL,
    temperature DECIMAL(4,2) NULL,
    medication_given TINYINT(1) DEFAULT 0,
    medication_name VARCHAR(255) NULL,
    other_treatment VARCHAR(255) NULL,
    medication_notes TEXT NULL,
    injury TINYINT(1) DEFAULT 0,
    first_aid_given TINYINT(1) DEFAULT 0,
    first_aid_type VARCHAR(255) NULL,
    nurse_name VARCHAR(100) NULL,
    archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by INT UNSIGNED NOT NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_original_id (original_id),
    INDEX idx_archived_at (archived_at),
    INDEX idx_patient_type (patient_type),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Faculty Visitation Archive
CREATE TABLE IF NOT EXISTS faculty_visitation_archive (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    patient_type ENUM('student','faculty') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    visit_date DATETIME NOT NULL,
    symptoms TEXT NULL,
    other_notes TEXT NULL,
    heart_rate INT NULL,
    blood_pressure VARCHAR(50) NULL,
    temperature DECIMAL(4,2) NULL,
    medication_given TINYINT(1) DEFAULT 0,
    medication_name VARCHAR(255) NULL,
    other_treatment VARCHAR(255) NULL,
    medication_notes TEXT NULL,
    injury TINYINT(1) DEFAULT 0,
    first_aid_given TINYINT(1) DEFAULT 0,
    first_aid_type VARCHAR(255) NULL,
    nurse_name VARCHAR(100) NULL,
    archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by INT UNSIGNED NOT NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_original_id (original_id),
    INDEX idx_archived_at (archived_at),
    INDEX idx_patient_type (patient_type),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MEDICAL ARCHIVE TABLES
-- =====================================================

-- Student Medical Archive
CREATE TABLE IF NOT EXISTS student_medical_archive (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    patient_type ENUM('student','faculty') NOT NULL,
    form_type VARCHAR(100) NOT NULL,
    form_data JSON NOT NULL,
    archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by INT UNSIGNED NOT NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_original_id (original_id),
    INDEX idx_archived_at (archived_at),
    INDEX idx_patient_type (patient_type),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Faculty Medical Archive
CREATE TABLE IF NOT EXISTS faculty_medical_archive (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    patient_type ENUM('student','faculty') NOT NULL,
    form_type VARCHAR(100) NOT NULL,
    form_data JSON NOT NULL,
    archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by INT UNSIGNED NOT NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_original_id (original_id),
    INDEX idx_archived_at (archived_at),
    INDEX idx_patient_type (patient_type),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORPHANED RECORDS TABLE
-- =====================================================
-- For handling visitation records when patient data is missing
CREATE TABLE IF NOT EXISTS orphaned_visitation_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NULL,
    patient_type ENUM('student','faculty') NULL,
    reason VARCHAR(255) NOT NULL,
    visit_date DATETIME NOT NULL,
    symptoms TEXT NULL,
    other_notes TEXT NULL,
    heart_rate INT NULL,
    blood_pressure VARCHAR(50) NULL,
    temperature DECIMAL(4,2) NULL,
    medication_given TINYINT(1) DEFAULT 0,
    medication_name VARCHAR(255) NULL,
    other_treatment VARCHAR(255) NULL,
    medication_notes TEXT NULL,
    injury TINYINT(1) DEFAULT 0,
    first_aid_given TINYINT(1) DEFAULT 0,
    first_aid_type VARCHAR(255) NULL,
    nurse_name VARCHAR(100) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    moved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    moved_by INT UNSIGNED NULL,
    reason_moved VARCHAR(255) NOT NULL,
    INDEX idx_original_id (original_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_created_at (created_at),
    INDEX idx_moved_at (moved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MASKED DATA VIEWS (for security/logs)
-- =====================================================

-- Students view with masked sensitive data
CREATE OR REPLACE VIEW students_masked AS
SELECT 
    id,
    CONCAT(LEFT(name, 1), REPEAT('*', GREATEST(0, CHAR_LENGTH(name) - 2)), RIGHT(name, 1)) as name,
    level,
    course,
    section,
    strand,
    year_grade,
    CONCAT(REPEAT('*', GREATEST(0, CHAR_LENGTH(rfid) - 4)), RIGHT(rfid, 4)) as rfid,
    CONCAT(LEFT(address, 3), REPEAT('*', GREATEST(0, CHAR_LENGTH(address) - 6)), RIGHT(address, 3)) as address,
    age,
    dob,
    religion,
    CONCAT(LEFT(guardian, 1), REPEAT('*', GREATEST(0, CHAR_LENGTH(guardian) - 2)), RIGHT(guardian, 1)) as guardian,
    allergies,
    contacts,
    CONCAT(LEFT(emergency_contact, 1), REPEAT('*', GREATEST(0, CHAR_LENGTH(emergency_contact) - 2)), RIGHT(emergency_contact, 1)) as emergency_contact,
    medical_notes,
    status,
    created_at,
    updated_at
FROM students;

-- Faculty view with masked sensitive data
CREATE OR REPLACE VIEW faculty_masked AS
SELECT 
    id,
    CONCAT(LEFT(name, 1), REPEAT('*', GREATEST(0, CHAR_LENGTH(name) - 2)), RIGHT(name, 1)) as name,
    department,
    gender,
    CONCAT(REPEAT('*', GREATEST(0, CHAR_LENGTH(rfid) - 4)), RIGHT(rfid, 4)) as rfid,
    CONCAT(LEFT(address, 3), REPEAT('*', GREATEST(0, CHAR_LENGTH(address) - 6)), RIGHT(address, 3)) as address,
    age,
    sr,
    dob,
    religion,
    CONCAT(LEFT(emergency_contact, 1), REPEAT('*', GREATEST(0, CHAR_LENGTH(emergency_contact) - 2)), RIGHT(emergency_contact, 1)) as emergency_contact,
    allergies,
    medical_notes,
    employee_id,
    position,
    status,
    created_at,
    updated_at
FROM faculty;

-- Users view with masked sensitive data
CREATE OR REPLACE VIEW users_masked AS
SELECT 
    id,
    CONCAT(LEFT(name, 1), REPEAT('*', GREATEST(0, CHAR_LENGTH(name) - 2)), RIGHT(name, 1)) as name,
    CONCAT(LEFT(email, 2), REPEAT('*', GREATEST(0, CHAR_LENGTH(email) - 4)), RIGHT(email, 2)) as email,
    CONCAT(REPEAT('*', GREATEST(0, CHAR_LENGTH(rfid) - 4)), RIGHT(rfid, 4)) as rfid,
    is_admin,
    verified,
    last_login,
    failed_attempts,
    locked_until,
    created_at,
    updated_at
FROM users;

-- =====================================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- =====================================================

-- Add indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_visitation_logs_patient ON visitation_logs(patient_id, patient_type);
CREATE INDEX IF NOT EXISTS idx_visitation_logs_date ON visitation_logs(visit_date);
CREATE INDEX IF NOT EXISTS idx_medical_records_patient ON medical_records(patient_id, patient_type);
CREATE INDEX IF NOT EXISTS idx_medical_records_type ON medical_records(form_type);

-- =====================================================
-- INSERT DEFAULT ADMIN USER
-- =====================================================
INSERT IGNORE INTO users (name, email, password_hash, rfid, is_admin, verified) 
VALUES (
    'Admin User', 
    'admin@care.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'ADMIN001', 
    1, 
    1
);

-- =====================================================
-- COMPLETION
-- =====================================================
SELECT 'CARE CMS database created successfully!' as status;
SELECT 'Main tables store ORIGINAL data' as note1;
SELECT 'Views show MASKED data for security' as note2;
SELECT 'Archive tables created for patient-specific archives' as note3;
SELECT 'Orphaned records table for data integrity' as note4;
SELECT 'Default admin: admin@care.com / password' as note5;