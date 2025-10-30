<?php
/**
 * SYSTEM-TEST & REPARATUR
 * Prüft alle Komponenten und zeigt Status
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System-Test - KI Leadsystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-600 to-blue-600 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-8 text-white">
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-tools mr-3"></i>System-Test & Diagnose
                </h1>
                <p class="opacity-90">Überprüft alle Komponenten deines KI-Leadsystems</p>
            </div>

            <div class="p-8 space-y-6">

                <?php
                $tests = [];
                $allGood = true;

                // ==========================================
                // TEST 1: Config-Datei vorhanden?
                // ==========================================
                echo "<div class='border-b pb-6'>";
                echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-file-code mr-2 text-blue-600'></i>1. Config-Datei</h2>";
                
                $config_path = __DIR__ . '/config/database.php';
                if (file_exists($config_path)) {
                    echo "<div class='bg-green-50 border-l-4 border-green-500 p-4 rounded'>";
                    echo "<p class='text-green-800'><i class='fas fa-check-circle mr-2'></i><strong>✅ Gefunden:</strong> config/database.php</p>";
                    echo "<p class='text-sm text-green-700 mt-1'>Pfad: <code class='bg-green-100 px-2 py-1 rounded'>" . $config_path . "</code></p>";
                    echo "</div>";
                    $tests['config'] = true;
                } else {
                    echo "<div class='bg-red-50 border-l-4 border-red-500 p-4 rounded'>";
                    echo "<p class='text-red-800'><i class='fas fa-times-circle mr-2'></i><strong>❌ Fehlt:</strong> config/database.php</p>";
                    echo "<p class='text-sm text-red-700 mt-2'>Bitte lade die FIXED-database.php hoch als <code>config/database.php</code></p>";
                    echo "</div>";
                    $tests['config'] = false;
                    $allGood = false;
                }
                echo "</div>";

                // ==========================================
                // TEST 2: Datenbankverbindung
                // ==========================================
                echo "<div class='border-b pb-6'>";
                echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-database mr-2 text-green-600'></i>2. Datenbankverbindung</h2>";
                
                if (file_exists($config_path)) {
                    try {
                        require_once $config_path;
                        
                        if (isset($pdo) && $pdo instanceof PDO) {
                            echo "<div class='bg-green-50 border-l-4 border-green-500 p-4 rounded'>";
                            echo "<p class='text-green-800'><i class='fas fa-check-circle mr-2'></i><strong>✅ Verbindung erfolgreich!</strong></p>";
                            
                            // DB Info
                            $stmt = $pdo->query("SELECT DATABASE() as db_name, VERSION() as version");
                            $info = $stmt->fetch();
                            echo "<div class='mt-3 space-y-1 text-sm text-green-700'>";
                            echo "<p>📊 Datenbank: <code class='bg-green-100 px-2 py-1 rounded'>" . $info['db_name'] . "</code></p>";
                            echo "<p>⚙️ MySQL Version: <code class='bg-green-100 px-2 py-1 rounded'>" . $info['version'] . "</code></p>";
                            echo "</div>";
                            echo "</div>";
                            $tests['database'] = true;
                            
                            // TEST 2a: getDBConnection() Funktion verfügbar?
                            echo "<div class='mt-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded'>";
                            if (function_exists('getDBConnection')) {
                                $testPdo = getDBConnection();
                                echo "<p class='text-blue-800'><i class='fas fa-check-circle mr-2'></i><strong>✅ getDBConnection() funktioniert!</strong></p>";
                                echo "<p class='text-sm text-blue-700 mt-1'>Die Funktion ist verfügbar und gibt eine PDO-Verbindung zurück.</p>";
                                $tests['getDBConnection'] = true;
                            } else {
                                echo "<p class='text-red-800'><i class='fas fa-times-circle mr-2'></i><strong>❌ getDBConnection() fehlt!</strong></p>";
                                echo "<p class='text-sm text-red-700 mt-1'>Diese Funktion wird für login.php benötigt. Bitte verwende die FIXED-database.php!</p>";
                                $tests['getDBConnection'] = false;
                                $allGood = false;
                            }
                            echo "</div>";
                            
                        } else {
                            echo "<div class='bg-red-50 border-l-4 border-red-500 p-4 rounded'>";
                            echo "<p class='text-red-800'><i class='fas fa-times-circle mr-2'></i><strong>❌ PDO nicht verfügbar</strong></p>";
                            echo "</div>";
                            $tests['database'] = false;
                            $allGood = false;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='bg-red-50 border-l-4 border-red-500 p-4 rounded'>";
                        echo "<p class='text-red-800'><i class='fas fa-times-circle mr-2'></i><strong>❌ Verbindung fehlgeschlagen</strong></p>";
                        echo "<p class='text-sm text-red-700 mt-2'>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                        $tests['database'] = false;
                        $allGood = false;
                    }
                } else {
                    echo "<div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded'>";
                    echo "<p class='text-yellow-800'><i class='fas fa-exclamation-triangle mr-2'></i>Config-Datei fehlt - Test übersprungen</p>";
                    echo "</div>";
                    $tests['database'] = false;
                }
                echo "</div>";

                // ==========================================
                // TEST 3: Tabellen vorhanden?
                // ==========================================
                echo "<div class='border-b pb-6'>";
                echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-table mr-2 text-purple-600'></i>3. Datenbank-Tabellen</h2>";
                
                if (isset($pdo) && $tests['database']) {
                    $requiredTables = ['users', 'freebies', 'courses', 'sessions'];
                    $tableStatus = [];
                    
                    foreach ($requiredTables as $table) {
                        try {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                            $exists = $stmt->rowCount() > 0;
                            $tableStatus[$table] = $exists;
                            
                            if ($exists) {
                                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                                $count = $countStmt->fetch()['count'];
                                echo "<div class='bg-green-50 p-3 rounded mb-2'>";
                                echo "<p class='text-green-800'><i class='fas fa-check mr-2'></i><strong>$table</strong> - $count Einträge</p>";
                                echo "</div>";
                            } else {
                                echo "<div class='bg-red-50 p-3 rounded mb-2'>";
                                echo "<p class='text-red-800'><i class='fas fa-times mr-2'></i><strong>$table</strong> - Tabelle fehlt!</p>";
                                echo "</div>";
                                $allGood = false;
                            }
                        } catch (Exception $e) {
                            echo "<div class='bg-red-50 p-3 rounded mb-2'>";
                            echo "<p class='text-red-800'><i class='fas fa-exclamation-triangle mr-2'></i><strong>$table</strong> - Fehler: " . $e->getMessage() . "</p>";
                            echo "</div>";
                        }
                    }
                    
                    $tests['tables'] = !in_array(false, $tableStatus);
                } else {
                    echo "<div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded'>";
                    echo "<p class='text-yellow-800'><i class='fas fa-exclamation-triangle mr-2'></i>DB-Verbindung fehlt - Test übersprungen</p>";
                    echo "</div>";
                    $tests['tables'] = false;
                }
                echo "</div>";

                // ==========================================
                // TEST 4: Admin-User vorhanden?
                // ==========================================
                echo "<div class='border-b pb-6'>";
                echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-user-shield mr-2 text-red-600'></i>4. Admin-Benutzer</h2>";
                
                if (isset($pdo) && $tests['database']) {
                    try {
                        $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 5");
                        $admins = $stmt->fetchAll();
                        
                        if (count($admins) > 0) {
                            echo "<div class='bg-green-50 border-l-4 border-green-500 p-4 rounded'>";
                            echo "<p class='text-green-800 mb-3'><i class='fas fa-check-circle mr-2'></i><strong>✅ " . count($admins) . " Admin(s) gefunden</strong></p>";
                            
                            echo "<div class='space-y-2'>";
                            foreach ($admins as $admin) {
                                echo "<div class='bg-white p-3 rounded border'>";
                                echo "<p class='font-medium'>👤 " . htmlspecialchars($admin['username']) . "</p>";
                                echo "<p class='text-sm text-gray-600'>📧 " . htmlspecialchars($admin['email']) . "</p>";
                                echo "</div>";
                            }
                            echo "</div>";
                            echo "</div>";
                            $tests['admin'] = true;
                        } else {
                            echo "<div class='bg-red-50 border-l-4 border-red-500 p-4 rounded'>";
                            echo "<p class='text-red-800'><i class='fas fa-times-circle mr-2'></i><strong>❌ Kein Admin-User gefunden</strong></p>";
                            echo "<p class='text-sm text-red-700 mt-2'>Du musst einen Admin-User in der users-Tabelle anlegen.</p>";
                            echo "</div>";
                            $tests['admin'] = false;
                            $allGood = false;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='bg-red-50 border-l-4 border-red-500 p-4 rounded'>";
                        echo "<p class='text-red-800'><i class='fas fa-times-circle mr-2'></i>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                        $tests['admin'] = false;
                        $allGood = false;
                    }
                } else {
                    echo "<div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded'>";
                    echo "<p class='text-yellow-800'><i class='fas fa-exclamation-triangle mr-2'></i>DB-Verbindung fehlt - Test übersprungen</p>";
                    echo "</div>";
                    $tests['admin'] = false;
                }
                echo "</div>";

                // ==========================================
                // TEST 5: Wichtige Dateien vorhanden?
                // ==========================================
                echo "<div class='pb-6'>";
                echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-file-code mr-2 text-indigo-600'></i>5. Admin-Dateien</h2>";
                
                $requiredFiles = [
                    'admin/freebie-templates.php' => 'Freebie Templates Übersicht',
                    'admin/freebie-create.php' => 'Template Erstellen',
                    'admin/freebie-edit.php' => 'Template Bearbeiten',
                    'admin/dashboard.php' => 'Dashboard',
                ];
                
                $filesOk = true;
                foreach ($requiredFiles as $file => $description) {
                    $fullPath = __DIR__ . '/' . $file;
                    $exists = file_exists($fullPath);
                    
                    if ($exists) {
                        echo "<div class='bg-green-50 p-3 rounded mb-2'>";
                        echo "<p class='text-green-800'><i class='fas fa-check mr-2'></i><strong>$description</strong></p>";
                        echo "<p class='text-xs text-green-600 mt-1'>$file</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='bg-yellow-50 p-3 rounded mb-2'>";
                        echo "<p class='text-yellow-800'><i class='fas fa-exclamation-triangle mr-2'></i><strong>$description</strong> (optional)</p>";
                        echo "<p class='text-xs text-yellow-600 mt-1'>$file - nicht gefunden</p>";
                        echo "</div>";
                    }
                }
                echo "</div>";
                ?>

                <!-- Zusammenfassung -->
                <div class="mt-8 p-6 rounded-xl <?php echo $allGood ? 'bg-green-50 border-2 border-green-500' : 'bg-red-50 border-2 border-red-500'; ?>">
                    <?php if ($allGood): ?>
                        <h3 class="text-2xl font-bold text-green-800 mb-3">
                            <i class="fas fa-check-circle mr-2"></i>🎉 Alles funktioniert perfekt!
                        </h3>
                        <p class="text-green-700 mb-4">Dein System ist vollständig einsatzbereit!</p>
                        <div class="flex gap-3">
                            <a href="admin/dashboard.php" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition">
                                <i class="fas fa-home mr-2"></i>Zum Dashboard
                            </a>
                            <a href="admin/freebie-templates.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition">
                                <i class="fas fa-gift mr-2"></i>Zu den Templates
                            </a>
                        </div>
                    <?php else: ?>
                        <h3 class="text-2xl font-bold text-red-800 mb-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i>⚠️ Probleme gefunden
                        </h3>
                        <p class="text-red-700 mb-4">Bitte behebe die oben angezeigten Fehler.</p>
                        
                        <div class="bg-white p-4 rounded-lg mt-4">
                            <h4 class="font-bold text-gray-800 mb-2">🔧 Schnellhilfe:</h4>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                <li>Lade die <strong>FIXED-database.php</strong> hoch als <code>config/database.php</code></li>
                                <li>Prüfe die Datenbank-Zugangsdaten in der Config</li>
                                <li>Stelle sicher, dass alle Tabellen existieren</li>
                                <li>Lege einen Admin-User an falls fehlt</li>
                            </ol>
                        </div>
                        
                        <button onclick="location.reload()" class="mt-4 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-sync-alt mr-2"></i>Tests erneut ausführen
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Debug Info -->
        <div class="mt-6 text-center">
            <details class="inline-block text-left">
                <summary class="cursor-pointer text-white text-sm opacity-75 hover:opacity-100">
                    <i class="fas fa-info-circle mr-1"></i>Debug-Informationen anzeigen
                </summary>
                <div class="mt-3 bg-gray-900 text-green-400 p-4 rounded-lg text-xs font-mono">
                    <p>PHP Version: <?php echo PHP_VERSION; ?></p>
                    <p>Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                    <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
                    <p>Script Path: <?php echo __DIR__; ?></p>
                    <p>Time: <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </details>
        </div>
    </div>
</body>
</html>