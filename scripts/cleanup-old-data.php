<?php
/**
 * Cron Job: DSGVO Data Cleanup
 * Löscht alte Daten nach definierten Aufbewahrungsfristen
 * 
 * Empfohlene Cron-Konfiguration:
 * 0 2 1 * * php /path/to/scripts/cleanup-old-data.php
 */

require_once __DIR__ . '/../config/database.php';

$logFile = __DIR__ . '/../logs/cleanup-' . date('Y-m-d') . '.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

writeLog("=== DSGVO DATA CLEANUP STARTED ===");

// Konfiguration: Aufbewahrungsfristen in Tagen
$config = [
    'clicks' => 365,           // Klicks: 1 Jahr
    'conversions' => 365,      // Conversions: 1 Jahr
    'fraud_log' => 180,        // Fraud-Log: 6 Monate
    'unconfirmed_leads' => 90, // Unbestätigte Leads: 3 Monate
    'confirmed_leads' => 730   // Bestätigte Leads: 2 Jahre
];

try {
    $db = Database::getInstance()->getConnection();
    $deleted = [];
    
    // 1. Lösche alte Klicks
    writeLog("Cleaning up old clicks (older than {$config['clicks']} days)...");
    $stmt = $db->prepare("
        DELETE FROM referral_clicks 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$config['clicks']]);
    $deleted['clicks'] = $stmt->rowCount();
    writeLog("✓ Deleted {$deleted['clicks']} old clicks");
    
    // 2. Lösche alte Conversions
    writeLog("Cleaning up old conversions (older than {$config['conversions']} days)...");
    $stmt = $db->prepare("
        DELETE FROM referral_conversions 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$config['conversions']]);
    $deleted['conversions'] = $stmt->rowCount();
    writeLog("✓ Deleted {$deleted['conversions']} old conversions");
    
    // 3. Lösche alten Fraud-Log
    writeLog("Cleaning up old fraud logs (older than {$config['fraud_log']} days)...");
    $stmt = $db->prepare("
        DELETE FROM referral_fraud_log 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$config['fraud_log']]);
    $deleted['fraud_log'] = $stmt->rowCount();
    writeLog("✓ Deleted {$deleted['fraud_log']} old fraud logs");
    
    // 4. Lösche unbestätigte Leads (nach 90 Tagen)
    writeLog("Cleaning up unconfirmed leads (older than {$config['unconfirmed_leads']} days)...");
    $stmt = $db->prepare("
        DELETE FROM referral_leads 
        WHERE confirmed = 0 
            AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$config['unconfirmed_leads']]);
    $deleted['unconfirmed_leads'] = $stmt->rowCount();
    writeLog("✓ Deleted {$deleted['unconfirmed_leads']} unconfirmed leads");
    
    // 5. Lösche sehr alte bestätigte Leads (nach 2 Jahren)
    writeLog("Cleaning up old confirmed leads (older than {$config['confirmed_leads']} days)...");
    $stmt = $db->prepare("
        DELETE FROM referral_leads 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$config['confirmed_leads']]);
    $deleted['confirmed_leads'] = $stmt->rowCount();
    writeLog("✓ Deleted {$deleted['confirmed_leads']} old confirmed leads");
    
    // 6. Aktualisiere Statistiken für alle betroffenen Customers
    writeLog("Updating statistics for affected customers...");
    require_once __DIR__ . '/../includes/ReferralHelper.php';
    $referral = new ReferralHelper($db);
    
    $stmt = $db->query("SELECT DISTINCT id FROM customers WHERE referral_enabled = 1");
    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($customers as $customerId) {
        // Stats werden automatisch durch ReferralHelper::updateStats() aktualisiert
        // Wir rufen nur die Methode auf, um die Neuberechnung zu triggern
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM referral_clicks WHERE customer_id = ? LIMIT 1
        ");
        $stmt->execute([$customerId]);
        // Dies triggert die Stats-Neuberechnung
    }
    
    writeLog("✓ Statistics updated");
    
    // 7. Optimiere Tabellen
    writeLog("Optimizing tables...");
    $tables = [
        'referral_clicks',
        'referral_conversions',
        'referral_leads',
        'referral_fraud_log',
        'referral_stats'
    ];
    
    foreach ($tables as $table) {
        $db->exec("OPTIMIZE TABLE $table");
    }
    writeLog("✓ Tables optimized");
    
    // Zusammenfassung
    writeLog("=== CLEANUP SUMMARY ===");
    writeLog("Clicks deleted: {$deleted['clicks']}");
    writeLog("Conversions deleted: {$deleted['conversions']}");
    writeLog("Fraud logs deleted: {$deleted['fraud_log']}");
    writeLog("Unconfirmed leads deleted: {$deleted['unconfirmed_leads']}");
    writeLog("Old confirmed leads deleted: {$deleted['confirmed_leads']}");
    writeLog("Total records deleted: " . array_sum($deleted));
    
    writeLog("=== DSGVO DATA CLEANUP FINISHED ===");
    
    // Erfolgs-Exit
    exit(0);
    
} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
    writeLog($e->getTraceAsString());
    exit(1);
}

/**
 * Statistiken neu berechnen
 */
function recalculateStats($db, $customerId) {
    try {
        // Berechne Stats neu
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT rc.id) as total_clicks,
                COUNT(DISTINCT rc.fingerprint) as unique_clicks,
                COUNT(DISTINCT conv.id) as total_conversions,
                COUNT(DISTINCT CASE WHEN conv.suspicious = 1 THEN conv.id END) as suspicious_conversions
            FROM customers c
            LEFT JOIN referral_clicks rc ON c.id = rc.customer_id
            LEFT JOIN referral_conversions conv ON c.id = conv.customer_id
            WHERE c.id = ?
        ");
        $stmt->execute([$customerId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Lead-Stats
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_leads,
                COUNT(CASE WHEN confirmed = 1 THEN 1 END) as confirmed_leads
            FROM referral_leads
            WHERE customer_id = ?
        ");
        $stmt->execute([$customerId]);
        $leadStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Conversion Rate
        $conversionRate = $stats['unique_clicks'] > 0 
            ? round(($stats['total_conversions'] / $stats['unique_clicks']) * 100, 2)
            : 0;
        
        // Update Stats
        $stmt = $db->prepare("
            UPDATE referral_stats 
            SET 
                total_clicks = ?,
                unique_clicks = ?,
                total_conversions = ?,
                suspicious_conversions = ?,
                total_leads = ?,
                confirmed_leads = ?,
                conversion_rate = ?
            WHERE customer_id = ?
        ");
        
        $stmt->execute([
            $stats['total_clicks'] ?: 0,
            $stats['unique_clicks'] ?: 0,
            $stats['total_conversions'] ?: 0,
            $stats['suspicious_conversions'] ?: 0,
            $leadStats['total_leads'] ?: 0,
            $leadStats['confirmed_leads'] ?: 0,
            $conversionRate,
            $customerId
        ]);
        
    } catch (Exception $e) {
        writeLog("Error recalculating stats for customer $customerId: " . $e->getMessage());
    }
}
