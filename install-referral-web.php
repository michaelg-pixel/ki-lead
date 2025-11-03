<?php
/**
 * WEB-INSTALLER FÜR REFERRAL-SYSTEM
 * Automatische Installation über Browser
 * 
 * WICHTIG: Nach Installation diese Datei löschen oder umbenennen!
 * URL: https://app.mehr-infos-jetzt.de/install-referral-web.php
 */

// Fehlerberichterstattung aktivieren für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Sicherheits-Token (ändere dies!)
define('INSTALL_TOKEN', 'mein-geheimes-token-2025');

// Session starten
session_start();

// Basis-Pfade
define('BASE_PATH', __DIR__);
define('LOG_PATH', BASE_PATH . '/logs'); // Logs jetzt unter public_html/logs

// Datenbank-Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'lumisaas');
define('DB_USER', 'lumisaas52');
define('DB_PASS', 'I1zx1XdL1hrWd75yu57e');

// Installation durchgeführt?
$install_done = isset($_SESSION['install_complete']) && $_SESSION['install_complete'];

// POST-Request verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Token prüfen
    $token = $_POST['token'] ?? '';
    if ($token !== INSTALL_TOKEN) {
        echo json_encode(['success' => false, 'message' => '❌ Ungültiger Token!', 'debug' => 'Token mismatch']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'check_requirements':
                $result = checkRequirements();
                break;
            
            case 'create_logs':
                $result = createLogsFolder();
                break;
            
            case 'migrate_database':
                $result = migrateDatabase();
                break;
            
            case 'set_permissions':
                $result = setPermissions();
                break;
            
            case 'create_test_data':
                $result = createTestData();
                break;
            
            case 'validate_system':
                $result = validateSystem();
                break;
            
            case 'complete_install':
                $_SESSION['install_complete'] = true;
                $result = ['success' => true, 'message' => '🎉 Installation abgeschlossen!'];
                break;
            
            default:
                $result = ['success' => false, 'message' => 'Unbekannte Aktion: ' . $action];
        }
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => '❌ Fehler: ' . $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
    }
    exit;
}

// Funktionen
function checkRequirements() {
    $checks = [];
    
    // PHP-Version
    $checks[] = [
        'name' => 'PHP-Version >= 7.4',
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'value' => PHP_VERSION
    ];
    
    // MySQL-Extension
    $checks[] = [
        'name' => 'MySQL-Extension',
        'status' => extension_loaded('pdo_mysql'),
        'value' => extension_loaded('pdo_mysql') ? 'Verfügbar' : 'Nicht verfügbar'
    ];
    
    // Schreibrechte für BASE_PATH
    $checks[] = [
        'name' => 'Schreibrechte BASE_PATH',
        'status' => is_writable(BASE_PATH),
        'value' => BASE_PATH
    ];
    
    // config/database.php existiert
    $checks[] = [
        'name' => 'Database-Config vorhanden',
        'status' => file_exists(BASE_PATH . '/config/database.php'),
        'value' => file_exists(BASE_PATH . '/config/database.php') ? 'Ja' : 'Nein'
    ];
    
    // Migrations-Datei existiert
    $checks[] = [
        'name' => 'Migrations-Datei vorhanden',
        'status' => file_exists(BASE_PATH . '/database/migrations/004_referral_system.sql'),
        'value' => file_exists(BASE_PATH . '/database/migrations/004_referral_system.sql') ? 'Ja' : 'Nein'
    ];
    
    $all_success = true;
    foreach ($checks as $check) {
        if (!$check['status']) {
            $all_success = false;
            break;
        }
    }
    
    return [
        'success' => $all_success,
        'checks' => $checks,
        'message' => $all_success ? '✅ Alle Anforderungen erfüllt' : '⚠️ Einige Anforderungen nicht erfüllt'
    ];
}

