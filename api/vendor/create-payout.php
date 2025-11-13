<?php
/**
 * Create Vendor Payout API
 * Erstellt eine neue Auszahlung für einen Vendor
 * Wird typischerweise von einem Cronjob zum 1. des Monats aufgerufen
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung - könnte später auf Admin beschränkt werden
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
    if (empty($input['vendor_id'])) {
        throw new Exception('vendor_id erforderlich');
    }
    
    $vendor_id = (int)$input['vendor_id'];
    
    // Lade Vendor-Daten
    $stmt = $pdo->prepare("
        SELECT 
            is_vendor,
            vendor_company_name,
            vendor_paypal_email,
            vendor_bank_account
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vendor || !$vendor['is_vendor']) {
        throw new Exception('Kein gültiger Vendor');
    }
    
    // Berechne ausstehenden Betrag
    $stmt = $pdo->prepare("
        SELECT SUM(total_revenue) as total_earnings
        FROM vendor_reward_templates
        WHERE vendor_id = ?
    ");
    $stmt->execute([$vendor_id]);
    $total_earnings = $stmt->fetchColumn() ?? 0;
    
    // Bereits ausbezahlt
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as paid_amount
        FROM vendor_payouts
        WHERE vendor_id = ? AND status IN ('paid', 'processing')
    ");
    $stmt->execute([$vendor_id]);
    $paid_amount = $stmt->fetchColumn() ?? 0;
    
    $pending_amount = $total_earnings - $paid_amount;
    
    // Mindestbetrag prüfen
    $min_payout = 50.00;
    if ($pending_amount < $min_payout) {
        throw new Exception("Mindestbetrag von {$min_payout}€ nicht erreicht (Ausstehend: {$pending_amount}€)");
    }
    
    // Keine Zahlungsmethode hinterlegt?
    if (empty($vendor['vendor_paypal_email']) && empty($vendor['vendor_bank_account'])) {
        throw new Exception('Keine Zahlungsmethode hinterlegt');
    }
    
    // Zeitraum: Letzter Monat
    $period_end = new DateTime('last day of last month');
    $period_start = new DateTime('first day of last month');
    
    // Bestimme Zahlungsmethode
    $payment_method = !empty($vendor['vendor_paypal_email']) ? 'paypal' : 'bank_transfer';
    $payment_email = $vendor['vendor_paypal_email'];
    $payment_account = $vendor['vendor_bank_account'];
    
    // Erstelle Auszahlung
    $stmt = $pdo->prepare("
        INSERT INTO vendor_payouts (
            vendor_id,
            amount,
            period_start,
            period_end,
            status,
            payment_method,
            payment_email,
            payment_account
        ) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)
    ");
    
    $stmt->execute([
        $vendor_id,
        $pending_amount,
        $period_start->format('Y-m-d'),
        $period_end->format('Y-m-d'),
        $payment_method,
        $payment_email,
        $payment_account
    ]);
    
    $payout_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Auszahlung erstellt',
        'payout_id' => $payout_id,
        'amount' => $pending_amount,
        'vendor_name' => $vendor['vendor_company_name'],
        'payment_method' => $payment_method,
        'period' => [
            'start' => $period_start->format('d.m.Y'),
            'end' => $period_end->format('d.m.Y')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Create Payout Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>