<?php
/**
 * Quentn Helper Functions
 * Zentrale Funktionen für Quentn API Integration
 * 
 * API Dokumentation: https://docs.quentn.com/en/api-dokumentation/contact-api
 */

require_once __DIR__ . '/../config/quentn_config.php';

/**
 * Erstellt oder updated einen Kontakt in Quentn bei Registrierung
 * 
 * @param string $email E-Mail Adresse
 * @param string $firstName Vorname
 * @param string $lastName Nachname  
 * @param array $tags Array von Tag-Namen (optional)
 * @return bool Success
 */
function quentnCreateContact($email, $firstName, $lastName, $tags = []) {
    try {
        // Kontakt-Daten mit korrekter Quentn API Struktur
        $contactData = [
            'contact' => [
                'mail' => $email,
                'first_name' => $firstName,
                'family_name' => $lastName,
            ],
            'skip_double_opt_in' => true,
        ];
        
        // POST /contact
        $ch = curl_init(QUENTN_API_BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($contactData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            $contactId = $data['id'] ?? null;
            
            if ($contactId && !empty($tags)) {
                // Füge Tags hinzu
                quentnAddTagsByName($contactId, $tags);
            }
            
            error_log("Quentn: Contact created/updated for $email (ID: $contactId)");
            return true;
        } else {
            error_log("Quentn: Failed to create contact for $email - HTTP $httpCode - $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Quentn API error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fügt Tags (als Namen) zu einem Kontakt hinzu
 * Konvertiert Tag-Namen automatisch zu IDs
 */
function quentnAddTagsByName($contactId, $tagNames) {
    try {
        // Hole alle verfügbaren Tags
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tagId = quentnGetTagIdByName($tagName);
            if ($tagId) {
                $tagIds[] = $tagId;
            }
        }
        
        if (empty($tagIds)) {
            error_log("Quentn: No valid tags found for: " . implode(', ', $tagNames));
            return false;
        }
        
        // POST /contact/<id>/terms
        $ch = curl_init(QUENTN_API_BASE_URL . '/' . $contactId . '/terms');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($tagIds),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode >= 200 && $httpCode < 300);
        
    } catch (Exception $e) {
        error_log("Quentn: Error adding tags - " . $e->getMessage());
        return false;
    }
}

/**
 * Findet Tag-ID anhand des Namens
 */
function quentnGetTagIdByName($tagName) {
    static $tagsCache = null;
    
    // Lade Tags nur einmal
    if ($tagsCache === null) {
        $tagsCache = quentnGetAllTags();
    }
    
    if (!is_array($tagsCache)) {
        return null;
    }
    
    foreach ($tagsCache as $tag) {
        if (isset($tag['name']) && strtolower($tag['name']) === strtolower($tagName)) {
            return $tag['id'];
        }
    }
    
    return null;
}

/**
 * Holt alle verfügbaren Tags aus Quentn
 */
function quentnGetAllTags() {
    try {
        // GET /terms
        $termsUrl = str_replace('/contact', '/terms', QUENTN_API_BASE_URL);
        $ch = curl_init($termsUrl . '?limit=1000');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return json_decode($response, true);
        }
        
        return [];
        
    } catch (Exception $e) {
        error_log("Quentn: Error fetching tags - " . $e->getMessage());
        return [];
    }
}

/**
 * Findet Kontakt by E-Mail
 * @return array|null ['id' => int, 'first_name' => string, ...]
 */
function quentnFindContactByEmail($email) {
    try {
        // GET /contact/<email>
        $ch = curl_init(QUENTN_API_BASE_URL . '/' . urlencode($email));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            
            // API gibt Array zurück
            if (!empty($data) && is_array($data)) {
                return is_array($data[0]) ? $data[0] : $data;
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Quentn: Error finding contact - " . $e->getMessage());
        return null;
    }
}

/**
 * Updated Custom Field bei einem Kontakt
 */
function quentnUpdateCustomField($contactId, $fieldName, $fieldValue) {
    try {
        // PUT /contact/<id>
        $updateData = [
            $fieldName => $fieldValue
        ];
        
        $ch = curl_init(QUENTN_API_BASE_URL . '/' . $contactId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($updateData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode >= 200 && $httpCode < 300);
        
    } catch (Exception $e) {
        error_log("Quentn: Error updating custom field - " . $e->getMessage());
        return false;
    }
}
