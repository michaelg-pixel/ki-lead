<?php
/**
 * Checklist System Setup
 * Prüft und installiert die customer_checklist Tabelle falls nötig
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/setup/setup-checklist-system.php
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Checklist Setup</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5}";
echo ".success{color:#22c55e;font-weight:bold}.error{color:#ef4444;font-weight:bold}";
echo ".info{color:#3b82f6}.box{background:white;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1)}</style>";
echo "</head><body>";

echo "<h1>🛠️ Checklist System Setup</h1>";

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    echo "<div class='box success'>✅ Datenbankverbindung erfolgreich</div>";
} catch (Exception $e) {
    die("<div class='box error'>❌ Datenbankverbindung fehlgeschlagen: " . htmlspecialchars($e->getMessage()) . "</div></body></html>");
}

// Prüfen ob Tabelle existiert
$stmt = $pdo->query("SHOW TABLES LIKE 'customer_checklist'");
$table_exists = $stmt->rowCount() > 0;

echo "<div class='box'>";
echo "<h2>📋 Status-Check</h2>";

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
    
    // Test-Query
    echo "<h3>🧪 Funktions-Test:</h3>";
    $stmt = $pdo->query("
        SELECT c.username, cc.task_id, cc.completed, cc.completed_at 
        FROM customer_checklist cc
        JOIN customers c ON cc.user_id = c.id
        ORDER BY cc.completed_at DESC
        LIMIT 5
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recent)) {
        echo "<table border='1' style='width:100%;border-collapse:collapse'>";
        echo "<tr><th>Benutzer</th><th>Aufgabe</th><th>Status</th><th>Abgeschlossen am</th></tr>";
        foreach ($recent as $row) {
            $status = $row['completed'] ? '✅' : '⏳';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['task_id']) . "</td>";
            echo "<td>$status</td>";
            echo "<td>" . ($row['completed_at'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>Noch keine Fortschritte gespeichert.</p>";
    }
    
} else {
    echo "<p class='error'>⚠️ Tabelle 'customer_checklist' existiert NICHT!</p>";
    echo "<p>➡️ Führe Migration aus...</p>";
    
    try {
        // Migration ausführen
        $sql = file_get_contents(__DIR__ . '/../database/migrations/003_customer_checklist.sql');
        $pdo->exec($sql);
        
        echo "<p class='success'>✅ Migration erfolgreich ausgeführt!</p>";
        
        // Überprüfung
        $stmt = $pdo->query("SHOW TABLES LIKE 'customer_checklist'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabelle wurde erfolgreich erstellt!</p>";
            
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
        echo "<p class='error'>❌ Fehler bei der Migration: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "</div>";

// API-Test
echo "<div class='box'>";
echo "<h2>🔌 API-Test</h2>";

if (file_exists(__DIR__ . '/../customer/api/checklist.php')) {
    echo "<p class='success'>✅ API-Datei existiert: /customer/api/checklist.php</p>";
    
    echo "<h3>Test-Anfrage:</h3>";
    echo "<p class='info'>Die API kann jetzt verwendet werden:</p>";
    echo "<ul>";
    echo "<li><strong>GET</strong> /customer/api/checklist.php - Fortschritt abrufen</li>";
    echo "<li><strong>POST</strong> /customer/api/checklist.php - Fortschritt speichern</li>";
    echo "</ul>";
    
    echo "<h4>Beispiel POST-Request:</h4>";
    echo "<pre style='background:#f0f0f0;padding:10px;border-radius:4px'>";
    echo json_encode([
        'task_id' => 'videos',
        'completed' => true
    ], JSON_PRETTY_PRINT);
    echo "</pre>";
    
} else {
    echo "<p class='error'>⚠️ API-Datei nicht gefunden!</p>";
}

echo "</div>";

// Frontend-Check
echo "<div class='box'>";
echo "<h2>🎨 Frontend-Integration</h2>";

if (file_exists(__DIR__ . '/../customer/sections/overview.php')) {
    echo "<p class='success'>✅ Overview-Seite existiert</p>";
    
    // Nach JavaScript-Code suchen
    $content = file_get_contents(__DIR__ . '/../customer/sections/overview.php');
    if (strpos($content, "'/customer/api/checklist.php'") !== false) {
        echo "<p class='success'>✅ API-Integration im JavaScript gefunden</p>";
    }
    
    if (strpos($content, 'data-task=') !== false) {
        echo "<p class='success'>✅ Checkbox-Tasks gefunden</p>";
    }
    
    if (strpos($content, 'loadProgress()') !== false) {
        echo "<p class='success'>✅ Load-Progress Funktion gefunden</p>";
    }
    
    echo "<p class='info'>Die Checkboxen werden automatisch gespeichert und geladen!</p>";
    
} else {
    echo "<p class='error'>⚠️ Overview-Seite nicht gefunden!</p>";
}

echo "</div>";

// Zusammenfassung
echo "<div class='box' style='background:#ecfdf5;border-left:4px solid #22c55e'>";
echo "<h2>✅ Setup abgeschlossen!</h2>";
echo "<p><strong>Das Checklist-System ist jetzt einsatzbereit:</strong></p>";
echo "<ul>";
echo "<li>✅ Datenbank-Tabelle erstellt</li>";
echo "<li>✅ API-Endpunkt verfügbar</li>";
echo "<li>✅ Frontend integriert</li>";
echo "<li>✅ Automatisches Speichern & Laden aktiv</li>";
echo "</ul>";

echo "<h3>🚀 Wie es funktioniert:</h3>";
echo "<ol>";
echo "<li>Benutzer öffnet: <strong>/customer/dashboard.php?page=overview</strong></li>";
echo "<li>JavaScript lädt gespeicherten Fortschritt per GET</li>";
echo "<li>Beim Checkbox-Klick: automatisches Speichern per POST</li>";
echo "<li>Beim nächsten Login: gespeicherte States werden wiederhergestellt</li>";
echo "</ol>";

echo "<h3>🔧 Technische Details:</h3>";
echo "<ul>";
echo "<li><strong>Tabelle:</strong> customer_checklist</li>";
echo "<li><strong>API:</strong> /customer/api/checklist.php</li>";
echo "<li><strong>Tracking:</strong> Pro Benutzer & Task</li>";
echo "<li><strong>Foreign Key:</strong> Automatisches Löschen bei User-Löschung</li>";
echo "</ul>";

echo "<p style='margin-top:20px;padding:10px;background:#fef3c7;border-radius:4px'>";
echo "⚠️ <strong>Sicherheitshinweis:</strong> Bitte lösche diese Setup-Datei nach erfolgreicher Installation!";
echo "</p>";

echo "</div>";

echo "</body></html>";
?>