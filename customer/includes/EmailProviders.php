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
        $this->baseUrl = $config['base_url'] ?? 'https://api.quentn.com/public/v1';
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

/**
 * KLICK-TIPP Provider
 * https://www.klick-tipp.com/handbuch/api
 */
class KlickTippProvider extends BaseEmailProvider {
    private $username;
    private $password;
    private $sessionId;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? $apiKey;
    }
    
    private function login(): bool {
        $connector = new KlickTippConnector();
        try {
            $connector->login($this->username, $this->password);
            $this->sessionId = $connector->getSessionId();
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function addContact(array $leadData, array $options = []): array {
        if (!$this->login()) {
            return ['success' => false, 'message' => 'Login fehlgeschlagen'];
        }
        
        try {
            $connector = new KlickTippConnector();
            $connector->setSessionId($this->sessionId);
            
            $subscriberId = $connector->subscribe(
                $leadData['email'],
                !empty($options['list_id']) ? $options['list_id'] : null,
                !empty($options['tags']) ? (array)$options['tags'] : []
            );
            
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu Klick-Tipp hinzugefügt',
                'contact_id' => $subscriberId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function addTag(string $email, string $tag): array {
        if (!$this->login()) {
            return ['success' => false, 'message' => 'Login fehlgeschlagen'];
        }
        
        try {
            $connector = new KlickTippConnector();
            $connector->setSessionId($this->sessionId);
            $connector->tag($email, $tag);
            
            return ['success' => true, 'message' => 'Tag hinzugefügt'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        return [
            'success' => false,
            'message' => 'Direkter Email-Versand über Klick-Tipp API nicht verfügbar'
        ];
    }
    
    public function testConnection(): array {
        if ($this->login()) {
            return [
                'success' => true,
                'message' => 'Verbindung zu Klick-Tipp erfolgreich'
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
            $connector = new KlickTippConnector();
            $connector->setSessionId($this->sessionId);
            $subscriber = $connector->get($email);
            
            return [
                'success' => true,
                'exists' => !empty($subscriber),
                'status' => 'active',
                'tags' => $subscriber['tags'] ?? []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'exists' => false];
        }
    }
}

/**
 * GETRESPONSE Provider
 * https://apidocs.getresponse.com/v3
 */
class GetResponseProvider extends BaseEmailProvider {
    private $baseUrl = 'https://api.getresponse.com/v3';
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'email' => $leadData['email'],
            'name' => ($leadData['first_name'] ?? '') . ' ' . ($leadData['last_name'] ?? ''),
            'campaign' => [
                'campaignId' => $options['campaign_id'] ?? $this->config['default_campaign_id']
            ]
        ];
        
        // Tags hinzufügen
        if (!empty($options['tags'])) {
            $data['tags'] = is_array($options['tags']) ? $options['tags'] : [$options['tags']];
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
            'message' => 'Fehler bei GetResponse: ' . ($result['response']['message'] ?? 'Unbekannter Fehler')
        ];
    }
    
    public function addTag(string $email, string $tag): array {
        // Zuerst Kontakt finden
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts?query[email]=' . urlencode($email),
            'GET',
            null,
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if (!$result['success'] || empty($result['response'])) {
            return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
        }
        
        $contactId = $result['response'][0]['contactId'];
        
        // Tag erstellen oder holen
        $tagResult = $this->makeRequest(
            $this->baseUrl . '/tags',
            'POST',
            ['name' => $tag],
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        $tagId = $tagResult['response']['tagId'] ?? null;
        
        if (!$tagId) {
            // Tag existiert bereits, suchen
            $searchResult = $this->makeRequest(
                $this->baseUrl . '/tags?query[name]=' . urlencode($tag),
                'GET',
                null,
                ['X-Auth-Token: api-key ' . $this->apiKey]
            );
            
            $tagId = $searchResult['response'][0]['tagId'] ?? null;
        }
        
        if (!$tagId) {
            return ['success' => false, 'message' => 'Tag konnte nicht erstellt werden'];
        }
        
        // Tag zu Kontakt hinzufügen
        $addResult = $this->makeRequest(
            $this->baseUrl . '/contacts/' . $contactId . '/tags',
            'POST',
            ['tagId' => $tagId],
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        return [
            'success' => $addResult['success'],
            'message' => $addResult['success'] ? 'Tag hinzugefügt' : 'Fehler beim Hinzufügen'
        ];
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        // GetResponse unterstützt Transaktional-Emails
        $data = [
            'recipients' => [
                'to' => [['email' => $email]]
            ],
            'content' => [
                'subject' => $subject,
                'html' => $body
            ]
        ];
        
        $result = $this->makeRequest(
            $this->baseUrl . '/transactional-emails',
            'POST',
            $data,
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Email erfolgreich versendet',
                'message_id' => $result['response']['messageId'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Email-Versand fehlgeschlagen: ' . ($result['response']['message'] ?? 'Unbekannter Fehler')
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
                'message' => 'Verbindung zu GetResponse erfolgreich',
                'details' => $result['response']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen'
        ];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts?query[email]=' . urlencode($email),
            'GET',
            null,
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response'])) {
            $contact = $result['response'][0];
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $contact['contactId'],
                'status' => $contact['activities']['subscription']['status'] ?? 'active',
                'tags' => array_map(function($t) { return $t['name']; }, $contact['tags'] ?? [])
            ];
        }
        
        return ['success' => true, 'exists' => false];
    }
}

/**
 * BREVO (ehemals Sendinblue) Provider
 * https://developers.brevo.com/docs
 */
class BrevoProvider extends BaseEmailProvider {
    private $baseUrl = 'https://api.brevo.com/v3';
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'email' => $leadData['email'],
            'attributes' => [
                'FIRSTNAME' => $leadData['first_name'] ?? '',
                'LASTNAME' => $leadData['last_name'] ?? ''
            ],
            'updateEnabled' => true
        ];
        
        // Listen hinzufügen
        if (!empty($options['list_id'])) {
            $data['listIds'] = is_array($options['list_id']) 
                ? $options['list_id'] 
                : [(int)$options['list_id']];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts',
            'POST',
            $data,
            ['api-key: ' . $this->apiKey]
        );
        
        if ($result['success'] || $result['http_code'] === 201) {
            return [
                'success' => true,
                'message' => 'Kontakt erfolgreich zu Brevo hinzugefügt',
                'contact_id' => $result['response']['id'] ?? $leadData['email']
            ];
        }
        
        // Wenn Kontakt bereits existiert, Listen aktualisieren
        if ($result['http_code'] === 400 && strpos($result['response']['message'] ?? '', 'already exists') !== false) {
            return $this->updateContact($leadData['email'], $options);
        }
        
        return [
            'success' => false,
            'message' => 'Fehler bei Brevo: ' . ($result['response']['message'] ?? 'Unbekannter Fehler')
        ];
    }
    
    private function updateContact(string $email, array $options): array {
        $data = ['updateEnabled' => true];
        
        if (!empty($options['list_id'])) {
            $data['listIds'] = is_array($options['list_id']) 
                ? $options['list_id'] 
                : [(int)$options['list_id']];
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts/' . urlencode($email),
            'PUT',
            $data,
            ['api-key: ' . $this->apiKey]
        );
        
        return [
            'success' => $result['success'] || $result['http_code'] === 204,
            'message' => $result['success'] ? 'Kontakt aktualisiert' : 'Fehler beim Aktualisieren'
        ];
    }
    
    public function addTag(string $email, string $tag): array {
        // Bei Brevo werden Tags als Attribute gespeichert
        $data = [
            'attributes' => [
                'TAGS' => [$tag]
            ]
        ];
        
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts/' . urlencode($email),
            'PUT',
            $data,
            ['api-key: ' . $this->apiKey]
        );
        
        return [
            'success' => $result['success'] || $result['http_code'] === 204,
            'message' => $result['success'] ? 'Tag hinzugefügt' : 'Fehler beim Hinzufügen'
        ];
    }
    
    public function sendEmail(string $email, string $subject, string $body, array $options = []): array {
        $data = [
            'to' => [['email' => $email]],
            'subject' => $subject,
            'htmlContent' => $body
        ];
        
        // Sender muss konfiguriert sein
        if (!empty($this->config['sender_email'])) {
            $data['sender'] = [
                'email' => $this->config['sender_email'],
                'name' => $this->config['sender_name'] ?? 'System'
            ];
        }
        
        // Template verwenden falls angegeben
        if (!empty($options['template_id'])) {
            $data['templateId'] = (int)$options['template_id'];
            unset($data['subject']);
            unset($data['htmlContent']);
        }
        
        $result = $this->makeRequest(
            $this->baseUrl . '/smtp/email',
            'POST',
            $data,
            ['api-key: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Email erfolgreich versendet',
                'message_id' => $result['response']['messageId'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Email-Versand fehlgeschlagen: ' . ($result['response']['message'] ?? 'Unbekannter Fehler')
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
                'message' => 'Verbindung zu Brevo erfolgreich',
                'details' => [
                    'email' => $result['response']['email'] ?? null,
                    'company' => $result['response']['companyName'] ?? null
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen'
        ];
    }
    
    public function getContactStatus(string $email): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/contacts/' . urlencode($email),
            'GET',
            null,
            ['api-key: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            $contact = $result['response'];
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $contact['id'] ?? $email,
                'status' => $contact['emailBlacklisted'] ? 'blacklisted' : 'active',
                'tags' => $contact['attributes']['TAGS'] ?? []
            ];
        }
        
        return ['success' => true, 'exists' => false];
    }
}

/**
 * ACTIVECAMPAIGN Provider
 * https://developers.activecampaign.com/reference
 */
class ActiveCampaignProvider extends BaseEmailProvider {
    private $baseUrl;
    
    public function __construct(string $apiKey, array $config = []) {
        parent::__construct($apiKey, $config);
        $this->baseUrl = $config['account_url'] ?? 'https://youraccounthere.api-us1.com';
    }
    
    public function addContact(array $leadData, array $options = []): array {
        $data = [
            'contact' => [
                'email' => $leadData['email'],
                'firstName' => $leadData['first_name'] ?? '',
                'lastName' => $leadData['last_name'] ?? ''
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
            
            // Liste hinzufügen wenn angegeben
            if ($contactId && !empty($options['list_id'])) {
                $this->addContactToList($contactId, $options['list_id']);
            }
            
            // Tags hinzufügen wenn angegeben
            if ($contactId && !empty($options['tags'])) {
                foreach ((array)$options['tags'] as $tag) {
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
            'message' => 'Fehler bei ActiveCampaign: ' . ($result['response']['message'] ?? 'Unbekannter Fehler')
        ];
    }
    
    private function addContactToList(int $contactId, $listId): void {
        $this->makeRequest(
            $this->baseUrl . '/api/3/contactLists',
            'POST',
            [
                'contactList' => [
                    'list' => $listId,
                    'contact' => $contactId,
                    'status' => 1
                ]
            ],
            ['Api-Token: ' . $this->apiKey]
        );
    }
    
    public function addTag(string $email, string $tag): array {
        // Tag erstellen oder ID holen
        $tagResult = $this->makeRequest(
            $this->baseUrl . '/api/3/tags',
            'POST',
            ['tag' => ['tag' => $tag, 'tagType' => 'contact']],
            ['Api-Token: ' . $this->apiKey]
        );
        
        $tagId = $tagResult['response']['tag']['id'] ?? null;
        
        if (!$tagId) {
            // Tag existiert bereits, suchen
            $searchResult = $this->makeRequest(
                $this->baseUrl . '/api/3/tags?search=' . urlencode($tag),
                'GET',
                null,
                ['Api-Token: ' . $this->apiKey]
            );
            
            $tagId = $searchResult['response']['tags'][0]['id'] ?? null;
        }
        
        if (!$tagId) {
            return ['success' => false, 'message' => 'Tag konnte nicht gefunden/erstellt werden'];
        }
        
        // Kontakt-ID holen
        $contactStatus = $this->getContactStatus($email);
        if (!$contactStatus['exists']) {
            return ['success' => false, 'message' => 'Kontakt nicht gefunden'];
        }
        
        // Tag zu Kontakt hinzufügen
        $result = $this->makeRequest(
            $this->baseUrl . '/api/3/contactTags',
            'POST',
            [
                'contactTag' => [
                    'contact' => $contactStatus['contact_id'],
                    'tag' => $tagId
                ]
            ],
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
            'message' => 'Direkter Email-Versand über ActiveCampaign API erfordert Kampagnen-Setup'
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
                'message' => 'Verbindung zu ActiveCampaign erfolgreich',
                'details' => $result['response']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Verbindung fehlgeschlagen'
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
            
            // Tags laden
            $tagsResult = $this->makeRequest(
                $this->baseUrl . '/api/3/contacts/' . $contact['id'] . '/contactTags',
                'GET',
                null,
                ['Api-Token: ' . $this->apiKey]
            );
            
            return [
                'success' => true,
                'exists' => true,
                'contact_id' => $contact['id'],
                'status' => 'active',
                'tags' => array_column($tagsResult['response']['contactTags'] ?? [], 'tag')
            ];
        }
        
        return ['success' => true, 'exists' => false];
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
                
            case 'klicktipp':
            case 'klick-tipp':
                return new KlickTippProvider($apiKey, $config);
                
            case 'getresponse':
                return new GetResponseProvider($apiKey, $config);
                
            case 'brevo':
            case 'sendinblue':
                return new BrevoProvider($apiKey, $config);
                
            case 'activecampaign':
                return new ActiveCampaignProvider($apiKey, $config);
                
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
                'config_fields' => ['base_url']
            ],
            'klicktipp' => [
                'name' => 'Klick-Tipp',
                'supports_direct_email' => false,
                'supports_tags' => true,
                'supports_campaigns' => false,
                'config_fields' => ['username', 'password']
            ],
            'getresponse' => [
                'name' => 'GetResponse',
                'supports_direct_email' => true,
                'supports_tags' => true,
                'supports_campaigns' => true,
                'config_fields' => ['default_campaign_id']
            ],
            'brevo' => [
                'name' => 'Brevo (Sendinblue)',
                'supports_direct_email' => true,
                'supports_tags' => true,
                'supports_campaigns' => false,
                'config_fields' => ['sender_email', 'sender_name']
            ],
            'activecampaign' => [
                'name' => 'ActiveCampaign',
                'supports_direct_email' => false,
                'supports_tags' => true,
                'supports_campaigns' => true,
                'config_fields' => ['account_url']
            ]
        ];
    }
}