<?php
// DEBUG-SCRIPT für Freebie-Templates
// Diese Datei hilft, Probleme zu identifizieren

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DEBUG: Freebie-Templates System</h1>";
echo "<hr>";

// Test 1: Session
echo "<h2>1. Session-Check</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ Session aktiv<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'nicht gesetzt') . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'nicht gesetzt') . "<br>";
} else {
    echo "❌ Keine Session gefunden<br>";
    echo "→ Du musst eingeloggt sein!<br>";
}
echo "<hr>";

// Test 2: Database Config
echo "<h2>2. Datenbank-Konfiguration</h2>";
$config_path = __DIR__ . '/../config/database.php';
if (file_exists($config_path)) {
    echo "✅ config/database.php existiert<br>";
    echo "Pfad: $config_path<br>";
    
    try {
        require_once $config_path;
        echo "✅ Config geladen<br>";
        
        if (isset($pdo)) {
            echo "✅ PDO-Verbindung vorhanden<br>";
        } else {
            echo "❌ PDO-Variable nicht gesetzt<br>";
        }
    } catch (Exception $e) {
        echo "❌ Fehler beim Laden: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config/database.php nicht gefunden<br>";
    echo "Erwarteter Pfad: $config_path<br>";
    echo "Aktuelles Verzeichnis: " . __DIR__ . "<br>";
    echo "<strong>TIPP:</strong> Der config-Ordner liegt eine Ebene höher! Verwende '../config/database.php'<br>";
}
echo "<hr>";

// Test 3: Database Connection
echo "<h2>3. Datenbank-Verbindung</h2>";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT DATABASE()");
        $db_name = $stmt->fetchColumn();
        echo "✅ Verbindung erfolgreich<br>";
        echo "Datenbank: $db_name<br>";
    } catch (PDOException $e) {
        echo "❌ Verbindungsfehler: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Keine PDO-Verbindung verfügbar<br>";
}
echo "<hr>";

// Test 4: Freebies Table
echo "<h2>4. Freebies Tabelle</h2>";
if (isset($pdo)) {
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'freebies'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabelle 'freebies' existiert<br>";
            
            // Count records
            $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
            $count = $stmt->fetchColumn();
            echo "Anzahl Templates: $count<br>";
            
            // Show structure
            $stmt = $pdo->query("DESCRIBE freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<br><strong>Tabellenstruktur:</strong><br>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . $col['Field'] . "</td>";
                echo "<td>" . $col['Type'] . "</td>";
                echo "<td>" . $col['Null'] . "</td>";
                echo "<td>" . $col['Key'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "❌ Tabelle 'freebies' nicht gefunden<br>";
            echo "→ Tabelle muss erstellt werden!<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Fehler: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Keine Datenbankverbindung<br>";
}
echo "<hr>";

// Test 5: File Permissions
echo "<h2>5. Datei-Berechtigungen</h2>";
$files_to_check = [
    'freebie-templates.php',
    'freebie-create.php',
    'freebie-edit.php'
];

foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        $perms = fileperms($filepath);
        $perms_str = substr(sprintf('%o', $perms), -4);
        echo "✅ $file existiert (Rechte: $perms_str)<br>";
        
        if (is_readable($filepath)) {
            echo "  → Lesbar: Ja<br>";
        } else {
            echo "  → Lesbar: ❌ NEIN<br>";
        }
    } else {
        echo "❌ $file nicht gefunden<br>";
    }
}
echo "<hr>";

// Test 6: PHP Info
echo "<h2>6. PHP-Konfiguration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "<hr>";

// Test 7: Try to include the actual file
echo "<h2>7. Test freebie-templates.php laden</h2>";
$template_file = __DIR__ . '/freebie-templates.php';
if (file_exists($template_file)) {
    echo "Versuche freebie-templates.php zu laden...<br><br>";
    echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    echo "<strong>Output:</strong><br><br>";
    
    ob_start();
    try {
        // Reset session to start fresh
        session_destroy();
        session_start();
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        
        include $template_file;
    } catch (Exception $e) {
        echo "❌ Fehler beim Laden: " . $e->getMessage() . "<br>";
        echo "Stack Trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
    }
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "Länge: " . strlen($output) . " Zeichen<br>";
        // Show first 500 chars
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>";
    } else {
        echo "❌ Keine Ausgabe!<br>";
    }
    
    echo "</div>";
} else {
    echo "❌ Datei nicht gefunden: $template_file<br>";
}
echo "<hr>";

echo "<h2>✅ Debug abgeschlossen</h2>";
echo "<p><a href='freebie-templates.php'>→ Zurück zu Freebie-Templates</a></p>";
echo "<p><a href='dashboard.php'>→ Zurück zum Dashboard</a></p>";

// List all files in directory
echo "<hr>";
echo "<h2>8. Alle Dateien im /admin Verzeichnis:</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $size = filesize(__DIR__ . '/' . $file);
        $size_kb = round($size / 1024, 1);
        echo "<li>$file ($size_kb KB)</li>";
    }
}
echo "</ul>";
?>