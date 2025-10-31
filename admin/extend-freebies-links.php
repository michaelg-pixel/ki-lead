<?php
/**
 * Erweitert die freebies-Tabelle um Link- und Tracking-Funktionen
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "🔧 Starte Datenbank-Erweiterung...\n\n";
    
    // Spalten für öffentliche Links hinzufügen
    $columns = [
        'public_link' => "ALTER TABLE freebies ADD COLUMN public_link VARCHAR(255) DEFAULT NULL AFTER url_slug",
        'short_link' => "ALTER TABLE freebies ADD COLUMN short_link VARCHAR(100) DEFAULT NULL AFTER public_link",
        'thank_you_link' => "ALTER TABLE freebies ADD COLUMN thank_you_link VARCHAR(255) DEFAULT NULL AFTER short_link",
        'thank_you_short_link' => "ALTER TABLE freebies ADD COLUMN thank_you_short_link VARCHAR(100) DEFAULT NULL AFTER thank_you_link",
        'freebie_clicks' => "ALTER TABLE freebies ADD COLUMN freebie_clicks INT DEFAULT 0 AFTER thank_you_short_link",
        'thank_you_clicks' => "ALTER TABLE freebies ADD COLUMN thank_you_clicks INT DEFAULT 0 AFTER freebie_clicks",
        'video_button_text' => "ALTER TABLE freebies ADD COLUMN video_button_text VARCHAR(255) DEFAULT 'Zum Videokurs' AFTER thank_you_clicks",
        'video_course_url' => "ALTER TABLE freebies ADD COLUMN video_course_url TEXT DEFAULT NULL AFTER video_button_text",
        'thank_you_headline' => "ALTER TABLE freebies ADD COLUMN thank_you_headline VARCHAR(255) DEFAULT 'Vielen Dank!' AFTER video_course_url",
        'thank_you_text' => "ALTER TABLE freebies ADD COLUMN thank_you_text TEXT DEFAULT NULL AFTER thank_you_headline"
    ];
    
    foreach ($columns as $name => $sql) {
        try {
            // Prüfen ob Spalte bereits existiert
            $check = $pdo->query("SHOW COLUMNS FROM freebies LIKE '$name'")->fetch();
            if (!$check) {
                $pdo->exec($sql);
                echo "✅ Spalte '$name' hinzugefügt\n";
            } else {
                echo "ℹ️  Spalte '$name' existiert bereits\n";
            }
        } catch (PDOException $e) {
            echo "⚠️  Fehler bei Spalte '$name': " . $e->getMessage() . "\n";
        }
    }
    
    // Links für existierende Templates generieren
    echo "\n🔗 Generiere Links für existierende Templates...\n";
    
    $templates = $pdo->query("SELECT id, url_slug, name FROM freebies WHERE public_link IS NULL")->fetchAll();
    
    foreach ($templates as $template) {
        $slug = $template['url_slug'];
        if (empty($slug)) {
            $slug = generateSlug($template['name']);
            $pdo->prepare("UPDATE freebies SET url_slug = ? WHERE id = ?")->execute([$slug, $template['id']]);
        }
        
        $public_link = '/freebie/view.php?id=' . $template['id'];
        $thank_you_link = '/freebie/thankyou.php?id=' . $template['id'];
        
        $pdo->prepare("UPDATE freebies SET public_link = ?, thank_you_link = ? WHERE id = ?")
            ->execute([$public_link, $thank_you_link, $template['id']]);
        
        echo "  ✅ Template #{$template['id']}: Links generiert\n";
    }
    
    echo "\n✅ Datenbank-Erweiterung erfolgreich abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

function generateSlug($text) {
    $search = ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'];
    $replace = ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'];
    $text = str_replace($search, $replace, $text);
    
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'freebie-' . uniqid();
}