function createLogsFolder() {
    // Prüfe ob Ordner bereits existiert
    if (is_dir(LOG_PATH)) {
        // Ordner existiert bereits, prüfe Schreibrechte
        if (!is_writable(LOG_PATH)) {
            @chmod(LOG_PATH, 0755);
        }
        
        $test_file = LOG_PATH . '/cron.log';
        $content = date('Y-m-d H:i:s') . " - Referral System Web-Installation (Ordner bereits vorhanden)\n";
        
        if (@file_put_contents($test_file, $content, FILE_APPEND) === false) {
            return [
                'success' => false, 
                'message' => '❌ Logs-Ordner existiert, aber keine Schreibrechte: ' . LOG_PATH,
                'details' => [
                    'exists' => true,
                    'writable' => false,
                    'path' => LOG_PATH
                ]
            ];
        }
        
        return [
            'success' => true,
            'message' => '✅ Logs-Ordner bereits vorhanden und beschreibbar: ' . LOG_PATH,
            'details' => [
                'exists' => true,
                'writable' => true,
                'path' => LOG_PATH,
                'action' => 'verified'
            ]
        ];
    }
    
    // Versuche Ordner zu erstellen
    if (!@mkdir(LOG_PATH, 0755, true)) {
        // Fehler beim Erstellen
        $error = error_get_last();
        return [
            'success' => false, 
            'message' => '❌ Konnte Logs-Ordner nicht erstellen: ' . LOG_PATH,
            'details' => [
                'path' => LOG_PATH,
                'parent_writable' => is_writable(dirname(LOG_PATH)),
                'parent_path' => dirname(LOG_PATH),
                'error' => $error ? $error['message'] : 'Unbekannter Fehler'
            ]
        ];
    }
    
    // Ordner erfolgreich erstellt
    @chmod(LOG_PATH, 0755);
    
    // Test-Log erstellen
    $log_file = LOG_PATH . '/cron.log';
    $content = date('Y-m-d H:i:s') . " - Referral System Web-Installation gestartet\n";
    @file_put_contents($log_file, $content, FILE_APPEND);
    @chmod($log_file, 0644);
    
    return [
        'success' => true,
        'message' => '✅ Logs-Ordner erfolgreich erstellt: ' . LOG_PATH,
        'details' => [
            'path' => LOG_PATH,
            'writable' => is_writable(LOG_PATH),
            'exists' => is_dir(LOG_PATH),
            'action' => 'created'
        ]
    ];
}

function migrateDatabase() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Prüfe ob Tabellen existieren
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $existing_tables = $stmt->rowCount();
        
        if ($existing_tables >= 6) {
            return [
                'success' => true,
                'message' => '✅ Tabellen bereits vorhanden (' . $existing_tables . '/6)',
                'details' => ['tables' => $existing_tables, 'action' => 'skipped']
            ];
        }
        
        // Migration ausführen
        $migration_file = BASE_PATH . '/database/migrations/004_referral_system.sql';
        if (!file_exists($migration_file)) {
            return ['success' => false, 'message' => '❌ Migrations-Datei nicht gefunden: ' . $migration_file];
        }
        
        $sql = file_get_contents($migration_file);
        $pdo->exec($sql);
        
        // Prüfe erneut
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $tables = $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => '✅ Datenbank migriert (' . $tables . ' Tabellen erstellt)',
            'details' => ['tables' => $tables, 'action' => 'created']
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => '❌ Datenbank-Fehler: ' . $e->getMessage(),
            'debug' => [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ];
    }
}

function setPermissions() {
    $results = [];
    
    // API-Ordner
    if (is_dir(BASE_PATH . '/api/referral')) {
        @chmod(BASE_PATH . '/api/referral', 0755);
        $results[] = 'API-Ordner: 0755';
    }
    
    // Scripts
    $scripts = glob(BASE_PATH . '/scripts/*.php');
    foreach ($scripts as $script) {
        @chmod($script, 0755);
    }
    $results[] = count($scripts) . ' Scripts: 0755';
    
    // Logs
    if (is_dir(LOG_PATH)) {
        @chmod(LOG_PATH, 0755);
        $results[] = 'Logs-Ordner: 0755';
    }
    
    // .htaccess für Logs-Schutz erstellen
    $htaccess_content = "# Zugriff auf Logs verweigern\nOrder deny,allow\nDeny from all\n";
    @file_put_contents(LOG_PATH . '/.htaccess', $htaccess_content);
    $results[] = 'Logs-Schutz: .htaccess erstellt';
    
    return [
        'success' => true,
        'message' => '✅ Berechtigungen gesetzt',
        'details' => $results
    ];
}

