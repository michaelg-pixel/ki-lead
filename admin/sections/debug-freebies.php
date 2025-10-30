<?php
// DEBUG-Seite - Hochladen nach /admin/sections/debug-freebies.php
// Aufruf: ?page=debug-freebies

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<div class="p-8">
    <h2 class="text-2xl font-bold mb-6">🔍 Freebie System Debug</h2>
    
    <div class="space-y-4">
        
        <!-- 1. Datei-Check -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">1️⃣ Datei-Prüfung</h3>
            
            <?php
            $files_to_check = [
                '/api/save-freebie.php' => __DIR__ . '/../../api/save-freebie.php',
                '/api/delete-freebie.php' => __DIR__ . '/../../api/delete-freebie.php',
                '/admin/sections/freebie-edit.php' => __DIR__ . '/freebie-edit.php',
                '/admin/sections/freebies.php' => __DIR__ . '/freebies.php',
            ];
            
            foreach ($files_to_check as $label => $path) {
                $exists = file_exists($path);
                $color = $exists ? 'green' : 'red';
                $icon = $exists ? '✅' : '❌';
                echo "<p style='color: $color;'>$icon <strong>$label</strong>: ";
                echo $exists ? "Gefunden" : "NICHT GEFUNDEN";
                echo " <span style='color: gray; font-size: 12px;'>($path)</span></p>";
            }
            ?>
        </div>
        
        <!-- 2. Datenbank-Check -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">2️⃣ Datenbank-Prüfung</h3>
            
            <?php
            try {
                // Tabelle existiert?
                $stmt = $pdo->query("SHOW TABLES LIKE 'freebies'");
                $table_exists = $stmt->rowCount() > 0;
                
                echo $table_exists ? "✅" : "❌";
                echo " <strong>freebies Tabelle:</strong> ";
                echo $table_exists ? "Existiert" : "NICHT GEFUNDEN";
                echo "<br><br>";
                
                if ($table_exists) {
                    // Spalten prüfen
                    $stmt = $pdo->query("SHOW COLUMNS FROM freebies");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    echo "<strong>Spalten in freebies:</strong><br>";
                    echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px;'>";
                    foreach ($columns as $col) {
                        echo "<span style='background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>$col</span>";
                    }
                    echo "</div><br>";
                    
                    // Foreign Keys prüfen
                    $stmt = $pdo->query("SELECT 
                        CONSTRAINT_NAME, 
                        COLUMN_NAME, 
                        REFERENCED_TABLE_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'freebies' 
                    AND TABLE_SCHEMA = DATABASE()
                    AND REFERENCED_TABLE_NAME IS NOT NULL");
                    
                    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<strong>Foreign Keys:</strong><br>";
                    if (empty($fks)) {
                        echo "❌ Keine Foreign Keys gefunden<br>";
                    } else {
                        foreach ($fks as $fk) {
                            echo "🔗 {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}<br>";
                        }
                    }
                    
                    // course_id NULL Check
                    echo "<br><strong>course_id Einstellungen:</strong><br>";
                    $stmt = $pdo->query("SHOW COLUMNS FROM freebies WHERE Field = 'course_id'");
                    $col_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($col_info) {
                        $null_allowed = $col_info['Null'] === 'YES';
                        echo ($null_allowed ? "✅" : "❌") . " NULL erlaubt: ";
                        echo $null_allowed ? "JA" : "NEIN (PROBLEM!)";
                        echo "<br>";
                        echo "Default: " . ($col_info['Default'] ?? 'keiner');
                    }
                    
                    // Templates zählen
                    $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
                    $count = $stmt->fetchColumn();
                    echo "<br><strong>Templates in DB:</strong> $count Stück<br>";
                }
                
            } catch (PDOException $e) {
                echo "❌ <strong>Datenbankfehler:</strong> " . $e->getMessage();
            }
            ?>
        </div>
        
        <!-- 3. URL-Test -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">3️⃣ URL-Tests</h3>
            
            <p><strong>Aktuelle URL:</strong><br>
            <code style="background: #f3f4f6; padding: 8px; display: block; margin-top: 4px;">
                <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
            </code></p>
            
            <p style="margin-top: 16px;"><strong>Test-Links:</strong></p>
            <ul style="list-style: none; padding: 0;">
                <li style="margin: 8px 0;">
                    <a href="?page=freebies" style="color: #8B5CF6; text-decoration: underline;">
                        → Template-Übersicht (?page=freebies)
                    </a>
                </li>
                <li style="margin: 8px 0;">
                    <a href="?page=freebie-edit&id=1" style="color: #8B5CF6; text-decoration: underline;">
                        → Template Bearbeiten (?page=freebie-edit&id=1)
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- 4. JavaScript-Test -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">4️⃣ JavaScript-Test</h3>
            
            <button onclick="testDeleteFunction()" class="bg-blue-600 text-white px-4 py-2 rounded">
                🧪 Test Löschen-Funktion
            </button>
            
            <button onclick="testSaveAPI()" class="bg-green-600 text-white px-4 py-2 rounded ml-2">
                🧪 Test Save API
            </button>
            
            <div id="test-result" style="margin-top: 16px; padding: 12px; background: #f3f4f6; border-radius: 8px; display: none;">
                <pre id="test-output" style="margin: 0; white-space: pre-wrap;"></pre>
            </div>
        </div>
        
        <!-- 5. Session-Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">5️⃣ Session-Info</h3>
            
            <?php
            echo "✅ <strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'NICHT GESETZT') . "<br>";
            echo ($_SESSION['role'] === 'admin' ? "✅" : "❌") . " <strong>Role:</strong> " . ($_SESSION['role'] ?? 'NICHT GESETZT') . "<br>";
            echo "✅ <strong>Username:</strong> " . ($_SESSION['username'] ?? 'NICHT GESETZT') . "<br>";
            ?>
        </div>
        
    </div>
</div>

<script>
// Test ob deleteTemplate Funktion existiert
function testDeleteFunction() {
    const result = document.getElementById('test-result');
    const output = document.getElementById('test-output');
    result.style.display = 'block';
    
    if (typeof deleteTemplate === 'function') {
        output.textContent = '✅ deleteTemplate Funktion existiert!\n\nDie Funktion ist verfügbar und kann aufgerufen werden.';
        output.style.color = 'green';
    } else {
        output.textContent = '❌ deleteTemplate Funktion NICHT gefunden!\n\nProblem: Das JavaScript wurde nicht korrekt eingebunden.\n\nLösung: Fügen Sie das <script> Tag am Ende von freebies.php ein.';
        output.style.color = 'red';
    }
}

// Test Save API
async function testSaveAPI() {
    const result = document.getElementById('test-result');
    const output = document.getElementById('test-output');
    result.style.display = 'block';
    output.textContent = '⏳ Teste API-Verbindung...';
    output.style.color = 'blue';
    
    try {
        const response = await fetch('/api/save-freebie.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ test: true })
        });
        
        const data = await response.json();
        
        output.textContent = '✅ API erreichbar!\n\n';
        output.textContent += 'Status: ' + response.status + '\n';
        output.textContent += 'Response:\n' + JSON.stringify(data, null, 2);
        output.style.color = 'green';
        
    } catch (error) {
        output.textContent = '❌ API-Fehler!\n\n';
        output.textContent += 'Fehler: ' + error.message + '\n\n';
        output.textContent += 'Mögliche Ursachen:\n';
        output.textContent += '- save-freebie.php nicht im /api/ Ordner\n';
        output.textContent += '- Datei hat falschen Namen\n';
        output.textContent += '- PHP-Fehler in der Datei';
        output.style.color = 'red';
    }
}
</script>