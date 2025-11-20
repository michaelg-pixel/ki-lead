<?php
/**
 * Reward Auto-Delivery System - UNIVERSAL für alle Provider
 * Automatische Belohnungsauslieferung bei erreichten Empfehlungen
 * Version: 3.0 - Universal Multi-Provider Support
 * 
 * ✅ Unterstützte Provider:
 * - Quentn
 * - ActiveCampaign  
 * - Klick-Tipp
 * - Brevo (Sendinblue)
 * - GetResponse
 * 
 * Features:
 * - Automatische Prüfung bei Conversions
 * - Provider-unabhängige Benachrichtigung
 * - Custom Fields Updates für alle Provider
 * - Tracking aller Auslieferungen
 * - API für externen Zugriff
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/rewards/email-delivery-service.php';

/**
 * Prüft und liefert Belohnungen für einen Lead aus
 * Wird aufgerufen nach jeder Conversion oder manuell
 * 
 * @param PDO $pdo Database Connection
 * @param int $lead_id Lead ID
 * @return array Result mit Erfolg, Details und ausgelieferten Belohnungen
 */
function checkAndDeliverRewards($pdo, $lead_id) {
    try {
        // Lead-Daten laden
        $stmt = $pdo->prepare("
            SELECT lu.*, u.company_name, u.email as customer_email
            FROM lead_users lu
            LEFT JOIN users u ON lu.user_id = u.id
            WHERE lu.id = ?
        ");
        $stmt->execute([$lead_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lead) {
            return ['success' => false, 'error' => 'Lead nicht gefunden'];
        }
        
        error_log("🎯 Prüfe Belohnungen für Lead: {$lead['email']} (ID: $lead_id)");
        
        // Anzahl erfolgreicher Referrals zählen
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM lead_referrals 
            WHERE referrer_id = ? 
            AND (status = 'active' OR status = 'converted')
        ");
        $stmt->execute([$lead_id]);
        $referral_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        error_log("📊 Lead hat $referral_count erfolgreiche Empfehlungen");
        
        // Alle erreichbaren Belohnungen finden die noch nicht ausgeliefert wurden
        $stmt = $pdo->prepare("
            SELECT rd.* 
            FROM reward_definitions rd
            WHERE rd.user_id = ?
            AND rd.is_active = 1
            AND rd.required_referrals <= ?
            AND rd.id NOT IN (
                SELECT reward_id 
                FROM reward_deliveries 
                WHERE lead_id = ?
            )
            ORDER BY rd.tier_level ASC
        ");
        $stmt->execute([$lead['user_id'], $referral_count, $lead_id]);
        $pending_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pending_rewards)) {
            error_log("ℹ️ Keine neuen Belohnungen für Lead $lead_id");
            return [
                'success' => true,
                'lead_id' => $lead_id,
                'referral_count' => $referral_count,
                'rewards_delivered' => 0,
                'message' => 'Keine neuen Belohnungen verfügbar'
            ];
        }
        
        error_log("🎁 " . count($pending_rewards) . " neue Belohnungen gefunden!");
        
        $delivered = [];
        $emailService = new RewardEmailDeliveryService();
        
        foreach ($pending_rewards as $reward) {
            error_log("⏳ Liefere Belohnung aus: {$reward['reward_title']} (Tier {$reward['tier_level']})");
            
            // Belohnung in Datenbank speichern
            $delivery_id = deliverReward($pdo, $lead, $reward);
            
            if ($delivery_id) {
                // 🆕 UNIVERSAL PROVIDER NOTIFICATION
                try {
                    $notificationResult = $emailService->sendRewardEmail(
                        $lead['user_id'],  // Customer ID
                        $lead_id,          // Lead ID
                        $reward['id']      // Reward ID
                    );
                    
                    if ($notificationResult['success']) {
                        error_log("✅ Benachrichtigung erfolgreich via {$notificationResult['method']}: {$notificationResult['message']}");
                        
                        $delivered[] = [
                            'reward_id' => $reward['id'],
                            'reward_title' => $reward['reward_title'],
                            'tier_level' => $reward['tier_level'],
                            'delivery_id' => $delivery_id,
                            'notification_method' => $notificationResult['method'],
                            'provider' => $notificationResult['provider'] ?? 'none',
                            'notification_success' => true
                        ];
                    } else {
                        error_log("⚠️ Benachrichtigung fehlgeschlagen: {$notificationResult['message']}");
                        
                        // Fallback: System-Email versenden
                        $emailSent = sendRewardNotificationEmail($lead, $reward);
                        
                        $delivered[] = [
                            'reward_id' => $reward['id'],
                            'reward_title' => $reward['reward_title'],
                            'tier_level' => $reward['tier_level'],
                            'delivery_id' => $delivery_id,
                            'notification_method' => $emailSent ? 'fallback_email' : 'none',
                            'notification_success' => $emailSent,
                            'notification_error' => $notificationResult['message']
                        ];
                    }
                } catch (Exception $e) {
                    error_log("❌ Notification Error: " . $e->getMessage());
                    
                    // Fallback: System-Email
                    $emailSent = sendRewardNotificationEmail($lead, $reward);
                    
                    $delivered[] = [
                        'reward_id' => $reward['id'],
                        'reward_title' => $reward['reward_title'],
                        'tier_level' => $reward['tier_level'],
                        'delivery_id' => $delivery_id,
                        'notification_method' => $emailSent ? 'fallback_email' : 'none',
                        'notification_success' => $emailSent,
                        'notification_error' => $e->getMessage()
                    ];
                }
            }
        }
        
        error_log("✅ Belohnungs-Auslieferung abgeschlossen: " . count($delivered) . " Belohnungen ausgeliefert");
        
        return [
            'success' => true,
            'lead_id' => $lead_id,
            'referral_count' => $referral_count,
            'rewards_delivered' => count($delivered),
            'rewards' => $delivered
        ];
        
    } catch (Exception $e) {
        error_log("❌ Reward Delivery Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Liefert eine einzelne Belohnung aus (DB-Eintrag)
 * 
 * @param PDO $pdo Database Connection
 * @param array $lead Lead-Daten
 * @param array $reward Reward-Daten
 * @return int|null Delivery ID oder null bei Fehler
 */
function deliverReward($pdo, $lead, $reward) {
    try {
        // Prüfen ob bereits ausgeliefert
        $stmt = $pdo->prepare("
            SELECT id FROM reward_deliveries 
            WHERE lead_id = ? AND reward_id = ?
        ");
        $stmt->execute([$lead['id'], $reward['id']]);
        
        if ($stmt->fetch()) {
            error_log("ℹ️ Belohnung {$reward['id']} bereits ausgeliefert an Lead {$lead['id']}");
            return null; // Bereits ausgeliefert
        }
        
        // Auslieferung in Datenbank speichern
        $stmt = $pdo->prepare("
            INSERT INTO reward_deliveries (
                lead_id,
                reward_id,
                user_id,
                reward_type,
                reward_title,
                reward_value,
                delivery_url,
                access_code,
                delivery_instructions,
                delivered_at,
                delivery_status,
                email_sent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'delivered', 0)
        ");
        
        $stmt->execute([
            $lead['id'],
            $reward['id'],
            $lead['user_id'],
            $reward['reward_type'],
            $reward['reward_title'],
            $reward['reward_value'],
            $reward['delivery_url'] ?? null,
            $reward['access_code'] ?? null,
            $reward['delivery_instructions'] ?? null
        ]);
        
        $delivery_id = $pdo->lastInsertId();
        
        // In alte Tabelle auch eintragen (für Kompatibilität)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO referral_claimed_rewards (
                    lead_id, reward_id, reward_name, claimed_at
                ) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$lead['id'], $reward['id'], $reward['reward_title']]);
        } catch (PDOException $e) {
            // Ignorieren falls bereits vorhanden
        }
        
        error_log("💾 Belohnung in DB gespeichert (Delivery ID: $delivery_id)");
        return $delivery_id;
        
    } catch (Exception $e) {
        error_log("❌ Single Reward Delivery Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Sendet Fallback Email-Benachrichtigung an Lead
 * Wird verwendet wenn keine API-Integration konfiguriert ist
 * 
 * @param array $lead Lead-Daten
 * @param array $reward Reward-Daten
 * @return bool Erfolgreich versendet?
 */
function sendRewardNotificationEmail($lead, $reward) {
    $subject = "🎁 Du hast eine Belohnung freigeschaltet!";
    
    // Belohnungsdetails formatieren
    $reward_details = '';
    
    if (!empty($reward['delivery_url'])) {
        $reward_details .= "
        <div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; border-radius: 6px; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; font-weight: bold; color: #166534;'>🔗 Download-Link:</p>
            <a href='{$reward['delivery_url']}' 
               style='color: #22c55e; font-weight: bold; word-break: break-all;'>
                {$reward['delivery_url']}
            </a>
        </div>
        ";
    }
    
    if (!empty($reward['access_code'])) {
        $reward_details .= "
        <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; font-weight: bold; color: #92400e;'>🔑 Zugriffscode:</p>
            <code style='font-size: 18px; background: white; padding: 8px 16px; border-radius: 6px; 
                         display: inline-block; font-family: monospace; color: #92400e;'>
                {$reward['access_code']}
            </code>
        </div>
        ";
    }
    
    if (!empty($reward['delivery_instructions'])) {
        $reward_details .= "
        <div style='background: #e0e7ff; border-left: 4px solid #6366f1; padding: 15px; border-radius: 6px; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; font-weight: bold; color: #3730a3;'>📋 Einlöse-Anweisungen:</p>
            <p style='margin: 0; color: #3730a3;'>" . nl2br(htmlspecialchars($reward['delivery_instructions'])) . "</p>
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
                    <div style='font-size: 48px; margin-bottom: 12px;'>🎁</div>
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
                    Dein Team
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . ($lead['company_name'] ?? 'Mehr Infos Jetzt') . " <noreply@mehr-infos-jetzt.de>\r\n";
    
    $sent = mail($lead['email'], $subject, $message, $headers);
    
    if ($sent) {
        error_log("📧 Fallback-Email erfolgreich versendet an: {$lead['email']}");
    } else {
        error_log("❌ Fallback-Email konnte nicht versendet werden an: {$lead['email']}");
    }
    
    return $sent;
}

/**
 * API Endpoint für externe Zugriffe
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action'])) {
            throw new Exception('Action required');
        }
        
        $pdo = getDBConnection();
        
        switch ($input['action']) {
            case 'check_and_deliver':
                if (!isset($input['lead_id'])) {
                    throw new Exception('lead_id required');
                }
                
                $result = checkAndDeliverRewards($pdo, $input['lead_id']);
                echo json_encode($result);
                break;
                
            case 'get_delivered_rewards':
                if (!isset($input['lead_id'])) {
                    throw new Exception('lead_id required');
                }
                
                $stmt = $pdo->prepare("
                    SELECT * FROM reward_deliveries 
                    WHERE lead_id = ? 
                    ORDER BY delivered_at DESC
                ");
                $stmt->execute([$input['lead_id']]);
                $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'rewards' => $rewards
                ]);
                break;
                
            case 'manual_delivery':
                // Manuelle Auslieferung durch Admin
                if (!isset($input['lead_id']) || !isset($input['reward_id'])) {
                    throw new Exception('lead_id and reward_id required');
                }
                
                $stmt = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
                $stmt->execute([$input['lead_id']]);
                $lead = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("SELECT * FROM reward_definitions WHERE id = ?");
                $stmt->execute([$input['reward_id']]);
                $reward = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$lead || !$reward) {
                    throw new Exception('Lead or Reward not found');
                }
                
                $delivery_id = deliverReward($pdo, $lead, $reward);
                
                if ($delivery_id) {
                    // Universal Benachrichtigung
                    $emailService = new RewardEmailDeliveryService();
                    $notificationResult = $emailService->sendRewardEmail(
                        $lead['user_id'],
                        $lead['id'],
                        $reward['id']
                    );
                }
                
                echo json_encode([
                    'success' => true,
                    'delivery_id' => $delivery_id,
                    'notification' => $notificationResult ?? null,
                    'message' => 'Reward manually delivered'
                ]);
                break;
                
            default:
                throw new Exception('Unknown action');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
