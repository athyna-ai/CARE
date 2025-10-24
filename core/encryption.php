<?php
declare(strict_types=1);

/**
 * Patient ID Encryption System
 * Encrypts patient IDs for URL safety and privacy
 */

class PatientIdEncryption {
    private static $key;
    private static $cipher = 'AES-256-CBC';
    
    public static function init(): void {
        // Use a secure key from environment or generate one
        self::$key = $_ENV['PATIENT_ENCRYPTION_KEY'] ?? 'care_cms_patient_encryption_key_2024_secure';
        
        // Ensure key is exactly 32 bytes for AES-256
        self::$key = hash('sha256', self::$key, true);
    }
    
    /**
     * Encrypt patient ID for URL use
     */
    public static function encrypt(int $patientId, string $type = 'student'): string {
        self::init();
        
        $data = json_encode([
            'id' => $patientId,
            'type' => $type,
            'timestamp' => time(),
            'expires' => time() + 3600 // 1 hour expiry
        ]);
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, self::$cipher, self::$key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt patient ID from URL
     */
    public static function decrypt(string $encryptedData): ?array {
        self::init();
        
        try {
            $data = base64_decode($encryptedData);
            if ($data === false) return null;
            
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            $decrypted = openssl_decrypt($encrypted, self::$cipher, self::$key, 0, $iv);
            if ($decrypted === false) return null;
            
            $result = json_decode($decrypted, true);
            if (!$result) return null;
            
            // Check expiry
            if (isset($result['expires']) && $result['expires'] < time()) {
                return null; // Expired
            }
            
            return $result;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Generate secure token for patient access
     */
    public static function generateToken(int $patientId, string $type = 'student'): string {
        return self::encrypt($patientId, $type);
    }
    
    /**
     * Validate and extract patient data from token
     */
    public static function validateToken(string $token): ?array {
        return self::decrypt($token);
    }
}

// Initialize encryption system
PatientIdEncryption::init();
?>
