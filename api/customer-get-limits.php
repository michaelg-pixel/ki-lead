<?php
/**
 * Admin API: Aktuelle Limits eines Kunden abrufen
 */

session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Zugriff verweigert']));
}

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('User-ID fehlt');
    }
    
    // User-Daten laden
    $stmt = $pdo->prepare("
        SELECT id, name, email, digistore_product_name
        FROM users 
        WHERE id = ? AND role = 'customer'
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Freebie-Limit laden
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$userId]);
    $freebieData = $stmt->fetch();
    
    $freebieLimit = $freebieData ? (int)$freebieData['freebie_limit'] : 0;
    $productName = $freebieData['product_name'] ?? $user['digistore_product_name'] ?? 'Unbekannt';
    
    // Empfehlungs-Slots laden
    $stmt = $pdo->prepare("
        SELECT total_slots, used_slots
        FROM customer_referral_slots 
        WHERE customer_id = ?
    ");
    $stmt->execute([$userId]);
    $referralData = $stmt->fetch();
    
    $referralSlots = $referralData ? (int)$referralData['total_slots'] : 0;
    $usedSlots = $referralData ? (int)$referralData['used_slots'] : 0;
    
    // Anzahl erstellter Freebies zählen
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM customer_freebies 
        WHERE customer_id = ?
    ");
    $stmt->execute([$userId]);
    $freebiesCount = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ],
            'freebie_limit' => $freebieLimit,
            'freebies_created' => (int)$freebiesCount,
            'referral_slots' => $referralSlots,
            'referral_slots_used' => $usedSlots,
            'referral_slots_available' => max(0, $referralSlots - $usedSlots),
            'product_name' => $productName
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
