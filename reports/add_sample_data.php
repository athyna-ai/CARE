<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Add sample visitation data for testing charts
try {
    // Check if we already have sample data
    $existingData = $pdo->query("SELECT COUNT(*) FROM visitation_logs")->fetchColumn();
    
    if ($existingData == 0) {
        echo "<h2>Adding Sample Visitation Data...</h2>";
        
        // Get a sample student and faculty ID
        $studentId = $pdo->query("SELECT id FROM students LIMIT 1")->fetchColumn();
        $facultyId = $pdo->query("SELECT id FROM faculty LIMIT 1")->fetchColumn();
        $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
        
        if (!$studentId || !$facultyId || !$userId) {
            echo "<p style='color: red;'>Error: Need at least one student, faculty member, and user to create sample data.</p>";
            exit;
        }
        
        // Generate sample data for the last 30 days
        $sampleData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $visits = rand(1, 10); // Random number of visits per day
            
            for ($j = 0; $j < $visits; $j++) {
                $patientType = rand(0, 1) ? 'student' : 'faculty';
                $patientId = $patientType === 'student' ? $studentId : $facultyId;
                $hour = rand(8, 16); // School hours
                $minute = rand(0, 59);
                
                $sampleData[] = [
                    'patient_id' => $patientId,
                    'patient_type' => $patientType,
                    'reason' => ['Headache', 'Fever', 'Cough', 'Injury', 'Medication', 'Check-up'][rand(0, 5)],
                    'visit_date' => "$date $hour:$minute:00",
                    'symptoms' => 'Sample symptoms for testing',
                    'temperature' => rand(360, 390) / 10, // 36.0 to 39.0
                    'medication_given' => rand(0, 1),
                    'medication_name' => rand(0, 1) ? 'Paracetamol' : null,
                    'injury' => rand(0, 1),
                    'created_by' => $userId
                ];
            }
        }
        
        // Insert sample data
        $insertQuery = $pdo->prepare("
            INSERT INTO visitation_logs 
            (patient_id, patient_type, reason, visit_date, symptoms, temperature, medication_given, medication_name, injury, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inserted = 0;
        foreach ($sampleData as $data) {
            $insertQuery->execute([
                $data['patient_id'],
                $data['patient_type'],
                $data['reason'],
                $data['visit_date'],
                $data['symptoms'],
                $data['temperature'],
                $data['medication_given'],
                $data['medication_name'],
                $data['injury'],
                $data['created_by']
            ]);
            $inserted++;
        }
        
        echo "<p style='color: green;'>Successfully added $inserted sample visitation records!</p>";
        echo "<p><a href='analytics.php'>View Analytics Dashboard</a></p>";
        
    } else {
        echo "<h2>Sample Data Already Exists</h2>";
        echo "<p>There are already $existingData records in the visitation_logs table.</p>";
        echo "<p><a href='analytics.php'>View Analytics Dashboard</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
