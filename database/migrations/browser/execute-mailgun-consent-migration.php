<?php
/**
 * Migration Executor: Mailgun Consent Type
 * Erweitert acceptance_type ENUM um 'mailgun_consent'
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Prüfe aktuelle ENUM-Werte
    $stmt = $pdo->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'av_contract_acceptances'
        AND COLUMN_NAME = 'acceptance_type'
    ");
    
    $currentEnum = $stmt->fetchColumn();
    
    // Prüfe ob 'mailgun_consent' bereits vorhanden
    if (strpos($currentEnum, 'mailgun_consent') !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Migration bereits durchgeführt - mailgun_consent existiert bereits.',
            'details' => 'Aktuelles ENUM: ' . $currentEnum,
            'already_exists' => true
        ]);
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
    
    $pdo->exec($sql);
    
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
    error_log("   Neues ENUM: " . $newEnum);
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Migration erfolgreich durchgeführt!',
        'details' => 'ENUM wurde erweitert: ' . $newEnum,
        'previous_enum' => $currentEnum,
        'new_enum' => $newEnum
    ]);
    
} catch (PDOException $e) {
    error_log("❌ MIGRATION ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
