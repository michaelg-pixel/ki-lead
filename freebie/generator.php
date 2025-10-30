<?php
/**
 * Dynamischer Freebie-Seiten-Generator
 * 
 * Diese Datei wird von freebie-editor.php aufgerufen, wenn ein Freebie gespeichert wird.
 * Sie erstellt eine physische PHP-Datei im /freebie/ Verzeichnis mit der unique_id.
 */

require_once '../config/database.php';

function generateFreebieFile($freebie_id) {
    $conn = getDBConnection();
    
    // Freebie-Daten laden
    $stmt = $conn->prepare("
        SELECT f.*, c.title as course_title, c.thumbnail, c.id as course_id
        FROM freebies f
        JOIN courses c ON f.course_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$freebie_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        return false;
    }
    
    // Template laden basierend auf gewähltem Layout
    $template_file = __DIR__ . '/templates/' . $freebie['layout'] . '.php';
    
    if (!file_exists($template_file)) {
        return false;
    }
    
    // Template-Inhalt laden
    ob_start();
    
    // Variablen für Template verfügbar machen
    $course = [
        'title' => $freebie['course_title'],
        'thumbnail' => $freebie['thumbnail'],
        'id' => $freebie['course_id']
    ];
    
    include $template_file;
    $template_content = ob_get_clean();
    
    // Freebie-Datei erstellen
    $freebie_filename = __DIR__ . '/' . $freebie['unique_id'] . '.php';
    
    // PHP-Wrapper hinzufügen
    $file_content = "<?php\n";
    $file_content .= "// Auto-generated Freebie Page\n";
    $file_content .= "// Freebie ID: " . $freebie['id'] . "\n";
    $file_content .= "// Created: " . date('Y-m-d H:i:s') . "\n\n";
    $file_content .= "require_once '../config/database.php';\n";
    $file_content .= "\$conn = getDBConnection();\n\n";
    $file_content .= "// Freebie-Daten laden\n";
    $file_content .= "\$stmt = \$conn->prepare(\"SELECT f.*, c.title as course_title, c.thumbnail FROM freebies f JOIN courses c ON f.course_id = c.id WHERE f.unique_id = ?\");\n";
    $file_content .= "\$stmt->execute(['" . $freebie['unique_id'] . "']);\n";
    $file_content .= "\$freebie = \$stmt->fetch(PDO::FETCH_ASSOC);\n";
    $file_content .= "\$course = ['title' => \$freebie['course_title'], 'thumbnail' => \$freebie['thumbnail']];\n";
    $file_content .= "?>\n\n";
    $file_content .= $template_content;
    
    // Datei schreiben
    if (file_put_contents($freebie_filename, $file_content)) {
        return true;
    }
    
    return false;
}

/**
 * Freebie-Seite löschen
 */
function deleteFreebieFile($unique_id) {
    $freebie_filename = __DIR__ . '/' . $unique_id . '.php';
    
    if (file_exists($freebie_filename)) {
        return unlink($freebie_filename);
    }
    
    return true;
}

/**
 * Browser-Link generieren
 */
function generateFreebieLink($unique_id) {
    return BASE_URL . '/freebie/' . $unique_id . '.php';
}
