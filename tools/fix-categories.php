<?php
/**
 * Kategorien-Diagnose und Auto-Fix
 * Prüft ob Kategorien-Tabelle existiert und befüllt sie bei Bedarf
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

echo "<h2>🔍 Kategorien-Diagnose</h2>";

// 1. Prüfen ob Tabelle existiert
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebie_template_categories'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Tabelle <code>freebie_template_categories</code> existiert<br><br>";
        
        // 2. Anzahl Kategorien prüfen
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM freebie_template_categories");
        $count = $stmt->fetchColumn();
        
        echo "📊 Anzahl Kategorien in DB: <strong>$count</strong><br><br>";
        
        if ($count > 0) {
            echo "<h3>📋 Vorhandene Kategorien:</h3>";
            $stmt = $pdo->query("SELECT * FROM freebie_template_categories ORDER BY name ASC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<ul>";
            foreach ($categories as $cat) {
                echo "<li><strong>" . htmlspecialchars($cat['name']) . "</strong> (Slug: " . htmlspecialchars($cat['slug']) . ")</li>";
            }
            echo "</ul><br>";
            
            echo "✅ Kategorien sind vorhanden und sollten funktionieren!<br>";
            echo "<p><a href='/customer/dashboard.php?page=freebies'>Zurück zu Freebies</a></p>";
            
        } else {
            echo "⚠️ Tabelle ist leer - fülle sie jetzt mit Standard-Kategorien...<br><br>";
            
            // Kategorien einfügen
            $defaultCategories = [
                ['name' => '💼 Online Business & Marketing', 'slug' => 'online-business'],
                ['name' => '💪 Gesundheit & Fitness', 'slug' => 'gesundheit-fitness'],
                ['name' => '🧠 Persönliche Entwicklung', 'slug' => 'persoenliche-entwicklung'],
                ['name' => '💰 Finanzen & Investment', 'slug' => 'finanzen-investment'],
                ['name' => '🏠 Immobilien', 'slug' => 'immobilien'],
                ['name' => '🛒 E-Commerce & Dropshipping', 'slug' => 'ecommerce-dropshipping'],
                ['name' => '📈 Affiliate Marketing', 'slug' => 'affiliate-marketing'],
                ['name' => '📱 Social Media Marketing', 'slug' => 'social-media-marketing'],
                ['name' => '🤖 KI & Automation', 'slug' => 'ki-automation'],
                ['name' => '👔 Coaching & Consulting', 'slug' => 'coaching-consulting'],
                ['name' => '✨ Spiritualität & Mindfulness', 'slug' => 'spiritualitaet-mindfulness'],
                ['name' => '❤️ Beziehungen & Dating', 'slug' => 'beziehungen-dating'],
                ['name' => '👨‍👩‍👧 Eltern & Familie', 'slug' => 'eltern-familie'],
                ['name' => '🎯 Karriere & Beruf', 'slug' => 'karriere-beruf'],
                ['name' => '🎨 Hobbys & Freizeit', 'slug' => 'hobbys-freizeit'],
                ['name' => '📂 Sonstiges', 'slug' => 'sonstiges']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO freebie_template_categories (name, slug, created_at) 
                VALUES (?, ?, NOW())
            ");
            
            $inserted = 0;
            foreach ($defaultCategories as $cat) {
                try {
                    $stmt->execute([$cat['name'], $cat['slug']]);
                    $inserted++;
                    echo "✅ Kategorie eingefügt: <strong>" . htmlspecialchars($cat['name']) . "</strong><br>";
                } catch (PDOException $e) {
                    echo "⚠️ Fehler bei: " . htmlspecialchars($cat['name']) . " - " . $e->getMessage() . "<br>";
                }
            }
            
            echo "<br>✅ $inserted Kategorien erfolgreich eingefügt!<br><br>";
            echo "<p><a href='/customer/dashboard.php?page=freebies'>Zurück zu Freebies - Kategorien sollten jetzt sichtbar sein!</a></p>";
        }
        
    } else {
        echo "❌ Tabelle <code>freebie_template_categories</code> existiert NICHT<br><br>";
        echo "<h3>🔧 Erstelle Tabelle jetzt...</h3>";
        
        // Tabelle erstellen
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS freebie_template_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                icon VARCHAR(50),
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "✅ Tabelle erstellt!<br><br>";
        
        // Kategorien einfügen
        echo "<h3>📝 Füge Standard-Kategorien ein...</h3>";
        
        $defaultCategories = [
            ['name' => '💼 Online Business & Marketing', 'slug' => 'online-business'],
            ['name' => '💪 Gesundheit & Fitness', 'slug' => 'gesundheit-fitness'],
            ['name' => '🧠 Persönliche Entwicklung', 'slug' => 'persoenliche-entwicklung'],
            ['name' => '💰 Finanzen & Investment', 'slug' => 'finanzen-investment'],
            ['name' => '🏠 Immobilien', 'slug' => 'immobilien'],
            ['name' => '🛒 E-Commerce & Dropshipping', 'slug' => 'ecommerce-dropshipping'],
            ['name' => '📈 Affiliate Marketing', 'slug' => 'affiliate-marketing'],
            ['name' => '📱 Social Media Marketing', 'slug' => 'social-media-marketing'],
            ['name' => '🤖 KI & Automation', 'slug' => 'ki-automation'],
            ['name' => '👔 Coaching & Consulting', 'slug' => 'coaching-consulting'],
            ['name' => '✨ Spiritualität & Mindfulness', 'slug' => 'spiritualitaet-mindfulness'],
            ['name' => '❤️ Beziehungen & Dating', 'slug' => 'beziehungen-dating'],
            ['name' => '👨‍👩‍👧 Eltern & Familie', 'slug' => 'eltern-familie'],
            ['name' => '🎯 Karriere & Beruf', 'slug' => 'karriere-beruf'],
            ['name' => '🎨 Hobbys & Freizeit', 'slug' => 'hobbys-freizeit'],
            ['name' => '📂 Sonstiges', 'slug' => 'sonstiges']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO freebie_template_categories (name, slug, created_at) 
            VALUES (?, ?, NOW())
        ");
        
        $inserted = 0;
        foreach ($defaultCategories as $cat) {
            try {
                $stmt->execute([$cat['name'], $cat['slug']]);
                $inserted++;
                echo "✅ " . htmlspecialchars($cat['name']) . "<br>";
            } catch (PDOException $e) {
                echo "⚠️ Fehler: " . htmlspecialchars($cat['name']) . " - " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<br>✅ $inserted Kategorien eingefügt!<br><br>";
        echo "<h3>🎉 Setup abgeschlossen!</h3>";
        echo "<p><a href='/customer/dashboard.php?page=freebies'>Zurück zu Freebies</a></p>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Fehler:</strong> " . $e->getMessage() . "<br><br>";
    echo "<p>Bitte stelle sicher, dass die Datenbank-Verbindung funktioniert.</p>";
}
?>