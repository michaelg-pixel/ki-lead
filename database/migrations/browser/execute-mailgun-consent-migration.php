<?php
/**
 * Migration Executor: Mailgun Consent Type
 * Erweitert acceptance_type ENUM um 'mailgun_consent'
 * 
 * VERBESSERT: Mit korrektem Pfad zur config
 */

// Fehler-Reporting aktivieren für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keine direkten Fehler ausgeben
ini_set('log_errors', 1);

// Sicherstellen dass immer JSON zurückgegeben wird
header('Content-Type: application/json');

// Output-Buffer starten um sicherzustellen dass nur JSON ausgegeben wird
ob_start();

try {
    // KORREKTER PFAD: Von database/migrations/browser/ nach config/
    // browser/ -> migrations/ -> database/ -> ROOT -> config/
    $configPath = __DIR__ . '/../../../config/database.php';
    
    if (!file_exists($configPath)) {
        throw new Exception('Datenbankconfig nicht gefunden: ' . $configPath);
    }
    
    require_once $configPath;
    
    // Prüfe ob Funktion existiert
    if (!function_exists('getDBConnection')) {
        throw new Exception('getDBConnection() Funktion nicht gefunden');
    }
    
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('Datenbankverbindung konnte nicht hergestellt werden');
    }
    
    // Prüfe aktuelle ENUM-Werte
    $stmt = $pdo->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'av_contract_acceptances'
        AND COLUMN_NAME = 'acceptance_type'
    ");
    
    if (!$stmt) {
        throw new Exception('Konnte Tabellen-Informationen nicht abrufen');
    }
    
    $currentEnum = $stmt->fetchColumn();
    
    if (!$currentEnum) {
        throw new Exception('Tabelle av_contract_acceptances oder Spalte acceptance_type nicht gefunden');
    }
    
    // Prüfe ob 'mailgun_consent' bereits vorhanden
    if (strpos($currentEnum, 'mailgun_consent') !== false) {
        // Buffer leeren
        ob_end_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Migration bereits durchgeführt - mailgun_consent existiert bereits.',
            'details' => 'Aktuelles ENUM: ' . $currentEnum,
            'already_exists' => true
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Führe Migration aus
    $sql = "
        ALTER TABLE `av_contract_acceptances` 
        MODIFY COLUMN `acceptance_type` 
        ENUM('registration','update','renewal','mailgun_consent') 
        NOT NULL DEFAULT 'registration'
        COMMENT 'Typ der Zustimmung: registration, update, renewal, mailgun_consent'
    ";
    
    $result = $pdo->exec($sql);
    
    if ($result === false) {
        $errorInfo = $pdo->errorInfo();
        throw new Exception('SQL Fehler: ' . $errorInfo[2]);
    }
    
    // Verifiziere Migration
    $stmt = $pdo->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'av_contract_acceptances'
        AND COLUMN_NAME = 'acceptance_type'
    ");
    
    $newEnum = $stmt->fetchColumn();
    
    // Log
    error_log("✅ MIGRATION SUCCESS: acceptance_type ENUM erweitert um 'mailgun_consent'");
    error_log("   Vorher: " . $currentEnum);
    error_log("   Nachher: " . $newEnum);
    
    // Buffer leeren
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Migration erfolgreich durchgeführt!',
        'details' => 'ENUM wurde erweitert',
        'previous_enum' => $currentEnum,
        'new_enum' => $newEnum
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Buffer leeren
    ob_end_clean();
    
    error_log("❌ MIGRATION ERROR (PDO): " . $e->getMessage());
    error_log("   Stack: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'type' => 'PDOException'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Buffer leeren
    ob_end_clean();
    
    error_log("❌ MIGRATION ERROR: " . $e->getMessage());
    error_log("   Stack: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'type' => 'Exception'
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    // Buffer leeren falls noch offen
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    error_log("❌ MIGRATION FATAL ERROR: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Kritischer Fehler: ' . $e->getMessage(),
        'type' => 'Throwable'
    ], JSON_PRETTY_PRINT);
}
