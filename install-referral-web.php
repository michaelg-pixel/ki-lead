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
        
        // SQL in einzelne Statements aufteilen
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $executed = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Ignoriere "Duplicate column" und "Table exists" Fehler
                $error_code = $e->getCode();
                if ($error_code == '42S01' || $error_code == '42000') {
                    // 42S01 = Table already exists
                    // 42000 = Syntax error or Duplicate column
                    if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                        strpos($e->getMessage(), 'already exists') !== false) {
                        $skipped++;
                        continue;
                    }
                }
                
                // Alle anderen Fehler sammeln
                $errors[] = [
                    'statement' => substr($statement, 0, 100) . '...',
                    'error' => $e->getMessage(),
                    'code' => $error_code
                ];
            }
        }
        
        // Prüfe erneut Tabellen
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $tables = $stmt->rowCount();
        
        // Wenn wir mindestens 6 Tabellen haben, ist es ein Erfolg
        if ($tables >= 6) {
            return [
                'success' => true,
                'message' => '✅ Datenbank migriert (' . $tables . ' Tabellen)',
                'details' => [
                    'tables' => $tables,
                    'executed' => $executed,
                    'skipped' => $skipped,
                    'action' => 'migrated'
                ]
            ];
        }
        
        // Wenn Fehler aufgetreten sind, die nicht ignoriert werden konnten
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '❌ Migration teilweise fehlgeschlagen',
                'details' => [
                    'tables' => $tables,
                    'executed' => $executed,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]
            ];
        }
        
        return [
            'success' => true,
            'message' => '✅ Migration abgeschlossen',
            'details' => [
                'tables' => $tables,
                'executed' => $executed,
                'skipped' => $skipped
            ]
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => '❌ Datenbank-Verbindungsfehler: ' . $e->getMessage(),
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
        
        // Erstelle Test-Klick (falls noch nicht vorhanden)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_clicks WHERE customer_id = 1");
        $has_clicks = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$has_clicks) {
            $pdo->exec("
                INSERT INTO referral_clicks (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
                VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Mozilla/5.0 Test Browser', MD5('test_fingerprint'), NOW())
            ");
        }
        
        // Erstelle Test-Conversion (falls noch nicht vorhanden)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_conversions WHERE customer_id = 1");
        $has_conversions = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$has_conversions) {
            $pdo->exec("
                INSERT INTO referral_conversions (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, source, suspicious, created_at)
                VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Mozilla/5.0 Test Browser', MD5('test_fingerprint'), 'thankyou', 0, NOW())
            ");
        }
        
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
                'clicks' => $has_clicks ? 'vorhanden' : 'erstellt',
                'conversions' => $has_conversions ? 'vorhanden' : 'erstellt',
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
?><?php include 'installer-ui.html'; ?>
