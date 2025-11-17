<?php
/**
 * Reward Auto-Delivery Helper Functions
 * 
 * Diese Datei enthält die aktualisierten Funktionen für:
 * 1. Automatische Belohnungsauslieferung
 * 2. Integration mit Customer's Autoresponder
 * 3. Fallback auf normale Email
 */

/**
 * Sendet Email-Benachrichtigung mit Belohnungsdetails
 * 
 * ERWEITERT: Nutzt Customer's Autoresponder-API wenn konfiguriert
 * 
 * @param array $lead Lead-Daten inkl. company_name
 * @param array $reward Belohnungsdaten
 * @param PDO|null $pdo Optional: Datenbankverbindung (für API-Daten)
 * @return bool Erfolg der Email-Zusendung
 */
function sendRewardDeliveryEmail($lead, $reward, $pdo = null) {
    // Prüfen ob Customer Autoresponder konfiguriert hat
    $autoresponder_config = null;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    autoresponder_webhook_url,
                    autoresponder_api_key,
                    autoresponder_provider
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$lead['user_id']]);
            $autoresponder_config = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Fehler beim Laden der Autoresponder-Config: " . $e->getMessage());
        }
    }
    
    // Wenn Autoresponder konfiguriert: nutze API
    if ($autoresponder_config && !empty($autoresponder_config['autoresponder_webhook_url'])) {
        return sendViaAutoresponder($lead, $reward, $autoresponder_config);
    }
    
    // Fallback: Normale Email
    return sendViaBuiltinMail($lead, $reward);
}

/**
 * Versendet Belohnung über Customer's Autoresponder-API
 */
function sendViaAutoresponder($lead, $reward, $config) {
    try {
        // Payload erstellen
        $payload = [
            'event' => 'reward_delivery',
            'lead' => [
                'email' => $lead['email'],
                'name' => $lead['name'],
                'id' => $lead['id'] ?? null
            ],
            'reward' => [
                'title' => $reward['reward_title'],
                'description' => $reward['reward_description'] ?? '',
                'type' => $reward['reward_type'] ?? '',
                'value' => $reward['reward_value'] ?? '',
                'download_url' => $reward['reward_download_url'] ?? '',
                'access_code' => $reward['reward_access_code'] ?? '',
                'instructions' => $reward['reward_instructions'] ?? ''
            ],
            'timestamp' => date('c')
        ];
        
        // HTTP Request
        $ch = curl_init($config['autoresponder_webhook_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . ($config['autoresponder_api_key'] ?? '')
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            error_log("Reward Email via Autoresponder erfolgreich: {$lead['email']} - {$reward['reward_title']}");
            return true;
        } else {
            error_log("Autoresponder Fehler (HTTP $http_code): $curl_error - Fallback auf normale Email");
            // Bei Fehler: Fallback
            return sendViaBuiltinMail($lead, $reward);
        }
        
    } catch (Exception $e) {
        error_log("Autoresponder Exception: " . $e->getMessage() . " - Fallback auf normale Email");
        return sendViaBuiltinMail($lead, $reward);
    }
}

/**
 * Versendet Belohnung über normale PHP mail() Funktion
 */
function sendViaBuiltinMail($lead, $reward) {
    $subject = "🎁 Du hast eine Belohnung freigeschaltet!";
    
    // Belohnungsdetails formatieren
    $reward_details = '';
    
    if (!empty($reward['reward_download_url'])) {
        $reward_details .= "
        <div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; border-radius: 6px; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; font-weight: bold; color: #166534;'>🔗 Download-Link:</p>
            <a href='" . htmlspecialchars($reward['reward_download_url']) . "' 
               style='display: inline-block; background: #22c55e; color: white; padding: 12px 24px; 
                      text-decoration: none; border-radius: 8px; font-weight: bold;'>
                Jetzt herunterladen
            </a>
        </div>
        ";
    }
    
    if (!empty($reward['reward_access_code'])) {
        $reward_details .= "
        <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; font-weight: bold; color: #92400e;'>🔑 Zugriffscode:</p>
            <code style='font-size: 18px; background: white; padding: 8px 16px; border-radius: 6px; 
                         display: inline-block; font-family: monospace; color: #92400e;'>
                " . htmlspecialchars($reward['reward_access_code']) . "
            </code>
        </div>
        ";
    }
    
    if (!empty($reward['reward_instructions'])) {
        $reward_details .= "
        <div style='background: #e0e7ff; border-left: 4px solid #6366f1; padding: 15px; border-radius: 6px; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; font-weight: bold; color: #3730a3;'>📋 Einlöse-Anweisungen:</p>
            <p style='margin: 0; color: #3730a3;'>" . nl2br(htmlspecialchars($reward['reward_instructions'])) . "</p>
        </div>
        ";
    }
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); 
                        padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Glückwunsch!</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0;'>
                    Du hast eine Belohnung freigeschaltet!
                </p>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; 
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                
                <p style='font-size: 16px;'>Hallo " . htmlspecialchars($lead['name']) . ",</p>
                
                <p>durch deine erfolgreichen Empfehlungen hast du folgende Belohnung freigeschaltet:</p>
                
                <div style='background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); 
                            padding: 24px; border-radius: 12px; margin: 20px 0; text-align: center;'>
                    <h2 style='color: white; margin: 0 0 8px 0; font-size: 24px;'>
                        " . htmlspecialchars($reward['reward_title']) . "
                    </h2>
                    " . (!empty($reward['reward_description']) ? "
                    <p style='color: rgba(255,255,255,0.9); margin: 0; font-size: 14px;'>
                        " . htmlspecialchars($reward['reward_description']) . "
                    </p>
                    " : "") . "
                    " . (!empty($reward['reward_value']) ? "
                    <p style='color: white; margin: 12px 0 0 0; font-size: 20px; font-weight: bold;'>
                        " . htmlspecialchars($reward['reward_value']) . "
                    </p>
                    " : "") . "
                </div>
                
                " . $reward_details . "
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://app.mehr-infos-jetzt.de/lead_dashboard.php' 
                       style='display: inline-block; background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); 
                              color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; 
                              font-weight: bold; font-size: 16px;'>
                        🎯 Zum Dashboard
                    </a>
                </div>
                
                <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin-top: 30px;'>
                    <p style='margin: 0; font-size: 14px; color: #6b7280;'>
                        <strong style='color: #374151;'>💡 Tipp:</strong> 
                        Empfiehl weiter und schalte noch mehr Belohnungen frei!
                    </p>
                </div>
                
                <p style='color: #888; font-size: 14px; margin-top: 30px; text-align: center;'>
                    Viel Spaß mit deiner Belohnung! 🎁<br>
                    " . htmlspecialchars($lead['company_name'] ?? 'Dein Team') . "
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . ($lead['company_name'] ?? 'KI Leadsystem') . " <noreply@mehr-infos-jetzt.de>\r\n";
    
    $sent = mail($lead['email'], $subject, $message, $headers);
    
    if ($sent) {
        error_log("Reward Email erfolgreich versendet (builtin mail): {$lead['email']} - {$reward['reward_title']}");
    } else {
        error_log("Fehler beim Versenden der Reward Email: {$lead['email']} - {$reward['reward_title']}");
    }
    
    return $sent;
}
?>