<?php
/**
 * Email Marketing API Provider Interface
 * Erweitert um alle gängigen deutschen Email-Marketing-Provider
 */

interface EmailMarketingProvider {
    public function addContact(array $leadData, array $options = []): array;
    public function addTag(string $email, string $tag): array;
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array;
    public function testConnection(): array;
    public function getContactStatus(string $email): array;
}

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
 * API Dokumentation: https://docs.quentn.com/de/api-dokumentation/contact-api
 */
class QuentnProvider extends BaseEmailProvider {
    private $baseUrl;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        $this->baseUrl = $config['api_url'] ?? $config['base_url'] ?? '';
        $this->baseUrl = rtrim($this->baseUrl, '/');
        
        if (!empty($this->baseUrl) && !str_ends_with($this->baseUrl, '/V1')) {
            if (str_ends_with($this->baseUrl, '/v1')) {
                $this->baseUrl = substr($this->baseUrl, 0, -3) . '/V1';
            }
        }
    }
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'mail' => $leadData['email'],
            'first_name' => $leadData['first_name'] ?? '',
            'family_name' => $leadData['last_name'] ?? '',
        ];
        
        if (!empty($options['tags'])) {
            $data['terms'] = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contact',
            'POST',
            ['contact' => $data],
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
            'message' => 'Fehler bei Quentn: ' . ($result['response']['message'] ?? json_encode($result['response']))
        ];
    }
    
    public function addTag(string $email, string $tag): array {
        $contact = $this->getContactStatus($email);
        
        if (!$contact['exists']) {
            return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contact/' . $contact['contact_id'] . '/terms',
            'PUT',
            ['terms' => [$tag]],
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
        $result = $this->makeRequest(
            $this->baseUrl . '/users?limit=1',
            'GET',
            null,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => '✅ Verbindung zu Quentn erfolgreich! API-Key ist gültig.',
                'details' => ['api_url' => $this->baseUrl]
            ];
        }
        
        $errorMsg = 'Verbindung fehlgeschlagen';
        if (!empty($result['error'])) {
            $errorMsg .= ': ' . $result['error'];
        } elseif ($result['http_code'] === 401) {
            $errorMsg .= ': Ungültiger API-Key';
        } elseif ($result['http_code'] === 404) {
            $errorMsg .= ': API-URL nicht gefunden. Format: https://SYSTEM.SERVER.quentn.com/public/api/V1';
        }
        
        return ['success' => false, 'message' => $errorMsg];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/contact/' . urlencode($email),
            'GET',
            null,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response'])) {
            $contacts = is_array($result['response']) ? $result['response'] : [$result['response']];
            if (!empty($contacts[0])) {
                return [
                    'success' => true,
                    'exists' => true,
                    'contact_id' => $contacts[0]['id'],
                    'status' => 'active',
                    'tags' => $contacts[0]['terms'] ?? []
                ];
            }
        }
        
        return ['success' => true, 'exists' => false];
    }
}

/**
 * ACTIVECAMPAIGN Provider
 * API Dokumentation: https://developers.activecampaign.com/
 */
class ActiveCampaignProvider extends BaseEmailProvider {
    private $baseUrl;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        $this->baseUrl = rtrim($config['api_url'] ?? '', '/');
    }
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'contact' => [
                'email' => $leadData['email'],
                'firstName' => $leadData['first_name'] ?? '',
                'lastName' => $leadData['last_name'] ?? '',
            ]
        ];
        
        $result = $this->makeRequest(
            $this->baseUrl . '/api/3/contacts',
            'POST',
            $data,
            ['Api-Token: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            $contactId = $result['response']['contact']['id'] ?? null;
            
            // Tags hinzufügen wenn vorhanden
            if (!empty($options['tags']) && $contactId) {
                $tags = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
                foreach ($tags as $tag) {
                    $this->addTag($leadData['email'], $tag);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu ActiveCampaign hinzugefügt',
                'contact_id' => $contactId
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Fehler bei ActiveCampaign: ' . json_encode($result['response'])
        ];
    }
    
    public function addTag(string $email, string $tag): array {
        // Erst Kontakt suchen
        $contact = $this->getContactStatus($email);
        if (!$contact['exists']) {
            return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
        }
        
        // Tag erstellen/finden
        $tagResult = $this->makeRequest(
            $this->baseUrl . '/api/3/tags',
            'POST',
            ['tag' => ['tag' => $tag, 'tagType' => 'contact']],
            ['Api-Token: ' . $this->apiKey]
        );
        
        $tagId = $tagResult['response']['tag']['id'] ?? null;
        if (!$tagId) {
            return ['success' => false, 'message' => 'Tag konnte nicht erstellt werden'];
        }
        
        // Tag zu Kontakt hinzufügen
        $result = $this->makeRequest(
            $this->baseUrl . '/api/3/contactTags',
            'POST',
            ['contactTag' => ['contact' => $contact['contact_id'], 'tag' => $tagId]],
            ['Api-Token: ' . $this->apiKey]
        );
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Tag hinzugefügt' : 'Fehler beim Hinzufügen'
        ];
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        return [
            'success' => false,
            'message' => 'Direkter Email-Versand über ActiveCampaign API nicht implementiert'
        ];
    }
    
    public function testConnection(): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/api/3/users/me',
            'GET',
            null,
            ['Api-Token: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => '✅ Verbindung zu ActiveCampaign erfolgreich!',
                'details' => ['api_url' => $this->baseUrl]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen: ' . ($result['error'] ?? 'HTTP ' . $result['http_code'])
        ];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/api/3/contacts?email=' . urlencode($email),
            'GET',
            null,
            ['Api-Token: ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response']['contacts'])) {
            $contact = $result['response']['contacts'][0];
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $contact['id'],
                'status' => 'active'
            ];
        }
        
        return ['success' => true, 'exists' => false];
    }
}

