<?php
/**
 * API: Mailgun Consent - Zustimmung speichern
 * 
 * Speichert Mailgun + AVV Zustimmung in av_contract_acceptances
 * Wird von customer/sections/empfehlungsprogramm.php aufgerufen
 */

header('Content-Type: application/json');

// Sichere Session-Konfiguration laden
require_once __DIR__ . '/../../config/security.php';

// Starte sichere Session
startSecureSession();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Nicht autorisiert'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Request-Daten lesen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['consent_given']) || $input['consent_given'] !== true) {
        throw new Exception('Zustimmung wurde nicht erteilt');
    }
    
    $customer_id = $_SESSION['user_id'];
    $acceptance_type = 'mailgun_consent';
    $av_contract_version = 'Mailgun_AVV_2025_v1';
    
    // IP-Adresse und User-Agent erfassen
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Prüfen ob bereits Zustimmung existiert
    $stmt_check = $pdo->prepare("
        SELECT id FROM av_contract_acceptances 
        WHERE user_id = ? AND acceptance_type = ?
    ");
    $stmt_check->execute([$customer_id, $acceptance_type]);
    
    if ($stmt_check->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Zustimmung bereits vorhanden',
            'already_exists' => true
        ]);
        exit;
    }
    
    // Zustimmung speichern
    $stmt = $pdo->prepare("
        INSERT INTO av_contract_acceptances (
            user_id,
            accepted_at,
            ip_address,
            user_agent,
            av_contract_version,
            acceptance_type,
            created_at
        ) VALUES (?, NOW(), ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $customer_id,
        $ip_address,
        $user_agent,
        $av_contract_version,
        $acceptance_type
    ]);
    
    // Log für Admin
    error_log(sprintf(
        "✅ MAILGUN CONSENT: User #%d hat Mailgun+AVV zugestimmt (IP: %s)",
        $customer_id,
        $ip_address
    ));
    
    echo json_encode([
        'success' => true,
        'message' => 'Zustimmung erfolgreich gespeichert',
        'acceptance_id' => $pdo->lastInsertId(),
        'accepted_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("❌ MAILGUN CONSENT ERROR (DB): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler beim Speichern der Zustimmung'
    ]);
} catch (Exception $e) {
    error_log("❌ MAILGUN CONSENT ERROR: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}