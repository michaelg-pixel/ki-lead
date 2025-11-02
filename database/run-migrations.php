<?php
/**
 * Database Migration Runner
 * Führt Datenbank-Migrationen aus
 * 
 * Aufruf: php run-migrations.php
 * oder im Browser: https://app.mehr-infos-jetzt.de/database/run-migrations.php
 */

echo "🚀 Database Migration Runner\n";
echo "============================\n\n";

// Datenbankverbindung
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    echo "✅ Datenbankverbindung erfolgreich\n\n";
} catch (Exception $e) {
    die("❌ Datenbankverbindung fehlgeschlagen: " . $e->getMessage() . "\n");
}

// Migrations-Verzeichnis
$migrations_dir = __DIR__ . '/migrations';

if (!is_dir($migrations_dir)) {
    die("❌ Migrations-Verzeichnis nicht gefunden: $migrations_dir\n");
}

// Migrations-Tabelle erstellen (zum Tracking welche Migrationen bereits ausgeführt wurden)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Migrations-Tracking-Tabelle bereit\n\n";
} catch (PDOException $e) {
    die("❌ Fehler beim Erstellen der Migrations-Tabelle: " . $e->getMessage() . "\n");
}

// Alle SQL-Dateien im Migrations-Verzeichnis finden
$migration_files = glob($migrations_dir . '/*.sql');
sort($migration_files);

if (empty($migration_files)) {
    echo "ℹ️  Keine Migrations-Dateien gefunden in: $migrations_dir\n";
    exit(0);
}

echo "📂 Gefundene Migrationen:\n";
foreach ($migration_files as $file) {
    echo "   - " . basename($file) . "\n";
}
echo "\n";

// Bereits ausgeführte Migrationen abrufen
$stmt = $pdo->query("SELECT migration FROM migrations");
$executed_migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

$executed_count = 0;
$skipped_count = 0;
$errors = [];

// Jede Migration ausführen
foreach ($migration_files as $migration_file) {
    $migration_name = basename($migration_file);
    
    // Prüfen, ob Migration bereits ausgeführt wurde
    if (in_array($migration_name, $executed_migrations)) {
        echo "⏭️  Überspringe (bereits ausgeführt): $migration_name\n";
        $skipped_count++;
        continue;
    }
    
    echo "🔄 Führe aus: $migration_name ... ";
    
    try {
        // SQL-Datei lesen
        $sql = file_get_contents($migration_file);
        
        if (empty($sql)) {
            throw new Exception("Leere SQL-Datei");
        }
        
        // SQL ausführen
        $pdo->exec($sql);
        
        // Migration als ausgeführt markieren
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration_name]);
        
        echo "✅ Erfolgreich\n";
        $executed_count++;
        
    } catch (Exception $e) {
        echo "❌ Fehler\n";
        $errors[] = [
            'migration' => $migration_name,
            'error' => $e->getMessage()
        ];
    }
}

echo "\n";
echo "============================\n";
echo "📊 Zusammenfassung:\n";
echo "   ✅ Ausgeführt: $executed_count\n";
echo "   ⏭️  Übersprungen: $skipped_count\n";
echo "   ❌ Fehler: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n⚠️  Fehlerdetails:\n";
    foreach ($errors as $error) {
        echo "   • {$error['migration']}: {$error['error']}\n";
    }
}

echo "\n✨ Migration abgeschlossen!\n";

// Überprüfung: Wurde customer_tracking Tabelle erstellt?
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_tracking'");
    if ($stmt->rowCount() > 0) {
        echo "\n✅ Tracking-Tabelle 'customer_tracking' existiert\n";
        
        // Struktur anzeigen
        $stmt = $pdo->query("DESCRIBE customer_tracking");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n📋 Tabellen-Struktur:\n";
        foreach ($columns as $col) {
            echo "   • {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "\n⚠️  Warnung: Tracking-Tabelle 'customer_tracking' wurde nicht gefunden\n";
    }
} catch (PDOException $e) {
    echo "\n⚠️  Konnte Tabellen-Status nicht prüfen: " . $e->getMessage() . "\n";
}

echo "\n🎉 Fertig!\n";
echo "\n⚠️  WICHTIG: Lösche diese Datei nach erfolgreicher Migration!\n";
