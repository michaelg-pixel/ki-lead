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

try {
    $pdo = getDBConnection();
    $customer_id = $_SESSION['user_id'];
    
    // JSON Input lesen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['freebie_id']) || !isset($input['niche'])) {
        throw new Exception('Freebie ID und Nische sind erforderlich');
    }
    
    $freebie_id = intval($input['freebie_id']);
    $niche = trim($input['niche']);
    
    // Gültige Nischen
    $validNiches = [
        'online-business',
        'gesundheit-fitness',
        'persoenliche-entwicklung',
        'finanzen-investment',
        'immobilien',
        'ecommerce-dropshipping',
        'affiliate-marketing',
        'social-media-marketing',
        'ki-automation',
        'coaching-consulting',
        'spiritualitaet-mindfulness',
        'beziehungen-dating',
        'eltern-familie',
        'karriere-beruf',
        'hobbys-freizeit',
        'sonstiges'
    ];
    
    if (!in_array($niche, $validNiches)) {
        throw new Exception('Ungültige Nische');
    }
    
    // Prüfen ob das Freebie dem Kunden gehört
    $stmt = $pdo->prepare("
        SELECT id 
        FROM customer_freebies 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
    }
    
    // Nische aktualisieren
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET niche = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND customer_id = ?
    ");
    
    $stmt->execute([$niche, $freebie_id, $customer_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Nische erfolgreich aktualisiert',
        'niche' => $niche
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
