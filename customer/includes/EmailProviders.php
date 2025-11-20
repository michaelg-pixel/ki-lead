<?php
/**
 * Email Marketing API Provider Interface
 * Zentrale Schnittstelle für alle Email-Marketing-Anbieter
 */

interface EmailMarketingProvider {
    public function addContact(array $leadData, array $options = []): array;
    public function addTag(string $email, string $tag): array;
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array;
    public function testConnection(): array;
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
 */
class QuentnProvider extends BaseEmailProvider {
    private $baseUrl;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        // FIX: Unterstütze beide Varianten: api_url (vom Frontend) und base_url (Legacy)
        $this->baseUrl = $config['api_url'] ?? $config['base_url'] ?? 'https://api.quentn.com/public/v1';
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'email' => $leadData['email'],
            'first_name' => $leadData['first_name'] ?? '',
            'last_name' => $leadData['last_name'] ?? '',
        ];
        
        if (!empty($options['tags'])) {
            $data['tags'] = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
        }
        
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
        return [
            'success' => false,
            'message' => 'Direkter Email-Versand über Quentn API nicht verfügbar. Bitte Kampagne verwenden.'
        ];
    }
    
    public function testConnection(): array {
        // FIX: Teste die Verbindung durch Abrufen der Contacts-Liste (limit=1)
        // Quentn hat keinen /account Endpoint
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts?limit=1',
            'GET',
            null,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Verbindung zu Quentn erfolgreich! API-Key ist gültig.',
                'details' => [
                    'api_url' => $this->baseUrl,
                    'contacts_found' => isset($result['response']['data']) ? count($result['response']['data']) : 0
                ]
            ];
        }
        
        // Detaillierte Fehlermeldung
        $errorMsg = 'Verbindung fehlgeschlagen';
        if (!empty($result['error'])) {
            $errorMsg .= ': ' . $result['error'];
        } elseif (!empty($result['response']['message'])) {
            $errorMsg .= ': ' . $result['response']['message'];
        } elseif ($result['http_code'] === 401) {
            $errorMsg .= ': Ungültiger API-Key';
        } elseif ($result['http_code'] === 403) {
            $errorMsg .= ': Zugriff verweigert';
        } elseif ($result['http_code'] === 404) {
            $errorMsg .= ': API-URL nicht gefunden. Bitte überprüfe deine API-URL.';
        } else {
            $errorMsg .= ': HTTP ' . ($result['http_code'] ?? 'N/A');
        }
        
        return [
            'success' => false,
            'message' => $errorMsg
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

/**
 * Factory für Provider-Instanzen
 */
class EmailProviderFactory {
    public static function create(string $provider, string $apiKey, array $config = []): EmailMarketingProvider {
        switch (strtolower($provider)) {
            case 'quentn':
                return new QuentnProvider($apiKey, $config);
            default:
                throw new Exception('Unbekannter Provider: ' . $provider);
        }
    }
    
    public static function getSupportedProviders(): array {
        return [
            'quentn' => [
                'name' => 'Quentn',
                'supports_direct_email' => false,
                'supports_tags' => true,
                'supports_campaigns' => true,
                'config_fields' => ['api_url']
            ]
        ];
    }
}
