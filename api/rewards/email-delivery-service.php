<?php
/**
 * Reward Email Delivery Service
 * Versendet Belohnungs-Emails über die konfigurierte Customer-Email-API
 * 
 * Unterstützt Platzhalter:
 * - {{reward_title}}
 * - {{reward_description}}
 * - {{reward_warning}}
 * - {{successful_referrals}}
 * - {{current_points}}
 * - {{referral_code}}
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../customer/includes/EmailProviders.php';

class RewardEmailDeliveryService {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Sendet Belohnungs-Email an Lead über Customer's Email-API
     * 
     * @param int $customerId Customer-ID
     * @param int $leadId Lead-ID
     * @param int $rewardId Reward-Definition-ID
     * @return array ['success' => bool, 'message' => string, 'delivery_method' => string]
     */
    public function sendRewardEmail($customerId, $leadId, $rewardId) {
        try {
            // 1. Lead-Daten laden
            $lead = $this->getLeadData($leadId);
            if (!$lead) {
                return ['success' => false, 'message' => 'Lead nicht gefunden'];
            }
            
            // 2. Reward-Daten laden
            $reward = $this->getRewardData($rewardId);
            if (!$reward) {
                return ['success' => false, 'message' => 'Belohnung nicht gefunden'];
            }
            
            // 3. Email-API-Konfiguration laden
            $apiConfig = $this->getEmailApiConfig($customerId);
            if (!$apiConfig) {
                return [
                    'success' => false, 
                    'message' => 'Keine Email-API konfiguriert. Bitte Email-Integration unter Empfehlungsprogramm einrichten.'
                ];
            }
            
            // 4. Prüfen ob Provider Direct Email unterstützt
            if ($this->supportsDirectEmail($apiConfig['provider'])) {
                // Email direkt versenden
                return $this->sendViaEmailProvider($lead, $reward, $apiConfig);
            } else {
                // Tag hinzufügen für Kampagnen-Trigger
                return $this->triggerViaTag($lead, $reward, $apiConfig);
            }
            
        } catch (Exception $e) {
            error_log("RewardEmailDelivery Error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Fehler beim Email-Versand: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lädt Lead-Daten mit Empfehlungsstatistiken
     */
    private function getLeadData($leadId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                lu.*,
                u.company_name,
                u.company_email
            FROM lead_users lu
            LEFT JOIN users u ON lu.user_id = u.id
            WHERE lu.id = ?
        ");
        $stmt->execute([$leadId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lädt Reward-Definition
     */
    private function getRewardData($rewardId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM reward_definitions 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$rewardId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lädt Email-API-Konfiguration des Customers
     */
    private function getEmailApiConfig($customerId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM customer_email_api_settings 
            WHERE customer_id = ? AND is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Prüft ob Provider direkte Email unterstützt
     */
    private function supportsDirectEmail($provider) {
        $directEmailProviders = ['brevo', 'sendinblue', 'getresponse'];
        return in_array(strtolower($provider), $directEmailProviders);
    }
    
    /**
     * Versendet Email direkt über Provider (Brevo, GetResponse)
     */
    private function sendViaEmailProvider($lead, $reward, $apiConfig) {
        try {
            // Provider initialisieren
            $config = [
                'api_url' => $apiConfig['api_url'] ?? null,
                'account_url' => $apiConfig['api_url'] ?? null,
                'sender_email' => $lead['company_email'] ?? 'noreply@mehr-infos-jetzt.de',
                'sender_name' => $lead['company_name'] ?? 'Belohnungsprogramm'
            ];
            
            $provider = EmailProviderFactory::create(
                $apiConfig['provider'],
                $apiConfig['api_key'],
                $config
            );
            
            // Email-Betreff und Body vorbereiten
            $subject = $this->getEmailSubject($reward);
            $body = $this->getEmailBody($lead, $reward);
            
            // Email versenden
            $result = $provider->sendEmail($lead['email'], $subject, $body);
            
            if ($result['success']) {
                error_log("Reward Email erfolgreich über {$apiConfig['provider']} versendet: {$lead['email']} - {$reward['reward_title']}");
                return [
                    'success' => true,
                    'message' => 'Email erfolgreich versendet',
                    'delivery_method' => 'direct_email',
                    'provider' => $apiConfig['provider']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Email-Versand fehlgeschlagen: ' . $result['message']
                ];
            }
            
        } catch (Exception $e) {
            error_log("Provider Email Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Provider-Fehler: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Triggert Kampagne über Tag (Quentn, Klick-Tipp, ActiveCampaign)
     */
    private function triggerViaTag($lead, $reward, $apiConfig) {
        try {
            // Provider initialisieren
            $config = [
                'api_url' => $apiConfig['api_url'] ?? null,
                'base_url' => $apiConfig['api_url'] ?? null,
                'account_url' => $apiConfig['api_url'] ?? null
            ];
            
            $provider = EmailProviderFactory::create(
                $apiConfig['provider'],
                $apiConfig['api_key'],
                $config
            );
            
            // Tag ermitteln: Erst reward_tag prüfen, dann api_settings reward_tag, dann fallback
            $rewardTag = $this->getRewardTag($reward, $apiConfig);
            
            // Custom Fields aktualisieren (BEVOR Tag hinzugefügt wird!)
            $this->updateLeadCustomFields($lead, $reward, $provider);
            
            // Tag hinzufügen
            $result = $provider->addTag($lead['email'], $rewardTag);
            
            if ($result['success']) {
                error_log("Reward Tag erfolgreich hinzugefügt bei {$apiConfig['provider']}: {$lead['email']} - Tag: {$rewardTag}");
                return [
                    'success' => true,
                    'message' => "Tag '{$rewardTag}' erfolgreich hinzugefügt. Stelle sicher, dass in deinem {$apiConfig['provider']}-Account eine Kampagne für diesen Tag existiert.",
                    'delivery_method' => 'tag_trigger',
                    'provider' => $apiConfig['provider'],
                    'tag' => $rewardTag
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tag konnte nicht hinzugefügt werden: ' . $result['message']
                ];
            }
            
        } catch (Exception $e) {
            error_log("Provider Tag Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Provider-Fehler: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ermittelt den Tag-Namen für die Belohnung
     * Priority: 1. Reward-Definition, 2. API-Settings, 3. Default
     */
    private function getRewardTag($reward, $apiConfig) {
        // 1. Prüfe ob reward_tag in reward_definitions existiert
        if (!empty($reward['reward_tag'])) {
            return $reward['reward_tag'];
        }
        
        // 2. Prüfe ob reward_tag in api_settings existiert (global für alle Rewards)
        if (!empty($apiConfig['reward_tag'])) {
            return $apiConfig['reward_tag'];
        }
        
        // 3. Fallback: Dynamischer Tag mit Tier-Level
        return 'reward_' . $reward['tier_level'] . '_earned';
    }
    
    /**
     * Aktualisiert Custom Fields beim Lead (für alle Provider)
     */
    private function updateLeadCustomFields($lead, $reward, $provider) {
        try {
            // Custom Field Update nur für Provider die addContact unterstützen
            $customFields = [
                'successful_referrals' => $lead['successful_referrals'],
                'total_referrals' => $lead['total_referrals'],
                'referral_code' => $lead['referral_code'],
                'rewards_earned' => $lead['rewards_earned'] ?? 0,
                'last_reward' => $reward['reward_title'],
                'reward_title' => $reward['reward_title'],
                'reward_description' => $reward['reward_description'] ?? '',
                'reward_warning' => $reward['reward_warning'] ?? '',
                'current_points' => $lead['successful_referrals'] // Alias für successful_referrals
            ];
            
            // Update via addContact (erstellt oder aktualisiert)
            $provider->addContact([
                'email' => $lead['email'],
                'first_name' => $lead['name'],
                'last_name' => ''
            ], [
                'custom_fields' => $customFields
            ]);
            
        } catch (Exception $e) {
            error_log("Custom Fields Update Error: " . $e->getMessage());
            // Nicht kritisch, weiter machen
        }
    }
    
    /**
     * Generiert Email-Betreff
     */
    private function getEmailSubject($reward) {
        return "🎁 Glückwunsch! Du hast eine Belohnung freigeschaltet";
    }
    
    /**
     * Generiert Email-Body mit Platzhalter-Ersetzung
     */
    private function getEmailBody($lead, $reward) {
        // Basis-Template (Customer muss in seinem Email-System ein besseres Template anlegen)
        $body = "
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
                    
                    <p style='font-size: 16px;'>Hallo {{name}},</p>
                    
                    <p>durch deine {{successful_referrals}} erfolgreichen Empfehlungen hast du folgende Belohnung freigeschaltet:</p>
                    
                    <div style='background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); 
                                padding: 24px; border-radius: 12px; margin: 20px 0; text-align: center;'>
                        <h2 style='color: white; margin: 0 0 8px 0; font-size: 24px;'>
                            {{reward_title}}
                        </h2>
                        <p style='color: rgba(255,255,255,0.9); margin: 0; font-size: 14px;'>
                            {{reward_description}}
                        </p>
                    </div>
                    
                    <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0;'>
                        <p style='margin: 0; color: #92400e;'>
                            <strong>📋 Wichtig:</strong> {{reward_warning}}
                        </p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <p style='color: #6b7280; font-size: 14px;'>
                            <strong>Dein Empfehlungscode:</strong> {{referral_code}}<br>
                            <strong>Deine Punkte:</strong> {{current_points}}
                        </p>
                    </div>
                    
                    <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin-top: 30px;'>
                        <p style='margin: 0; font-size: 14px; color: #6b7280;'>
                            <strong style='color: #374151;'>💡 Tipp:</strong> 
                            Empfiehl weiter und schalte noch mehr Belohnungen frei!
                        </p>
                    </div>
                    
                    <p style='color: #888; font-size: 14px; margin-top: 30px; text-align: center;'>
                        Viel Spaß mit deiner Belohnung! 🎁<br>
                        {{company_name}}
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Platzhalter ersetzen
        $replacements = [
            '{{name}}' => htmlspecialchars($lead['name'] ?? 'Lead'),
            '{{reward_title}}' => htmlspecialchars($reward['reward_title']),
            '{{reward_description}}' => htmlspecialchars($reward['reward_description'] ?? ''),
            '{{reward_warning}}' => htmlspecialchars($reward['reward_warning'] ?? 'Keine weiteren Hinweise'),
            '{{successful_referrals}}' => $lead['successful_referrals'] ?? 0,
            '{{current_points}}' => $lead['successful_referrals'] ?? 0, // Points = successful_referrals
            '{{referral_code}}' => htmlspecialchars($lead['referral_code'] ?? ''),
            '{{company_name}}' => htmlspecialchars($lead['company_name'] ?? 'Dein Team')
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $body
        );
    }
}

// Wenn als API-Endpoint aufgerufen
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    
    // POST-Daten lesen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['customer_id']) || !isset($input['lead_id']) || !isset($input['reward_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'customer_id, lead_id und reward_id sind erforderlich'
        ]);
        exit;
    }
    
    $service = new RewardEmailDeliveryService();
    $result = $service->sendRewardEmail(
        $input['customer_id'],
        $input['lead_id'],
        $input['reward_id']
    );
    
    echo json_encode($result);
}
