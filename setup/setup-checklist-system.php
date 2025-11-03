<?php
/**
 * Checklist System Setup - IMPROVED VERSION
 * Prüft und installiert die customer_checklist Tabelle
 * Erkennt automatisch die richtige User-Tabelle
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/setup/setup-checklist-system.php
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Checklist Setup</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5}";
echo ".success{color:#22c55e;font-weight:bold}.error{color:#ef4444;font-weight:bold}";
echo ".warning{color:#f59e0b;font-weight:bold}.info{color:#3b82f6}";
echo ".box{background:white;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1)}</style>";
echo "</head><body>";

echo "<h1>🛠️ Checklist System Setup (v2.0)</h1>";

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    echo "<div class='box success'>✅ Datenbankverbindung erfolgreich</div>";
} catch (Exception $e) {
    die("<div class='box error'>❌ Datenbankverbindung fehlgeschlagen: " . htmlspecialchars($e->getMessage()) . "</div></body></html>");
}

echo "<div class='box'>";
echo "<h2>🔍 Schritt 1: Datenbank-Analyse</h2>";

// Alle Tabellen abrufen
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<p class='info'>📊 Gefundene Tabellen: " . count($tables) . "</p>";

// Nach User-Tabelle suchen
$user_table = null;
$possible_tables = ['customers', 'users', 'customer', 'user'];

foreach ($possible_tables as $table) {
    if (in_array($table, $tables)) {
        $user_table = $table;
        echo "<p class='success'>✅ User-Tabelle gefunden: <strong>$user_table</strong></p>";
        break;
    }
}

if (!$user_table) {
    // Versuche intelligenter zu suchen
    foreach ($tables as $table) {
        if (stripos($table, 'user') !== false || stripos($table, 'customer') !== false) {
            // Prüfe ob Tabelle eine 'id' Spalte hat
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $has_id = false;
            foreach ($columns as $col) {
                if ($col['Field'] === 'id' && $col['Key'] === 'PRI') {
                    $has_id = true;
                    break;
                }
            }
            if ($has_id) {
                $user_table = $table;
                echo "<p class='warning'>⚠️ Mögliche User-Tabelle gefunden: <strong>$user_table</strong></p>";
                break;
            }
        }
    }
}

if (!$user_table) {
    echo "<p class='warning'>⚠️ Keine User-Tabelle gefunden. Erstelle Tabelle OHNE Foreign Key.</p>";
}

echo "</div>";

// Prüfen ob Tabelle existiert
echo "<div class='box'>";
echo "<h2>📋 Schritt 2: Checklist-Tabelle prüfen</h2>";

$stmt = $pdo->query("SHOW TABLES LIKE 'customer_checklist'");
$table_exists = $stmt->rowCount() > 0;

if ($table_exists) {
    echo "<p class='success'>✅ Tabelle 'customer_checklist' existiert bereits!</p>";
    
    // Struktur anzeigen
    $stmt = $pdo->query("DESCRIBE customer_checklist");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tabellen-Struktur:</h3><ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    // Anzahl Einträge
    $stmt = $pdo->query("SELECT COUNT(*) FROM customer_checklist");
    $count = $stmt->fetchColumn();
    echo "<p class='info'>📊 Gespeicherte Fortschritte: <strong>$count</strong></p>";
    
} else {
    echo "<p class='warning'>⚠️ Tabelle 'customer_checklist' existiert NICHT!</p>";
    echo "<p>➡️ Erstelle Tabelle...</p>";
    
    try {
        // SQL-Statement dynamisch erstellen
        $sql = "
        CREATE TABLE IF NOT EXISTS `customer_checklist` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) NOT NULL,
          `task_id` VARCHAR(50) NOT NULL,
          `completed` TINYINT(1) DEFAULT 0,
          `completed_at` TIMESTAMP NULL DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_user_task` (`user_id`, `task_id`),
          INDEX `idx_user_id` (`user_id`)";
        
        // Foreign Key nur hinzufügen wenn User-Tabelle existiert
        if ($user_table) {
            $sql .= ",\n  FOREIGN KEY (`user_id`) REFERENCES `$user_table`(`id`) ON DELETE CASCADE";
            echo "<p class='info'>🔗 Foreign Key wird zu '$user_table' erstellt</p>";
        } else {
            echo "<p class='warning'>⚠️ Kein Foreign Key (User-Tabelle nicht gefunden)</p>";
        }
        
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Tabelle erstellen
        $pdo->exec($sql);
        
        echo "<p class='success'>✅ Tabelle erfolgreich erstellt!</p>";
        
        // Überprüfung
        $stmt = $pdo->query("SHOW TABLES LIKE 'customer_checklist'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabelle wurde verifiziert!</p>";
            
            // Struktur anzeigen
            $stmt = $pdo->query("DESCRIBE customer_checklist");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Tabellen-Struktur:</h3><ul>";
            foreach ($columns as $col) {
                echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Fehler beim Erstellen der Tabelle:</p>";
        echo "<pre style='background:#fee2e2;padding:10px;border-radius:4px'>";
        echo htmlspecialchars($e->getMessage());
        echo "</pre>";
        
        // Alternativer Versuch ohne Foreign Key
        if ($user_table) {
            echo "<p class='warning'>⚠️ Versuche Erstellung ohne Foreign Key...</p>";
            try {
                $sql_no_fk = "
                CREATE TABLE IF NOT EXISTS `customer_checklist` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `user_id` INT(11) NOT NULL,
                  `task_id` VARCHAR(50) NOT NULL,
                  `completed` TINYINT(1) DEFAULT 0,
                  `completed_at` TIMESTAMP NULL DEFAULT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_user_task` (`user_id`, `task_id`),
                  INDEX `idx_user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                
                $pdo->exec($sql_no_fk);
                echo "<p class='success'>✅ Tabelle ohne Foreign Key erstellt!</p>";
            } catch (Exception $e2) {
                echo "<p class='error'>❌ Auch ohne Foreign Key fehlgeschlagen: " . htmlspecialchars($e2->getMessage()) . "</p>";
            }
        }
    }
}

echo "</div>";

// API-Test
echo "<div class='box'>";
echo "<h2>🔌 Schritt 3: API-Test</h2>";

if (file_exists(__DIR__ . '/../customer/api/checklist.php')) {
    echo "<p class='success'>✅ API-Datei existiert: /customer/api/checklist.php</p>";
    
    echo "<h3>API-Endpunkte:</h3>";
    echo "<ul>";
    echo "<li><strong>GET</strong> /customer/api/checklist.php - Fortschritt abrufen</li>";
    echo "<li><strong>POST</strong> /customer/api/checklist.php - Fortschritt speichern</li>";
    echo "</ul>";
    
} else {
    echo "<p class='error'>⚠️ API-Datei nicht gefunden!</p>";
}

echo "</div>";

// Frontend-Check
echo "<div class='box'>";
echo "<h2>🎨 Schritt 4: Frontend-Integration</h2>";

if (file_exists(__DIR__ . '/../customer/sections/overview.php')) {
    echo "<p class='success'>✅ Overview-Seite existiert</p>";
    
    $content = file_get_contents(__DIR__ . '/../customer/sections/overview.php');
    if (strpos($content, "'/customer/api/checklist.php'") !== false) {
        echo "<p class='success'>✅ API-Integration gefunden</p>";
    }
    
    if (strpos($content, 'data-task=') !== false) {
        echo "<p class='success'>✅ Checkbox-Tasks gefunden</p>";
    }
    
    if (strpos($content, 'loadProgress()') !== false) {
        echo "<p class='success'>✅ Load-Progress Funktion gefunden</p>";
    }
    
} else {
    echo "<p class='error'>⚠️ Overview-Seite nicht gefunden!</p>";
}

echo "</div>";

// Funktionstest
echo "<div class='box'>";
echo "<h2>🧪 Schritt 5: Funktionstest</h2>";

try {
    // Test-Insert (nur wenn Tabelle existiert)
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_checklist'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Tabelle ist bereit für Daten</p>";
        
        // Prüfe ob wir einen Test-User haben
        if ($user_table) {
            $stmt = $pdo->query("SELECT id FROM `$user_table` LIMIT 1");
            $test_user = $stmt->fetch();
            if ($test_user) {
                echo "<p class='info'>ℹ️ Test-User gefunden (ID: {$test_user['id']})</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p class='warning'>⚠️ Funktionstest übersprungen: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Zusammenfassung
$all_ok = true;
$stmt = $pdo->query("SHOW TABLES LIKE 'customer_checklist'");
$table_ok = $stmt->rowCount() > 0;
$api_ok = file_exists(__DIR__ . '/../customer/api/checklist.php');
$frontend_ok = file_exists(__DIR__ . '/../customer/sections/overview.php');

$all_ok = $table_ok && $api_ok && $frontend_ok;

if ($all_ok) {
    echo "<div class='box' style='background:#d1fae5;border-left:4px solid #22c55e'>";
    echo "<h2>✅ Setup erfolgreich abgeschlossen!</h2>";
} else {
    echo "<div class='box' style='background:#fef3c7;border-left:4px solid #f59e0b'>";
    echo "<h2>⚠️ Setup teilweise abgeschlossen</h2>";
}

echo "<h3>📊 Status-Übersicht:</h3>";
echo "<ul>";
echo "<li>" . ($table_ok ? "✅" : "❌") . " Datenbank-Tabelle</li>";
echo "<li>" . ($api_ok ? "✅" : "❌") . " API-Endpunkt</li>";
echo "<li>" . ($frontend_ok ? "✅" : "❌") . " Frontend-Integration</li>";
echo "</ul>";

if ($all_ok) {
    echo "<h3>🚀 Nächste Schritte:</h3>";
    echo "<ol>";
    echo "<li>Teste die Funktion unter: <a href='/customer/dashboard.php?page=overview'>/customer/dashboard.php?page=overview</a></li>";
    echo "<li>Setze einige Häkchen bei den Aufgaben</li>";
    echo "<li>Logge dich aus und wieder ein</li>";
    echo "<li>Verifiziere, dass die Häkchen gespeichert wurden</li>";
    echo "</ol>";
} else {
    echo "<h3>🔧 Problembehebung:</h3>";
    echo "<ul>";
    if (!$table_ok) echo "<li>❌ Datenbank-Tabelle konnte nicht erstellt werden - prüfe Fehlerlog</li>";
    if (!$api_ok) echo "<li>❌ API-Datei fehlt - stelle sicher dass /customer/api/checklist.php existiert</li>";
    if (!$frontend_ok) echo "<li>❌ Frontend-Datei fehlt - stelle sicher dass /customer/sections/overview.php existiert</li>";
    echo "</ul>";
    
    echo "<p><a href='/setup/check-db-structure.php' style='color:#3b82f6;font-weight:bold'>➡️ Datenbank-Struktur analysieren</a></p>";
}

echo "</div>";

echo "<div class='box' style='background:#fef3c7'>";
echo "<p>⚠️ <strong>Sicherheitshinweis:</strong> Lösche diese Setup-Dateien nach erfolgreicher Installation!</p>";
echo "<ul>";
echo "<li>/setup/setup-checklist-system.php</li>";
echo "<li>/setup/check-db-structure.php</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>