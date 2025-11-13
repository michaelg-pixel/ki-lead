<?php
/**
 * Process Monthly Payouts (Cronjob)
 * Erstellt automatisch Auszahlungen für alle Vendors
 * Sollte monatlich am 1. des Monats ausgeführt werden
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung - später auf Admin oder Cronjob-Token beschränken
if (!isset($_SESSION['user_id'])) {
    // Alternative: Prüfe Cronjob-Token
    $cronjob_token = $_GET['token'] ?? '';
    $expected_token = 'CHANGE_THIS_TOKEN'; // TODO: In Config auslagern
    
    if ($cronjob_token !== $expected_token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $min_payout = 50.00;
    $period_end = new DateTime('last day of last month');
    $period_start = new DateTime('first day of last month');
    
    // Finde alle Vendors
    $stmt = $pdo->query("
        SELECT 
            id,
            vendor_company_name,
            vendor_paypal_email,
            vendor_bank_account
        FROM users 
        WHERE is_vendor = 1
    ");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [
        'processed' => 0,
        'created' => 0,
        'skipped' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    foreach ($vendors as $vendor) {
        $results['processed']++;
        
        try {
            // Berechne ausstehenden Betrag
            $stmt = $pdo->prepare("
                SELECT SUM(total_revenue) as total_earnings
                FROM vendor_reward_templates
                WHERE vendor_id = ?
            ");
            $stmt->execute([$vendor['id']]);
            $total_earnings = $stmt->fetchColumn() ?? 0;
            
            // Bereits ausbezahlt
            $stmt = $pdo->prepare("
                SELECT SUM(amount) as paid_amount
                FROM vendor_payouts
                WHERE vendor_id = ? AND status IN ('paid', 'processing')
            ");
            $stmt->execute([$vendor['id']]);
            $paid_amount = $stmt->fetchColumn() ?? 0;
            
            $pending_amount = $total_earnings - $paid_amount;
            
            // Mindestbetrag nicht erreicht?
            if ($pending_amount < $min_payout) {
                $results['skipped']++;
                $results['details'][] = [
                    'vendor' => $vendor['vendor_company_name'],
                    'reason' => "Mindestbetrag nicht erreicht ({$pending_amount}€)",
                    'status' => 'skipped'
                ];
                continue;
            }
            
            // Keine Zahlungsmethode?
            if (empty($vendor['vendor_paypal_email']) && empty($vendor['vendor_bank_account'])) {
                $results['skipped']++;
                $results['details'][] = [
                    'vendor' => $vendor['vendor_company_name'],
                    'reason' => 'Keine Zahlungsmethode hinterlegt',
                    'status' => 'skipped'
                ];
                continue;
            }
            
            // Bestimme Zahlungsmethode
            $payment_method = !empty($vendor['vendor_paypal_email']) ? 'paypal' : 'bank_transfer';
            
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
                $vendor['id'],
                $pending_amount,
                $period_start->format('Y-m-d'),
                $period_end->format('Y-m-d'),
                $payment_method,
                $vendor['vendor_paypal_email'],
                $vendor['vendor_bank_account']
            ]);
            
            $results['created']++;
            $results['details'][] = [
                'vendor' => $vendor['vendor_company_name'],
                'amount' => $pending_amount,
                'method' => $payment_method,
                'status' => 'created'
            ];
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = [
                'vendor' => $vendor['vendor_company_name'],
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Auszahlungen verarbeitet',
        'summary' => $results,
        'period' => [
            'start' => $period_start->format('d.m.Y'),
            'end' => $period_end->format('d.m.Y')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Process Monthly Payouts Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>