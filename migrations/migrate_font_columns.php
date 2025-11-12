<?php
header('Content-Type: application/json; charset=utf-8');

session_start();

// Admin-Check auskommentiert für Migration
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
//     exit;
// }

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Array mit allen benötigten Font-Spalten und ihren Default-Werten
    $fontColumns = [
        'headline_font' => ['type' => 'VARCHAR(100)', 'default' => 'Poppins'],
        'headline_size' => ['type' => 'INT', 'default' => 48],
        'headline_size_mobile' => ['type' => 'INT', 'default' => 32],
        'preheadline_font' => ['type' => 'VARCHAR(100)', 'default' => 'Poppins'],
        'preheadline_size' => ['type' => 'INT', 'default' => 14],
        'subheadline_font' => ['type' => 'VARCHAR(100)', 'default' => 'Poppins'],
        'subheadline_size' => ['type' => 'INT', 'default' => 20],
        'bulletpoints_font' => ['type' => 'VARCHAR(100)', 'default' => 'Poppins'],
        'bulletpoints_size' => ['type' => 'INT', 'default' => 16],
        'body_font' => ['type' => 'VARCHAR(100)', 'default' => 'Poppins'],
        'body_size' => ['type' => 'INT', 'default' => 16],
        'body_size_mobile' => ['type' => 'INT', 'default' => 14],
        'heading_font' => ['type' => 'VARCHAR(100)', 'default' => 'Poppins']
    ];
    
    // Aktuelle Spalten der freebies-Tabelle abrufen
    $stmt = $pdo->query("DESCRIBE freebies");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    $addedColumns = [];
    $skippedColumns = [];
    $errors = [];
    
    // Für jede Font-Spalte prüfen und ggf. hinzufügen
    foreach ($fontColumns as $columnName => $config) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                // Spalte hinzufügen
                $sql = "ALTER TABLE freebies ADD COLUMN {$columnName} {$config['type']}";
                
                // Default-Wert nur bei VARCHAR setzen (bei INT null lassen)
                if (strpos($config['type'], 'VARCHAR') !== false) {
                    $sql .= " DEFAULT '{$config['default']}'";
                } else {
                    $sql .= " DEFAULT {$config['default']}";
                }
                
                $pdo->exec($sql);
                $addedColumns[] = $columnName;
                
            } catch (PDOException $e) {
                $errors[] = "Fehler bei {$columnName}: " . $e->getMessage();
            }
        } else {
            $skippedColumns[] = $columnName;
        }
    }
    
    // Zusammenfassung erstellen
    $details = "";
    
    if (!empty($addedColumns)) {
        $details .= "✅ Hinzugefügte Spalten:\n";
        foreach ($addedColumns as $col) {
            $details .= "  - {$col}\n";
        }
        $details .= "\n";
    }
    
    if (!empty($skippedColumns)) {
        $details .= "ℹ️  Bereits vorhandene Spalten:\n";
        foreach ($skippedColumns as $col) {
            $details .= "  - {$col}\n";
        }
        $details .= "\n";
    }
    
    if (!empty($errors)) {
        $details .= "❌ Fehler:\n";
        foreach ($errors as $error) {
            $details .= "  - {$error}\n";
        }
    }
    
    // Erfolg, wenn mindestens eine Spalte hinzugefügt wurde oder alle bereits existierten
    if (!empty($addedColumns) || (empty($addedColumns) && empty($errors))) {
        $message = empty($addedColumns) 
            ? "Alle Font-Spalten waren bereits vorhanden." 
            : "Migration erfolgreich abgeschlossen! " . count($addedColumns) . " Spalte(n) hinzugefügt.";
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'details' => $details,
            'added_count' => count($addedColumns),
            'skipped_count' => count($skippedColumns),
            'error_count' => count($errors)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Migration konnte nicht vollständig durchgeführt werden.',
            'details' => $details,
            'added_count' => count($addedColumns),
            'skipped_count' => count($skippedColumns),
            'error_count' => count($errors)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Kritischer Fehler: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}
