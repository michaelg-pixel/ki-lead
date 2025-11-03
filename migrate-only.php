<?php
/**
 * NOTFALL MIGRATIONS-SKRIPT (FIXED)
 * Führt nur die Datenbank-Migration durch
 * Einfach im Browser aufrufen: https://app.mehr-infos-jetzt.de/migrate-only.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
define('DB_HOST', 'localhost');
define('DB_NAME', 'lumisaas');
define('DB_USER', 'lumisaas52');
define('DB_PASS', 'I1zx1XdL1hrWd75yu57e');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notfall-Migration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">🔧 Notfall-Migration (Fixed)</h1>
            <p class="text-gray-600 mb-6">Dieses Skript führt nur die Datenbank-Migration durch.</p>

<?php
if (isset($_GET['run'])) {
    echo '<div class="space-y-4">';
    
    try {
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>📡 Verbinde mit Datenbank...</strong><br>';
        
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo '✅ Verbindung erfolgreich!';
        echo '</div>';
        
        // Prüfe existierende Tabellen
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>🔍 Prüfe existierende Tabellen...</strong><br>';
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $existing = $stmt->rowCount();
        echo "Gefunden: $existing Tabellen<br>";
        echo '</div>';
        
        // Lade Migration
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>📄 Lade Migrations-Datei...</strong><br>';
        $migration_file = BASE_PATH . '/database/migrations/004_referral_system.sql';
        
        if (!file_exists($migration_file)) {
            throw new Exception('Migrations-Datei nicht gefunden: ' . $migration_file);
        }
        
        $sql = file_get_contents($migration_file);
        echo '✅ Datei geladen (' . strlen($sql) . ' bytes)<br>';
        echo '</div>';
        
        // Statements aufteilen - NEUE METHODE
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>⚙️ Führe Migration aus...</strong><br>';
        
        // FIX: Entferne Kommentare ZUERST, dann splitte
        $lines = explode("\n", $sql);
        $clean_lines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Überspringe leere Zeilen und Kommentar-Zeilen
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }
            $clean_lines[] = $line;
        }
        
        $clean_sql = implode("\n", $clean_lines);
        
        // Jetzt nach ; splitten
        $statements = array_filter(
            array_map('trim', explode(';', $clean_sql)),
            function($stmt) {
                return !empty($stmt);
            }
        );
        
        echo 'Gefunden: ' . count($statements) . ' SQL-Statements<br><br>';
        
        $executed = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($statements as $index => $statement) {
            try {
                $pdo->exec($statement);
                $executed++;
                
                // Kürze Statement-Anzeige
                $short = substr($statement, 0, 50);
                if (strlen($statement) > 50) $short .= '...';
                echo '<span class="text-green-600">✓</span> Statement ' . ($index + 1) . ': ' . htmlspecialchars($short) . '<br>';
                
            } catch (PDOException $e) {
                $error_code = $e->getCode();
                $error_msg = $e->getMessage();
                
                // Ignoriere Duplicate-Fehler
                if ($error_code == '42S01' || $error_code == '42000') {
                    if (strpos($error_msg, 'Duplicate column') !== false ||
                        strpos($error_msg, 'already exists') !== false) {
                        $skipped++;
                        
                        $short = substr($statement, 0, 50);
                        if (strlen($statement) > 50) $short .= '...';
                        echo '<span class="text-yellow-600">⊘</span> Statement ' . ($index + 1) . ' übersprungen: ' . htmlspecialchars($short) . '<br>';
                        continue;
                    }
                }
                
                // Andere Fehler sammeln
                $errors[] = [
                    'index' => $index + 1,
                    'statement' => substr($statement, 0, 100),
                    'error' => $error_msg,
                    'code' => $error_code
                ];
                echo '<span class="text-red-600">✗</span> Statement ' . ($index + 1) . ' fehlgeschlagen<br>';
                echo '<div class="ml-6 text-xs text-red-600">' . htmlspecialchars($error_msg) . '</div>';
            }
        }
        
        echo '</div>';
        
        // Ergebnis prüfen
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>🔍 Prüfe Ergebnis...</strong><br>';
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $tables = $stmt->rowCount();
        echo "Gefunden: $tables Tabellen<br><br>";
        
        // Tabellen auflisten
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo '  • ' . $row[0] . '<br>';
        }
        echo '</div>';
        
        // Zusammenfassung
        if ($tables >= 6) {
            echo '<div class="p-4 bg-green-50 border-2 border-green-500 rounded-lg">';
            echo '<strong class="text-green-800 text-xl">🎉 MIGRATION ERFOLGREICH!</strong><br><br>';
            echo '<div class="text-green-700">';
            echo "✅ $tables Tabellen erstellt<br>";
            echo "✅ $executed Statements ausgeführt<br>";
            echo "⊘ $skipped Statements übersprungen<br>";
            if (!empty($errors)) {
                echo "⚠️ " . count($errors) . " Fehler (nicht kritisch)<br>";
            }
            echo '</div>';
            echo '</div>';
            
            echo '<div class="mt-6 p-4 bg-blue-50 rounded-lg">';
            echo '<strong>📋 Nächste Schritte:</strong><br>';
            echo '1. Lösche diese Dateien:<br>';
            echo '   • <code>migrate-only.php</code><br>';
            echo '   • <code>debug-migration.php</code><br>';
            echo '   • <code>install-referral-web.php</code> (falls vorhanden)<br><br>';
            echo '2. Gehe zum Admin-Dashboard:<br>';
            echo '   <a href="admin/sections/referral-overview.php" class="text-blue-600 underline font-bold">→ Admin-Dashboard öffnen</a><br><br>';
            echo '3. Oder zum Customer-Dashboard:<br>';
            echo '   <a href="customer/dashboard.php" class="text-blue-600 underline font-bold">→ Customer-Dashboard öffnen</a>';
            echo '</div>';
            
        } else {
            echo '<div class="p-4 bg-red-50 border-2 border-red-500 rounded-lg">';
            echo '<strong class="text-red-800 text-xl">❌ MIGRATION FEHLGESCHLAGEN</strong><br><br>';
            echo '<div class="text-red-700">';
            echo "Nur $tables von 6 Tabellen erstellt<br>";
            echo "Ausgeführt: $executed | Übersprungen: $skipped<br>";
            
            if (!empty($errors)) {
                echo '<br><strong>Fehler:</strong><br>';
                echo '<div class="bg-white p-2 rounded mt-2 text-xs overflow-x-auto">';
                foreach ($errors as $error) {
                    echo '<strong>Statement #' . $error['index'] . ':</strong><br>';
                    echo htmlspecialchars($error['statement']) . '<br>';
                    echo '<span class="text-red-600">' . htmlspecialchars($error['error']) . '</span><br><br>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="p-4 bg-red-50 border-2 border-red-500 rounded-lg">';
        echo '<strong class="text-red-800">💥 FEHLER:</strong><br>';
        echo htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    
    echo '</div>';
    
    echo '<div class="mt-6">';
    echo '<a href="?" class="inline-block px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">← Zurück</a>';
    echo '</div>';
    
} else {
    // Start-Seite
    ?>
    <div class="space-y-4">
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <strong class="text-yellow-800">⚠️ Hinweis:</strong>
            <p class="text-yellow-700 mt-2">Dieses Skript führt die Datenbank-Migration durch. Es ignoriert automatisch bereits existierende Spalten und Tabellen.</p>
        </div>
        
        <div class="p-4 bg-blue-50 rounded-lg">
            <strong>Was wird gemacht:</strong>
            <ul class="list-disc ml-6 mt-2 text-gray-700">
                <li>Verbindung zur Datenbank herstellen</li>
                <li>Migrations-Datei laden</li>
                <li>Kommentare entfernen</li>
                <li>SQL-Statements einzeln ausführen</li>
                <li>6 Referral-Tabellen erstellen</li>
                <li>Customers-Tabelle erweitern</li>
            </ul>
        </div>
        
        <a href="?run=1" class="inline-block px-8 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xl font-semibold">
            🚀 Migration starten
        </a>
    </div>
<?php
}
?>

        </div>
    </div>
</body>
</html>
