<?php
/**
 * Referral System Core Helper
 * DSGVO-konforme Tracking- und Anti-Fraud-Funktionen
 */

class ReferralHelper {
    private $db;
    private $salt;
    
    // Anti-Fraud Konfiguration
    private const CLICK_RATE_LIMIT = 24; // Stunden zwischen Klicks derselben IP
    private const CONVERSION_RATE_LIMIT = 24; // Stunden zwischen Conversions derselben IP
    private const SUSPICIOUS_CONVERSION_TIME = 5; // Sekunden - zu schnelle Conversion = verdächtig
    private const MAX_CLICKS_PER_IP_PER_DAY = 10; // Max. Klicks pro IP pro Tag
    
    public function __construct($db) {
        $this->db = $db;
        // Salt für IP-Hashing aus Umgebungsvariable oder Config
        $this->salt = getenv('REFERRAL_SALT') ?: 'Ki-L3ad-R3ferral-2025-Secure';
    }
    
    /**
     * Hash IP-Adresse DSGVO-konform
     */
    public function hashIP($ip) {
        return hash('sha256', $ip . $this->salt);
    }
    
    /**
     * Hash E-Mail für Deduplizierung
     */
    public function hashEmail($email) {
        return hash('sha256', strtolower(trim($email)) . $this->salt);
    }
    
    /**
     * Erstelle Fingerprint aus IP + UserAgent
     */
    public function createFingerprint($ip, $userAgent) {
        return hash('sha256', $ip . $userAgent . $this->salt);
    }
    
