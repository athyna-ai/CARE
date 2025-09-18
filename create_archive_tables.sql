-- Create archive tables for CARE CMS
-- Run this SQL in your care_cms database

-- Create students_archive table
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
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT NULL,
    INDEX idx_original_id (original_id),
    INDEX idx_name (name),
    INDEX idx_level (level),
    INDEX idx_archived_at (archived_at)
);

-- Create faculty_archive table
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
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT NULL,
    INDEX idx_original_id (original_id),
    INDEX idx_name (name),
    INDEX idx_department (department),
    INDEX idx_archived_at (archived_at)
);

-- Add foreign key constraints if needed
-- ALTER TABLE students_archive ADD CONSTRAINT fk_students_archive_archived_by FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE faculty_archive ADD CONSTRAINT fk_faculty_archive_archived_by FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL;
