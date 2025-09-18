-- Create medical records and visitation logs tables for CARE CMS
-- Run this SQL in your care_cms database

-- Create medical_records table
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    patient_type ENUM('student', 'faculty') NOT NULL,
    form_type VARCHAR(50) NOT NULL,
    form_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient (patient_id, patient_type),
    INDEX idx_form_type (form_type),
    INDEX idx_created_at (created_at)
);

-- Create visitation_logs table
CREATE TABLE IF NOT EXISTS visitation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    patient_type ENUM('student', 'faculty') NOT NULL,
    reason VARCHAR(100) NOT NULL,
    visit_date DATETIME NOT NULL,
    symptoms TEXT NULL,
    heart_rate INT NULL,
    blood_pressure VARCHAR(20) NULL,
    temperature DECIMAL(4,1) NULL,
    other_notes TEXT NULL,
    medication_given BOOLEAN DEFAULT FALSE,
    medication_name VARCHAR(100) NULL,
    other_treatment VARCHAR(100) NULL,
    medication_notes TEXT NULL,
    injury BOOLEAN DEFAULT FALSE,
    first_aid_given BOOLEAN DEFAULT FALSE,
    first_aid_type VARCHAR(100) NULL,
    nurse_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient (patient_id, patient_type),
    INDEX idx_visit_date (visit_date),
    INDEX idx_reason (reason)
);

-- Add foreign key constraints if needed
-- ALTER TABLE medical_records ADD CONSTRAINT fk_medical_records_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE visitation_logs ADD CONSTRAINT fk_visitation_logs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
