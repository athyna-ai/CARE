<?php
/**
 * Advanced Rate Limiting System
 * Enhanced protection against brute force and DoS attacks
 */

class AdvancedRateLimiter {
    private static $rateLimitFile = __DIR__ . '/logs/rate_limits.json';
    private static $maxAttempts = [
        'login' => 5,           // Login attempts per 15 minutes
        'api' => 100,           // API calls per hour
        'general' => 200,       // General requests per hour
        'admin' => 50           // Admin actions per hour
    ];
    
    public static function checkRateLimit($action = 'general', $ip = null) {
        $ip = $ip ?: self::getClientIP();
        $currentTime = time();
        
        // Load existing rate limits
        $rateLimits = self::loadRateLimits();
        
        // Clean old entries (older than 1 hour)
        $rateLimits = self::cleanOldEntries($rateLimits, $currentTime);
        
        // Initialize IP entry if not exists
        if (!isset($rateLimits[$ip])) {
            $rateLimits[$ip] = [];
        }
        
        // Initialize action entry if not exists
        if (!isset($rateLimits[$ip][$action])) {
            $rateLimits[$ip][$action] = [
                'attempts' => 0,
                'first_attempt' => $currentTime,
                'last_attempt' => $currentTime,
                'blocked_until' => 0
            ];
        }
        
        $entry = &$rateLimits[$ip][$action];
        
        // Check if currently blocked
        if ($currentTime < $entry['blocked_until']) {
            $blockTimeRemaining = $entry['blocked_until'] - $currentTime;
            return [
                'allowed' => false,
                'reason' => 'rate_limited',
                'retry_after' => $blockTimeRemaining,
                'message' => "Too many {$action} attempts. Try again in " . ceil($blockTimeRemaining / 60) . " minutes."
            ];
        }
        
        // Reset counter if outside time window
        $timeWindow = self::getTimeWindow($action);
        if (($currentTime - $entry['first_attempt']) > $timeWindow) {
            $entry['attempts'] = 0;
            $entry['first_attempt'] = $currentTime;
        }
        
        // Increment attempt counter
        $entry['attempts']++;
        $entry['last_attempt'] = $currentTime;
        
        // Check if limit exceeded
        $maxAttempts = self::$maxAttempts[$action] ?? self::$maxAttempts['general'];
        if ($entry['attempts'] > $maxAttempts) {
            // Block for increasing duration based on attempts
            $blockDuration = min(3600, pow(2, floor($entry['attempts'] / $maxAttempts)) * 300); // 5min, 10min, 20min, 40min, 1hr max
            $entry['blocked_until'] = $currentTime + $blockDuration;
            
            // Log the rate limiting
            self::logRateLimitExceeded($ip, $action, $entry['attempts']);
            
            // Save updated rate limits
            self::saveRateLimits($rateLimits);
            
            return [
                'allowed' => false,
                'reason' => 'rate_limited',
                'retry_after' => $blockDuration,
                'message' => "Rate limit exceeded for {$action}. Blocked for " . ceil($blockDuration / 60) . " minutes."
            ];
        }
        
        // Save updated rate limits
        self::saveRateLimits($rateLimits);
        
        return [
            'allowed' => true,
            'attempts' => $entry['attempts'],
            'max_attempts' => $maxAttempts,
            'reset_after' => $timeWindow - ($currentTime - $entry['first_attempt'])
        ];
    }
    
    private static function getTimeWindow($action) {
        switch ($action) {
            case 'login': return 900;  // 15 minutes
            case 'api': return 3600;   // 1 hour
            case 'admin': return 3600; // 1 hour
            default: return 3600;      // 1 hour
        }
    }
    
    private static function loadRateLimits() {
        if (!file_exists(self::$rateLimitFile)) {
            return [];
        }
        
        $content = file_get_contents(self::$rateLimitFile);
        return json_decode($content, true) ?: [];
    }
    
    private static function saveRateLimits($rateLimits) {
        $dir = dirname(self::$rateLimitFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents(self::$rateLimitFile, json_encode($rateLimits), LOCK_EX);
    }
    
    private static function cleanOldEntries($rateLimits, $currentTime) {
        foreach ($rateLimits as $ip => $actions) {
            foreach ($actions as $action => $entry) {
                $timeWindow = self::getTimeWindow($action);
                if (($currentTime - $entry['first_attempt']) > $timeWindow && $currentTime > $entry['blocked_until']) {
                    unset($rateLimits[$ip][$action]);
                }
            }
            if (empty($rateLimits[$ip])) {
                unset($rateLimits[$ip]);
            }
        }
        return $rateLimits;
    }
    
    private static function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    private static function logRateLimitExceeded($ip, $action, $attempts) {
        try {
            $pdo = get_pdo();
            log_activity($pdo, null, 'rate_limit_exceeded', "Rate limit exceeded for {$action}: {$attempts} attempts from IP {$ip}", 'security/rate_limit', false);
        } catch (Exception $e) {
            error_log("Rate limiting log failed: " . $e->getMessage());
        }
    }
    
    public static function getRateLimitStatus($ip = null) {
        $ip = $ip ?: self::getClientIP();
        $rateLimits = self::loadRateLimits();
        
        if (!isset($rateLimits[$ip])) {
            return ['status' => 'clean', 'actions' => []];
        }
        
        $status = ['status' => 'clean', 'actions' => []];
        $currentTime = time();
        
        foreach ($rateLimits[$ip] as $action => $entry) {
            if ($currentTime < $entry['blocked_until']) {
                $status['status'] = 'blocked';
                $status['actions'][$action] = [
                    'blocked' => true,
                    'retry_after' => $entry['blocked_until'] - $currentTime
                ];
            } else {
                $status['actions'][$action] = [
                    'blocked' => false,
                    'attempts' => $entry['attempts'],
                    'max_attempts' => self::$maxAttempts[$action] ?? self::$maxAttempts['general']
                ];
            }
        }
        
        return $status;
    }
}

// Usage example:
// $rateLimit = AdvancedRateLimiter::checkRateLimit('login');
// if (!$rateLimit['allowed']) {
//     http_response_code(429);
//     die($rateLimit['message']);
// }
?>
