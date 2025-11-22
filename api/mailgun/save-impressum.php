<?php
/**
 * API: Impressum speichern
 * Speichert/aktualisiert das Kunden-Impressum für Mailgun-E-Mails
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
    
    if (empty($input['impressum_html'])) {
        throw new Exception('Impressum darf nicht leer sein');
    }
    
    $customer_id = $_SESSION['user_id'];
    $impressum_html = trim($input['impressum_html']);
    
    // Sicherstellen dass Impressum HTML-Entities enthält (XSS-Schutz)
    $impressum_html = htmlspecialchars_decode($impressum_html);
    $impressum_html = strip_tags($impressum_html, '<p><br><strong><b><em><i><u><a>');
    
    // Impressum in users Tabelle aktualisieren
    $stmt = $pdo->prepare("
        UPDATE users 
        SET company_imprint_html = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $impressum_html,
        $customer_id
    ]);
    
    // Prüfen ob Update erfolgreich
    if ($stmt->rowCount() === 0) {
        throw new Exception('Impressum konnte nicht gespeichert werden');
    }
    
    // Log für Admin
    error_log(sprintf(
        "✅ IMPRESSUM SAVED: User #%d hat Impressum gespeichert (%d Zeichen)",
        $customer_id,
        strlen($impressum_html)
    ));
    
    echo json_encode([
        'success' => true,
        'message' => 'Impressum erfolgreich gespeichert',
        'impressum_length' => strlen($impressum_html),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("❌ IMPRESSUM SAVE ERROR (DB): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler beim Speichern des Impressums'
    ]);
} catch (Exception $e) {
    error_log("❌ IMPRESSUM SAVE ERROR: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
