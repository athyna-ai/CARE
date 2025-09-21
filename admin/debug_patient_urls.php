<?php
// Debug page to help find correct patient URLs
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h1>Patient URL Debug</h1>";

try {
    $pdo = get_pdo();
    
    // Get some sample students
    $stmt = $pdo->prepare('SELECT id, name FROM students LIMIT 3');
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "<h2>Sample Student URLs:</h2>";
    echo "<ul>";
    foreach ($students as $student) {
        $url = "http://localhost/Care/patient_view.php?id={$student['id']}&type=student";
        echo "<li><a href='{$url}' target='_blank'>Student: {$student['name']} - {$url}</a></li>";
    }
    echo "</ul>";
    
    // Get some sample faculty
    $stmt = $pdo->prepare('SELECT id, name FROM faculty LIMIT 3');
    $stmt->execute();
    $faculty = $stmt->fetchAll();
    
    echo "<h2>Sample Faculty URLs:</h2>";
    echo "<ul>";
    foreach ($faculty as $person) {
        $url = "http://localhost/Care/patient_view.php?id={$person['id']}&type=faculty";
        echo "<li><a href='{$url}' target='_blank'>Faculty: {$person['name']} - {$url}</a></li>";
    }
    echo "</ul>";
    
    // Test the patient view directly
    if (isset($_GET['test_id']) && isset($_GET['test_type'])) {
        $testId = (int)$_GET['test_id'];
        $testType = $_GET['test_type'];
        
        echo "<h2>Testing Patient View:</h2>";
        echo "<p>Testing with ID: {$testId}, Type: {$testType}</p>";
        
        if ($testType === 'student') {
            $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
        } else {
            $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
        }
        $stmt->execute([$testId]);
        $patient = $stmt->fetch();
        
        if ($patient) {
            echo "<p style='color: green;'>✅ Patient found: " . htmlspecialchars($patient['name']) . "</p>";
            $url = "patient_view.php?id={$testId}&type={$testType}";
            echo "<p><a href='{$url}' target='_blank'>Open Patient View: {$url}</a></p>";
            echo "<p><small>Note: The back button will take you to the " . ucfirst($testType) . " listing page</small></p>";
        } else {
            echo "<p style='color: red;'>❌ Patient not found</p>";
        }
    }
    
    echo "<h2>Test Specific Patient:</h2>";
    echo "<form method='GET'>";
    echo "<p>Patient ID: <input type='number' name='test_id' value='1' min='1'></p>";
    echo "<p>Type: <select name='test_type'><option value='student'>Student</option><option value='faculty'>Faculty</option></select></p>";
    echo "<p><button type='submit'>Test Patient View</button></p>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
