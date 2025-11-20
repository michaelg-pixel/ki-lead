<?php
/**
 * Email Marketing API Provider Interface
 * Zentrale Schnittstelle für alle Email-Marketing-Anbieter
 */

interface EmailMarketingProvider {
    /**
     * Lead zu Liste/Tag hinzufügen
     * 
     * @param array $leadData ['email', 'name', 'custom_fields']
     * @param array $options ['list_id', 'tags', 'campaign_id']
     * @return array ['success' => bool, 'message' => string, 'contact_id' => mixed]
     */
    public function addContact(array $leadData, array $options = []): array;
    
    /**
     * Tag zu Kontakt hinzufügen
     * 
     * @param string $email
     * @param string $tag
     * @return array ['success' => bool, 'message' => string]
     */
    public function addTag(string $email, string $tag): array;
    
    /**
     * Email an Kontakt senden
     * 
     * @param string $email
     * @param string $subject
     * @param string $body
     * @param array $options ['template_id', 'attachments']
     * @return array ['success' => bool, 'message' => string, 'message_id' => string]
     */
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array;
    
    /**
     * API-Verbindung testen
     * 
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(): array;
    
    /**
     * Kontakt-Status prüfen
     * 
     * @param string $email
     * @return array ['success' => bool, 'exists' => bool, 'status' => string, 'tags' => array]
     */
    public function getContactStatus(string $email): array;
}

/**
 * Basis-Klasse für alle Provider
 */
abstract class BaseEmailProvider implements EmailMarketingProvider {
    protected $apiKey;
    protected $config;
    protected $lastError;
    
    public function __construct(string $apiKey, array $config = []) {
        $this->apiKey = $apiKey;
        $this->config = $config;
    }
    
    /**
     * HTTP Request ausführen
     */
    protected function makeRequest(string $url, string $method = 'GET', array $data = null, array $headers = []): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        if ($data !== null) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            $this->lastError = $error;
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => $error,
                'response' => null
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => json_decode($response, true) ?? $response
        ];
    }
    
    public function getLastError(): ?string {
        return $this->lastError;
    }
}

/**
 * QUENTN Provider
 * https://help.quentn.com/hc/de/articles/4405815323537
 */
class QuentnProvider extends BaseEmailProvider {
    private $baseUrl;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        // Unterstütze beide Varianten: api_url (vom Frontend) und base_url (Legacy)
        $this->baseUrl = $config['api_url'] ?? $config['base_url'] ?? 'https://api.quentn.com/public/v1';
        
        // Entferne trailing slash
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'email' => $leadData['email'],
            'first_name' => $leadData['first_name'] ?? '',
            'last_name' => $leadData['last_name'] ?? '',
        ];
        
        // Tags hinzufügen
        if (!empty($options['tags'])) {
            $data['tags'] = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
        }
        
        // Campaign zuordnen
        if (!empty($options['campaign_id'])) {
            $data['campaign_id'] = $options['campaign_id'];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts',
            'POST',
            $data,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu Quentn hinzugefügt',
                'contact_id' => $result['response']['id'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Fehler bei Quentn: ' . ($result['response']['message'] ?? 'Unbekannter Fehler')
        ];
    }
    
    public function addTag(string $email, string $tag): array {
        // Zuerst Kontakt-ID holen
        $contact = $this->getContactStatus($email);
        
        if (!$contact['exists']) {
            return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts/' . $contact['contact_id'] . '/tags',
            'POST',
            ['tag' => $tag],
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Tag hinzugefügt' : 'Fehler beim Hinzufügen des Tags'
        ];
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        // Quentn sendet Emails über Kampagnen, nicht direkt
        return [
            'success' => false,
            'message' => 'Direkter Email-Versand über Quentn API nicht verfügbar. Bitte Kampagne verwenden.'
        ];
    }
    
    public function testConnection(): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/account',
            'GET',
            null,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Verbindung zu Quentn erfolgreich',
                'details' => $result['response']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen: ' . ($result['error'] ?? 'Unbekannter Fehler')
        ];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts?email=' . urlencode($email),
            'GET',
            null,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response']['data'])) {
            $contact = $result['response']['data'][0];
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $contact['id'],
                'status' => $contact['status'] ?? 'active',
                'tags' => $contact['tags'] ?? []
            ];
        }
        
        return [
            'success' => true,
            'exists' => false
        ];
    }
}

// ... Rest des Codes bleibt unverändert (KlickTipp, GetResponse, Brevo, ActiveCampaign Provider)
