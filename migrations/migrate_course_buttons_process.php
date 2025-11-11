<?php
/**
 * Migration Processor: Button-Felder für Kurse
 * Führt die SQL-Migration aus
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Prüfe welche Spalten bereits existieren
    $stmt = $pdo->query("DESCRIBE courses");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columns_to_add = [];
    
    if (!in_array('button_text', $existing_columns)) {
        $columns_to_add[] = "ADD COLUMN button_text VARCHAR(100) DEFAULT NULL COMMENT 'Text des CTA-Buttons'";
    }
    
    if (!in_array('button_url', $existing_columns)) {
        $columns_to_add[] = "ADD COLUMN button_url VARCHAR(500) DEFAULT NULL COMMENT 'Link/URL des Buttons'";
    }
    
    if (!in_array('button_new_window', $existing_columns)) {
        $columns_to_add[] = "ADD COLUMN button_new_window TINYINT(1) DEFAULT 1 COMMENT 'Button in neuem Fenster öffnen (1=ja, 0=nein)'";
    }
    
    // Wenn Spalten hinzugefügt werden müssen
    if (count($columns_to_add) > 0) {
        $sql = "ALTER TABLE courses " . implode(", ", $columns_to_add);
        $pdo->exec($sql);
        
        $message = count($columns_to_add) . " Spalte(n) erfolgreich hinzugefügt: " . implode(", ", ['button_text', 'button_url', 'button_new_window']);
    } else {
        $message = "Alle Spalten existieren bereits. Keine Änderungen notwendig.";
    }
    
    // Verifizierung: Prüfen ob alle Spalten existieren
    $stmt = $pdo->query("DESCRIBE courses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasButtonText = in_array('button_text', $columns);
    $hasButtonUrl = in_array('button_url', $columns);
    $hasButtonNewWindow = in_array('button_new_window', $columns);
    
    if ($hasButtonText && $hasButtonUrl && $hasButtonNewWindow) {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'columns_status' => [
                'button_text' => '✅ Vorhanden',
                'button_url' => '✅ Vorhanden',
                'button_new_window' => '✅ Vorhanden'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Migration konnte nicht vollständig durchgeführt werden.',
            'columns_status' => [
                'button_text' => $hasButtonText ? '✅ Vorhanden' : '❌ Fehlt',
                'button_url' => $hasButtonUrl ? '✅ Vorhanden' : '❌ Fehlt',
                'button_new_window' => $hasButtonNewWindow ? '✅ Vorhanden' : '❌ Fehlt'
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
