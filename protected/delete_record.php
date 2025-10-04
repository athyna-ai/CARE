<?php
/**
 * Example Protected Page - Record Deletion
 * 
 * Demonstrates comprehensive security integration:
 * - Authentication requirement
 * - Authorization and ownership validation
 * - CSRF protection
 * - Input validation and sanitization
 * - HTTP method enforcement
 * - Security logging
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Include security framework
require_once __DIR__ . '/../includes/security.php';

// Set page title for security logging
$page_title = 'Record Deletion';

try {
    // =============================================
    // ENFORCE SECURITY FRAMEWORK PROTECTIONS
    // =============================================
    
    // 1. Authentication Enforcement
    requireLogin('admin'); // Require admin role for this critical action
    
    // 2. HTTP Method Enforcement
    requirePOST(); // Only allow POST requests for deletions
    
    // 3. CSRF Protection
    if (!validateCSRFToken()) {
        logSecurityEvent('CSRF_VIOLATION', 'CSRF token validation failed during record deletion attempt');
        throw new SecurityException('Invalid security token. Please refresh the page and try again.');
    }
    
    // =============================================
    // INPUT VALIDATION AND SANITIZATION
    // =============================================
    
    // Validate and sanitize record ID
    $record_id = validateInput($_POST['record_id'] ?? '', 'integer', [
        'options' => ['min_range' => 1, 'max_range' => 999999]
    ]);
    
    if (!$record_id) {
        logSecurityEvent('INVALID_INPUT', 'Invalid record ID provided for deletion');
        throw new SecurityException('Invalid record ID provided.');
    }
    
    // Validate and sanitize table name (additional security check)
    $table_name = validateInput($_POST['table_name'] ?? '', 'string', [
        'max_length' => 50,
        'pattern' => '/^[a-zA-Z_][a-zA-Z0-9_]*$/'
    ]);
    
    if (!$table_name || !in_array($table_name, ['medical_records', 'patients', 'visitations', 'users'])) {
        logSecurityEvent('INVALID_INPUT', "Invalid table name provided: $table_name");
        throw new SecurityException('Invalid table specified for deletion.');
    }
    
    $confirmation_code = $_POST['confirmation'] ?? '';
    if ($confirmation_code !== 'DELETE') {
        logSecurityEvent('INVALID_INPUT', 'Deletion confirmation code invalid');
        throw new SecurityException('Invalid confirmation code. Type "DELETE" to confirm.');
    }
    
    // =============================================
    // DATABASE AUTHORIZATION AND OWNERSHIP VALIDATION
    // =============================================
    
    // Connect to database (secure method)
    require_once __DIR__ . '/../core/config.php';
    $pdo = get_pdo();
    
    // Validate record ownership and existence
    $stmt = $pdo->prepare("SELECT user_id FROM $table_name WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        logsecurityActivity('RECORD_NOT_FOUND', "Attempted to delete non-existent record ID: $record_id from table: $table_name");
        throw new SecurityException('Record not found.');
    }
    
    // Check ownership (except for admin users)
    $current_user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    if ($user_role !== 'admin' && $record['user_id'] != $current_user_id) {
        logSecurityEvent('UNAUTHORIZED_DELETE_ATTEMPT', "User $current_user_id attempted to delete record $record_id owned by user {$record['user_id']}");
        throw new SecurityException('You do not have permission to delete this record.');
    }
    
    // =============================================
    // SECURE DELETION WITH TRANSACTION
    // =============================================
    
    try {
        // Begin transaction for data integrity
        $pdo->beginTransaction();
        
        // Perform the deletion
        $stmt = $pdo->prepare("DELETE FROM $table_name WHERE id = ?");
        $result = $stmt->execute([$record_id]);
        
        if (!$result) {
            throw new PDOException('Delete operation failed');
        }
        
        // Log the successful deletion
        logActivity('RECORD_DELETED', "Record ID: $record_id from table: $table_name deleted by user: $current_user_id");
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $response = [
            'success' => true,
            'message' => 'Record deleted successfully.',
            'record_id' => $record_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        logSecurityEvent('DATABASE_ERROR', "Delete operation failed for record $record_id: " . $e->getMessage());
        throw new SecurityException('Database error occurred during deletion.');
    }
    
} catch (SecurityException $e) {
    // Security-related exceptions
    $response = [
        'success' => false,
        'error' => 'Security Error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    // General exceptions
    logSecurityEvent('UNEXPECTED_ERROR', "Unexpected error in delete_record.php: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => 'System Error',
        'message' => 'An unexpected error occurred. Please try again later.',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// =============================================
// SECURE RESPONSE OUTPUT
// =============================================

// Set appropriate headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Prevent caching of sensitive responses
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output response
echo json_encode($response, JSON_UNESCAPED_SLASHES);

exit;

/**
 * Custom Security Exception Class
 */
class SecurityException extends Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Helper function for database connection
 */
function get_pdo() {
    try {
        $config = require __DIR__ . '/../core/config.php';
        return new PDO(
            "mysql:host=localhost;dbname=care_cms;charset=utf8mb4",
            $config['db_user'] ?? 'root',
            $config['db_pass'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
    } catch (PDOException $e) {
        logSecurityEvent('DATABASE_CONNECTION_ERROR', "Failed to connect to database: " . $e->getMessage());
        throw new Exception('Database connection failed');
    }
}
?>
