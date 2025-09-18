-- =====================================================
-- Complete Medical Forms SQL Setup
-- =====================================================
-- This SQL sets up all medical form types and their data structures

USE care_cms;

-- =====================================================
-- MEDICAL RECORDS TABLE (if not exists)
-- =====================================================
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
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA FOR ALL FORM TYPES
-- =====================================================

-- 1. MEDICAL HISTORY FORM SAMPLE
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

-- 2. ATHLETE MEDICAL FORM SAMPLE
INSERT IGNORE INTO medical_records (patient_id, patient_type, form_type, form_data, created_by) 
VALUES (
    1, 
    'student', 
    'athlete', 
    JSON_OBJECT(
        'sport', 'Basketball',
        'position', 'Point Guard',
        'height', '175',
        'weight', '70',
        'medical_history', 'No previous injuries, regular checkups',
        'physical_exam', 'Heart rate: 65 bpm, BP: 120/80, Normal',
        'recommendations', 'Continue regular training, maintain hydration'
    ),
    1
);

-- 3. GENERAL MEDICAL FORM SAMPLE
INSERT IGNORE INTO medical_records (patient_id, patient_type, form_type, form_data, created_by) 
VALUES (
    1, 
    'student', 
    'general', 
    JSON_OBJECT(
        'chief_complaint', 'Headache and fever',
        'duration', '2 days',
        'history_present', 'Patient reports headache starting 2 days ago, fever developed yesterday',
        'past_medical', 'No significant medical history',
        'physical_exam', 'Temp: 38.5°C, HR: 85 bpm, BP: 110/70, No neck stiffness',
        'assessment_plan', 'Viral infection, rest and fluids, follow up if symptoms worsen'
    ),
    1
);

-- 4. EMERGENCY MEDICAL FORM SAMPLE
INSERT IGNORE INTO medical_records (patient_id, patient_type, form_type, form_data, created_by) 
VALUES (
    1, 
    'student', 
    'emergency', 
    JSON_OBJECT(
        'emergency_type', 'injury',
        'severity', 'moderate',
        'emergency_description', 'Student fell during PE class, injured left ankle',
        'immediate_actions', 'Applied ice pack, elevated leg, called parents',
        'heart_rate', '95',
        'blood_pressure', '115/75',
        'temperature', '36.8',
        'follow_up', 'Refer to orthopedic specialist, X-ray recommended'
    ),
    1
);

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- Medical History View
CREATE OR REPLACE VIEW medical_history_view AS
SELECT 
    mr.id,
    mr.patient_id,
    mr.patient_type,
    mr.created_at,
    mr.created_by,
    u.name as created_by_name,
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

-- Athlete Medical View
CREATE OR REPLACE VIEW athlete_medical_view AS
SELECT 
    mr.id,
    mr.patient_id,
    mr.patient_type,
    mr.created_at,
    mr.created_by,
    u.name as created_by_name,
    JSON_EXTRACT(mr.form_data, '$.sport') as sport,
    JSON_EXTRACT(mr.form_data, '$.position') as position,
    JSON_EXTRACT(mr.form_data, '$.height') as height,
    JSON_EXTRACT(mr.form_data, '$.weight') as weight,
    JSON_EXTRACT(mr.form_data, '$.medical_history') as medical_history,
    JSON_EXTRACT(mr.form_data, '$.physical_exam') as physical_exam,
    JSON_EXTRACT(mr.form_data, '$.recommendations') as recommendations
FROM medical_records mr
LEFT JOIN users u ON mr.created_by = u.id
WHERE mr.form_type = 'athlete';

-- General Medical View
CREATE OR REPLACE VIEW general_medical_view AS
SELECT 
    mr.id,
    mr.patient_id,
    mr.patient_type,
    mr.created_at,
    mr.created_by,
    u.name as created_by_name,
    JSON_EXTRACT(mr.form_data, '$.chief_complaint') as chief_complaint,
    JSON_EXTRACT(mr.form_data, '$.duration') as duration,
    JSON_EXTRACT(mr.form_data, '$.history_present') as history_present,
    JSON_EXTRACT(mr.form_data, '$.past_medical') as past_medical,
    JSON_EXTRACT(mr.form_data, '$.physical_exam') as physical_exam,
    JSON_EXTRACT(mr.form_data, '$.assessment_plan') as assessment_plan
FROM medical_records mr
LEFT JOIN users u ON mr.created_by = u.id
WHERE mr.form_type = 'general';

-- Emergency Medical View
CREATE OR REPLACE VIEW emergency_medical_view AS
SELECT 
    mr.id,
    mr.patient_id,
    mr.patient_type,
    mr.created_at,
    mr.created_by,
    u.name as created_by_name,
    JSON_EXTRACT(mr.form_data, '$.emergency_type') as emergency_type,
    JSON_EXTRACT(mr.form_data, '$.severity') as severity,
    JSON_EXTRACT(mr.form_data, '$.emergency_description') as emergency_description,
    JSON_EXTRACT(mr.form_data, '$.immediate_actions') as immediate_actions,
    JSON_EXTRACT(mr.form_data, '$.heart_rate') as heart_rate,
    JSON_EXTRACT(mr.form_data, '$.blood_pressure') as blood_pressure,
    JSON_EXTRACT(mr.form_data, '$.temperature') as temperature,
    JSON_EXTRACT(mr.form_data, '$.follow_up') as follow_up
FROM medical_records mr
LEFT JOIN users u ON mr.created_by = u.id
WHERE mr.form_type = 'emergency';

-- =====================================================
-- USEFUL QUERIES FOR REPORTING
-- =====================================================

-- Get all medical forms for a specific patient
-- SELECT * FROM medical_records WHERE patient_id = ? AND patient_type = ? ORDER BY created_at DESC;

-- Get patients with specific ongoing conditions
-- SELECT patient_id, ongoing_conditions FROM medical_history_view 
-- WHERE JSON_CONTAINS(ongoing_conditions, '"asthma"');

-- Get patients with COVID-19 history
-- SELECT patient_id, covid_positive, covid_details FROM medical_history_view 
-- WHERE JSON_EXTRACT(covid_positive, '$') = 'yes';

-- Get immunization status
-- SELECT patient_id, immunization FROM medical_history_view 
-- WHERE JSON_CONTAINS(immunization, '"mmr"');

-- Get emergency cases by severity
-- SELECT patient_id, emergency_type, severity, emergency_description FROM emergency_medical_view 
-- WHERE JSON_EXTRACT(severity, '$') = 'high' OR JSON_EXTRACT(severity, '$') = 'critical';

-- Get athlete forms by sport
-- SELECT patient_id, sport, position FROM athlete_medical_view 
-- WHERE JSON_EXTRACT(sport, '$') = 'Basketball';

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_medical_records_form_type ON medical_records(form_type);
CREATE INDEX IF NOT EXISTS idx_medical_records_patient_type ON medical_records(patient_type);
CREATE INDEX IF NOT EXISTS idx_medical_records_created_at ON medical_records(created_at);

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
SELECT 'Complete Medical Forms SQL setup completed successfully!' as status;
SELECT 'All form types: medical_history, athlete, general, emergency' as note1;
SELECT 'Views created for easy querying of each form type' as note2;
SELECT 'Sample data inserted for testing' as note3;
SELECT 'Indexes created for optimal performance' as note4;
