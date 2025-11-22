<?php
/**
 * DEBUG: Test Database Connection & Environment
 * Prüft ob alles korrekt konfiguriert ist
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$results = [
    'checks' => [],
    'success' => true
];

// 1. PHP Version
$results['checks']['php_version'] = [
    'status' => PHP_VERSION >= '7.4' ? 'OK' : 'WARNING',
    'value' => PHP_VERSION,
    'required' => '7.4+'
];

// 2. Config-Datei
$configPath = __DIR__ . '/../../config/database.php';
$results['checks']['config_file'] = [
    'status' => file_exists($configPath) ? 'OK' : 'ERROR',
    'path' => $configPath,
    'exists' => file_exists($configPath)
];

if (!file_exists($configPath)) {
    $results['success'] = false;
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// 3. Require config
try {
    require_once $configPath;
    $results['checks']['config_loaded'] = [
        'status' => 'OK',
        'message' => 'Config erfolgreich geladen'
    ];
} catch (Exception $e) {
    $results['checks']['config_loaded'] = [
        'status' => 'ERROR',
        'error' => $e->getMessage()
    ];
    $results['success'] = false;
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// 4. Funktion getDBConnection
$results['checks']['function_exists'] = [
    'status' => function_exists('getDBConnection') ? 'OK' : 'ERROR',
    'function' => 'getDBConnection'
];

if (!function_exists('getDBConnection')) {
    $results['success'] = false;
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// 5. Datenbankverbindung
try {
    $pdo = getDBConnection();
    $results['checks']['database_connection'] = [
        'status' => $pdo ? 'OK' : 'ERROR',
        'message' => 'Verbindung erfolgreich'
    ];
} catch (PDOException $e) {
    $results['checks']['database_connection'] = [
        'status' => 'ERROR',
        'error' => $e->getMessage()
    ];
    $results['success'] = false;
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// 6. Tabelle existiert
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'av_contract_acceptances'");
    $tableExists = $stmt->rowCount() > 0;
    
    $results['checks']['table_exists'] = [
        'status' => $tableExists ? 'OK' : 'ERROR',
        'table' => 'av_contract_acceptances',
        'exists' => $tableExists
    ];
    
    if (!$tableExists) {
        $results['success'] = false;
    }
} catch (PDOException $e) {
    $results['checks']['table_exists'] = [
        'status' => 'ERROR',
        'error' => $e->getMessage()
    ];
    $results['success'] = false;
}

// 7. Spalte acceptance_type existiert
if ($tableExists) {
    try {
        $stmt = $pdo->query("
            SELECT COLUMN_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'av_contract_acceptances'
            AND COLUMN_NAME = 'acceptance_type'
        ");
        
        $currentEnum = $stmt->fetchColumn();
        
        $results['checks']['column_acceptance_type'] = [
            'status' => $currentEnum ? 'OK' : 'ERROR',
            'current_enum' => $currentEnum,
            'has_mailgun_consent' => strpos($currentEnum, 'mailgun_consent') !== false
        ];
        
    } catch (PDOException $e) {
        $results['checks']['column_acceptance_type'] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
        $results['success'] = false;
    }
}

// 8. Datenbank-Name
try {
    $stmt = $pdo->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    
    $results['checks']['database_name'] = [
        'status' => 'OK',
        'name' => $dbName
    ];
} catch (PDOException $e) {
    $results['checks']['database_name'] = [
        'status' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// 9. Schreibrechte testen
try {
    $pdo->exec("SELECT 1"); // Dummy query
    $results['checks']['write_permission'] = [
        'status' => 'OK',
        'message' => 'Schreibrechte vorhanden'
    ];
} catch (PDOException $e) {
    $results['checks']['write_permission'] = [
        'status' => 'ERROR',
        'error' => $e->getMessage()
    ];
    $results['success'] = false;
}

echo json_encode($results, JSON_PRETTY_PRINT);
