<?php
/**
 * Referral System Setup Script
 * Installiert das komplette Empfehlungsprogramm-System
 */

require_once __DIR__ . '/../config/database.php';

echo "========================================\n";
echo "  REFERRAL SYSTEM INSTALLATION\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "[1/5] Prüfe Datenbank-Verbindung...\n";
    if (!$db) {
        throw new Exception("Datenbankverbindung fehlgeschlagen");
    }
    echo "✓ Verbindung erfolgreich\n\n";
    
    echo "[2/5] Führe Datenbank-Migration aus...\n";
    $migrationFile = __DIR__ . '/../database/migrations/004_referral_system.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration-Datei nicht gefunden: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    $statements = array_filter(
        explode(';', $sql),
        function($stmt) {
            return trim($stmt) !== '' && !preg_match('/^--/', trim($stmt));
        }
    );
    
    $db->beginTransaction();
    
    try {
        foreach ($statements as $statement) {
            $stmt = trim($statement);
            if (empty($stmt) || strpos($stmt, '--') === 0) continue;
            
            $db->exec($stmt);
        }
        
        $db->commit();
        echo "✓ Migration erfolgreich ausgeführt\n\n";
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception("Migration fehlgeschlagen: " . $e->getMessage());
    }
    
    echo "[3/5] Generiere Referral-Codes für existierende Kunden...\n";
    $stmt = $db->query("
        SELECT id FROM customers 
        WHERE referral_code IS NULL OR referral_code = ''
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updateStmt = $db->prepare("
        UPDATE customers 
        SET referral_code = ? 
        WHERE id = ?
    ");
    
    $count = 0;
    foreach ($customers as $customer) {
        $refCode = 'REF' . str_pad($customer['id'], 6, '0', STR_PAD_LEFT) 
                 . substr(md5($customer['id'] . time()), 0, 6);
        $updateStmt->execute([strtoupper($refCode), $customer['id']]);
        $count++;
    }
    
    echo "✓ $count Referral-Codes generiert\n\n";
    
    echo "[4/5] Initialisiere Statistiken und Rewards...\n";
    
    // Stats initialisieren
    $db->exec("
        INSERT INTO referral_stats (customer_id)
        SELECT id FROM customers
        WHERE id NOT IN (SELECT customer_id FROM referral_stats)
    ");
    
    // Rewards initialisieren
    $db->exec("
        INSERT INTO referral_rewards (customer_id)
        SELECT id FROM customers
        WHERE id NOT IN (SELECT customer_id FROM referral_rewards)
    ");
    
    echo "✓ Initialisierung abgeschlossen\n\n";
    
    echo "[5/5] Prüfe Installation...\n";
    
    $checks = [
        'referral_clicks' => 'Klicks-Tabelle',
        'referral_conversions' => 'Conversions-Tabelle',
        'referral_leads' => 'Leads-Tabelle',
        'referral_stats' => 'Statistiken-Tabelle',
        'referral_rewards' => 'Rewards-Tabelle',
        'referral_fraud_log' => 'Fraud-Log-Tabelle'
    ];
    
    foreach ($checks as $table => $label) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $label vorhanden\n";
        } else {
            throw new Exception("Tabelle fehlt: $table");
        }
    }
    
    echo "\n========================================\n";
    echo "  INSTALLATION ERFOLGREICH!\n";
    echo "========================================\n\n";
    
    echo "Nächste Schritte:\n";
    echo "1. Integriere das Tracking-Script in Freebie-Seiten:\n";
    echo "   <script src=\"/assets/js/referral-tracking.js\"></script>\n\n";
    
    echo "2. Füge Navigation zum Customer-Dashboard hinzu:\n";
    echo "   <a href=\"?section=empfehlungsprogramm\">Empfehlungsprogramm</a>\n\n";
    
    echo "3. Füge Navigation zum Admin-Dashboard hinzu:\n";
    echo "   <a href=\"?section=referral-overview\">Referral-Übersicht</a>\n\n";
    
    echo "4. Konfiguriere SMTP für E-Mail-Versand (optional)\n\n";
    
    // Statistiken ausgeben
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers WHERE referral_enabled = 1");
    $activeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Statistik:\n";
    echo "- Gesamt-Kunden: $totalCount\n";
    echo "- Aktive Empfehlungsprogramme: $activeCount\n\n";
    
} catch (Exception $e) {
    echo "\n✗ FEHLER: " . $e->getMessage() . "\n\n";
    exit(1);
}
