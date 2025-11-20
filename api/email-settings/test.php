<?php
/**
 * API Endpoint: Email-Marketing API-Verbindung testen
 * 🆕 ERSTELLT AUTOMATISCH TAGS FÜR EMPFEHLUNGSPROGRAMM
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../customer/includes/EmailProvidersExtended.php';

// Session starten
startSecureSession();

// Auth Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

$customer_id = $_SESSION['user_id'] ?? null;
if (!$customer_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Keine User ID']);
    exit;
}

try {
    // DB-Verbindung
    $pdo = getDBConnection();
    
    // Aktive API-Einstellungen laden
    $stmt = $pdo->prepare("
        SELECT * FROM customer_email_api_settings 
        WHERE customer_id = ? AND is_active = TRUE
        LIMIT 1
    ");
    $stmt->execute([$customer_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        throw new Exception('Keine API-Einstellungen gefunden');
    }
    
    // Custom Settings parsen
    $customSettings = json_decode($settings['custom_settings'] ?? '{}', true);
    
    // Provider-Instanz erstellen (mit Extended Factory)
    $provider = EmailProviderFactoryExtended::create(
        $settings['provider'],
        $settings['api_key'],
        $customSettings
    );
    
    // Verbindung testen
    $result = $provider->testConnection();
    
    if ($result['success']) {
        // 🆕 AUTOMATISCHE TAG-ERSTELLUNG
        $createdTags = [];
        $tagErrors = [];
        
        // Tags die automatisch erstellt werden sollen
        $requiredTags = [
            'Empfehlungsprogramm-Lead' => 'Lead hat sich registriert',
            'Aktiver-Werber' => 'Lead hat mindestens 1 Empfehlung',
            'Belohnung-Stufe-1' => 'Erste Belohnungsstufe erreicht',
            'Belohnung-Stufe-2' => 'Zweite Belohnungsstufe erreicht',
            'Belohnung-Stufe-3' => 'Dritte Belohnungsstufe erreicht'
        ];
        
        foreach ($requiredTags as $tagName => $description) {
            try {
                $tagResult = $provider->ensureTagExists($tagName);
                if ($tagResult['success']) {
                    $createdTags[] = [
                        'name' => $tagName,
                        'status' => $tagResult['created'] ? 'created' : 'exists',
                        'id' => $tagResult['tag_id'] ?? null
                    ];
                } else {
                    $tagErrors[] = [
                        'name' => $tagName,
                        'error' => $tagResult['message'] ?? $tagResult['error'] ?? 'Unbekannter Fehler'
                    ];
                }
            } catch (Exception $e) {
                $tagErrors[] = [
                    'name' => $tagName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Verifizierung in DB speichern
        $stmt = $pdo->prepare("
            UPDATE customer_email_api_settings SET
                is_verified = TRUE,
                last_verified_at = NOW(),
                verification_error = NULL
            WHERE id = ?
        ");
        $stmt->execute([$settings['id']]);
        
        // API-Log erstellen
        $stmt = $pdo->prepare("
            INSERT INTO email_api_logs (
                customer_id,
                provider,
                endpoint,
                method,
                response_code,
                success,
                duration_ms
            ) VALUES (?, ?, ?, ?, ?, TRUE, ?)
        ");
        $stmt->execute([
            $customer_id,
            $settings['provider'],
            'test-connection',
            'GET',
            200,
            50
        ]);
        
        // Erfolgreiche Antwort mit Tag-Info
        $message = $result['message'];
        if (count($createdTags) > 0) {
            $newTagsCount = count(array_filter($createdTags, fn($t) => $t['status'] === 'created'));
            $existingTagsCount = count(array_filter($createdTags, fn($t) => $t['status'] === 'exists'));
            
            if ($newTagsCount > 0) {
                $message .= " | 🏷️ {$newTagsCount} Tag(s) wurden automatisch erstellt";
            }
            if ($existingTagsCount > 0) {
                $message .= " | ✅ {$existingTagsCount} Tag(s) existieren bereits";
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'details' => $result['details'] ?? null,
            'tags' => [
                'created' => $createdTags,
                'errors' => $tagErrors
            ]
        ]);
    } else {
        // Fehler in DB speichern
        $stmt = $pdo->prepare("
            UPDATE customer_email_api_settings SET
                is_verified = FALSE,
                verification_error = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $result['message'],
            $settings['id']
        ]);
        
        // API-Log erstellen
        $stmt = $pdo->prepare("
            INSERT INTO email_api_logs (
                customer_id,
                provider,
                endpoint,
                method,
                response_code,
                success,
                error_message
            ) VALUES (?, ?, ?, ?, ?, FALSE, ?)
        ");
        $stmt->execute([
            $customer_id,
            $settings['provider'],
            'test-connection',
            'GET',
            500,
            $result['message']
        ]);
        
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Email API Test Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
