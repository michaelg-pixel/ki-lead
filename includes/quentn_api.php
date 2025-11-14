<?php
/**
 * Quentn API Integration für Passwort-Reset E-Mails
 * Nutzt Custom Fields und Campaign Trigger
 * 
 * WICHTIG: Quentn API nutzt SINGULAR Endpoints:
 * - /contact (nicht /contacts)
 * - /contact/<email> (Suche nach E-Mail)
 * - /contact/<id>/terms (Tags setzen, nicht /tags)
 */

require_once __DIR__ . '/../config/quentn_config.php';

/**
 * Sendet eine Passwort-Reset E-Mail über Quentn
 * 
 * @param string $toEmail Empfänger E-Mail
 * @param string $toName Empfänger Name
 * @param string $resetLink Der Reset-Link
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    try {
        // 1. Kontakt in Quentn finden oder erstellen
        $contact = findOrCreateContact($toEmail, $toName);
        
        if (!$contact['success']) {
            return $contact; // Fehler zurückgeben
        }
        
        $contactId = $contact['contact_id'];
        
        // 2. Custom Field mit Reset-Link setzen
        $updateResult = updateContactWithResetLink($contactId, $resetLink);
        
        if (!$updateResult['success']) {
            return $updateResult;
        }
        
        // 3. Tag "Kunde-OptinPilot" setzen um Campaign zu triggern
        $tagResult = addTagToContact($contactId, 'Kunde-OptinPilot');
        
        if (!$tagResult['success']) {
            error_log("Warning: Tag 'Kunde-OptinPilot' could not be added: " . $tagResult['message']);
        }
        
        error_log("Password reset email triggered via Quentn for: $toEmail");
        
        return [
            'success' => true,
            'message' => 'E-Mail wird über Quentn versendet'
        ];
        
    } catch (Exception $e) {
        error_log("Quentn API error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Fehler beim E-Mail-Versand: ' . $e->getMessage()
        ];
    }
}

/**
 * Findet einen Kontakt oder erstellt einen neuen
 */
function findOrCreateContact($email, $name) {
    // 1. Versuche Kontakt zu finden via E-Mail
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
    
    // Kontakt gefunden?
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        
        // Quentn gibt Array zurück, auch wenn nur 1 Kontakt
        if (!empty($data) && is_array($data)) {
            $firstContact = is_array($data[0]) ? $data[0] : $data;
            if (isset($firstContact['id'])) {
                return [
                    'success' => true,
                    'contact_id' => $firstContact['id']
                ];
            }
        }
    }
    
    // 2. Kontakt erstellen, wenn nicht gefunden
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0] ?? $name;
    $lastName = $nameParts[1] ?? '';
    
    $contactData = [
        'contact' => [
            'mail' => $email,
            'first_name' => $firstName,
            'family_name' => $lastName,
        ],
        'skip_double_opt_in' => true
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
        
        if (isset($data['id'])) {
            return [
                'success' => true,
                'contact_id' => $data['id']
            ];
        }
    }
    
    error_log("Quentn contact creation failed: HTTP $httpCode - $response");
    return [
        'success' => false,
        'message' => 'Kontakt konnte nicht erstellt werden'
    ];
}

/**
 * Aktualisiert Kontakt mit Reset-Link im Custom Field
 */
function updateContactWithResetLink($contactId, $resetLink) {
    // PUT /contact/<id>
    $updateData = [
        'reset_link' => $resetLink
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
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'message' => 'Kontakt aktualisiert'
        ];
    }
    
    error_log("Quentn contact update failed: HTTP $httpCode - $response");
    return [
        'success' => false,
        'message' => 'Custom Field konnte nicht gesetzt werden'
    ];
}

/**
 * Fügt Tag zu Kontakt hinzu (triggert Campaign)
 * POST /contact/<id>/terms
 * 
 * Tag "Kunde-OptinPilot" hat die ID 790
 */
function addTagToContact($contactId, $tagName) {
    // Direkt mit bekannter Tag-ID für "Kunde-OptinPilot"
    if ($tagName === 'Kunde-OptinPilot') {
        $tagId = 790;
    } else {
        // Für andere Tags: ID suchen
        $tagId = findTagId($tagName);
        
        if (!$tagId) {
            // Tag erstellen wenn nicht vorhanden
            $tagId = createTag($tagName);
        }
    }
    
    if (!$tagId) {
        return [
            'success' => false,
            'message' => 'Tag konnte nicht gefunden oder erstellt werden'
        ];
    }
    
    // POST /contact/<id>/terms mit Array von IDs
    $ch = curl_init(QUENTN_API_BASE_URL . '/' . $contactId . '/terms');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([$tagId]),
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
        return [
            'success' => true,
            'message' => 'Tag hinzugefügt'
        ];
    }
    
    error_log("Quentn tag addition failed: HTTP $httpCode - $response");
    return [
        'success' => false,
        'message' => 'Tag konnte nicht gesetzt werden'
    ];
}

/**
 * Findet Tag-ID anhand des Tag-Namens
 */
function findTagId($tagName) {
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
        $tags = json_decode($response, true);
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if (isset($tag['name']) && $tag['name'] === $tagName) {
                    return $tag['id'];
                }
            }
        }
    }
    
    return null;
}

/**
 * Erstellt einen neuen Tag
 */
function createTag($tagName) {
    // POST /terms
    $termsUrl = str_replace('/contact', '/terms', QUENTN_API_BASE_URL);
    $ch = curl_init($termsUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'name' => $tagName,
            'description' => 'Auto-created for password reset'
        ]),
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
        return $data['id'] ?? null;
    }
    
    return null;
}

/**
 * Rate Limiting für Passwort-Reset Anfragen
 * Max 3 Anfragen pro E-Mail pro Stunde
 */
function checkPasswordResetRateLimit($pdo, $email) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE email = ? 
            AND password_reset_token IS NOT NULL 
            AND password_reset_expires > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['count'] < 3);
        
    } catch (Exception $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return true;
    }
}
