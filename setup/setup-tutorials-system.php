<?php
/**
 * Tutorial-System Setup Script
 * 
 * Dieses Script erstellt automatisch die benötigten Datenbank-Tabellen
 * für das Tutorial-System
 */

// Nur Admins dürfen dieses Script ausführen
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('⛔ Zugriff verweigert! Nur Admins können dieses Setup ausführen.');
}

require_once '../config/database.php';

echo '<pre>';
echo "==============================================\n";
echo "   TUTORIAL-SYSTEM SETUP\n";
echo "==============================================\n\n";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Kategorien-Tabelle erstellen
    echo "📁 Erstelle tutorial_categories Tabelle...\n";
    $sql = "CREATE TABLE IF NOT EXISTS tutorial_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        icon VARCHAR(50) DEFAULT 'video',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ tutorial_categories Tabelle erstellt\n\n";
    
    // 2. Alte Tutorials-Tabelle löschen und neu erstellen
    echo "🎥 Erstelle tutorials Tabelle...\n";
    
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'tutorials'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Alte tutorials Tabelle gefunden - wird gesichert...\n";
        $pdo->exec("RENAME TABLE tutorials TO tutorials_backup_" . date('YmdHis'));
        echo "✅ Backup erstellt: tutorials_backup_" . date('YmdHis') . "\n";
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS tutorials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        vimeo_url VARCHAR(500) NOT NULL,
        thumbnail_url VARCHAR(500),
        duration VARCHAR(20),
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES tutorial_categories(id) ON DELETE CASCADE,
        INDEX idx_category (category_id),
        INDEX idx_active (is_active),
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ tutorials Tabelle erstellt\n\n";
    
    // 3. Standard-Kategorien einfügen (wenn noch nicht vorhanden)
    echo "📂 Erstelle Standard-Kategorien...\n";
    
    $categories = [
        ['name' => 'Erste Schritte', 'slug' => 'erste-schritte', 'description' => 'Grundlegende Einführung in das System', 'icon' => 'rocket', 'sort_order' => 1],
        ['name' => 'Freebie-Editor', 'slug' => 'freebie-editor', 'description' => 'Wie du den Freebie-Editor verwendest', 'icon' => 'edit', 'sort_order' => 2],
        ['name' => 'Kurse', 'slug' => 'kurse', 'description' => 'Alles über die Kursverwaltung', 'icon' => 'graduation-cap', 'sort_order' => 3],
        ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Marketing-Tipps und Strategien', 'icon' => 'chart-line', 'sort_order' => 4],
        ['name' => 'Fortgeschritten', 'slug' => 'fortgeschritten', 'description' => 'Erweiterte Funktionen und Tipps', 'icon' => 'star', 'sort_order' => 5],
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO tutorial_categories (name, slug, description, icon, sort_order)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $inserted = 0;
    foreach ($categories as $cat) {
        $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['icon'], $cat['sort_order']]);
        if ($stmt->rowCount() > 0) {
            $inserted++;
            echo "  ✅ Kategorie '{$cat['name']}' erstellt\n";
        }
    }
    
    if ($inserted > 0) {
        echo "\n✅ {$inserted} Kategorien erfolgreich erstellt\n\n";
    } else {
        echo "\nℹ️  Kategorien existieren bereits\n\n";
    }
    
    // 4. Beispiel-Videos einfügen (optional)
    echo "🎬 Möchtest du Beispiel-Videos hinzufügen? (Nur zu Demonstrationszwecken)\n";
    echo "   Diese können später im Admin-Bereich gelöscht werden.\n\n";
    
    $create_examples = false; // Auf true setzen, um Beispiele zu erstellen
    
    if ($create_examples) {
        // IDs der Kategorien abrufen
        $cat_ids = [];
        foreach ($categories as $cat) {
            $stmt = $pdo->prepare("SELECT id FROM tutorial_categories WHERE slug = ?");
            $stmt->execute([$cat['slug']]);
            $cat_ids[$cat['slug']] = $stmt->fetchColumn();
        }
        
        $examples = [
            [
                'category_id' => $cat_ids['erste-schritte'],
                'title' => 'Willkommen im KI Lead-System',
                'description' => 'Eine kurze Einführung in alle wichtigen Funktionen',
                'vimeo_url' => 'https://player.vimeo.com/video/123456789',
                'sort_order' => 1
            ],
            [
                'category_id' => $cat_ids['erste-schritte'],
                'title' => 'Dashboard-Übersicht',
                'description' => 'Lerne dein Dashboard kennen und nutze es effektiv',
                'vimeo_url' => 'https://player.vimeo.com/video/123456790',
                'sort_order' => 2
            ],
            [
                'category_id' => $cat_ids['freebie-editor'],
                'title' => 'Dein erstes Freebie erstellen',
                'description' => 'Schritt-für-Schritt Anleitung zum Freebie-Editor',
                'vimeo_url' => 'https://player.vimeo.com/video/123456791',
                'sort_order' => 1
            ],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO tutorials (category_id, title, description, vimeo_url, sort_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($examples as $ex) {
            $stmt->execute([$ex['category_id'], $ex['title'], $ex['description'], $ex['vimeo_url'], $ex['sort_order']]);
            echo "  ✅ Beispiel-Video '{$ex['title']}' erstellt\n";
        }
        
        echo "\n✅ Beispiel-Videos erstellt\n\n";
    }
    
    // 5. Abschluss
    echo "==============================================\n";
    echo "✅ SETUP ERFOLGREICH ABGESCHLOSSEN!\n";
    echo "==============================================\n\n";
    
    echo "📋 Nächste Schritte:\n\n";
    echo "1. Gehe zu: Admin Dashboard → Anleitungen & Tutorials\n";
    echo "2. Erstelle deine Kategorien (falls gewünscht)\n";
    echo "3. Füge deine ersten Tutorial-Videos hinzu\n";
    echo "4. Stelle sicher, dass Videos auf 'Aktiv' gesetzt sind\n";
    echo "5. Kunden können die Videos dann unter 'Anleitungen & Tutorials' sehen\n\n";
    
    echo "📖 Dokumentation: Siehe TUTORIALS_SYSTEM_README.md\n\n";
    
    echo "⚠️  WICHTIG: Lösche diese setup-tutorials-system.php Datei\n";
    echo "   aus Sicherheitsgründen nach erfolgreicher Installation!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ FEHLER BEI DER INSTALLATION:\n";
    echo $e->getMessage() . "\n\n";
    echo "Bitte prüfe:\n";
    echo "- Datenbank-Verbindung\n";
    echo "- Schreibrechte\n";
    echo "- MySQL-Version\n\n";
}

echo '</pre>';
?>
