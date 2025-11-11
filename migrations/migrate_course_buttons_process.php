<?php
/**
 * Migration Processor: Button-Felder für Kurse
 * Führt die SQL-Migration aus
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Migration ausführen
    $sql = "
        ALTER TABLE courses
        ADD COLUMN IF NOT EXISTS button_text VARCHAR(100) DEFAULT NULL COMMENT 'Text des CTA-Buttons',
        ADD COLUMN IF NOT EXISTS button_url VARCHAR(500) DEFAULT NULL COMMENT 'Link/URL des Buttons',
        ADD COLUMN IF NOT EXISTS button_new_window TINYINT(1) DEFAULT 1 COMMENT 'Button in neuem Fenster öffnen (1=ja, 0=nein)'
    ";
    
    $pdo->exec($sql);
    
    // Verifizierung: Prüfen ob Spalten existieren
    $stmt = $pdo->query("DESCRIBE courses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasButtonText = in_array('button_text', $columns);
    $hasButtonUrl = in_array('button_url', $columns);
    $hasButtonNewWindow = in_array('button_new_window', $columns);
    
    if ($hasButtonText && $hasButtonUrl && $hasButtonNewWindow) {
        echo json_encode([
            'success' => true,
            'message' => 'Migration erfolgreich! Die Button-Felder wurden zur courses-Tabelle hinzugefügt.',
            'columns_added' => [
                'button_text' => $hasButtonText,
                'button_url' => $hasButtonUrl,
                'button_new_window' => $hasButtonNewWindow
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Migration konnte nicht vollständig durchgeführt werden. Bitte überprüfe die Datenbank-Rechte.',
            'columns_status' => [
                'button_text' => $hasButtonText,
                'button_url' => $hasButtonUrl,
                'button_new_window' => $hasButtonNewWindow
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
