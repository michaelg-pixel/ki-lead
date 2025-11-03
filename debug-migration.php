<?php
/**
 * DEBUG MIGRATIONS-SKRIPT
 * Zeigt genau was in der SQL-Datei steht
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Debug Migration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4">🔍 Debug Migration</h1>

<?php
$migration_file = BASE_PATH . '/database/migrations/004_referral_system.sql';

echo '<div class="space-y-4">';

// 1. Prüfe Datei
echo '<div class="p-4 bg-blue-50 rounded-lg">';
echo '<strong>📁 Migrations-Datei:</strong><br>';
echo 'Pfad: ' . $migration_file . '<br>';
echo 'Existiert: ' . (file_exists($migration_file) ? '✅ Ja' : '❌ Nein') . '<br>';
if (file_exists($migration_file)) {
    echo 'Größe: ' . filesize($migration_file) . ' bytes<br>';
    echo 'Lesbar: ' . (is_readable($migration_file) ? '✅ Ja' : '❌ Nein') . '<br>';
}
echo '</div>';

if (file_exists($migration_file)) {
    $sql = file_get_contents($migration_file);
    
    // 2. Zeige Datei-Inhalt (erste 2000 Zeichen)
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>📄 Datei-Inhalt (erste 2000 Zeichen):</strong><br>';
    echo '<pre class="bg-white p-4 rounded mt-2 text-xs overflow-x-auto">' . htmlspecialchars(substr($sql, 0, 2000)) . '</pre>';
    if (strlen($sql) > 2000) {
        echo '<em class="text-gray-500">... (noch ' . (strlen($sql) - 2000) . ' Zeichen mehr)</em>';
    }
    echo '</div>';
    
    // 3. Zeige wie Statements aufgeteilt werden
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>✂️ Statement-Aufteilung:</strong><br>';
    
    // Methode 1: Einfaches explode
    $parts = explode(';', $sql);
    echo 'Nach explode(";", $sql): ' . count($parts) . ' Teile<br>';
    
    // Methode 2: Mit trim
    $trimmed = array_map('trim', $parts);
    echo 'Nach trim: ' . count($trimmed) . ' Teile<br>';
    
    // Methode 3: Ohne leere
    $filtered = array_filter($trimmed, function($s) { return !empty($s); });
    echo 'Nach filter (nicht leer): ' . count($filtered) . ' Teile<br>';
    
    // Methode 4: Ohne Kommentare
    $final = array_filter($trimmed, function($s) { return !empty($s) && !preg_match('/^--/', $s); });
    echo 'Nach filter (keine Kommentare): ' . count($final) . ' Teile<br><br>';
    
    // Zeige die ersten 5 Statements
    echo '<strong>Erste 5 Statements:</strong><br>';
    echo '<div class="space-y-2 mt-2">';
    $count = 0;
    foreach ($final as $index => $stmt) {
        if ($count >= 5) break;
        echo '<div class="bg-white p-3 rounded">';
        echo '<strong>Statement #' . ($count + 1) . ':</strong><br>';
        echo '<pre class="text-xs mt-1 overflow-x-auto">' . htmlspecialchars(substr($stmt, 0, 300)) . '</pre>';
        if (strlen($stmt) > 300) {
            echo '<em class="text-gray-500">... (noch ' . (strlen($stmt) - 300) . ' Zeichen)</em>';
        }
        echo '</div>';
        $count++;
    }
    echo '</div>';
    echo '</div>';
    
    // 4. Test-Ausführung des ersten Statements
    echo '<div class="p-4 bg-yellow-50 rounded-lg">';
    echo '<strong>🧪 Test-Ausführung des ersten Statements:</strong><br>';
    
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=lumisaas;charset=utf8mb4",
            "lumisaas52",
            "I1zx1XdL1hrWd75yu57e",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo '✅ Datenbank-Verbindung erfolgreich<br><br>';
        
        $statements = array_values($final);
        if (count($statements) > 0) {
            $first_stmt = $statements[0];
            echo '<strong>Erstes Statement:</strong><br>';
            echo '<pre class="bg-white p-2 rounded text-xs mt-1">' . htmlspecialchars($first_stmt) . '</pre><br>';
            
            try {
                $pdo->exec($first_stmt);
                echo '<span class="text-green-600 font-bold">✅ Erfolgreich ausgeführt!</span>';
            } catch (PDOException $e) {
                echo '<span class="text-red-600 font-bold">❌ Fehler:</span><br>';
                echo '<pre class="bg-white p-2 rounded text-xs mt-1">' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<br>Error Code: ' . $e->getCode();
            }
        } else {
            echo '<span class="text-red-600">❌ Keine Statements gefunden!</span>';
        }
        
    } catch (Exception $e) {
        echo '<span class="text-red-600">❌ Verbindungsfehler:</span> ' . htmlspecialchars($e->getMessage());
    }
    echo '</div>';
    
    // 5. Prüfe existierende Tabellen
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>📊 Existierende Referral-Tabellen:</strong><br>';
    try {
        if (isset($pdo)) {
            $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_NUM);
            if (count($tables) > 0) {
                foreach ($tables as $table) {
                    echo '  • ' . $table[0] . '<br>';
                }
            } else {
                echo 'Keine Referral-Tabellen gefunden.';
            }
        }
    } catch (Exception $e) {
        echo 'Fehler: ' . $e->getMessage();
    }
    echo '</div>';
    
} else {
    echo '<div class="p-4 bg-red-50 border-2 border-red-500 rounded-lg">';
    echo '<strong class="text-red-800">❌ FEHLER:</strong> Migrations-Datei nicht gefunden!';
    echo '</div>';
}

echo '</div>';
?>

            <div class="mt-6">
                <a href="migrate-only.php" class="inline-block px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">← Zurück zur Migration</a>
            </div>
        </div>
    </div>
</body>
</html>