/**
 * KLICK-TIPP Provider
 * API Dokumentation: https://www.klick-tipp.com/handbuch/api
 */
class KlickTippProvider extends BaseEmailProvider {
    private $baseUrl = 'https://api.klick-tipp.com';
    private $username;
    private $password;
    private $sessionId;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        $this->username = $config['username'] ?? '';
        $this->password = $apiKey; // Bei Klick-Tipp ist API-Key = Password
    }
    
    private function login(): bool {
        if ($this->sessionId) {
            return true;
        }
        
        $connector = new XmlRpcConnector($this->baseUrl);
        try {
            $this->sessionId = $connector->login($this->username, $this->password);
            return !empty($this->sessionId);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function addContact(array $leadData, array $options = []): array {
        if (!$this->login()) {
            return ['success' => false, 'message' => 'Login fehlgeschlagen: ' . $this->lastError];
        }
        
        try {
            $connector = new XmlRpcConnector($this->baseUrl);
            $contactId = $connector->subscribe(
                $this->sessionId,
                $leadData['email'],
                [
                    'fieldFirstName' => $leadData['first_name'] ?? '',
                    'fieldLastName' => $leadData['last_name'] ?? ''
                ],
                $options['list_id'] ?? null
            );
            
            // Tags hinzufügen
            if (!empty($options['tags']) && $contactId) {
                $tags = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
                foreach ($tags as $tag) {
                    $connector->tag($this->sessionId, $leadData['email'], $tag);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu Klick-Tipp hinzugefügt',
                'contact_id' => $contactId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
        }
    }
    
    public function addTag(string $email, string $tag): array {
        if (!$this->login()) {
            return ['success' => false, 'message' => 'Login fehlgeschlagen'];
        }
        
        try {
            $connector = new XmlRpcConnector($this->baseUrl);
            $connector->tag($this->sessionId, $email, $tag);
            return ['success' => true, 'message' => 'Tag hinzugefügt'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
        }
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        return [
            'success' => false,
            'message' => 'Direkter Email-Versand über Klick-Tipp nicht verfügbar'
        ];
    }
    
    public function testConnection(): array {
        if ($this->login()) {
            return [
                'success' => true,
                'message' => '✅ Verbindung zu Klick-Tipp erfolgreich!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen: ' . $this->lastError
        ];
    }
    
    public function getContactStatus(string $email): array {
        if (!$this->login()) {
            return ['success' => false, 'exists' => false];
        }
        
        try {
            $connector = new XmlRpcConnector($this->baseUrl);
            $subscriber = $connector->get($this->sessionId, $email);
            
            return [
                'success' => true,
                'exists' => !empty($subscriber),
                'contact_id' => $subscriber['id'] ?? null,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'exists' => false];
        }
    }
}

/**
 * BREVO (Sendinblue) Provider
 * API Dokumentation: https://developers.brevo.com/
 */
class BrevoProvider extends BaseEmailProvider {
    private $baseUrl = 'https://api.brevo.com/v3';
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'email' => $leadData['email'],
            'attributes' => [
                'FIRSTNAME' => $leadData['first_name'] ?? '',
                'LASTNAME' => $leadData['last_name'] ?? ''
            ]
        ];
        
        if (!empty($options['list_id'])) {
            $data['listIds'] = [intval($options['list_id'])];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts',
            'POST',
            $data,
            ['api-key: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu Brevo hinzugefügt',
                'contact_id' => $result['response']['id'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Fehler bei Brevo: ' . json_encode($result['response'])
        ];
    }
    
    public function addTag(string $email, string $tag): array {
        // Brevo verwendet Listen und Attribute statt Tags
        return [
            'success' => false,
            'message' => 'Brevo verwendet Listen statt Tags. Bitte in den Kontakt-Attributen speichern.'
        ];
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        $data = [
            'to' => [['email' => $email]],
            'sender' => [
                'email' => $options['sender_email'] ?? 'noreply@example.com',
                'name' => $options['sender_name'] ?? 'System'
            ],
            'subject' => $subject,
            'htmlContent' => $body
        ];
        
        $result = $this->makeRequest(
            $this->baseUrl . '/smtp/email',
            'POST',
            $data,
            ['api-key: ' . $this->apiKey]
        );
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Email gesendet' : 'Fehler beim Senden'
        ];
    }
    
    public function testConnection(): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/account',
            'GET',
            null,
            ['api-key: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => '✅ Verbindung zu Brevo erfolgreich!',
                'details' => [
                    'email' => $result['response']['email'] ?? null
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen: HTTP ' . $result['http_code']
        ];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts/' . urlencode($email),
            'GET',
            null,
            ['api-key: ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response'])) {
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $result['response']['id'] ?? null,
                'status' => 'active'
            ];
        }
        
        return ['success' => true, 'exists' => false];
    }
}

/**
 * GETRESPONSE Provider
 * API Dokumentation: https://apidocs.getresponse.com/v3/
 */
class GetResponseProvider extends BaseEmailProvider {
    private $baseUrl = 'https://api.getresponse.com/v3';
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'email' => $leadData['email'],
            'name' => trim(($leadData['first_name'] ?? '') . ' ' . ($leadData['last_name'] ?? '')),
            'campaign' => ['campaignId' => $options['list_id'] ?? '']
        ];
        
        if (!empty($options['tags'])) {
            $tags = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
            $data['tags'] = array_map(function($tag) {
                return ['name' => $tag];
            }, $tags);
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts',
            'POST',
            $data,
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu GetResponse hinzugefügt',
                'contact_id' => $result['response']['contactId'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Fehler bei GetResponse: ' . json_encode($result['response'])
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
            ['tags' => [['name' => $tag]]],
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Tag hinzugefügt' : 'Fehler beim Hinzufügen'
        ];
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        return [
            'success' => false,
            'message' => 'Direkter Email-Versand über GetResponse nicht implementiert'
        ];
    }
    
    public function testConnection(): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/accounts',
            'GET',
            null,
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => '✅ Verbindung zu GetResponse erfolgreich!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen: HTTP ' . $result['http_code']
        ];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts?query[email]=' . urlencode($email),
            'GET',
            null,
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response'][0])) {
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $result['response'][0]['contactId'],
                'status' => 'active'
            ];
        }
        
        return ['success' => true, 'exists' => false];
    }
}

