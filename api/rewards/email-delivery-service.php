<?php
/**
 * Universal Reward Email Delivery Service
 * Versendet Belohnungs-Benachrichtigungen über ALLE konfigurierten Email-Marketing-Provider
 * 
 * ✅ Unterstützte Provider:
 * - Quentn
 * - ActiveCampaign
 * - Klick-Tipp
 * - Brevo (Sendinblue)
 * - GetResponse
 * 
 * Features:
 * - Provider-spezifische Custom Fields Updates
 * - Tag-Trigger für Automationen
 * - Direkter Email-Versand wo möglich
 * - Fallback auf System-Email
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../customer/includes/EmailProviders.php';

class RewardEmailDeliveryService {
    
    private $pdo;
    
    /**
     * Provider-spezifische Feld-Mappings
     * Manche Provider haben unterschiedliche Namenskonventionen
     */
    private $fieldMappings = [
        'quentn' => [
            'referral_code' => 'referral_code',
            'referrer_code' => 'referrer_code',
            'total_referrals' => 'total_referrals',
            'successful_referrals' => 'successful_referrals',
            'rewards_earned' => 'rewards_earned',
            'current_points' => 'current_points',
            'reward_title' => 'reward_title',
            'reward_description' => 'reward_description',
            'reward_warning' => 'reward_warning',
            'company_name' => 'company_name'
        ],
        'activecampaign' => [
            'referral_code' => 'referral_code',
            'referrer_code' => 'referrer_code',
            'total_referrals' => 'total_referrals',
            'successful_referrals' => 'successful_referrals',
            'rewards_earned' => 'rewards_earned',
            'current_points' => 'current_points',
            'reward_title' => 'reward_title',
            'reward_description' => 'reward_description',
            'reward_warning' => 'reward_warning',
            'company_name' => 'company_name'
        ],
        'klicktipp' => [
            'referral_code' => 'referral_code',
            'referrer_code' => 'referrer_code',
            'total_referrals' => 'total_referrals',
            'successful_referrals' => 'successful_referrals',
            'rewards_earned' => 'rewards_earned',
            'current_points' => 'current_points',
            'reward_title' => 'reward_title',
            'reward_description' => 'reward_description',
            'reward_warning' => 'reward_warning',
            'company_name' => 'company_name'
        ],
        'brevo' => [
            'referral_code' => 'REFERRAL_CODE',
            'referrer_code' => 'REFERRER_CODE',
            'total_referrals' => 'TOTAL_REFERRALS',
            'successful_referrals' => 'SUCCESSFUL_REFERRALS',
            'rewards_earned' => 'REWARDS_EARNED',
            'current_points' => 'CURRENT_POINTS',
            'reward_title' => 'REWARD_TITLE',
            'reward_description' => 'REWARD_DESCRIPTION',
            'reward_warning' => 'REWARD_WARNING',
            'company_name' => 'COMPANY_NAME'
        ],
        'getresponse' => [
            'referral_code' => 'referral_code',
            'referrer_code' => 'referrer_code',
            'total_referrals' => 'total_referrals',
            'successful_referrals' => 'successful_referrals',
            'rewards_earned' => 'rewards_earned',
            'current_points' => 'current_points',
            'reward_title' => 'reward_title',
            'reward_description' => 'reward_description',
            'reward_warning' => 'reward_warning',
            'company_name' => 'company_name'
        ]
    ];
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * HAUPT-FUNKTION: Sendet Belohnungs-Benachrichtigung
     * 
     * @param int $customerId Customer-ID (Freebie-Owner)
     * @param int $leadId Lead-ID (Empfänger)
     * @param int $rewardId Reward-Definition-ID
     * @return array ['success' => bool, 'message' => string, 'method' => string]
     */
    public function sendRewardEmail($customerId, $leadId, $rewardId) {
        try {
            // 1. Lead-Daten laden
            $lead = $this->getLeadData($leadId);
            if (!$lead) {
                return ['success' => false, 'message' => 'Lead nicht gefunden', 'method' => 'none'];
            }
            
            // 2. Reward-Daten laden
            $reward = $this->getRewardData($rewardId);
            if (!$reward) {
                return ['success' => false, 'message' => 'Belohnung nicht gefunden', 'method' => 'none'];
            }
            
            // 3. Email-API-Konfiguration laden
            $apiConfig = $this->getEmailApiConfig($customerId);
            
            if (!$apiConfig) {
                error_log("⚠️ Keine API-Konfiguration für Customer $customerId - verwende Fallback-Email");
                // Fallback: System-Email versenden
                return $this->sendFallbackEmail($lead, $reward);
            }
            
            error_log("📧 Starte Belohnungs-Benachrichtigung via {$apiConfig['provider']} für Lead: {$lead['email']}");
            
            // 4. Custom Fields IMMER aktualisieren (egal welcher Provider)
            $fieldsUpdated = $this->updateCustomFieldsForProvider($lead, $reward, $apiConfig);
            
            if (!$fieldsUpdated['success']) {
                error_log("⚠️ Custom Fields Update fehlgeschlagen: " . $fieldsUpdated['message']);
            } else {
                error_log("✅ Custom Fields erfolgreich aktualisiert");
            }
            
            // 5. Tag hinzufügen (triggert Automation/Kampagne)
            if (!empty($apiConfig['start_tag'])) {
                $tagResult = $this->addTagToLead($lead['email'], $apiConfig);
                
                if ($tagResult['success']) {
                    error_log("✅ Tag '{$apiConfig['start_tag']}' erfolgreich hinzugefügt");
                    return [
                        'success' => true,
                        'message' => "Belohnung erfolgreich über {$apiConfig['provider']} benachrichtigt (Tag: {$apiConfig['start_tag']})",
                        'method' => 'tag_trigger',
                        'provider' => $apiConfig['provider'],
                        'tag' => $apiConfig['start_tag'],
                        'fields_updated' => $fieldsUpdated['success']
                    ];
                } else {
                    error_log("⚠️ Tag konnte nicht hinzugefügt werden: " . $tagResult['message']);
                }
            }
            
            // 6. Wenn Tag-Trigger nicht möglich/konfiguriert: Direkter Email-Versand
            if ($this->supportsDirectEmail($apiConfig['provider'])) {
                return $this->sendViaEmailProvider($lead, $reward, $apiConfig);
            }
            
            // 7. Fallback wenn nichts anderes funktioniert
            return [
                'success' => $fieldsUpdated['success'],
                'message' => $fieldsUpdated['success'] 
                    ? 'Custom Fields aktualisiert (kein Tag konfiguriert)' 
                    : 'Benachrichtigung fehlgeschlagen',
                'method' => 'fields_only',
                'provider' => $apiConfig['provider']
            ];
            
        } catch (Exception $e) {
            error_log("❌ RewardEmailDelivery Error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Fehler beim Email-Versand: ' . $e->getMessage(),
                'method' => 'error'
            ];
        }
    }
    
    /**
     * Aktualisiert Custom Fields beim Provider
     * Diese Funktion ist PROVIDER-UNABHÄNGIG und funktioniert für ALLE
     */
    private function updateCustomFieldsForProvider($lead, $reward, $apiConfig) {
        try {
            $provider = $apiConfig['provider'];
            $fields = $this->fieldMappings[$provider] ?? $this->fieldMappings['quentn'];
            
            // Kundeninfo laden
            $stmt = $this->pdo->prepare("SELECT company_name FROM users WHERE id = ?");
            $stmt->execute([$lead['user_id']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Daten für Custom Fields vorbereiten
            $customFieldsData = [
                $fields['total_referrals'] => $lead['total_referrals'] ?? 0,
                $fields['successful_referrals'] => $lead['successful_referrals'] ?? 0,
                $fields['rewards_earned'] => ($lead['rewards_earned'] ?? 0) + 1,
                $fields['current_points'] => $lead['successful_referrals'] ?? 0,
                $fields['reward_title'] => $this->truncate($reward['reward_title'] ?? '', 255),
                $fields['reward_description'] => $this->truncate($reward['reward_description'] ?? '', 500),
                $fields['reward_warning'] => $this->truncate($reward['reward_warning'] ?? '', 500),
                $fields['company_name'] => $customer['company_name'] ?? 'Mehr Infos Jetzt'
            ];
            
            // Provider-spezifisches Update
            return $this->executeProviderFieldsUpdate($lead['email'], $customFieldsData, $apiConfig);
            
        } catch (Exception $e) {
            error_log("Custom Fields Update Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Führt das eigentliche Custom Fields Update durch - provider-spezifisch
     */
    private function executeProviderFieldsUpdate($email, $fieldsData, $apiConfig) {
        switch (strtolower($apiConfig['provider'])) {
            case 'quentn':
                return $this->updateQuentnFields($email, $fieldsData, $apiConfig);
            
            case 'activecampaign':
                return $this->updateActiveCampaignFields($email, $fieldsData, $apiConfig);
            
            case 'klicktipp':
            case 'klick-tipp':
                return $this->updateKlickTippFields($email, $fieldsData, $apiConfig);
            
            case 'brevo':
            case 'sendinblue':
                return $this->updateBrevoFields($email, $fieldsData, $apiConfig);
            
            case 'getresponse':
                return $this->updateGetResponseFields($email, $fieldsData, $apiConfig);
            
            default:
                return ['success' => false, 'message' => 'Unbekannter Provider'];
        }
    }
    
    /**
     * QUENTN: Custom Fields Update
     */
    private function updateQuentnFields($email, $fieldsData, $apiConfig) {
        try {
            $provider = EmailProviderFactory::create($apiConfig['provider'], $apiConfig['api_key'], [
                'api_url' => $apiConfig['api_url']
            ]);
            
            $contact = $provider->getContactStatus($email);
            if (!$contact['exists']) {
                return ['success' => false, 'message' => 'Kontakt nicht in Quentn gefunden'];
            }
            
            $baseUrl = rtrim($apiConfig['api_url'], '/');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/contact/' . $contact['contact_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contact' => ['fields' => $fieldsData]
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiConfig['api_key'],
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'message' => ($httpCode >= 200 && $httpCode < 300) ? 'Quentn Fields aktualisiert' : "HTTP $httpCode"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Quentn Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * ACTIVECAMPAIGN: Custom Fields Update
     */
    private function updateActiveCampaignFields($email, $fieldsData, $apiConfig) {
        try {
            $provider = EmailProviderFactory::create($apiConfig['provider'], $apiConfig['api_key'], [
                'api_url' => $apiConfig['api_url']
            ]);
            
            $contact = $provider->getContactStatus($email);
            if (!$contact['exists']) {
                return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
            }
            
            $baseUrl = rtrim($apiConfig['api_url'], '/');
            
            // Field Values für ActiveCampaign
            $fieldValues = [];
            foreach ($fieldsData as $key => $value) {
                $fieldValues[] = ['field' => $key, 'value' => $value];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/3/contacts/' . $contact['contact_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contact' => ['fieldValues' => $fieldValues]
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Api-Token: ' . $apiConfig['api_key'],
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'message' => ($httpCode >= 200 && $httpCode < 300) ? 'ActiveCampaign Fields aktualisiert' : "HTTP $httpCode"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'ActiveCampaign Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * KLICK-TIPP: Custom Fields Update
     */
    private function updateKlickTippFields($email, $fieldsData, $apiConfig) {
        try {
            $connector = new XmlRpcConnector('https://api.klick-tipp.com');
            $sessionId = $connector->login($apiConfig['username'], $apiConfig['api_key']);
            
            if (!$sessionId) {
                return ['success' => false, 'message' => 'Login fehlgeschlagen'];
            }
            
            // Klick-Tipp: Fields mit 'field' Prefix
            $fields = [];
            foreach ($fieldsData as $key => $value) {
                $fields['field' . ucfirst($key)] = $value;
            }
            
            $connector->update($sessionId, $email, $fields);
            
            return ['success' => true, 'message' => 'Klick-Tipp Fields aktualisiert'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Klick-Tipp Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * BREVO: Custom Fields Update
     */
    private function updateBrevoFields($email, $fieldsData, $apiConfig) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/contacts/' . urlencode($email));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'attributes' => $fieldsData
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $apiConfig['api_key'],
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'message' => ($httpCode >= 200 && $httpCode < 300) ? 'Brevo Attributes aktualisiert' : "HTTP $httpCode"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Brevo Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * GETRESPONSE: Custom Fields Update
     */
    private function updateGetResponseFields($email, $fieldsData, $apiConfig) {
        try {
            $provider = EmailProviderFactory::create($apiConfig['provider'], $apiConfig['api_key']);
            $contact = $provider->getContactStatus($email);
            
            if (!$contact['exists']) {
                return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
            }
            
            // GetResponse: Custom Field Values
            $customFieldValues = [];
            foreach ($fieldsData as $key => $value) {
                $customFieldValues[] = [
                    'customFieldId' => $key,
                    'value' => [$value]
                ];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.getresponse.com/v3/contacts/' . $contact['contact_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'customFieldValues' => $customFieldValues
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Auth-Token: api-key ' . $apiConfig['api_key'],
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'message' => ($httpCode >= 200 && $httpCode < 300) ? 'GetResponse Fields aktualisiert' : "HTTP $httpCode"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'GetResponse Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Fügt Tag zum Lead hinzu (triggert Automation)
     */
    private function addTagToLead($email, $apiConfig) {
        try {
            $provider = EmailProviderFactory::create(
                $apiConfig['provider'],
                $apiConfig['api_key'],
                [
                    'api_url' => $apiConfig['api_url'] ?? '',
                    'username' => $apiConfig['username'] ?? ''
                ]
            );
            
            return $provider->addTag($email, $apiConfig['start_tag']);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Tag Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Fallback: System-Email versenden wenn kein Provider konfiguriert
     */
    private function sendFallbackEmail($lead, $reward) {
        $subject = "🎁 Glückwunsch! Du hast eine Belohnung freigeschaltet";
        $message = $this->getEmailBody($lead, $reward);
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . ($lead['company_name'] ?? 'Mehr Infos Jetzt') . " <noreply@mehr-infos-jetzt.de>\r\n";
        
        $sent = mail($lead['email'], $subject, $message, $headers);
        
        return [
            'success' => $sent,
            'message' => $sent ? 'Fallback-Email versendet' : 'Email-Versand fehlgeschlagen',
            'method' => 'fallback_email'
        ];
    }
    
    /**
     * Lädt Lead-Daten
     */
    private function getLeadData($leadId) {
        $stmt = $this->pdo->prepare("
            SELECT lu.*, u.company_name, u.company_email
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
        $stmt = $this->pdo->prepare("SELECT * FROM reward_definitions WHERE id = ? AND is_active = 1");
        $stmt->execute([$rewardId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lädt Email-API-Konfiguration
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
        return in_array(strtolower($provider), ['brevo', 'sendinblue']);
    }
    
    /**
     * Versendet Email direkt über Provider (nur Brevo)
     */
    private function sendViaEmailProvider($lead, $reward, $apiConfig) {
        try {
            $provider = EmailProviderFactory::create($apiConfig['provider'], $apiConfig['api_key']);
            $subject = "🎁 Glückwunsch! Du hast eine Belohnung freigeschaltet";
            $body = $this->getEmailBody($lead, $reward);
            
            $result = $provider->sendEmail($lead['email'], $subject, $body);
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Email direkt versendet' : $result['message'],
                'method' => 'direct_email',
                'provider' => $apiConfig['provider']
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Provider Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generiert Email-Body mit Platzhaltern
     */
    private function getEmailBody($lead, $reward) {
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
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <p style='color: #6b7280; font-size: 14px;'>
                            <strong>Dein Empfehlungscode:</strong> {{referral_code}}<br>
                            <strong>Deine Punkte:</strong> {{current_points}}
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
        
        $replacements = [
            '{{name}}' => htmlspecialchars($lead['name'] ?? 'Lead'),
            '{{reward_title}}' => htmlspecialchars($reward['reward_title']),
            '{{reward_description}}' => htmlspecialchars($reward['reward_description'] ?? ''),
            '{{successful_referrals}}' => $lead['successful_referrals'] ?? 0,
            '{{current_points}}' => $lead['successful_referrals'] ?? 0,
            '{{referral_code}}' => htmlspecialchars($lead['referral_code'] ?? ''),
            '{{company_name}}' => htmlspecialchars($lead['company_name'] ?? 'Dein Team')
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $body);
    }
    
    /**
     * Truncate Text
     */
    private function truncate($text, $maxLength) {
        return (strlen($text) <= $maxLength) ? $text : substr($text, 0, $maxLength - 3) . '...';
    }
}

// API Endpoint
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['customer_id']) || !isset($input['lead_id']) || !isset($input['reward_id'])) {
        echo json_encode(['success' => false, 'message' => 'customer_id, lead_id und reward_id erforderlich']);
        exit;
    }
    
    $service = new RewardEmailDeliveryService();
    $result = $service->sendRewardEmail($input['customer_id'], $input['lead_id'], $input['reward_id']);
    echo json_encode($result);
}
