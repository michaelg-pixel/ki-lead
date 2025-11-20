<?php
/**
 * Quentn API Integration für Empfehlungsprogramm
 * Setzt Tags und Custom Fields wenn Belohnungen erreicht werden
 * Version: 1.0
 */

// Quentn Konfiguration laden
require_once __DIR__ . '/../config/quentn_config.php';

// Basis-URL für alle API-Calls (ohne /contact am Ende)
define('QUENTN_API_BASE', 'https://pk1bh1.eu-1.quentn.com/public/api/V1');

/**
 * Sendet einen API-Request an Quentn
 */
function quentnApiRequest($endpoint, $method = 'GET', $data = null) {
    $url = QUENTN_API_BASE . $endpoint;
    
    $headers = [
        'Authorization: Bearer ' . QUENTN_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Quentn API Error: " . $error);
        return ['success' => false, 'error' => $error];
    }
    
    if ($httpCode >= 400) {
        error_log("Quentn API HTTP Error: " . $httpCode . " - " . $response);
        return ['success' => false, 'error' => 'HTTP ' . $httpCode, 'response' => $response];
    }
    
    $decoded = json_decode($response, true);
    return $decoded ?? ['success' => false, 'error' => 'Invalid JSON response'];
}

/**
 * Findet einen Kontakt in Quentn anhand der Email
 */
function quentnFindContact($email) {
    $response = quentnApiRequest('/contact?email=' . urlencode($email));
    
    if (isset($response['contact']) && is_array($response['contact'])) {
        return $response['contact'];  // Quentn V1 gibt einzelnen Kontakt zurück
    }
    
    return null;
}

/**
 * Setzt einen Tag für einen Kontakt
 */
function quentnSetTag($contactId, $tagName) {
    $endpoint = '/contact/' . $contactId . '/tags';
    
    $data = [
        'tags' => [$tagName]
    ];
    
    $response = quentnApiRequest($endpoint, 'POST', $data);
    
    return isset($response['success']) ? $response['success'] : true;  // Bei erfolg ist manchmal keine Antwort
}

/**
 * Aktualisiert Custom Fields eines Kontakts
 */
function quentnUpdateCustomFields($contactId, $fields) {
    $endpoint = '/contact/' . $contactId;
    
    // Quentn erwartet Custom Fields im Format "field_NAME"
    $customFields = [];
    foreach ($fields as $key => $value) {
        $customFields['field_' . $key] = (string)$value;
    }
    
    $data = [
        'custom_fields' => $customFields
    ];
    
    $response = quentnApiRequest($endpoint, 'PUT', $data);
    
    return isset($response['success']) ? $response['success'] : true;  // Bei PUT ist Antwort manchmal leer
}

/**
 * HAUPT-FUNKTION: Benachrichtigt Quentn über erreichte Belohnung
 * 
 * @param array $lead - Lead-Daten aus Datenbank
 * @param array $reward - Belohnungs-Daten
 * @param int $referral_count - Anzahl erfolgreicher Empfehlungen
 * @return bool - Erfolg
 */
function notifyQuentnRewardEarned($lead, $reward, $referral_count) {
    try {
        // 1. Kontakt in Quentn finden
        $contact = quentnFindContact($lead['email']);
        
        if (!$contact) {
            error_log("Quentn: Kontakt nicht gefunden für Email: " . $lead['email']);
            return false;
        }
        
        $contactId = $contact['id'];
        
        error_log("✅ Quentn: Kontakt gefunden - ID: " . $contactId . " - Email: " . $lead['email']);
        
        // 2. Tag setzen: optinpilot-belohung
        $tagSet = quentnSetTag($contactId, 'optinpilot-belohung');
        
        if ($tagSet) {
            error_log("✅ Quentn: Tag 'optinpilot-belohung' erfolgreich gesetzt für Kontakt-ID: " . $contactId);
        } else {
            error_log("⚠️ Quentn: Fehler beim Setzen des Tags für Kontakt-ID: " . $contactId);
        }
        
        // 3. Custom Fields aktualisieren
        $customFields = [
            'successful_referrals' => (string)$referral_count,
            'current_points' => (string)$referral_count,  // Punkte = Anzahl Empfehlungen
            'referral_code' => $lead['referral_code'] ?? '',
            'reward_title' => $reward['reward_title'] ?? '',
            'reward_warning' => 'Neue Belohnung freigeschaltet!'
        ];
        
        $fieldsUpdated = quentnUpdateCustomFields($contactId, $customFields);
        
        if ($fieldsUpdated) {
            error_log("✅ Quentn: Custom Fields aktualisiert für Kontakt-ID: " . $contactId);
        } else {
            error_log("⚠️ Quentn: Fehler beim Aktualisieren der Custom Fields für Kontakt-ID: " . $contactId);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Quentn API Error: " . $e->getMessage());
        return false;
    }
}

/**
 * TEST-FUNKTION: Teste die Quentn-Integration
 * Aufruf via CLI: php quentn_api.php test@example.com
 */
function testQuentnIntegration($testEmail) {
    echo "🧪 Teste Quentn-Integration...\n\n";
    
    // 1. Kontakt suchen
    echo "1. Suche Kontakt: " . $testEmail . "\n";
    $contact = quentnFindContact($testEmail);
    
    if ($contact) {
        echo "✅ Kontakt gefunden!\n";
        echo "   ID: " . $contact['id'] . "\n";
        echo "   Name: " . ($contact['first_name'] ?? '') . " " . ($contact['last_name'] ?? '') . "\n";
        
        $contactId = $contact['id'];
        
        // 2. Tag setzen
        echo "\n2. Setze Test-Tag...\n";
        $tagSet = quentnSetTag($contactId, 'test-tag-' . time());
        echo ($tagSet ? "✅" : "❌") . " Tag gesetzt\n";
        
        // 3. Custom Fields aktualisieren
        echo "\n3. Aktualisiere Custom Fields...\n";
        $fields = [
            'successful_referrals' => '99',
            'referral_code' => 'TEST' . time()
        ];
        $fieldsUpdated = quentnUpdateCustomFields($contactId, $fields);
        echo ($fieldsUpdated ? "✅" : "❌") . " Custom Fields aktualisiert\n";
        
        echo "\n✅ Test abgeschlossen!\n";
        return true;
    } else {
        echo "❌ Kontakt nicht gefunden!\n";
        echo "\nBitte prüfe:\n";
        echo "- Ist die Email korrekt?\n";
        echo "- Existiert der Kontakt in Quentn?\n";
        echo "- Ist der API-Key korrekt?\n";
        return false;
    }
}

// Wenn direkt aufgerufen: Test ausführen
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $testEmail = $argv[1];
    testQuentnIntegration($testEmail);
}