/**
 * Simple XML-RPC Connector für Klick-Tipp
 */
class XmlRpcConnector {
    private $baseUrl;
    
    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function login($username, $password) {
        $params = [$username, $password];
        $result = $this->call('login', $params);
        return $result['session_id'] ?? null;
    }
    
    public function subscribe($sessionId, $email, $fields = [], $listId = null) {
        $params = [$sessionId, $email, $fields, $listId];
        return $this->call('subscribe', $params);
    }
    
    public function tag($sessionId, $email, $tag) {
        $params = [$sessionId, $email, $tag];
        return $this->call('tag', $params);
    }
    
    public function get($sessionId, $email) {
        $params = [$sessionId, $email];
        return $this->call('get', $params);
    }
    
    private function call($method, $params) {
        $request = xmlrpc_encode_request($method, $params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }
        
        $result = xmlrpc_decode($response);
        
        if (is_array($result) && xmlrpc_is_fault($result)) {
            throw new Exception($result['faultString']);
        }
        
        return $result;
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
            case 'activecampaign':
                return new ActiveCampaignProvider($apiKey, $config);
            case 'klicktipp':
            case 'klick-tipp':
                return new KlickTippProvider($apiKey, $config);
            case 'brevo':
            case 'sendinblue':
                return new BrevoProvider($apiKey, $config);
            case 'getresponse':
                return new GetResponseProvider($apiKey, $config);
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
            ],
            'activecampaign' => [
                'name' => 'ActiveCampaign',
                'supports_direct_email' => false,
                'supports_tags' => true,
                'supports_campaigns' => true,
                'config_fields' => ['api_url']
            ],
            'klicktipp' => [
                'name' => 'Klick-Tipp',
                'supports_direct_email' => false,
                'supports_tags' => true,
                'supports_campaigns' => false,
                'config_fields' => ['username']
            ],
            'brevo' => [
                'name' => 'Brevo (Sendinblue)',
                'supports_direct_email' => true,
                'supports_tags' => false,
                'supports_campaigns' => true,
                'config_fields' => []
            ],
            'getresponse' => [
                'name' => 'GetResponse',
                'supports_direct_email' => false,
                'supports_tags' => true,
                'supports_campaigns' => true,
                'config_fields' => []
            ]
        ];
    }
}
