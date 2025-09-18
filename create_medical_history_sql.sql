-- =====================================================
-- Medical History Form SQL Setup
-- =====================================================
-- This SQL adds support for medical history forms in the existing medical_records table

USE care_cms;

-- The medical_records table already exists from create_medical_tables.sql
-- We just need to ensure it can handle the 'medical_history' form type

-- Update the form_type column to include 'medical_history' if it doesn't already support it
-- (The existing ENUM should already support any VARCHAR, but let's make sure)

-- Check if medical_records table exists, if not create it
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

-- Add foreign key constraints if they don't exist
-- ALTER TABLE medical_records ADD CONSTRAINT fk_medical_records_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Insert sample medical history form data structure for reference
-- This shows the expected JSON structure for medical history forms
INSERT IGNORE INTO medical_records (patient_id, patient_type, form_type, form_data, created_by) 
VALUES (
    1, 
    'student', 
    'medical_history', 
    JSON_OBJECT(
        'ongoing_conditions', JSON_ARRAY('asthma', 'error_refraction'),
        'ongoing_conditions_other', 'None',
        'surgery_status', 'no',
        'surgery_details', '',
        'family_conditions', JSON_ARRAY('diabetes', 'hypertension'),
        'family_conditions_other', 'None',
        'smoke_exposure', 'no',
        'immunization', JSON_ARRAY('mmr', 'dpt', 'bcg', 'hepatitis_b'),
        'covid_vaccine', JSON_ARRAY('first_dose', 'second_dose', 'booster_1'),
        'covid_positive', 'no',
        'covid_details', ''
    ),
    1
);

-- Create a view for easy querying of medical history data
CREATE OR REPLACE VIEW medical_history_view AS
SELECT 
    mr.id,
    mr.patient_id,
    mr.patient_type,
    mr.form_type,
    mr.created_at,
    mr.created_by,
    u.name as created_by_name,
    -- Extract specific fields from JSON for easier querying
    JSON_EXTRACT(mr.form_data, '$.ongoing_conditions') as ongoing_conditions,
    JSON_EXTRACT(mr.form_data, '$.ongoing_conditions_other') as ongoing_conditions_other,
    JSON_EXTRACT(mr.form_data, '$.surgery_status') as surgery_status,
    JSON_EXTRACT(mr.form_data, '$.surgery_details') as surgery_details,
    JSON_EXTRACT(mr.form_data, '$.family_conditions') as family_conditions,
    JSON_EXTRACT(mr.form_data, '$.family_conditions_other') as family_conditions_other,
    JSON_EXTRACT(mr.form_data, '$.smoke_exposure') as smoke_exposure,
    JSON_EXTRACT(mr.form_data, '$.immunization') as immunization,
    JSON_EXTRACT(mr.form_data, '$.covid_vaccine') as covid_vaccine,
    JSON_EXTRACT(mr.form_data, '$.covid_positive') as covid_positive,
    JSON_EXTRACT(mr.form_data, '$.covid_details') as covid_details
FROM medical_records mr
LEFT JOIN users u ON mr.created_by = u.id
WHERE mr.form_type = 'medical_history';

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_medical_records_form_type ON medical_records(form_type);
CREATE INDEX IF NOT EXISTS idx_medical_records_patient_type ON medical_records(patient_type);

-- Add some useful queries for reporting
-- Query to get all medical history forms for a specific patient
-- SELECT * FROM medical_history_view WHERE patient_id = ? AND patient_type = ?;

-- Query to get patients with specific ongoing conditions
-- SELECT patient_id, ongoing_conditions FROM medical_history_view 
-- WHERE JSON_CONTAINS(ongoing_conditions, '"asthma"');

-- Query to get patients with COVID-19 history
-- SELECT patient_id, covid_positive, covid_details FROM medical_history_view 
-- WHERE JSON_EXTRACT(covid_positive, '$') = 'yes';

-- Query to get immunization status
-- SELECT patient_id, immunization FROM medical_history_view 
-- WHERE JSON_CONTAINS(immunization, '"mmr"');

SELECT 'Medical History Form SQL setup completed successfully!' as status;
SELECT 'Medical records table is ready for medical history forms' as note1;
SELECT 'JSON structure supports all required fields' as note2;
SELECT 'Views and indexes created for optimal performance' as note3;
