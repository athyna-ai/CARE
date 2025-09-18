<?php
// Test patient view functionality
session_start();
require_once 'config.php';
require_once 'helpers.php';

echo "<h1>Patient View Test</h1>";

// Test with a sample patient ID
$patientId = 1;
$patientType = 'student';

try {
    $pdo = get_pdo();
    
    // Check if patient exists
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    
    if ($patient) {
        echo "<p style='color: green;'>✅ Patient found: " . htmlspecialchars($patient['name']) . "</p>";
        
        // Check medical history
        $medicalStmt = $pdo->prepare('SELECT * FROM medical_records WHERE patient_id = ? AND patient_type = ? ORDER BY created_at DESC');
        $medicalStmt->execute([$patientId, $patientType]);
        $medicalHistory = $medicalStmt->fetchAll();
        
        echo "<p>Medical History Records: " . count($medicalHistory) . "</p>";
        
        if (count($medicalHistory) > 0) {
            echo "<h3>Medical History Records:</h3>";
            echo "<ul>";
            foreach ($medicalHistory as $record) {
                echo "<li>ID: " . $record['id'] . ", Type: " . $record['form_type'] . ", Date: " . $record['created_at'] . "</li>";
            }
            echo "</ul>";
        }
        
        // Test URL
        $testUrl = "patient_view.php?id={$patientId}&type={$patientType}";
        echo "<p><a href='{$testUrl}' target='_blank'>Test Patient View: {$testUrl}</a></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Patient not found with ID: {$patientId}</p>";
        
        // Show available patients
        $stmt = $pdo->prepare('SELECT id, name FROM students LIMIT 5');
        $stmt->execute();
        $students = $stmt->fetchAll();
        
        echo "<h3>Available Students:</h3>";
        echo "<ul>";
        foreach ($students as $student) {
            $url = "patient_view.php?id={$student['id']}&type=student";
            echo "<li><a href='{$url}' target='_blank'>ID: {$student['id']} - {$student['name']}</a></li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
