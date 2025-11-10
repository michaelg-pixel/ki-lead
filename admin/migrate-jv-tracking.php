<?php
/**
 * JV Partner Tracking Migration
 * Fügt JV-Partner-Tracking-Spalten zur users-Tabelle hinzu
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    $steps = [];
    $errors = [];
    
    // Schritt 1: Prüfen ob Spalte jv_partner_username existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'jv_partner_username'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Spalte hinzufügen
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN jv_partner_username VARCHAR(100) NULL AFTER digistore_product_name,
            ADD INDEX idx_jv_partner (jv_partner_username)
        ");
        $steps[] = "✅ Spalte 'jv_partner_username' hinzugefügt";
    } else {
        $steps[] = "ℹ️ Spalte 'jv_partner_username' existiert bereits";
    }
    
    // Schritt 2: Prüfen ob Spalte affiliate_username existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'affiliate_username'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Spalte hinzufügen
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN affiliate_username VARCHAR(100) NULL AFTER jv_partner_username,
            ADD INDEX idx_affiliate (affiliate_username)
        ");
        $steps[] = "✅ Spalte 'affiliate_username' hinzugefügt";
    } else {
        $steps[] = "ℹ️ Spalte 'affiliate_username' existiert bereits";
    }
    
    // Schritt 3: Prüfen ob Spalte jv_commission_data existiert (für zusätzliche Infos)
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'jv_commission_data'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Spalte für JSON-Daten hinzufügen
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN jv_commission_data JSON NULL AFTER affiliate_username
        ");
        $steps[] = "✅ Spalte 'jv_commission_data' hinzugefügt (JSON)";
    } else {
        $steps[] = "ℹ️ Spalte 'jv_commission_data' existiert bereits";
    }
    
    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'message' => 'Migration erfolgreich abgeschlossen!',
        'details' => implode("\n", $steps)
    ]);
    
} catch (PDOException $e) {
    // Fehler
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Allgemeiner Fehler
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
