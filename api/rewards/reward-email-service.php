<?php
/**
 * Automatischer Belohnungs-Email-Versand
 * Wird aufgerufen wenn ein Lead eine neue Empfehlung macht
 * Prüft ob Belohnungsstufen erreicht wurden und versendet entsprechende Emails
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../customer/includes/EmailProviders.php';

class RewardEmailService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Prüft alle Belohnungen für einen Lead und versendet fällige Emails
     * 
     * @param int $leadId Lead User ID
     * @return array Ergebnis der Prüfung und Versendungen
     */
    public function checkAndSendRewards(int $leadId): array {
        try {
            // Lead-Daten laden
            $stmt = $this->pdo->prepare("
                SELECT 
                    lu.*,
                    u.id as customer_id,
                    u.company_name,
                    u.company_email
                FROM lead_users lu
                INNER JOIN users u ON lu.user_id = u.id
                WHERE lu.id = ?
            ");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                throw new Exception("Lead nicht gefunden");
            }
            
            $customerId = $lead['customer_id'];
            $currentReferrals = $lead['successful_referrals'] ?? 0;
            
            // Alle Belohnungsstufen für diesen Kunden laden
            // (sowohl allgemeine als auch freebie-spezifische)
            $stmt = $this->pdo->prepare("
                SELECT rd.* 
                FROM reward_definitions rd
                WHERE rd.user_id = ?
                    AND rd.is_active = TRUE
                    AND rd.required_referrals <= ?
                    AND rd.auto_send_email = TRUE
                ORDER BY rd.required_referrals ASC
            ");
            $stmt->execute([$customerId, $currentReferrals]);
            $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            $emailsSent = 0;
            
            foreach ($rewards as $reward) {
                // Prüfen ob Email für diese Belohnung bereits versendet wurde
                $stmt = $this->pdo->prepare("
                    SELECT id FROM lead_reward_emails
                    WHERE lead_id = ? AND reward_id = ? AND send_status = 'sent'
                ");
                $stmt->execute([$leadId, $reward['id']]);
                
                if ($stmt->fetch()) {
                    // Bereits versendet, überspringen
                    continue;
                }
                
                // Email versenden
                $sendResult = $this->sendRewardEmail($lead, $reward);
                
                $results[] = [
                    'reward_id' => $reward['id'],
                    'reward_name' => $reward['tier_name'],
                    'required_referrals' => $reward['required_referrals'],
                    'success' => $sendResult['success'],
                    'message' => $sendResult['message']
                ];
                
                if ($sendResult['success']) {
                    $emailsSent++;
                }
            }
            
            return [
                'success' => true,
                'lead_id' => $leadId,
                'current_referrals' => $currentReferrals,
                'rewards_checked' => count($rewards),
                'emails_sent' => $emailsSent,
                'details' => $results
            ];
            
        } catch (Exception $e) {
            error_log("Reward Email Check Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Versendet eine Belohnungs-Email
     * 
     * @param array $lead Lead-Daten
     * @param array $reward Belohnungs-Daten
     * @return array Ergebnis des Versands
     */
    private function sendRewardEmail(array $lead, array $reward): array {
        try {
            $customerId = $lead['customer_id'];
            $leadEmail = $lead['email'];
            
            // Email-Inhalt vorbereiten
            $subject = $reward['email_subject'] ?? 
                       "Glückwunsch! Du hast die {$reward['tier_name']}-Belohnung erreicht!";
            
            $body = $this->prepareEmailBody($lead, $reward);
            
            // API-Einstellungen laden
            $stmt = $this->pdo->prepare("
                SELECT * FROM customer_email_api_settings
                WHERE customer_id = ? AND is_active = TRUE AND is_verified = TRUE
                LIMIT 1
            ");
            $stmt->execute([$customerId]);
            $apiSettings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Email-Log-Eintrag erstellen (PENDING)
            $stmt = $this->pdo->prepare("
                INSERT INTO lead_reward_emails (
                    lead_id,
                    customer_id,
                    reward_id,
                    email_to,
                    email_subject,
                    email_body,
                    send_status,
                    send_method
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            
            $sendMethod = $apiSettings ? 'api' : 'manual';
            
            $stmt->execute([
                $lead['id'],
                $customerId,
                $reward['id'],
                $leadEmail,
                $subject,
                $body,
                $sendMethod
            ]);
            
            $emailLogId = $this->pdo->lastInsertId();
            
            // Wenn API konfiguriert, über API versenden
            if ($apiSettings && $reward['send_via_api']) {
                $sendResult = $this->sendViaAPI($apiSettings, $leadEmail, $subject, $body, $reward);
                
                if ($sendResult['success']) {
                    // Email-Log aktualisieren
                    $stmt = $this->pdo->prepare("
                        UPDATE lead_reward_emails SET
                            send_status = 'sent',
                            api_provider = ?,
                            api_message_id = ?,
                            sent_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $apiSettings['provider'],
                        $sendResult['message_id'] ?? null,
                        $emailLogId
                    ]);
                    
                    // Tag hinzufügen wenn konfiguriert
                    if (!empty($reward['reward_tag'])) {
                        $this->addTagToLead($apiSettings, $leadEmail, $reward['reward_tag']);
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Email erfolgreich versendet via ' . $apiSettings['provider'],
                        'email_log_id' => $emailLogId
                    ];
                } else {
                    // Fehler protokollieren
                    $stmt = $this->pdo->prepare("
                        UPDATE lead_reward_emails SET
                            send_status = 'failed',
                            error_message = ?,
                            retry_count = retry_count + 1
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $sendResult['error'],
                        $emailLogId
                    ]);
                    
                    throw new Exception('Email-Versand fehlgeschlagen: ' . $sendResult['error']);
                }
            } else {
                // Manuelle Versendung - Email bleibt auf 'pending'
                // Kunde muss manuell versenden
                return [
                    'success' => true,
                    'message' => 'Email vorbereitet für manuelle Versendung',
                    'email_log_id' => $emailLogId,
                    'manual' => true
                ];
            }
            
        } catch (Exception $e) {
            error_log("Send Reward Email Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Email über API versenden
     */
    private function sendViaAPI(array $apiSettings, string $email, string $subject, string $body, array $reward): array {
        try {
            $customSettings = json_decode($apiSettings['custom_settings'] ?? '{}', true);
            
            $provider = EmailProviderFactory::create(
                $apiSettings['provider'],
                $apiSettings['api_key'],
                $customSettings
            );
            
            $options = [];
            
            // Anhänge hinzufügen wenn vorhanden
            if (!empty($reward['attachment_urls'])) {
                $options['attachments'] = json_decode($reward['attachment_urls'], true);
            }
            
            // Email-Template verwenden wenn angegeben
            if (!empty($reward['email_template_id'])) {
                $options['template_id'] = $reward['email_template_id'];
            }
            
            $result = $provider->sendEmail($email, $subject, $body, $options);
            
            // API-Log erstellen
            $this->logAPICall(
                $apiSettings['customer_id'],
                $apiSettings['provider'],
                'send-email',
                'POST',
                $result['success'],
                $result['message']
            );
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Tag zu Lead hinzufügen
     */
    private function addTagToLead(array $apiSettings, string $email, string $tag): void {
        try {
            $customSettings = json_decode($apiSettings['custom_settings'] ?? '{}', true);
            
            $provider = EmailProviderFactory::create(
                $apiSettings['provider'],
                $apiSettings['api_key'],
                $customSettings
            );
            
            $provider->addTag($email, $tag);
            
        } catch (Exception $e) {
            error_log("Add Tag Error: " . $e->getMessage());
        }
    }
    
    /**
     * Email-Body vorbereiten mit Platzhaltern
     */
    private function prepareEmailBody(array $lead, array $reward): string {
        if (!empty($reward['email_body'])) {
            $body = $reward['email_body'];
        } else {
            // Standard-Template
            $body = $this->getDefaultEmailTemplate($lead, $reward);
        }
        
        // Platzhalter ersetzen
        $replacements = [
            '{{name}}' => $lead['name'],
            '{{email}}' => $lead['email'],
            '{{tier_name}}' => $reward['tier_name'],
            '{{tier_level}}' => $reward['tier_level'],
            '{{required_referrals}}' => $reward['required_referrals'],
            '{{reward_title}}' => $reward['reward_title'],
            '{{reward_description}}' => $reward['reward_description'] ?? '',
            '{{reward_value}}' => $reward['reward_value'] ?? '',
            '{{reward_instructions}}' => $reward['reward_instructions'] ?? '',
            '{{download_url}}' => $reward['reward_download_url'] ?? '',
            '{{access_code}}' => $reward['reward_access_code'] ?? '',
            '{{company_name}}' => $lead['company_name'] ?? '',
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $body
        );
    }
    
    /**
     * Standard Email-Template
     */
    private function getDefaultEmailTemplate(array $lead, array $reward): string {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
                .reward-box { background: white; border: 2px solid #667eea; border-radius: 10px; padding: 20px; margin: 20px 0; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Herzlichen Glückwunsch!</h1>
                    <p>Du hast eine neue Belohnung erreicht</p>
                </div>
                <div class='content'>
                    <h2>Hallo {$lead['name']}!</h2>
                    
                    <p>Fantastische Neuigkeiten! Du hast die <strong>{$reward['tier_name']}</strong>-Belohnung erreicht!</p>
                    
                    <div class='reward-box'>
                        <h3 style='color: #667eea; margin-top: 0;'>{$reward['reward_title']}</h3>
                        " . (!empty($reward['reward_description']) ? "<p>{$reward['reward_description']}</p>" : "") . "
                        " . (!empty($reward['reward_value']) ? "<p><strong>Wert:</strong> {$reward['reward_value']}</p>" : "") . "
                    </div>
                    
                    " . (!empty($reward['reward_instructions']) ? "
                    <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                        <strong>So löst du deine Belohnung ein:</strong><br>
                        {$reward['reward_instructions']}
                    </div>
                    " : "") . "
                    
                    " . (!empty($reward['reward_download_url']) ? "
                    <p style='text-align: center;'>
                        <a href='{$reward['reward_download_url']}' class='button'>
                            Belohnung herunterladen
                        </a>
                    </p>
                    " : "") . "
                    
                    " . (!empty($reward['reward_access_code']) ? "
                    <p><strong>Zugangscode:</strong> <code style='background: #f3f4f6; padding: 5px 10px; border-radius: 3px;'>{$reward['reward_access_code']}</code></p>
                    " : "") . "
                    
                    <p>Du hast jetzt <strong>{$reward['required_referrals']}</strong> erfolgreiche Empfehlungen! Mach weiter so! 🚀</p>
                    
                    <div class='footer'>
                        <p>{$lead['company_name']}<br>
                        Vielen Dank für deine Unterstützung!</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * API-Call loggen
     */
    private function logAPICall(int $customerId, string $provider, string $endpoint, string $method, bool $success, string $message): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_api_logs (
                    customer_id,
                    provider,
                    endpoint,
                    method,
                    success,
                    error_message
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customerId,
                $provider,
                $endpoint,
                $method,
                $success ? 1 : 0,
                $success ? null : $message
            ]);
        } catch (Exception $e) {
            error_log("API Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Alle ausstehenden Emails erneut versuchen zu versenden
     * (für Cronjob)
     */
    public function retryFailedEmails(): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT lre.*, lu.email as lead_email
                FROM lead_reward_emails lre
                INNER JOIN lead_users lu ON lre.lead_id = lu.id
                WHERE lre.send_status = 'failed'
                    AND lre.retry_count < lre.max_retries
                    AND lre.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY lre.created_at ASC
                LIMIT 50
            ");
            $stmt->execute();
            $failedEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $retried = 0;
            $succeeded = 0;
            
            foreach ($failedEmails as $emailLog) {
                // Lead und Reward laden
                $stmt = $this->pdo->prepare("
                    SELECT lu.*, u.id as customer_id, u.company_name, u.company_email
                    FROM lead_users lu
                    INNER JOIN users u ON lu.user_id = u.id
                    WHERE lu.id = ?
                ");
                $stmt->execute([$emailLog['lead_id']]);
                $lead = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $this->pdo->prepare("SELECT * FROM reward_definitions WHERE id = ?");
                $stmt->execute([$emailLog['reward_id']]);
                $reward = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lead && $reward) {
                    $result = $this->sendRewardEmail($lead, $reward);
                    $retried++;
                    
                    if ($result['success']) {
                        $succeeded++;
                    }
                }
            }
            
            return [
                'success' => true,
                'retried' => $retried,
                'succeeded' => $succeeded
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Wenn direkt aufgerufen (z.B. via Cronjob)
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $service = new RewardEmailService($pdo);
    
    if ($argv[1] === 'retry') {
        $result = $service->retryFailedEmails();
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } elseif ($argv[1] === 'check' && isset($argv[2])) {
        $leadId = (int)$argv[2];
        $result = $service->checkAndSendRewards($leadId);
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Usage: php reward-email-service.php [retry|check LEAD_ID]\n";
    }
}