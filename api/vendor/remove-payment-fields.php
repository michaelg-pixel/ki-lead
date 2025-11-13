<?php
/**
 * Remove Payment Fields Migration
 * Entfernt alle zahlungsbezogenen Felder aus dem Vendor System
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
    $details = [];
    
    // 1. Entferne Felder aus vendor_reward_templates
    $template_fields = [
        'marketplace_price',
        'digistore_product_id',
        'commission_per_import',
        'commission_per_claim',
        'total_revenue'
    ];
    
    foreach ($template_fields as $field) {
        try {
            // Prüfe ob Feld existiert
            $stmt = $pdo->query("SHOW COLUMNS FROM vendor_reward_templates LIKE '$field'");
            if ($stmt->rowCount() > 0) {
                $pdo->exec("ALTER TABLE vendor_reward_templates DROP COLUMN $field");
                $details[] = "✅ Feld '$field' aus vendor_reward_templates entfernt";
            } else {
                $details[] = "ℹ️ Feld '$field' existiert nicht in vendor_reward_templates";
            }
        } catch (PDOException $e) {
            // Feld existiert vielleicht nicht, weiter
            $details[] = "⚠️ Fehler bei '$field': " . $e->getMessage();
        }
    }
    
    // 2. Entferne Felder aus users
    $user_fields = [
        'vendor_paypal_email',
        'vendor_bank_account'
    ];
    
    foreach ($user_fields as $field) {
        try {
            // Prüfe ob Feld existiert
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$field'");
            if ($stmt->rowCount() > 0) {
                $pdo->exec("ALTER TABLE users DROP COLUMN $field");
                $details[] = "✅ Feld '$field' aus users entfernt";
            } else {
                $details[] = "ℹ️ Feld '$field' existiert nicht in users";
            }
        } catch (PDOException $e) {
            $details[] = "⚠️ Fehler bei '$field': " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment-Felder erfolgreich entfernt!',
        'details' => $details
    ]);
    
} catch (PDOException $e) {
    error_log('Remove Payment Fields Migration Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>