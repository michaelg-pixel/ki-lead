<?php
/**
 * Update Payout Status API
 * Aktualisiert den Status einer Auszahlung (z.B. pending → paid)
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

try {
    $pdo = getDBConnection();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validierung
    if (empty($input['payout_id'])) {
        throw new Exception('payout_id erforderlich');
    }
    
    if (empty($input['status'])) {
        throw new Exception('status erforderlich');
    }
    
    $payout_id = (int)$input['payout_id'];
    $new_status = $input['status'];
    
    // Valide Status-Werte
    $valid_statuses = ['pending', 'processing', 'paid', 'failed'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Ungültiger Status');
    }
    
    // Lade Auszahlung
    $stmt = $pdo->prepare("SELECT vendor_id, status FROM vendor_payouts WHERE id = ?");
    $stmt->execute([$payout_id]);
    $payout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payout) {
        throw new Exception('Auszahlung nicht gefunden');
    }
    
    // Optional: Prüfe ob User der Vendor ist (für später wenn Admin-System existiert)
    // Momentan kann jeder eingeloggte User Status ändern
    
    // Update Status
    $update_fields = ['status = ?', 'updated_at = NOW()'];
    $params = [$new_status];
    
    // Wenn auf "paid" gesetzt, setze auch paid_at
    if ($new_status === 'paid') {
        $update_fields[] = 'paid_at = NOW()';
    }
    
    // Optional: Transaktions-Details
    if (!empty($input['transaction_id'])) {
        $update_fields[] = 'transaction_id = ?';
        $params[] = $input['transaction_id'];
    }
    
    if (!empty($input['transaction_note'])) {
        $update_fields[] = 'transaction_note = ?';
        $params[] = $input['transaction_note'];
    }
    
    $params[] = $payout_id;
    
    $sql = "UPDATE vendor_payouts SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => "Status aktualisiert: {$new_status}",
        'payout_id' => $payout_id,
        'old_status' => $payout['status'],
        'new_status' => $new_status
    ]);
    
} catch (PDOException $e) {
    error_log('Update Payout Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>