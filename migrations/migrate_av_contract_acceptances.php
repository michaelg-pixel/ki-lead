<?php
/**
 * Migration: AV-Vertrags-Zustimmungen Tracking
 * 
 * Erstellt eine Tabelle zur DSGVO-konformen Speicherung von AV-Vertrags-Zustimmungen
 * mit Zeitstempel, IP-Adresse und User-Agent für Nachweispflicht
 * 
 * Ausführung: php migrations/migrate_av_contract_acceptances.php
 */

require_once __DIR__ . '/../config/database.php';

echo "\n=== Migration: AV-Vertrags-Zustimmungen ===\n";
echo "Erstelle Tabelle für DSGVO-konforme Nachweispflicht...\n\n";

try {
    $pdo = getDBConnection();
    
    // Lese SQL-Datei
    $sql = file_get_contents(__DIR__ . '/create_av_contract_acceptances.sql');
    
    if ($sql === false) {
        throw new Exception("SQL-Datei konnte nicht gelesen werden!");
    }
    
    // Führe Migration aus
    $pdo->exec($sql);
    
    echo "✅ Tabelle 'av_contract_acceptances' erfolgreich erstellt!\n\n";
    
    // Prüfe Tabellenstruktur
    $stmt = $pdo->query("DESCRIBE av_contract_acceptances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Tabellenstruktur:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-25s %-20s %-10s %-10s\n", "Spalte", "Typ", "NULL", "Schlüssel");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-25s %-20s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'],
            $column['Key']
        );
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    // Zähle vorhandene Einträge
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM av_contract_acceptances");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 Aktuelle Einträge: {$count}\n\n";
    
    echo "✨ Migration erfolgreich abgeschlossen!\n\n";
    echo "ℹ️  Hinweise:\n";
    echo "   - Die Tabelle ist jetzt bereit für die Speicherung von AV-Vertrags-Zustimmungen\n";
    echo "   - IP-Adressen werden sicher gespeichert (IPv4/IPv6 kompatibel)\n";
    echo "   - User-Agent wird vollständig gespeichert für Nachweiszwecke\n";
    echo "   - Foreign Key zu 'users' Tabelle ist aktiv (CASCADE bei Löschung)\n\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler bei der Migration: " . $e->getMessage() . "\n";
    echo "\nSQL State: " . $e->getCode() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== Migration abgeschlossen ===\n\n";
