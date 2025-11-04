<?php
/**
 * Migration Script: Mockup-Feld zu tutorials hinzufügen
 * Datum: 2025-11-04
 * Aufruf: Über Browser direkt aufrufen
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Migration: Mockup-Feld zu tutorials-Tabelle hinzufügen</h2>";
    
    // Prüfen ob Spalte bereits existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM tutorials LIKE 'mockup_image'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Spalte 'mockup_image' existiert bereits!</p>";
    } else {
        // Spalte hinzufügen
        $pdo->exec("ALTER TABLE tutorials ADD COLUMN mockup_image VARCHAR(500) NULL AFTER thumbnail_url");
        echo "<p style='color: green;'>✅ Spalte 'mockup_image' erfolgreich hinzugefügt!</p>";
        
        // Index hinzufügen
        try {
            $pdo->exec("CREATE INDEX idx_mockup ON tutorials(mockup_image)");
            echo "<p style='color: green;'>✅ Index 'idx_mockup' erfolgreich erstellt!</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>⚠️ Index 'idx_mockup' existiert bereits!</p>";
            } else {
                throw $e;
            }
        }
    }
    
    // Verzeichnis für Mockups erstellen falls nicht vorhanden
    $mockupDir = __DIR__ . '/../uploads/mockups';
    if (!is_dir($mockupDir)) {
        mkdir($mockupDir, 0755, true);
        echo "<p style='color: green;'>✅ Verzeichnis '/uploads/mockups' erstellt!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Verzeichnis '/uploads/mockups' existiert bereits!</p>";
    }
    
    // .htaccess für Uploads erstellen
    $htaccess = $mockupDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "# Allow access to images\n<FilesMatch \"\.(jpg|jpeg|png|gif|webp)$\">\n    Require all granted\n</FilesMatch>");
        echo "<p style='color: green;'>✅ .htaccess in mockups-Verzeichnis erstellt!</p>";
    }
    
    echo "<h3 style='color: green;'>✅ Migration erfolgreich abgeschlossen!</h3>";
    echo "<p><a href='/admin/dashboard.php?page=tutorials'>← Zurück zu Tutorials</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Fehler bei der Migration:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #1a1a2e;
        color: #e0e0e0;
    }
    h2, h3 {
        color: #a855f7;
    }
    p {
        padding: 10px;
        background: rgba(0,0,0,0.3);
        border-radius: 5px;
        margin: 10px 0;
    }
    a {
        color: #a855f7;
        text-decoration: none;
        font-weight: bold;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
