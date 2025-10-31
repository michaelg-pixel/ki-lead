<?php
/**
 * Update Script für bestehende courses Tabelle
 * Fügt fehlende Spalten hinzu
 */

require_once '../config/database.php';

try {
    echo "<h2>🔄 Aktualisiere courses Tabelle...</h2>";
    
    // Prüfe welche Spalten existieren
    $stmt = $pdo->query("DESCRIBE courses");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<p>Vorhandene Spalten: " . implode(', ', $existing_columns) . "</p>";
    
    // Spalten die hinzugefügt werden müssen
    $columns_to_add = [
        'type' => "ALTER TABLE courses ADD COLUMN type ENUM('video', 'pdf') NOT NULL DEFAULT 'video' AFTER description",
        'additional_info' => "ALTER TABLE courses ADD COLUMN additional_info TEXT AFTER type",
        'mockup_url' => "ALTER TABLE courses ADD COLUMN mockup_url VARCHAR(500) AFTER additional_info",
        'pdf_file' => "ALTER TABLE courses ADD COLUMN pdf_file VARCHAR(500) AFTER mockup_url",
        'is_freebie' => "ALTER TABLE courses ADD COLUMN is_freebie BOOLEAN DEFAULT FALSE AFTER pdf_file",
        'digistore_product_id' => "ALTER TABLE courses ADD COLUMN digistore_product_id VARCHAR(100) AFTER is_freebie",
        'sort_order' => "ALTER TABLE courses ADD COLUMN sort_order INT DEFAULT 0 AFTER digistore_product_id"
    ];
    
    $added = 0;
    foreach ($columns_to_add as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            try {
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Spalte '$column' hinzugefügt</p>";
                $added++;
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Spalte '$column': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Spalte '$column' existiert bereits</p>";
        }
    }
    
    echo "<br><h3>✅ Update abgeschlossen!</h3>";
    echo "<p>$added neue Spalten hinzugefügt</p>";
    
    // Zeige aktuelle Struktur
    echo "<br><h3>📋 Aktuelle Tabellenstruktur:</h3>";
    $stmt = $pdo->query("DESCRIBE courses");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p><a href='dashboard.php?page=templates' style='background: #a855f7; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>→ Zur Kursverwaltung</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Fehler:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #0a0a16;
        color: #e5e7eb;
    }
    h2, h3 {
        color: #a855f7;
    }
    table {
        width: 100%;
        margin-top: 10px;
        background: #1a1532;
        color: #e5e7eb;
    }
    th {
        background: #a855f7;
        color: white;
        padding: 8px;
    }
    td {
        padding: 6px;
    }
</style>