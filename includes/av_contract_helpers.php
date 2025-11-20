<?php
/**
 * AV-Vertrags-Zustimmungen Helper
 * 
 * Funktionen zur DSGVO-konformen Speicherung und Abfrage von AV-Vertrags-Zustimmungen
 */

/**
 * Speichert die AV-Vertrags-Zustimmung eines Benutzers
 * 
 * @param PDO $pdo Datenbankverbindung
 * @param int $user_id User ID
 * @param string $acceptance_type Art der Zustimmung (registration, update, renewal)
 * @param string $contract_version Version des AV-Vertrags (Standard: '1.0')
 * @return bool Erfolg der Speicherung
 */
function saveAvContractAcceptance($pdo, $user_id, $acceptance_type = 'registration', $contract_version = '1.0') {
    try {
        // IP-Adresse sicher ermitteln
        $ip_address = getClientIpAddress();
        
        // User-Agent sicher ermitteln
        $user_agent = getUserAgent();
        
        // Vorbereite Statement
        $stmt = $pdo->prepare("
            INSERT INTO av_contract_acceptances 
            (user_id, ip_address, user_agent, av_contract_version, acceptance_type, accepted_at, created_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $user_id,
            $ip_address,
            $user_agent,
            $contract_version,
            $acceptance_type
        ]);
        
        if ($result) {
            error_log("AV-Vertrag Zustimmung gespeichert - User: {$user_id}, IP: {$ip_address}, Type: {$acceptance_type}");
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Fehler beim Speichern der AV-Vertrags-Zustimmung: " . $e->getMessage());
        return false;
    }
}

/**
 * Holt die letzte AV-Vertrags-Zustimmung eines Benutzers
 * 
 * @param PDO $pdo Datenbankverbindung
 * @param int $user_id User ID
 * @return array|null Zustimmungsdaten oder null
 */
function getLatestAvContractAcceptance($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                user_id,
                accepted_at,
                ip_address,
                user_agent,
                av_contract_version,
                acceptance_type,
                created_at
            FROM av_contract_acceptances
            WHERE user_id = ?
            ORDER BY accepted_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Fehler beim Abrufen der AV-Vertrags-Zustimmung: " . $e->getMessage());
        return null;
    }
}

/**
 * Holt alle AV-Vertrags-Zustimmungen eines Benutzers (Historie)
 * 
 * @param PDO $pdo Datenbankverbindung
 * @param int $user_id User ID
 * @return array Liste aller Zustimmungen
 */
function getAllAvContractAcceptances($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                user_id,
                accepted_at,
                ip_address,
                user_agent,
                av_contract_version,
                acceptance_type,
                created_at
            FROM av_contract_acceptances
            WHERE user_id = ?
            ORDER BY accepted_at DESC
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Fehler beim Abrufen der AV-Vertrags-Zustimmungen: " . $e->getMessage());
        return [];
    }
}

/**
 * Prüft, ob ein Benutzer dem AV-Vertrag zugestimmt hat
 * 
 * @param PDO $pdo Datenbankverbindung
 * @param int $user_id User ID
 * @return bool Hat Benutzer zugestimmt?
 */
function hasAvContractAcceptance($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM av_contract_acceptances 
            WHERE user_id = ?
        ");
        
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Fehler beim Prüfen der AV-Vertrags-Zustimmung: " . $e->getMessage());
        return false;
    }
}

/**
 * Ermittelt die IP-Adresse des Clients sicher
 * Berücksichtigt Proxy-Server und CloudFlare
 * 
 * @return string IP-Adresse
 */
function getClientIpAddress() {
    // Prüfe verschiedene Header in der Reihenfolge ihrer Zuverlässigkeit
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP', // CloudFlare
        'HTTP_X_REAL_IP',        // Nginx proxy
        'HTTP_X_FORWARDED_FOR',  // Standard Proxy
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'            // Direkte Verbindung
    ];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            
            // Bei X-Forwarded-For kann es mehrere IPs geben (kommasepariert)
            if (strpos($ip, ',') !== false) {
                $ip_list = explode(',', $ip);
                $ip = trim($ip_list[0]); // Erste IP ist die echte Client-IP
            }
            
            // Validiere IP-Adresse
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback auf REMOTE_ADDR (auch wenn private IP)
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Ermittelt den User-Agent des Clients
 * 
 * @return string User-Agent String
 */
function getUserAgent() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Begrenze Länge auf 1000 Zeichen zur Sicherheit
    if (strlen($user_agent) > 1000) {
        $user_agent = substr($user_agent, 0, 1000);
    }
    
    return $user_agent;
}

/**
 * Formatiert die AV-Zustimmungsdaten für die Anzeige
 * 
 * @param array $acceptance Zustimmungsdaten
 * @return string Formatierter HTML-String
 */
function formatAvAcceptanceDisplay($acceptance) {
    if (!$acceptance) {
        return '<div class="alert alert-info">Keine AV-Vertrags-Zustimmung vorhanden.</div>';
    }
    
    $accepted_at = date('d.m.Y H:i:s', strtotime($acceptance['accepted_at']));
    $type_labels = [
        'registration' => 'Bei Registrierung',
        'update' => 'Bei Aktualisierung',
        'renewal' => 'Bei Erneuerung'
    ];
    
    $type = $type_labels[$acceptance['acceptance_type']] ?? $acceptance['acceptance_type'];
    
    return "
        <div style='background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;'>
            <h4 style='margin: 0 0 12px 0; color: #1f2937;'>AV-Vertrags-Zustimmung</h4>
            <table style='width: 100%; font-size: 14px;'>
                <tr>
                    <td style='padding: 4px 0; color: #6b7280;'><strong>Zeitpunkt:</strong></td>
                    <td style='padding: 4px 0; color: #1f2937;'>{$accepted_at}</td>
                </tr>
                <tr>
                    <td style='padding: 4px 0; color: #6b7280;'><strong>Art:</strong></td>
                    <td style='padding: 4px 0; color: #1f2937;'>{$type}</td>
                </tr>
                <tr>
                    <td style='padding: 4px 0; color: #6b7280;'><strong>Version:</strong></td>
                    <td style='padding: 4px 0; color: #1f2937;'>{$acceptance['av_contract_version']}</td>
                </tr>
                <tr>
                    <td style='padding: 4px 0; color: #6b7280;'><strong>IP-Adresse:</strong></td>
                    <td style='padding: 4px 0; color: #1f2937; font-family: monospace;'>{$acceptance['ip_address']}</td>
                </tr>
            </table>
        </div>
    ";
}
