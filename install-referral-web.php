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
define('LOG_PATH', '/home/lumisaas/logs');

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
    if (!is_dir(LOG_PATH)) {
        if (!@mkdir(LOG_PATH, 0755, true)) {
            return ['success' => false, 'message' => '❌ Konnte Logs-Ordner nicht erstellen: ' . LOG_PATH];
        }
    }
    
    // Test-Log erstellen
    $log_file = LOG_PATH . '/cron.log';
    $content = date('Y-m-d H:i:s') . " - Referral System Web-Installation gestartet\n";
    @file_put_contents($log_file, $content, FILE_APPEND);
    
    @chmod(LOG_PATH, 0755);
    @chmod($log_file, 0644);
    
    return [
        'success' => true,
        'message' => '✅ Logs-Ordner erstellt: ' . LOG_PATH,
        'details' => [
            'path' => LOG_PATH,
            'writable' => is_writable(LOG_PATH),
            'exists' => is_dir(LOG_PATH)
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

<!-- Debug Console (immer sichtbar) -->
<div id="debugConsole" class="fixed bottom-4 right-4 max-w-md bg-gray-900 text-green-400 text-xs font-mono p-4 rounded-lg shadow-2xl max-h-64 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-between mb-2">
        <span class="font-bold">🐛 Debug Console</span>
        <button onclick="document.getElementById('debugConsole').style.display='none'" class="text-red-400 hover:text-red-300">✕</button>
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
                        <strong>⚠️ Sicherheitshinweis:</strong> Bitte lösche diese Installer-Datei nach erfolgreicher Installation!
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
                
                <!-- Debug Toggle -->
                <button onclick="toggleDebug()" class="mt-4 px-4 py-2 bg-gray-800 text-gray-300 text-xs rounded hover:bg-gray-700 transition">
                    🐛 Debug-Console anzeigen
                </button>
            </div>
            
            <!-- Token-Eingabe -->
            <div id="tokenSection" class="bg-white rounded-2xl shadow-xl p-8 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">🔐 Sicherheits-Token</h2>
                <p class="text-gray-600 mb-4">Bitte gib den Installations-Token ein:</p>
                <div class="flex gap-4">
                    <input type="password" id="tokenInput" placeholder="Token eingeben..." class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                    <button onclick="verifyToken()" class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
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
                <!-- Schritt 1-6 wie vorher, aber mit id="stepX" -->
                <?php for ($i = 1; $i <= 6; $i++): 
                    $titles = [
                        1 => '1️⃣ Anforderungen prüfen',
                        2 => '2️⃣ Logs-Ordner erstellen',
                        3 => '3️⃣ Datenbank migrieren',
                        4 => '4️⃣ Berechtigungen setzen',
                        5 => '5️⃣ Test-Daten erstellen',
                        6 => '6️⃣ System validieren'
                    ];
                    $actions = [
                        1 => 'check_requirements',
                        2 => 'create_logs',
                        3 => 'migrate_database',
                        4 => 'set_permissions',
                        5 => 'create_test_data',
                        6 => 'validate_system'
                    ];
                    $opacity = $i > 1 ? 'opacity-50' : '';
                ?>
                <div class="bg-white rounded-2xl shadow-xl p-8 <?php echo $opacity; ?>" id="step<?php echo $i; ?>">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900"><?php echo $titles[$i]; ?></h3>
                        <div id="step<?php echo $i; ?>-status"></div>
                    </div>
                    <div id="step<?php echo $i; ?>-content" class="text-gray-600">
                        <button onclick="runStep('<?php echo $actions[$i]; ?>', <?php echo $i; ?>)" 
                                class="px-6 py-3 <?php echo $i === 1 ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-400 cursor-not-allowed'; ?> text-white rounded-lg transition"
                                <?php echo $i > 1 ? 'disabled' : ''; ?>
                                id="step<?php echo $i; ?>-btn">
                            <?php echo $i === 1 ? 'Prüfung starten' : 'Warten...'; ?>
                        </button>
                    </div>
                </div>
                <?php endfor; ?>
                
                <!-- Fertigstellen -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl shadow-xl p-8 text-white opacity-50" id="completeSection">
                    <h3 class="text-2xl font-bold mb-4">🎉 Installation abschließen</h3>
                    <button onclick="completeInstall()" class="px-8 py-3 bg-white text-green-600 rounded-lg hover:bg-gray-100 transition font-semibold cursor-not-allowed" disabled id="complete-btn">
                        Installation abschließen
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
let installToken = '';
let currentStep = 0;

function debugLog(message) {
    const output = document.getElementById('debugOutput');
    const time = new Date().toLocaleTimeString();
    output.innerHTML += `[${time}] ${message}<br>`;
    output.scrollTop = output.scrollHeight;
    console.log(message);
}

function toggleDebug() {
    const console = document.getElementById('debugConsole');
    console.style.display = console.style.display === 'none' ? 'block' : 'none';
}

function verifyToken() {
    debugLog('🔑 Token-Verifizierung gestartet...');
    const token = document.getElementById('tokenInput').value;
    debugLog('Token eingegeben: ' + token.substring(0, 5) + '...');
    
    if (token === '<?php echo INSTALL_TOKEN; ?>') {
        debugLog('✅ Token korrekt!');
        installToken = token;
        document.getElementById('tokenSection').classList.add('hidden');
        document.getElementById('installSection').classList.remove('hidden');
        toggleDebug(); // Debug-Console automatisch anzeigen
    } else {
        debugLog('❌ Token ungültig!');
        alert('❌ Ungültiger Token!');
    }
}

async function runStep(action, stepNumber) {
    debugLog(`🚀 Schritt ${stepNumber}: ${action} wird ausgeführt...`);
    
    const btn = document.getElementById(`step${stepNumber}-btn`);
    const status = document.getElementById(`step${stepNumber}-status`);
    const content = document.getElementById(`step${stepNumber}-content`);
    
    // Loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full"></span> Wird ausgeführt...';
    status.innerHTML = '<span class="text-yellow-500">⏳</span>';
    
    try {
        const formData = new FormData();
        formData.append('token', installToken);
        formData.append('action', action);
        
        debugLog(`📡 Sende POST-Request für Action: ${action}`);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        debugLog(`📥 Response erhalten. Status: ${response.status}`);
        
        const result = await response.json();
        debugLog(`📊 JSON geparst. Success: ${result.success}`);
        
        if (result.debug) {
            debugLog(`🐛 Debug-Info: ${JSON.stringify(result.debug)}`);
        }
        
        if (result.success) {
            debugLog(`✅ Schritt ${stepNumber} erfolgreich!`);
            status.innerHTML = '<span class="text-green-500 text-2xl">✅</span>';
            btn.innerHTML = '✓ Abgeschlossen';
            btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
            btn.classList.add('bg-green-500');
            
            // Details anzeigen
            if (result.details || result.checks) {
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
            }
            
            // Nächsten Schritt aktivieren
            if (stepNumber < 6) {
                currentStep = stepNumber + 1;
                debugLog(`➡️ Aktiviere Schritt ${currentStep}`);
                const nextStep = document.getElementById(`step${currentStep}`);
                const nextBtn = document.getElementById(`step${currentStep}-btn`);
                nextStep.classList.remove('opacity-50');
                nextBtn.disabled = false;
                nextBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                nextBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
                nextBtn.textContent = 'Ausführen';
            } else {
                debugLog(`🎉 Alle Schritte abgeschlossen!`);
                const completeSection = document.getElementById('completeSection');
                const completeBtn = document.getElementById('complete-btn');
                completeSection.classList.remove('opacity-50');
                completeBtn.disabled = false;
                completeBtn.classList.remove('cursor-not-allowed');
            }
            
        } else {
            debugLog(`❌ Schritt ${stepNumber} fehlgeschlagen: ${result.message}`);
            status.innerHTML = '<span class="text-red-500 text-2xl">❌</span>';
            btn.innerHTML = '❌ Fehler';
            btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
            btn.classList.add('bg-red-500');
            
            content.innerHTML += '<div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">' + result.message + '</div>';
        }
        
    } catch (error) {
        debugLog(`💥 JavaScript-Fehler: ${error.message}`);
        debugLog(`Stack: ${error.stack}`);
        status.innerHTML = '<span class="text-red-500 text-2xl">❌</span>';
        btn.innerHTML = '❌ Fehler';
        btn.classList.add('bg-red-500');
        content.innerHTML += '<div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">Netzwerkfehler: ' + error.message + '<br><br>Öffne die Browser-Console (F12) für Details.</div>';
    }
}

async function completeInstall() {
    debugLog('🏁 Finale Installation wird abgeschlossen...');
    const btn = document.getElementById('complete-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner inline-block w-5 h-5 border-2 border-green-600 border-t-transparent rounded-full"></span> Wird abgeschlossen...';
    
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
    }
}

// Bei Seitenladung Debug-Console anzeigen
window.addEventListener('load', () => {
    debugLog('✨ Installer geladen. Bereit zur Installation!');
});
</script>

</body>
</html>
