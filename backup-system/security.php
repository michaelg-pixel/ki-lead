<?php
/**
 * Security Layer für Backup System
 * DoS-Schutz, Rate Limiting, IP-Blocking
 */

class BackupSecurity {
    private $maxRequestsPerMinute = 10;
    private $maxLoginAttempts = 5;
    private $blockDuration = 300; // 5 Minuten
    private $securityLogFile;
    
    public function __construct() {
        $this->securityLogFile = BACKUP_LOGS_DIR . '/security.log';
    }
    
    /**
     * Rate Limiting prüfen
     */
    public function checkRateLimit() {
        $ip = $this->getClientIP();
        $cacheFile = BACKUP_ROOT_DIR . '/.rate_limit_' . md5($ip);
        
        // Geblockte IPs prüfen
        if ($this->isBlocked($ip)) {
            $this->logSecurityEvent('BLOCKED_ACCESS', $ip);
            $this->sendHTTPError(429, 'Too Many Requests - IP temporarily blocked');
        }
        
        // Rate Limit prüfen
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $requests = $data['requests'] ?? [];
            
            // Alte Requests entfernen (älter als 1 Minute)
            $requests = array_filter($requests, function($timestamp) {
                return $timestamp > (time() - 60);
            });
            
            // Zu viele Requests?
            if (count($requests) >= $this->maxRequestsPerMinute) {
                $this->blockIP($ip);
                $this->logSecurityEvent('RATE_LIMIT_EXCEEDED', $ip);
                $this->sendHTTPError(429, 'Too Many Requests');
            }
            
            // Neuen Request hinzufügen
            $requests[] = time();
            file_put_contents($cacheFile, json_encode(['requests' => $requests]));
        } else {
            // Erste Anfrage
            file_put_contents($cacheFile, json_encode(['requests' => [time()]]));
        }
    }
    
    /**
     * Login-Versuch prüfen
     */
    public function checkLoginAttempt($username, $success) {
        $ip = $this->getClientIP();
        $cacheFile = BACKUP_ROOT_DIR . '/.login_attempts_' . md5($ip);
        
        if ($success) {
            // Erfolgreicher Login - Counter zurücksetzen
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            $this->logSecurityEvent('LOGIN_SUCCESS', $ip, $username);
            return true;
        }
        
        // Fehlgeschlagener Login
        $attempts = 0;
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $attempts = $data['attempts'] ?? 0;
        }
        
        $attempts++;
        
        if ($attempts >= $this->maxLoginAttempts) {
            $this->blockIP($ip);
            $this->logSecurityEvent('LOGIN_BRUTEFORCE', $ip, $username);
            $this->sendHTTPError(403, 'Too many failed login attempts - IP blocked');
        }
        
        file_put_contents($cacheFile, json_encode([
            'attempts' => $attempts,
            'timestamp' => time()
        ]));
        
        $this->logSecurityEvent('LOGIN_FAILED', $ip, $username);
        
        return false;
    }
    
    /**
     * IP blockieren
     */
    private function blockIP($ip) {
        $blockFile = BACKUP_ROOT_DIR . '/.blocked_ips';
        $blocked = [];
        
        if (file_exists($blockFile)) {
            $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
        }
        
        $blocked[$ip] = time() + $this->blockDuration;
        file_put_contents($blockFile, json_encode($blocked));
        
        $this->logSecurityEvent('IP_BLOCKED', $ip);
    }
    
    /**
     * Prüfen ob IP geblockt ist
     */
    private function isBlocked($ip) {
        $blockFile = BACKUP_ROOT_DIR . '/.blocked_ips';
        
        if (!file_exists($blockFile)) {
            return false;
        }
        
        $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
        
        // Abgelaufene Blocks entfernen
        $currentTime = time();
        $blocked = array_filter($blocked, function($unblockTime) use ($currentTime) {
            return $unblockTime > $currentTime;
        });
        
        file_put_contents($blockFile, json_encode($blocked));
        
        return isset($blocked[$ip]);
    }
    
    /**
     * Client-IP ermitteln
     */
    private function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Proxy-Header prüfen (nur wenn von vertrauenswürdigem Proxy)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Security-Event loggen
     */
    private function logSecurityEvent($event, $ip, $details = '') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $event | IP: $ip";
        
        if ($details) {
            $logEntry .= " | Details: $details";
        }
        
        $logEntry .= " | UA: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
        
        file_put_contents($this->securityLogFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * HTTP-Fehler senden
     */
    private function sendHTTPError($code, $message) {
        http_response_code($code);
        die(json_encode([
            'error' => $message,
            'code' => $code
        ]));
    }
    
    /**
     * CSRF-Token generieren
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRF-Token validieren
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Input sanitizen
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Pfad validieren (Path Traversal verhindern)
     */
    public static function validatePath($path, $allowedBase) {
        $realPath = realpath($path);
        $realBase = realpath($allowedBase);
        
        if ($realPath === false || $realBase === false) {
            return false;
        }
        
        return strpos($realPath, $realBase) === 0;
    }
}
