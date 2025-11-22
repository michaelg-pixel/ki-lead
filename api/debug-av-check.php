<?php
/**
 * DEBUG: AV Contract Acceptances prüfen
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "=== AV_CONTRACT_ACCEPTANCES TABELLE ===\n\n";
    
    // Tabellenstruktur prüfen
    $stmt = $pdo->query("DESCRIBE av_contract_acceptances");
    echo "Spalten:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n\n=== ALLE EINTRÄGE ===\n\n";
    
    // Alle Einträge anzeigen
    $stmt = $pdo->query("SELECT * FROM av_contract_acceptances ORDER BY created_at DESC LIMIT 50");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "❌ KEINE EINTRÄGE in av_contract_acceptances gefunden!\n";
    } else {
        echo "✅ " . count($rows) . " Einträge gefunden:\n\n";
        foreach ($rows as $row) {
            echo "ID: {$row['id']}\n";
            echo "  user_id: {$row['user_id']}\n";
            echo "  acceptance_type: {$row['acceptance_type']}\n";
            echo "  accepted_at: {$row['accepted_at']}\n";
            echo "  av_contract_version: {$row['av_contract_version']}\n";
            echo "  created_at: {$row['created_at']}\n";
            echo "\n";
        }
    }
    
    echo "\n=== STATISTIK NACH TYPE ===\n\n";
    $stmt = $pdo->query("
        SELECT acceptance_type, COUNT(*) as count 
        FROM av_contract_acceptances 
        GROUP BY acceptance_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['acceptance_type']}: {$row['count']} Einträge\n";
    }
    
    echo "\n\n=== USERS MIT ZUSTIMMUNG ===\n\n";
    $stmt = $pdo->query("
        SELECT u.id, u.email, COUNT(a.id) as consent_count
        FROM users u
        LEFT JOIN av_contract_acceptances a ON u.id = a.user_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY u.id
        LIMIT 20
    ");
    echo "User ID | Email | Zustimmungen\n";
    echo "------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%7d | %-30s | %d\n", $row['id'], $row['email'], $row['consent_count']);
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
