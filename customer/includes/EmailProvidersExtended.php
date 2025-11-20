<?php
/**
 * EmailProviders Extension: Tag Creation
 * Erweitert alle Provider um Tag-Erstellung
 */

// Lade Original EmailProviders
require_once __DIR__ . '/EmailProviders.php';

/**
 * Erweiterte Quentn Provider Klasse
 */
class QuentnProviderExtended extends QuentnProvider {
    public function ensureTagExists(string $tagName): array {
        return $this->createTagIfNotExists($tagName);
    }
    
    protected function createTagIfNotExists(string $tagName): array {
        // Prüfe ob Tag existiert
        $result = $this->makeRequest(
            $this->baseUrl . '/terms?search=' . urlencode($tagName),
            'GET',
            null,
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success'] && !empty($result['response'])) {
            foreach ($result['response'] as $term) {
                if (isset($term['name']) && $term['name'] === $tagName) {
                    return [
                        'success' => true,
                        'created' => false,
                        'tag_id' => $term['id']
                    ];
                }
            }
        }
        
        // Tag erstellen
        $result = $this->makeRequest(
            $this->baseUrl . '/term',
            'POST',
            ['term' => ['name' => $tagName]],
            ['Authorization: Bearer ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'created' => true,
                'tag_id' => $result['response']['id'] ?? null
            ];
        }
        
        return ['success' => false, 'created' => false, 'error' => 'Tag-Erstellung fehlgeschlagen'];
    }
}

/**
 * Erweiterte ActiveCampaign Provider Klasse
 */
class ActiveCampaignProviderExtended extends ActiveCampaignProvider {
    public function ensureTagExists(string $tagName): array {
        return $this->createTagIfNotExists($tagName);
    }
    
    protected function createTagIfNotExists(string $tagName): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/api/3/tags',
            'POST',
            ['tag' => ['tag' => $tagName, 'tagType' => 'contact']],
            ['Api-Token: ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'created' => true,
                'tag_id' => $result['response']['tag']['id'] ?? null
            ];
        }
        
        if ($result['http_code'] === 422) {
            return ['success' => true, 'created' => false, 'tag_id' => null];
        }
        
        return ['success' => false, 'created' => false, 'error' => 'Tag-Erstellung fehlgeschlagen'];
    }
}

/**
 * Erweiterte Klick-Tipp Provider Klasse
 */
class KlickTippProviderExtended extends KlickTippProvider {
    public function ensureTagExists(string $tagName): array {
        return $this->createTagIfNotExists($tagName);
    }
    
    protected function createTagIfNotExists(string $tagName): array {
        // Klick-Tipp erstellt Tags automatisch bei Verwendung
        return [
            'success' => true,
            'created' => true,
            'tag_id' => $tagName
        ];
    }
}

/**
 * Erweiterte Brevo Provider Klasse
 */
class BrevoProviderExtended extends BrevoProvider {
    public function ensureTagExists(string $tagName): array {
        return $this->createTagIfNotExists($tagName);
    }
    
    protected function createTagIfNotExists(string $tagName): array {
        // Brevo verwendet Listen statt Tags - wir simulieren einfach Erfolg
        return [
            'success' => true,
            'created' => true,
            'tag_id' => null,
            'note' => 'Brevo verwendet Listen statt Tags'
        ];
    }
}

/**
 * Erweiterte GetResponse Provider Klasse
 */
class GetResponseProviderExtended extends GetResponseProvider {
    public function ensureTagExists(string $tagName): array {
        return $this->createTagIfNotExists($tagName);
    }
    
    protected function createTagIfNotExists(string $tagName): array {
        $result = $this->makeRequest(
            $this->baseUrl . '/tags',
            'POST',
            ['name' => $tagName, 'color' => '0066CC'],
            ['X-Auth-Token: api-key ' . $this->apiKey]
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'created' => true,
                'tag_id' => $result['response']['tagId'] ?? null
            ];
        }
        
        if ($result['http_code'] === 409) {
            return ['success' => true, 'created' => false, 'tag_id' => null];
        }
        
        return ['success' => false, 'created' => false, 'error' => 'Tag-Erstellung fehlgeschlagen'];
    }
}

/**
 * Erweiterte Factory mit neuen Provider-Klassen
 */
class EmailProviderFactoryExtended extends EmailProviderFactory {
    public static function create(string $provider, string $apiKey, array $config = []): EmailMarketingProvider {
        switch (strtolower($provider)) {
            case 'quentn':
                return new QuentnProviderExtended($apiKey, $config);
            case 'activecampaign':
                return new ActiveCampaignProviderExtended($apiKey, $config);
            case 'klicktipp':
            case 'klick-tipp':
                return new KlickTippProviderExtended($apiKey, $config);
            case 'brevo':
            case 'sendinblue':
                return new BrevoProviderExtended($apiKey, $config);
            case 'getresponse':
                return new GetResponseProviderExtended($apiKey, $config);
            default:
                throw new Exception('Unbekannter Provider: ' . $provider);
        }
    }
}
