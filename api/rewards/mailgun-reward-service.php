<?php
/**
 * Mailgun Reward Email Integration
 * 
 * Versendet Belohnungs-Emails über Mailgun mit automatischem Impressum
 * Wird als Fallback verwendet wenn kein E-Mail-Marketing-Tool konfiguriert ist
 */

require_once __DIR__ . '/../../mailgun/includes/MailgunService.php';

class MailgunRewardService {
    
    private $mailgun;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        try {
            $this->mailgun = new MailgunService();
        } catch (Exception $e) {
            error_log("❌ Mailgun init error: " . $e->getMessage());
            throw new Exception("Mailgun konnte nicht initialisiert werden: " . $e->getMessage());
        }
    }
    
    /**
     * Sendet Belohnungs-Email via Mailgun
     * 
     * @param int $leadId Lead User ID
     * @param int $rewardId Reward Definition ID
     * @return array ['success' => bool, 'message' => string, 'message_id' => string]
     */
    public function sendRewardEmail($leadId, $rewardId) {
        try {
            // Lead-Daten laden
            $lead = $this->getLeadData($leadId);
            if (!$lead) {
                throw new Exception("Lead nicht gefunden");
            }
            
            // Reward-Daten laden
            $reward = $this->getRewardData($rewardId);
            if (!$reward) {
                throw new Exception("Belohnung nicht gefunden");
            }
            
            // Customer-Daten laden (inkl. Impressum!)
            $customer = $this->getCustomerData($lead['user_id']);
            if (!$customer) {
                throw new Exception("Kunde nicht gefunden");
            }
            
            // Impressum-Check
            if (empty($customer['company_imprint_html'])) {
                throw new Exception("Kein Impressum hinterlegt - bitte zuerst Impressum im Dashboard anlegen");
            }
            
            // Mailgun E-Mail versenden
            $result = $this->mailgun->sendRewardEmail($lead, [
                'title' => $reward['reward_title'],
                'description' => $reward['reward_description'] ?? '',
                'warning_text' => $reward['reward_warning'] ?? ''
            ], $customer);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Belohnungs-Email erfolgreich über Mailgun versendet',
                    'message_id' => $result['message_id'] ?? null,
                    'method' => 'mailgun',
                    'provider' => 'Mailgun EU'
                ];
            } else {
                throw new Exception($result['error'] ?? 'Unbekannter Mailgun-Fehler');
            }
            
        } catch (Exception $e) {
            error_log("❌ Mailgun Reward Email Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Mailgun-Fehler: ' . $e->getMessage(),
                'method' => 'mailgun',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Lädt Lead-Daten
     */
    private function getLeadData($leadId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                name,
                email,
                user_id,
                freebie_id,
                referral_code,
                successful_referrals,
                total_referrals
            FROM lead_users 
            WHERE id = ?
        ");
        $stmt->execute([$leadId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lädt Reward-Definition
     */
    private function getRewardData($rewardId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                tier_name,
                tier_level,
                reward_title,
                reward_description,
                reward_type,
                reward_value,
                required_referrals,
                reward_warning
            FROM reward_definitions 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$rewardId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lädt Customer-Daten inkl. Impressum
     */
    private function getCustomerData($customerId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                company_name,
                company_email,
                company_imprint_html
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
