<?php
/**
 * DRIP-CONTENT MIGRATION - FIXED VERSION
 * Fügt granted_at Spalte zu course_access hinzu
 * Aufruf: https://app.mehr-infos-jetzt.de/database/migrate-drip-content.php
 */

require_once '../config/database.php';

// Sicherheits-Check (nur einmal ausführbar) - mit force parameter überbrückbar
$lockfile = __DIR__ . '/drip-content-migration.lock';
$force = isset($_GET['force']) && $_GET['force'] === 'yes';

if (file_exists($lockfile) && !$force) {
    die("⚠️ Migration wurde bereits ausgeführt! Lockfile gefunden.<br><br>
         <a href='?force=yes' style='color: red; font-weight: bold;'>→ Migration trotzdem erneut ausführen (FORCE)</a>");
}

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>Drip-Content Migration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .step h3 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step p {
            color: #555;
            line-height: 1.6;
            margin: 8px 0;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .success h3 { color: #28a745; }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .error h3 { color: #dc3545; }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .warning h3 { color: #ff9800; }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            overflow-x: auto;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-new { background: #e3f2fd; color: #1976d2; }
        .badge-updated { background: #fff3e0; color: #f57c00; }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔒 Drip-Content Migration</h1>
    <p class='subtitle'>Aktivierung des zeitgesteuerten Freischaltungs-Systems</p>
";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='step'>
        <h3>📋 Step 1: Überprüfung der Tabellen-Struktur</h3>";
    
    // Check ob course_access existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'course_access'");
    if ($stmt->rowCount() === 0) {
        echo "<div class='step error'>
            <h3>❌ Fehler: Tabelle 'course_access' nicht gefunden!</h3>
            <p>Die Tabelle muss zuerst erstellt werden.</p>
        </div>";
        die("</div></div></body></html>");
    }
    echo "<p>✓ Tabelle 'course_access' gefunden</p>";
    
    // Check ob granted_at bereits existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM course_access LIKE 'granted_at'");
    $column_exists = $stmt->rowCount() > 0;
    
    if ($column_exists) {
        echo "<p>✓ Spalte 'granted_at' existiert bereits</p>";
    } else {
        echo "<p>→ Spalte 'granted_at' wird hinzugefügt...</p>";
    }
    echo "</div>";
    
    // Step 2: Spalte hinzufügen (wenn nicht vorhanden)
    if (!$column_exists) {
        echo "<div class='step'>
            <h3>🔧 Step 2: Spalte 'granted_at' hinzufügen<span class='badge badge-new'>NEW</span></h3>";
        
        $pdo->exec("
            ALTER TABLE course_access 
            ADD COLUMN granted_at TIMESTAMP NULL DEFAULT NULL 
            COMMENT 'Zeitpunkt der Zugangserteilung für Drip-Content'
        ");
        
        echo "<p>✓ Spalte erfolgreich hinzugefügt (NULL erlaubt für Migration)</p>
            <div class='code'>ALTER TABLE course_access ADD COLUMN granted_at TIMESTAMP NULL DEFAULT NULL</div>
        </div>";
    } else {
        echo "<div class='step success'>
            <h3>✓ Step 2: Spalte existiert bereits</h3>
            <p>Keine Änderung notwendig</p>
        </div>";
    }
    
    // Step 3: Bestehende Einträge aktualisieren (FIXED)
    echo "<div class='step'>
        <h3>🔄 Step 3: Bestehende Einträge aktualisieren</h3>";
    
    // Zuerst zählen wir NULL Einträge
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM course_access WHERE granted_at IS NULL");
    $result = $stmt->fetch();
    $null_count = $result['count'];
    
    if ($null_count > 0) {
        // Setze granted_at auf jetzt für alle NULL-Einträge (FIXED: Kein Vergleich mit 0000-00-00)
        $pdo->exec("
            UPDATE course_access 
            SET granted_at = NOW() 
            WHERE granted_at IS NULL
        ");
        echo "<p>✓ {$null_count} Einträge aktualisiert (granted_at = NOW())</p>";
        echo "<p class='warning'>⚠️ Für bestehende Nutzer werden alle Lektionen sofort freigeschaltet</p>";
    } else {
        echo "<p>✓ Alle Einträge haben bereits ein granted_at Datum</p>";
    }
    
    // Setze granted_at als NOT NULL mit DEFAULT CURRENT_TIMESTAMP
    if (!$column_exists || $null_count > 0) {
        echo "<p>→ Spalte wird auf NOT NULL mit DEFAULT gesetzt...</p>";
        $pdo->exec("
            ALTER TABLE course_access 
            MODIFY COLUMN granted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
            COMMENT 'Zeitpunkt der Zugangserteilung für Drip-Content'
        ");
        echo "<p>✓ Spalte granted_at ist jetzt NOT NULL mit DEFAULT CURRENT_TIMESTAMP</p>";
    }
    
    echo "</div>";
    
    // Step 4: Statistiken
    echo "<div class='step'>
        <h3>📊 Step 4: Statistiken</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_access,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT course_id) as unique_courses,
            MIN(granted_at) as earliest_access,
            MAX(granted_at) as latest_access
        FROM course_access
    ");
    $stats = $stmt->fetch();
    
    echo "<p>→ Total Zugangsberechtigungen: <strong>{$stats['total_access']}</strong></p>";
    echo "<p>→ Unique Nutzer: <strong>{$stats['unique_users']}</strong></p>";
    echo "<p>→ Unique Kurse: <strong>{$stats['unique_courses']}</strong></p>";
    echo "<p>→ Frühester Zugang: <strong>{$stats['earliest_access']}</strong></p>";
    echo "<p>→ Letzter Zugang: <strong>{$stats['latest_access']}</strong></p>";
    
    // Prüfe Lektionen mit Drip-Content
    $stmt = $pdo->query("
        SELECT COUNT(*) as drip_lessons 
        FROM course_lessons 
        WHERE unlock_after_days > 0
    ");
    $drip_stats = $stmt->fetch();
    echo "<p>→ Lektionen mit Drip-Content: <strong>{$drip_stats['drip_lessons']}</strong></p>";
    echo "</div>";
    
    // Step 5: Aktivierung
    echo "<div class='step success'>
        <h3>🎉 Step 5: Drip-Content aktiviert!</h3>
        <p>✓ Datenbank-Migration erfolgreich abgeschlossen</p>
        <p>✓ Zeitgesteuertes Freischalten ist jetzt bereit</p>
        <p>✓ Neue Nutzer sehen gesperrte Lektionen basierend auf granted_at</p>
    </div>";
    
    // Beispiel-Abfrage
    echo "<div class='step'>
        <h3>💡 Verwendung</h3>
        <p>So funktioniert Drip-Content jetzt:</p>
        <div class='code'>
-- Lektion mit Tag 1 Freischaltung
UPDATE course_lessons 
SET unlock_after_days = 1 
WHERE id = 123;

-- Lektion mit Tag 7 Freischaltung
UPDATE course_lessons 
SET unlock_after_days = 7 
WHERE id = 456;

-- Sofort freigeschaltete Lektion
UPDATE course_lessons 
SET unlock_after_days = 0 
WHERE id = 789;
        </div>
        <p>→ User bekommt Zugang mit <code>granted_at = NOW()</code></p>
        <p>→ Tag 1 Lektion: Freigeschaltet ab <code>granted_at + 1 Tag</code></p>
        <p>→ Tag 7 Lektion: Freigeschaltet ab <code>granted_at + 7 Tage</code></p>
    </div>";
    
    // Nächster Schritt
    echo "<div class='step success'>
        <h3>🚀 Nächster Schritt</h3>
        <p>Migration erfolgreich! Jetzt Drip-Content aktivieren:</p>
        <a href='activate-drip-content.php' class='btn'>→ Drip-Content aktivieren</a>
    </div>";
    
    // Lockfile erstellen (nur wenn nicht FORCE)
    if (!$force) {
        file_put_contents($lockfile, date('Y-m-d H:i:s') . "\nMigration erfolgreich abgeschlossen");
    }
    
    echo "<div class='footer'>
        <p>🔒 Migration abgeschlossen" . ($force ? " (FORCE Modus)" : " (Lockfile erstellt)") . "</p>
        <p>Zeitpunkt: " . date('d.m.Y H:i:s') . "</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>
        <h3>❌ Fehler aufgetreten!</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Datei:</strong> " . htmlspecialchars($e->getFile()) . "</p>
        <p><strong>Zeile:</strong> " . $e->getLine() . "</p>
        <p style='margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;'>
            <strong>Mögliche Lösungen:</strong><br>
            • Stelle sicher, dass die Datenbank-Verbindung funktioniert<br>
            • Prüfe die Berechtigungen für ALTER TABLE<br>
            • Kontaktiere den Support mit dieser Fehlermeldung
        </p>
    </div>";
}

echo "</div></body></html>";
?>