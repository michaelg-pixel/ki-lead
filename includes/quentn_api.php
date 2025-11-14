<?php
/**
 * Quentn API Integration für Passwort-Reset E-Mails
 * Nutzt Custom Fields und Campaign Trigger
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
        
        // 3. Tag setzen um Campaign zu triggern
        $tagResult = addTagToContact($contactId, 'password-reset');
        
        if (!$tagResult['success']) {
            error_log("Warning: Tag could not be added, but contact updated: " . $tagResult['message']);
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
    // 1. Versuche Kontakt zu finden
    $ch = curl_init(QUENTN_API_BASE_URL . 'contacts?email=' . urlencode($email));
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
        
        if (!empty($data) && isset($data[0]['id'])) {
            return [
                'success' => true,
                'contact_id' => $data[0]['id']
            ];
        }
    }
    
    // 2. Kontakt erstellen, wenn nicht gefunden
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0] ?? $name;
    $lastName = $nameParts[1] ?? '';
    
    $contactData = [
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'skip_double_opt_in' => true // Wichtig für Transaktions-E-Mails
    ];
    
    $ch = curl_init(QUENTN_API_BASE_URL . 'contacts');
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
    $updateData = [
        'custom_fields' => [
            'reset_link' => $resetLink
        ]
    ];
    
    $ch = curl_init(QUENTN_API_BASE_URL . 'contacts/' . $contactId);
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
 */
function addTagToContact($contactId, $tagName) {
    $tagData = [
        'tags' => [$tagName]
    ];
    
    $ch = curl_init(QUENTN_API_BASE_URL . 'contacts/' . $contactId . '/tags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($tagData),
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
