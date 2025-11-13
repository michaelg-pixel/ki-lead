<?php
/**
 * Run Vendor Payouts Table Migration
 * Erstellt die vendor_payouts Tabelle
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung (nur für eingeloggte User)
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
    
    // Prüfe ob Tabelle bereits existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'vendor_payouts'");
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tabelle vendor_payouts existiert bereits',
            'details' => ['Keine Änderungen nötig']
        ]);
        exit;
    }
    
    // Erstelle vendor_payouts Tabelle
    $sql = "CREATE TABLE vendor_payouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_id INT NOT NULL,
        
        -- Auszahlungsdetails
        amount DECIMAL(10,2) NOT NULL,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        
        -- Status
        status ENUM('pending', 'processing', 'paid', 'failed') DEFAULT 'pending',
        
        -- Zahlungsmethode
        payment_method ENUM('paypal', 'bank_transfer') NOT NULL,
        payment_email VARCHAR(255),
        payment_account VARCHAR(255),
        
        -- Transaktionsdetails
        transaction_id VARCHAR(255),
        transaction_date DATETIME,
        transaction_note TEXT,
        
        -- Meta
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        paid_at DATETIME,
        
        FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_vendor (vendor_id),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Vendor Payouts Tabelle erfolgreich erstellt!',
        'details' => [
            '✅ Tabelle vendor_payouts erstellt',
            '✅ Indizes hinzugefügt',
            '✅ Foreign Keys konfiguriert',
            '✅ Bereit für Auszahlungsverwaltung'
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Vendor Payouts Migration Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>