    /**
     * Validiere Referral-Code
     */
    public function validateRefCode($refCode) {
        if (empty($refCode) || strlen($refCode) > 50) {
            return false;
        }
        
        // Prüfe ob Code existiert
        $stmt = $this->db->prepare("
            SELECT id, referral_enabled 
            FROM customers 
            WHERE referral_code = ? AND status = 'active'
        ");
        $stmt->execute([$refCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $customer && $customer['referral_enabled'];
    }
    
    /**
     * Hole Customer-ID von Referral-Code
     */
    public function getCustomerIdFromRefCode($refCode) {
        $stmt = $this->db->prepare("
            SELECT id FROM customers 
            WHERE referral_code = ? AND status = 'active'
        ");
        $stmt->execute([$refCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['id'] : null;
    }
    
    /**
     * Prüfe ob IP bereits geklickt hat (Rate Limiting)
     */
    public function hasRecentClick($customerId, $refCode, $ipHash) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM referral_clicks
            WHERE customer_id = ? 
                AND ref_code = ?
                AND ip_address_hash = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$customerId, $refCode, $ipHash, self::CLICK_RATE_LIMIT]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Prüfe IP-Rate-Limit für heute
     */
    public function hasExceededDailyLimit($customerId, $ipHash) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM referral_clicks
            WHERE customer_id = ?
                AND ip_address_hash = ?
                AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$customerId, $ipHash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] >= self::MAX_CLICKS_PER_IP_PER_DAY;
    }
    
    /**
     * Speichere Referral-Klick
     */
    public function trackClick($customerId, $refCode, $ipHash, $userAgent, $fingerprint, $referer = null) {
        try {
            // Prüfe Rate-Limit
            if ($this->hasRecentClick($customerId, $refCode, $ipHash)) {
                return ['success' => false, 'error' => 'rate_limit', 'message' => 'Klick bereits registriert'];
            }
            
            // Prüfe Tages-Limit
            if ($this->hasExceededDailyLimit($customerId, $ipHash)) {
                $this->logFraud($customerId, $refCode, 'rate_limit', $ipHash, $fingerprint, $userAgent);
                return ['success' => false, 'error' => 'daily_limit', 'message' => 'Tageslimit erreicht'];
            }
            
            // Session-ID generieren
            $sessionId = hash('sha256', $ipHash . $fingerprint . time());
            
            $stmt = $this->db->prepare("
                INSERT INTO referral_clicks 
                (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, session_id, referer)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customerId,
                $refCode,
                $ipHash,
                $userAgent,
                $fingerprint,
                $sessionId,
                $referer
            ]);
            
            // Update Stats
            $this->updateStats($customerId);
            
            return [
                'success' => true,
                'click_id' => $this->db->lastInsertId(),
                'session_id' => $sessionId
            ];
            
        } catch (Exception $e) {
            error_log("Referral Click Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'database', 'message' => 'Fehler beim Speichern'];
        }
    }
    
    /**
     * Prüfe ob Conversion bereits existiert
     */
    public function hasRecentConversion($customerId, $refCode, $ipHash) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM referral_conversions
            WHERE customer_id = ? 
                AND ref_code = ?
                AND ip_address_hash = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$customerId, $refCode, $ipHash, self::CONVERSION_RATE_LIMIT]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Berechne Zeit zwischen Klick und Conversion
     */
    private function getTimeToConvert($customerId, $refCode, $fingerprint) {
        $stmt = $this->db->prepare("
            SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as time_diff
            FROM referral_clicks
            WHERE customer_id = ? 
                AND ref_code = ?
                AND fingerprint = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$customerId, $refCode, $fingerprint]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['time_diff'] : null;
    }
    
    /**
     * Speichere Conversion
     */
    public function trackConversion($customerId, $refCode, $ipHash, $userAgent, $fingerprint, $source = 'thankyou') {
        try {
            // Prüfe Duplikat
            if ($this->hasRecentConversion($customerId, $refCode, $ipHash)) {
                return ['success' => false, 'error' => 'duplicate', 'message' => 'Conversion bereits registriert'];
            }
            
            // Berechne Zeit bis Conversion
            $timeToConvert = $this->getTimeToConvert($customerId, $refCode, $fingerprint);
            
            // Fraud Detection: Zu schnelle Conversion?
            $suspicious = false;
            if ($timeToConvert !== null && $timeToConvert < self::SUSPICIOUS_CONVERSION_TIME) {
                $suspicious = true;
                $this->logFraud($customerId, $refCode, 'fast_conversion', $ipHash, $fingerprint, $userAgent, [
                    'time_to_convert' => $timeToConvert
                ]);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO referral_conversions 
                (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, source, suspicious, time_to_convert)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customerId,
                $refCode,
                $ipHash,
                $userAgent,
                $fingerprint,
                $source,
                $suspicious ? 1 : 0,
                $timeToConvert
            ]);
            
            // Update Stats
            $this->updateStats($customerId);
            
            return [
                'success' => true,
                'conversion_id' => $this->db->lastInsertId(),
                'suspicious' => $suspicious,
                'time_to_convert' => $timeToConvert
            ];
            
        } catch (Exception $e) {
            error_log("Referral Conversion Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'database', 'message' => 'Fehler beim Speichern'];
        }
    }
    
    /**
     * Registriere Lead (Teilnehmer am Empfehlungsprogramm)
     */
    public function registerLead($customerId, $refCode, $email, $ipHash, $gdprConsent = true) {
        try {
            $emailHash = $this->hashEmail($email);
            
            // Prüfe ob E-Mail bereits existiert
            $stmt = $this->db->prepare("
                SELECT id FROM referral_leads 
                WHERE customer_id = ? AND email_hash = ?
            ");
            $stmt->execute([$customerId, $emailHash]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'duplicate_email', 'message' => 'E-Mail bereits registriert'];
            }
            
            // Generiere Confirmation Token
            $confirmationToken = bin2hex(random_bytes(32));
            
            $stmt = $this->db->prepare("
                INSERT INTO referral_leads 
                (customer_id, ref_code, email, email_hash, ip_address_hash, confirmation_token, gdpr_consent, gdpr_consent_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $customerId,
                $refCode,
                $email,
                $emailHash,
                $ipHash,
                $confirmationToken,
                $gdprConsent ? 1 : 0
            ]);
            
            $leadId = $this->db->lastInsertId();
            
            // Update Stats
            $this->updateStats($customerId);
            
            return [
                'success' => true,
                'lead_id' => $leadId,
                'confirmation_token' => $confirmationToken,
                'email' => $email
            ];
            
        } catch (Exception $e) {
            error_log("Referral Lead Registration Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'database', 'message' => 'Fehler beim Speichern'];
        }
    }
    
    /**
     * Bestätige E-Mail (Double-Opt-In)
     */
    public function confirmLead($token) {
        try {
            $stmt = $this->db->prepare("
                UPDATE referral_leads 
                SET confirmed = 1, confirmed_at = NOW()
                WHERE confirmation_token = ? AND confirmed = 0
            ");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                // Hole Customer-ID für Stats-Update
                $stmt = $this->db->prepare("
                    SELECT customer_id FROM referral_leads WHERE confirmation_token = ?
                ");
                $stmt->execute([$token]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $this->updateStats($result['customer_id']);
                }
                
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'invalid_token'];
            
        } catch (Exception $e) {
            error_log("Referral Confirmation Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'database'];
        }
    }
    
    /**
     * Update aggregierte Statistiken
     */
    private function updateStats($customerId) {
        try {
            // Berechne Stats
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT rc.id) as total_clicks,
                    COUNT(DISTINCT rc.fingerprint) as unique_clicks,
                    COUNT(DISTINCT conv.id) as total_conversions,
                    COUNT(DISTINCT CASE WHEN conv.suspicious = 1 THEN conv.id END) as suspicious_conversions,
                    MAX(rc.created_at) as last_click,
                    MAX(conv.created_at) as last_conversion
                FROM customers c
                LEFT JOIN referral_clicks rc ON c.id = rc.customer_id
                LEFT JOIN referral_conversions conv ON c.id = conv.customer_id
                WHERE c.id = ?
            ");
            $stmt->execute([$customerId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Lead-Stats
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_leads,
                    COUNT(CASE WHEN confirmed = 1 THEN 1 END) as confirmed_leads
                FROM referral_leads
                WHERE customer_id = ?
            ");
            $stmt->execute([$customerId]);
            $leadStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Conversion Rate berechnen
            $conversionRate = $stats['unique_clicks'] > 0 
                ? round(($stats['total_conversions'] / $stats['unique_clicks']) * 100, 2)
                : 0;
            
            // Update oder Insert
            $stmt = $this->db->prepare("
                INSERT INTO referral_stats 
                (customer_id, total_clicks, unique_clicks, total_conversions, suspicious_conversions, 
                 total_leads, confirmed_leads, conversion_rate, last_click_at, last_conversion_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_clicks = VALUES(total_clicks),
                    unique_clicks = VALUES(unique_clicks),
                    total_conversions = VALUES(total_conversions),
                    suspicious_conversions = VALUES(suspicious_conversions),
                    total_leads = VALUES(total_leads),
                    confirmed_leads = VALUES(confirmed_leads),
                    conversion_rate = VALUES(conversion_rate),
                    last_click_at = VALUES(last_click_at),
                    last_conversion_at = VALUES(last_conversion_at)
            ");
            
            $stmt->execute([
                $customerId,
                $stats['total_clicks'] ?: 0,
                $stats['unique_clicks'] ?: 0,
                $stats['total_conversions'] ?: 0,
                $stats['suspicious_conversions'] ?: 0,
                $leadStats['total_leads'] ?: 0,
                $leadStats['confirmed_leads'] ?: 0,
                $conversionRate,
                $stats['last_click'],
                $stats['last_conversion']
            ]);
            
        } catch (Exception $e) {
            error_log("Referral Stats Update Error: " . $e->getMessage());
        }
    }
    
    /**
     * Logge Betrugsversuch
     */
    private function logFraud($customerId, $refCode, $fraudType, $ipHash, $fingerprint, $userAgent, $additionalData = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO referral_fraud_log 
                (customer_id, ref_code, fraud_type, ip_address_hash, fingerprint, user_agent, additional_data)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customerId,
                $refCode,
                $fraudType,
                $ipHash,
                $fingerprint,
                $userAgent,
                $additionalData ? json_encode($additionalData) : null
            ]);
            
        } catch (Exception $e) {
            error_log("Referral Fraud Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Hole Statistiken für Customer
     */
    public function getStats($customerId) {
        $stmt = $this->db->prepare("
            SELECT * FROM referral_stats WHERE customer_id = ?
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Hole alle Statistiken für Admin-Übersicht
     */
    public function getAllStats() {
        $stmt = $this->db->query("
            SELECT 
                c.id,
                c.email,
                c.company_name,
                c.referral_code,
                c.referral_enabled,
                rs.total_clicks,
                rs.unique_clicks,
                rs.total_conversions,
                rs.suspicious_conversions,
                rs.total_leads,
                rs.confirmed_leads,
                rs.conversion_rate,
                rs.last_click_at,
                rs.last_conversion_at,
                rs.updated_at
            FROM customers c
            LEFT JOIN referral_stats rs ON c.id = rs.customer_id
            WHERE c.referral_enabled = 1
            ORDER BY rs.total_conversions DESC, rs.total_clicks DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