function createTestData() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Aktiviere Referral für ersten Customer
        $pdo->exec("
            UPDATE customers 
            SET 
                referral_enabled = 1,
                company_name = 'Test Firma GmbH',
                company_email = 'test@mehr-infos-jetzt.de',
                company_imprint_html = '<p>Test Firma GmbH<br>Teststraße 123<br>12345 Teststadt<br>E-Mail: test@mehr-infos-jetzt.de</p>'
            WHERE id = 1
            LIMIT 1
        ");
        
        // Erstelle Test-Klick
        $pdo->exec("
            INSERT INTO referral_clicks (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
            VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Mozilla/5.0 Test Browser', MD5('test_fingerprint'), NOW())
        ");
        
        // Erstelle Test-Conversion
        $pdo->exec("
            INSERT INTO referral_conversions (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, source, suspicious, created_at)
            VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Mozilla/5.0 Test Browser', MD5('test_fingerprint'), 'thankyou', 0, NOW())
        ");
        
        // Update Stats
        $pdo->exec("
            INSERT INTO referral_stats (customer_id, total_clicks, unique_clicks, total_conversions, conversion_rate)
            VALUES (1, 1, 1, 1, 100.00)
            ON DUPLICATE KEY UPDATE
                total_clicks = 1,
                unique_clicks = 1,
                total_conversions = 1,
                conversion_rate = 100.00,
                updated_at = NOW()
        ");
        
        return [
            'success' => true,
            'message' => '✅ Test-Daten erstellt (Customer ID: 1)',
            'details' => [
                'customer' => 'Test Firma GmbH',
                'clicks' => 1,
                'conversions' => 1,
                'rate' => '100%'
            ]
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => '❌ Fehler beim Erstellen der Test-Daten: ' . $e->getMessage()
        ];
    }
}

function validateSystem() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $checks = [];
        
        // Tabellen
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $tables = $stmt->rowCount();
        $checks['tables'] = ['count' => $tables, 'expected' => 6, 'ok' => $tables >= 6];
        
        // API-Endpoints
        $endpoints = glob(BASE_PATH . '/api/referral/*.php');
        $checks['api_endpoints'] = ['count' => count($endpoints), 'expected' => 11, 'ok' => count($endpoints) >= 10];
        
        // Logs-Ordner
        $checks['logs'] = ['exists' => is_dir(LOG_PATH), 'writable' => is_writable(LOG_PATH), 'ok' => is_dir(LOG_PATH) && is_writable(LOG_PATH)];
        
        // Aktive Programme
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE referral_enabled = 1");
        $active = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $checks['active_programs'] = ['count' => $active, 'ok' => $active > 0];
        
        // Statistik-Daten
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_clicks");
        $clicks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_conversions");
        $conversions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $checks['stats'] = ['clicks' => $clicks, 'conversions' => $conversions, 'ok' => $clicks > 0 || $conversions > 0];
        
        $all_ok = true;
        foreach ($checks as $check) {
            if (!$check['ok']) {
                $all_ok = false;
                break;
            }
        }
        
        return [
            'success' => $all_ok,
            'message' => $all_ok ? '✅ System vollständig validiert' : '⚠️ Einige Checks fehlgeschlagen',
            'checks' => $checks
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => '❌ Validierungs-Fehler: ' . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral-System Web-Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen">

<!-- Debug Console (immer sichtbar beim Start) -->
<div id="debugConsole" class="fixed bottom-4 right-4 max-w-md bg-gray-900 text-green-400 text-xs font-mono p-4 rounded-lg shadow-2xl max-h-64 overflow-y-auto">
    <div class="flex items-center justify-between mb-2">
        <span class="font-bold">🐛 Debug Console</span>
        <button onclick="toggleDebugConsole()" class="text-red-400 hover:text-red-300">✕</button>
    </div>
    <div id="debugOutput"></div>
</div>

<?php if ($install_done): ?>
    <!-- Installation abgeschlossen -->
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
                <div class="text-6xl mb-6">🎉</div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Installation erfolgreich!</h1>
                <p class="text-xl text-gray-600 mb-8">Das Referral-System ist jetzt vollständig eingerichtet.</p>
                
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">🚀 Nächste Schritte</h2>
                    
                    <div class="space-y-4 text-left">
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Cron-Job einrichten (SSH erforderlich)</h3>
                                <code class="block mt-2 p-3 bg-gray-900 text-green-400 text-sm rounded font-mono overflow-x-auto">
crontab -e
# Füge hinzu:
0 10 * * * php <?php echo BASE_PATH; ?>/scripts/send-reward-emails.php >> <?php echo LOG_PATH; ?>/cron.log 2>&1
                                </code>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Installer-Datei löschen (WICHTIG!)</h3>
                                <code class="block mt-2 p-3 bg-gray-900 text-green-400 text-sm rounded font-mono overflow-x-auto">
rm <?php echo BASE_PATH; ?>/install-referral-web.php
                                </code>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Dashboards öffnen</h3>
                                <div class="mt-2 space-y-2">
                                    <a href="https://app.mehr-infos-jetzt.de/admin/sections/referral-overview.php" target="_blank" class="block p-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-center">
                                        📊 Admin-Dashboard öffnen
                                    </a>
                                    <a href="https://app.mehr-infos-jetzt.de/customer/dashboard.php" target="_blank" class="block p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-center">
                                        👤 Customer-Dashboard öffnen
                                    </a>
                                    <a href="https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123" target="_blank" class="block p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-center">
                                        🧪 Test-Link aufrufen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4">
                    <p class="text-sm text-yellow-800">
                        <strong>⚠️ Sicherheitshinweis:</strong> Bitte lösche diese Installer-Datei nach erfolgreicher Installation!<br>
                        <strong>📁 Logs-Pfad:</strong> <?php echo LOG_PATH; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Installer-Interface -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">🚀</div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Referral-System Installer</h1>
                <p class="text-lg text-gray-600">Automatische Installation in wenigen Minuten</p>
                <p class="text-sm text-gray-500 mt-2">Logs werden gespeichert in: <code class="bg-gray-100 px-2 py-1 rounded"><?php echo LOG_PATH; ?></code></p>
            </div>
            
            <!-- Token-Eingabe -->
            <div id="tokenSection" class="bg-white rounded-2xl shadow-xl p-8 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">🔐 Sicherheits-Token</h2>
                <p class="text-gray-600 mb-4">Bitte gib den Installations-Token ein:</p>
                <div class="flex gap-4">
                    <input type="password" id="tokenInput" placeholder="Token eingeben..." class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                    <button onclick="verifyToken()" id="verifyBtn" class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                        Verifizieren
                    </button>
                </div>
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>💡 Hinweis:</strong> Der Standard-Token ist: <code class="bg-white px-2 py-1 rounded">mein-geheimes-token-2025</code>
                    </p>
                </div>
            </div>
            
            <!-- Installations-Schritte -->
            <div id="installSection" class="hidden space-y-6">
                <!-- Schritt 1 -->
                <div class="bg-white rounded-2xl shadow-xl p-8" id="step1">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">1️⃣ Anforderungen prüfen</h3>
                        <div id="step1-status"></div>
                    </div>
                    <div id="step1-content" class="text-gray-600">
                        <button onclick="executeStep('check_requirements', 1)" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition" id="step1-btn">
                            Prüfung starten
                        </button>
                    </div>
                </div>
                
                <!-- Schritt 2 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 opacity-50" id="step2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">2️⃣ Logs-Ordner erstellen</h3>
                        <div id="step2-status"></div>
                    </div>
                    <div id="step2-content" class="text-gray-600">
                        <button onclick="executeStep('create_logs', 2)" class="px-6 py-3 bg-gray-400 cursor-not-allowed text-white rounded-lg transition" disabled id="step2-btn">
                            Warten...
                        </button>
                    </div>
                </div>
                
                <!-- Schritt 3 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 opacity-50" id="step3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">3️⃣ Datenbank migrieren</h3>
                        <div id="step3-status"></div>
                    </div>
                    <div id="step3-content" class="text-gray-600">
                        <button onclick="executeStep('migrate_database', 3)" class="px-6 py-3 bg-gray-400 cursor-not-allowed text-white rounded-lg transition" disabled id="step3-btn">
                            Warten...
                        </button>
                    </div>
                </div>
                
                <!-- Schritt 4 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 opacity-50" id="step4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">4️⃣ Berechtigungen setzen</h3>
                        <div id="step4-status"></div>
                    </div>
                    <div id="step4-content" class="text-gray-600">
                        <button onclick="executeStep('set_permissions', 4)" class="px-6 py-3 bg-gray-400 cursor-not-allowed text-white rounded-lg transition" disabled id="step4-btn">
                            Warten...
                        </button>
                    </div>
                </div>
                
                <!-- Schritt 5 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 opacity-50" id="step5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">5️⃣ Test-Daten erstellen</h3>
                        <div id="step5-status"></div>
                    </div>
                    <div id="step5-content" class="text-gray-600">
                        <button onclick="executeStep('create_test_data', 5)" class="px-6 py-3 bg-gray-400 cursor-not-allowed text-white rounded-lg transition" disabled id="step5-btn">
                            Warten...
                        </button>
                    </div>
                </div>
                
                <!-- Schritt 6 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 opacity-50" id="step6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">6️⃣ System validieren</h3>
                        <div id="step6-status"></div>
                    </div>
                    <div id="step6-content" class="text-gray-600">
                        <button onclick="executeStep('validate_system', 6)" class="px-6 py-3 bg-gray-400 cursor-not-allowed text-white rounded-lg transition" disabled id="step6-btn">
                            Warten...
                        </button>
                    </div>
                </div>
                
                <!-- Fertigstellen -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl shadow-xl p-8 text-white opacity-50" id="completeSection">
                    <h3 class="text-2xl font-bold mb-4">🎉 Installation abschließen</h3>
                    <button onclick="completeInstallation()" class="px-8 py-3 bg-white text-green-600 rounded-lg hover:bg-gray-100 transition font-semibold cursor-not-allowed" disabled id="complete-btn">
                        Installation abschließen
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Globale Variablen
let installToken = '';
let currentStep = 0;

// Debug-Funktionen
function debugLog(message) {
    const output = document.getElementById('debugOutput');
    const time = new Date().toLocaleTimeString();
    const logEntry = `[${time}] ${message}`;
    output.innerHTML += logEntry + '<br>';
    output.scrollTop = output.scrollHeight;
    console.log(logEntry);
}

function toggleDebugConsole() {
    const console = document.getElementById('debugConsole');
    console.style.display = console.style.display === 'none' ? 'block' : 'none';
}

// Token-Verifizierung
function verifyToken() {
    debugLog('🔑 verifyToken() aufgerufen');
    
    const tokenInput = document.getElementById('tokenInput');
    const token = tokenInput.value.trim();
    const verifyBtn = document.getElementById('verifyBtn');
    
    debugLog('Token-Länge: ' + token.length);
    
    if (!token) {
        debugLog('❌ Kein Token eingegeben');
        alert('Bitte gib einen Token ein!');
        return;
    }
    
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = 'Prüfe...';
    
    if (token === '<?php echo INSTALL_TOKEN; ?>') {
        debugLog('✅ Token korrekt!');
        installToken = token;
        
        // UI aktualisieren
        document.getElementById('tokenSection').classList.add('hidden');
        document.getElementById('installSection').classList.remove('hidden');
        
        debugLog('✅ Installation UI angezeigt');
    } else {
        debugLog('❌ Token ungültig: ' + token);
        alert('❌ Ungültiger Token! Bitte überprüfe deine Eingabe.');
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = 'Verifizieren';
    }
}

// Enter-Taste für Token-Eingabe
document.addEventListener('DOMContentLoaded', function() {
    debugLog('✨ DOM geladen');
    
    const tokenInput = document.getElementById('tokenInput');
    if (tokenInput) {
        tokenInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                debugLog('⏎ Enter gedrückt in Token-Feld');
                verifyToken();
            }
        });
    }
});

// Installations-Schritt ausführen
async function executeStep(action, stepNumber) {
    debugLog(`🚀 executeStep() aufgerufen: action="${action}", step=${stepNumber}`);
    
    if (!installToken) {
        debugLog('❌ Kein Token vorhanden!');
        alert('Fehler: Kein Token vorhanden!');
        return;
    }
    
    const btn = document.getElementById(`step${stepNumber}-btn`);
    const status = document.getElementById(`step${stepNumber}-status`);
    const content = document.getElementById(`step${stepNumber}-content`);
    
    if (!btn || !status || !content) {
        debugLog(`❌ Elemente für Schritt ${stepNumber} nicht gefunden!`);
        return;
    }
    
    try {
        // Button deaktivieren
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full"></span> Wird ausgeführt...';
        status.innerHTML = '<span class="text-yellow-500">⏳</span>';
        
        debugLog(`📦 Erstelle FormData für action: ${action}`);
        
        // POST-Request erstellen
        const formData = new FormData();
        formData.append('token', installToken);
        formData.append('action', action);
        
        debugLog(`📡 Sende POST-Request an: ${window.location.href}`);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        debugLog(`📥 Response erhalten: status=${response.status}, ok=${response.ok}`);
        
        if (!response.ok) {
            throw new Error(`HTTP-Fehler: ${response.status} ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        debugLog(`📄 Content-Type: ${contentType}`);
        
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            debugLog(`❌ Keine JSON-Response! Inhalt: ${text.substring(0, 200)}`);
            throw new Error('Server hat kein JSON zurückgegeben!');
        }
        
        const result = await response.json();
        debugLog(`📊 JSON geparst: success=${result.success}, message="${result.message}"`);
        
        if (result.debug) {
            debugLog(`🐛 Debug-Info: ${JSON.stringify(result.debug)}`);
        }
        
        if (result.success) {
            handleSuccess(stepNumber, result, btn, status, content);
        } else {
            handleError(stepNumber, result, btn, status, content);
        }
        
    } catch (error) {
        debugLog(`💥 JavaScript-Fehler: ${error.message}`);
        debugLog(`📚 Stack: ${error.stack}`);
        
        status.innerHTML = '<span class="text-red-500 text-2xl">❌</span>';
        btn.innerHTML = '❌ Fehler';
        btn.classList.add('bg-red-500');
        
        content.innerHTML += `<div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            <strong>Fehler:</strong> ${error.message}<br>
            <br>
            <strong>Bitte öffne die Browser-Console (F12) für Details!</strong>
        </div>`;
    }
}

function handleSuccess(stepNumber, result, btn, status, content) {
    debugLog(`✅ Schritt ${stepNumber} erfolgreich!`);
    
    status.innerHTML = '<span class="text-green-500 text-2xl">✅</span>';
    btn.innerHTML = '✓ Abgeschlossen';
    btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
    btn.classList.add('bg-green-500');
    
    // Details anzeigen
    let detailsHtml = '<div class="mt-4 p-4 bg-green-50 rounded-lg text-sm">';
    detailsHtml += '<div class="font-semibold text-green-800 mb-2">' + result.message + '</div>';
    
    if (result.checks) {
        result.checks.forEach(check => {
            const icon = check.status || check.ok ? '✅' : '❌';
            const color = check.status || check.ok ? 'text-green-600' : 'text-red-600';
            detailsHtml += `<div class="flex items-center justify-between p-2 bg-white rounded mt-1">
                <span class="${color}">${icon} ${check.name || JSON.stringify(check)}</span>
                <span class="text-xs text-gray-600">${check.value || ''}</span>
            </div>`;
        });
    } else if (Array.isArray(result.details)) {
        result.details.forEach(detail => {
            detailsHtml += '<div class="text-green-700">• ' + detail + '</div>';
        });
    } else if (typeof result.details === 'object') {
        for (let key in result.details) {
            detailsHtml += '<div class="text-green-700">• ' + key + ': ' + JSON.stringify(result.details[key]) + '</div>';
        }
    }
    
    detailsHtml += '</div>';
    content.innerHTML += detailsHtml;
    
    // Nächsten Schritt aktivieren
    if (stepNumber < 6) {
        activateNextStep(stepNumber + 1);
    } else {
        // Installation abschlussbereit
        debugLog('🎉 Alle Schritte abgeschlossen!');
        const completeSection = document.getElementById('completeSection');
        const completeBtn = document.getElementById('complete-btn');
        completeSection.classList.remove('opacity-50');
        completeBtn.disabled = false;
        completeBtn.classList.remove('cursor-not-allowed');
    }
}

function handleError(stepNumber, result, btn, status, content) {
    debugLog(`❌ Schritt ${stepNumber} fehlgeschlagen: ${result.message}`);
    
    status.innerHTML = '<span class="text-red-500 text-2xl">❌</span>';
    btn.innerHTML = '❌ Fehler';
    btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
    btn.classList.add('bg-red-500');
    
    let errorHtml = '<div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">';
    errorHtml += '<strong>Fehler:</strong> ' + result.message;
    
    if (result.debug) {
        errorHtml += '<br><br><strong>Debug-Info:</strong><pre class="text-xs mt-2 bg-gray-100 p-2 rounded overflow-x-auto">' + JSON.stringify(result.debug, null, 2) + '</pre>';
    }
    
    if (result.details) {
        errorHtml += '<br><br><strong>Details:</strong><pre class="text-xs mt-2 bg-gray-100 p-2 rounded overflow-x-auto">' + JSON.stringify(result.details, null, 2) + '</pre>';
    }
    
    errorHtml += '</div>';
    content.innerHTML += errorHtml;
}

function activateNextStep(nextStepNumber) {
    debugLog(`➡️ Aktiviere Schritt ${nextStepNumber}`);
    
    currentStep = nextStepNumber;
    const nextStep = document.getElementById(`step${nextStepNumber}`);
    const nextBtn = document.getElementById(`step${nextStepNumber}-btn`);
    
    if (nextStep && nextBtn) {
        nextStep.classList.remove('opacity-50');
        nextBtn.disabled = false;
        nextBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
        nextBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        nextBtn.textContent = 'Ausführen';
    }
}

async function completeInstallation() {
    debugLog('🏁 Installation wird abgeschlossen...');
    
    const btn = document.getElementById('complete-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner inline-block w-5 h-5 border-2 border-green-600 border-t-transparent rounded-full"></span> Wird abgeschlossen...';
    
    try {
        const formData = new FormData();
        formData.append('token', installToken);
        formData.append('action', 'complete_install');
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            debugLog('✅ Installation abgeschlossen! Seite wird neu geladen...');
            window.location.reload();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        debugLog(`❌ Fehler beim Abschließen: ${error.message}`);
        alert('Fehler: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = 'Installation abschließen';
    }
}

// Initial-Log
debugLog('✨ Installer-Seite geladen und bereit!');
debugLog('📁 Logs werden gespeichert in: <?php echo LOG_PATH; ?>');
debugLog('🔍 Prüfe ob alle Funktionen verfügbar sind...');
debugLog('✓ verifyToken: ' + (typeof verifyToken === 'function'));
debugLog('✓ executeStep: ' + (typeof executeStep === 'function'));
debugLog('✓ completeInstallation: ' + (typeof completeInstallation === 'function'));
</script>

</body>
</html>
