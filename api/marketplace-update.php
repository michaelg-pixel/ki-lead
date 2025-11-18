<?php
session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

/**
 * Extrahiert die DigiStore24 Produkt-ID aus verschiedenen URL-Formaten
 * 
 * Unterstützte Formate:
 * - https://www.digistore24.com/product/12345
 * - https://www.digistore24.com/redir/12345/username
 * - https://www.digi24.com/product/12345
 * - Nur die ID: 12345
 */
function extractDigistoreProductId($input) {
    if (empty($input)) {
        return null;
    }
    
    $input = trim($input);
    
    // Wenn es nur Zahlen sind, direkt zurückgeben
    if (preg_match('/^\d+$/', $input)) {
        return $input;
    }
    
    // Pattern für verschiedene DigiStore24 URL-Formate
    $patterns = [
        // https://www.digistore24.com/product/12345 oder /product/12345/...
        '/\/product\/(\d+)/i',
        // https://www.digistore24.com/redir/12345/... 
        '/\/redir\/(\d+)/i',
        // https://www.digi24.com/product/12345
        '/digi(?:store)?24\.com.*?(\d+)/i',
        // Fallback: Erste längere Ziffernfolge
        '/(\d{4,})/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

try {
    $pdo = getDBConnection();
    $customer_id = $_SESSION['user_id'];
    
    // POST-Daten empfangen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['freebie_id'])) {
        throw new Exception('Freebie-ID fehlt');
    }
    
    $freebie_id = (int)$input['freebie_id'];
    
    // Prüfen, ob das Freebie dem Customer gehört
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
    }
    
    // Marktplatz-Daten vorbereiten und konvertieren
    // WICHTIG: Boolean zu Integer konvertieren!
    $marketplace_enabled = isset($input['marketplace_enabled']) && $input['marketplace_enabled'] ? 1 : 0;
    
    // Preis: Leerer String wird zu NULL
    $marketplace_price = null;
    if (isset($input['marketplace_price']) && $input['marketplace_price'] !== '' && $input['marketplace_price'] !== null) {
        $marketplace_price = (float)$input['marketplace_price'];
    }
    
    // DigiStore24 Produkt-ID: Extrahiere die ID aus der URL
    $digistore_product_id = null;
    $extracted_product_id = null;
    if (isset($input['digistore_product_id']) && trim($input['digistore_product_id']) !== '') {
        $original_input = trim($input['digistore_product_id']);
        $digistore_product_id = $original_input; // Speichere Original-Eingabe
        
        // Versuche die Produkt-ID zu extrahieren
        $extracted_product_id = extractDigistoreProductId($original_input);
        
        if (!$extracted_product_id) {
            throw new Exception('Konnte keine gültige DigiStore24 Produkt-ID aus dem Link extrahieren. Bitte gib eine gültige URL oder Produkt-ID ein.');
        }
    }
    
    // Marktplatz-Beschreibung: Leerer String wird zu NULL
    $marketplace_description = null;
    if (isset($input['marketplace_description']) && trim($input['marketplace_description']) !== '') {
        $marketplace_description = trim($input['marketplace_description']);
    }
    
    // Lektionen-Anzahl: Leerer String wird zu NULL
    $course_lessons_count = null;
    if (isset($input['course_lessons_count']) && $input['course_lessons_count'] !== '' && $input['course_lessons_count'] !== null) {
        $course_lessons_count = (int)$input['course_lessons_count'];
    }
    
    // Kursdauer: Leerer String wird zu NULL
    $course_duration = null;
    if (isset($input['course_duration']) && trim($input['course_duration']) !== '') {
        $course_duration = trim($input['course_duration']);
    }
    
    // Prüfe, ob Rechtstexte hinterlegt sind (Warnung)
    $legal_warning = null;
    if ($marketplace_enabled && $digistore_product_id) {
        $stmt = $pdo->prepare("
            SELECT impressum, datenschutz 
            FROM legal_texts 
            WHERE user_id = ?
        ");
        $stmt->execute([$customer_id]);
        $legal_texts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$legal_texts || (empty(trim($legal_texts['impressum'])) && empty(trim($legal_texts['datenschutz'])))) {
            $legal_warning = 'WICHTIG: Du hast noch keine Rechtstexte hinterlegt! Diese werden auf der Danke-Seite benötigt. Bitte fülle Impressum und Datenschutz unter "Rechtstexte" aus.';
        }
    }
    
    // Update durchführen
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET 
            marketplace_enabled = ?,
            marketplace_price = ?,
            digistore_product_id = ?,
            marketplace_description = ?,
            course_lessons_count = ?,
            course_duration = ?,
            marketplace_updated_at = NOW()
        WHERE id = ? AND customer_id = ?
    ");
    
    // Speichere nur die extrahierte ID (cleaner für Matching)
    $stmt->execute([
        $marketplace_enabled,
        $marketplace_price,
        $extracted_product_id, // Nur die reine ID speichern!
        $marketplace_description,
        $course_lessons_count,
        $course_duration,
        $freebie_id,
        $customer_id
    ]);
    
    $response = [
        'success' => true,
        'message' => 'Marktplatz-Einstellungen gespeichert',
        'data' => [
            'marketplace_enabled' => $marketplace_enabled,
            'marketplace_price' => $marketplace_price,
            'digistore_product_id' => $extracted_product_id,
            'original_input' => $digistore_product_id
        ]
    ];
    
    if ($legal_warning) {
        $response['warning'] = $legal_warning;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>