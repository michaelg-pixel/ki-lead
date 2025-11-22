<?php
/**
 * Mailgun Email Service
 * Versendet Belohnungs-Emails ueber Mailgun API
 */

class MailgunService {
    private $config;
    private $apiKey;
    private $domain;
    
    public function __construct() {
        // Korrigierter Pfad: von mailgun/includes/ nach config/
        $configPath = __DIR__ . '/../../config/mailgun.php';
        if (!file_exists($configPath)) {
            throw new Exception("Mailgun Config nicht gefunden: {$configPath}");
        }
        $this->config = require $configPath;
        $this->apiKey = $this->config['api_key'];
        $this->domain = $this->config['api_endpoint'] . '/' . $this->config['domain'];
    }
    
    public function sendRewardEmail($lead, $reward, $customer) {
        $html = $this->loadTemplate('reward_unlocked', [
            'lead_name' => $lead['name'] ?? 'Lead',
            'customer_name' => $customer['company_name'] ?? 'Team',
            'reward_title' => $reward['title'] ?? '',
            'reward_description' => $reward['description'] ?? '',
            'reward_warning' => $reward['warning_text'] ?? '',
            'successful_referrals' => $lead['successful_referrals'] ?? 0,
            'current_points' => $lead['successful_referrals'] ?? 0,
            'referral_link' => $this->generateReferralLink($lead),
            'dashboard_link' => $this->generateDashboardLink($lead),
            'customer_impressum' => $customer['company_imprint_html'] ?? '',
            'unsubscribe_link' => $this->generateUnsubscribeLink($lead, $customer['id'])
        ]);
        
        return $this->sendEmail([
            'to' => $lead['email'],
            'subject' => '🎉 Glueckwunsch! Du hast eine Belohnung freigeschaltet',
            'html' => $html,
            'tags' => ['reward-unlocked'],
            'tracking' => true
        ]);
    }
    
    private function sendEmail($params) {
        $ch = curl_init();
        
        $postData = [
            'from' => $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'to' => $params['to'],
            'subject' => $params['subject'],
            'html' => $params['html']
        ];
        
        if (!empty($params['tags'])) {
            foreach ($params['tags'] as $tag) {
                $postData['o:tag'][] = $tag;
            }
        }
        
        if ($params['tracking'] ?? true) {
            $postData['o:tracking'] = 'yes';
            $postData['o:tracking-clicks'] = 'yes';
            $postData['o:tracking-opens'] = 'yes';
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->domain . '/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_USERPWD => 'api:' . $this->apiKey,
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode === 200 && isset($result['id'])) {
            return ['success' => true, 'message_id' => $result['id']];
        }
        
        return ['success' => false, 'error' => $result['message'] ?? 'Unknown error'];
    }
    
    private function loadTemplate($name, $vars) {
        $path = $this->config['template_path'] . '/' . $name . '.php';
        if (!file_exists($path)) {
            throw new Exception("Template nicht gefunden: $name");
        }
        ob_start();
        extract($vars);
        include $path;
        return ob_get_clean();
    }
    
    private function generateReferralLink($lead) {
        return 'https://app.mehr-infos-jetzt.de/freebie/index.php?id=' . 
               ($lead['freebie_id'] ?? '') . '&ref=' . ($lead['referral_code'] ?? '');
    }
    
    private function generateDashboardLink($lead) {
        return 'https://app.mehr-infos-jetzt.de/lead_dashboard.php?freebie=' . 
               ($lead['freebie_id'] ?? '');
    }
    
    private function generateUnsubscribeLink($lead, $customerId) {
        $token = base64_encode($lead['email'] . '|' . $customerId);
        return 'https://app.mehr-infos-jetzt.de/unsubscribe.php?token=' . $token;
    }
}
