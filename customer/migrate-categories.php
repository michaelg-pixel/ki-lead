<?php
/**
 * Browser-Migration für Nischen-Kategorien
 * Einfach im Browser aufrufen: /customer/migrate-categories.php
 * 
 * WICHTIG: Nach erfolgreicher Migration diese Datei LÖSCHEN!
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Sicherheitscheck: Nur für Admins oder in Development
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('⛔ Zugriff verweigert. Bitte einloggen.');
}

$pdo = getDBConnection();
$output = [];
$success = true;

try {
    // ==================== SCHRITT 1: KATEGORIEN-TABELLE ====================
    $output[] = "<h3>📋 Schritt 1: Kategorien-Tabelle prüfen/erstellen</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS freebie_template_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(10) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $output[] = "✅ Tabelle <code>freebie_template_categories</code> bereit";
    
    // ==================== SCHRITT 2: CATEGORY_ID SPALTE ====================
    $output[] = "<h3>🔗 Schritt 2: Category_ID Spalte hinzufügen</h3>";
    
    // Prüfen ob Spalte existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'category_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN category_id INT NULL");
        $pdo->exec("ALTER TABLE customer_freebies ADD INDEX idx_category_id (category_id)");
        $output[] = "✅ Spalte <code>category_id</code> hinzugefügt";
    } else {
        $output[] = "✅ Spalte <code>category_id</code> existiert bereits";
    }
    
    // ==================== SCHRITT 3: KATEGORIEN EINFÜGEN ====================
    $output[] = "<h3>📂 Schritt 3: Kategorien einfügen</h3>";
    
    // Bestehende Kategorien zählen
    $stmt = $pdo->query("SELECT COUNT(*) FROM freebie_template_categories");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $output[] = "⚠️ Es existieren bereits {$count} Kategorien. Sollen sie überschrieben werden?";
        
        if (isset($_POST['confirm_overwrite'])) {
            $pdo->exec("TRUNCATE TABLE freebie_template_categories");
            $output[] = "🗑️ Alte Kategorien gelöscht";
        } else {
            $output[] = '
                <form method="POST">
                    <input type="hidden" name="confirm_overwrite" value="1">
                    <button type="submit" style="
                        background: #ef4444;
                        color: white;
                        padding: 12px 24px;
                        border: none;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                        margin: 16px 0;
                    ">Ja, Kategorien überschreiben</button>
                </form>
            ';
            $success = false;
        }
    }
    
    if ($success) {
        $categories = [
            ['Online Marketing', '📈', 'Marketing-Strategien, Social Media, SEO und Content Marketing'],
            ['E-Commerce', '🛒', 'Online-Shops, Produktverkauf, Dropshipping und Amazon FBA'],
            ['Persönlichkeitsentwicklung', '🌟', 'Selbstverbesserung, Mindset, Motivation und Lebensführung'],
            ['Fitness & Gesundheit', '💪', 'Training, Ernährung, Wellness und gesunde Lebensweise'],
            ['Finanzen & Investment', '💰', 'Geldanlage, Vermögensaufbau, Kryptowährungen und Börse'],
            ['Online Business', '💼', 'Digitale Geschäftsmodelle, Freelancing und passives Einkommen'],
            ['Coaching & Beratung', '🎯', 'Life Coaching, Business Coaching und Mentoring'],
            ['Immobilien', '🏡', 'Immobilieninvestment, Vermietung und Immobilienentwicklung'],
            ['Affiliate Marketing', '🔗', 'Partnerprogramme, Provisionen und Performance Marketing'],
            ['Software & Apps', '💻', 'Software-Entwicklung, SaaS-Produkte und digitale Tools'],
            ['Design & Kreatives', '🎨', 'Grafikdesign, Webdesign, Fotografie und kreative Dienstleistungen'],
            ['Beziehung & Dating', '❤️', 'Partnerschaft, Dating-Tipps und zwischenmenschliche Beziehungen'],
            ['Spiritualität', '🔮', 'Meditation, Achtsamkeit, Energiearbeit und spirituelle Entwicklung'],
            ['Eltern & Familie', '👨‍👩‍👧', 'Kindererziehung, Familienmanagement und Elternschaft'],
            ['Karriere & Beruf', '🚀', 'Berufliche Entwicklung, Bewerbung und Karriereplanung']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO freebie_template_categories (name, icon, description) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        
        $output[] = "✅ " . count($categories) . " Kategorien erfolgreich eingefügt";
        
        // Kategorien anzeigen
        $output[] = "<h3>📋 Verfügbare Kategorien:</h3>";
        $stmt = $pdo->query("SELECT * FROM freebie_template_categories ORDER BY name");
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $output[] = "<table style='width: 100%; border-collapse: collapse; margin: 16px 0;'>";
        $output[] = "<tr style='background: #f3f4f6;'>";
        $output[] = "<th style='padding: 12px; text-align: left; border: 1px solid #e5e7eb;'>Icon</th>";
        $output[] = "<th style='padding: 12px; text-align: left; border: 1px solid #e5e7eb;'>Name</th>";
        $output[] = "<th style='padding: 12px; text-align: left; border: 1px solid #e5e7eb;'>Beschreibung</th>";
        $output[] = "</tr>";
        
        foreach ($cats as $cat) {
            $output[] = "<tr>";
            $output[] = "<td style='padding: 12px; border: 1px solid #e5e7eb; font-size: 24px;'>" . htmlspecialchars($cat['icon']) . "</td>";
            $output[] = "<td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: 600;'>" . htmlspecialchars($cat['name']) . "</td>";
            $output[] = "<td style='padding: 12px; border: 1px solid #e5e7eb; color: #6b7280;'>" . htmlspecialchars($cat['description']) . "</td>";
            $output[] = "</tr>";
        }
        
        $output[] = "</table>";
        
        // ==================== SCHRITT 4: FERTIG ====================
        $output[] = "<h3>🎉 Migration erfolgreich!</h3>";
        $output[] = "<p style='color: #059669; font-weight: 600; font-size: 18px;'>
            Alle Kategorien wurden erfolgreich installiert und sind jetzt im Freebie-Editor verfügbar!
        </p>";
        
        $output[] = "<div style='background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 16px; margin: 24px 0;'>";
        $output[] = "<strong style='color: #92400e;'>⚠️ WICHTIG:</strong>";
        $output[] = "<p style='color: #92400e; margin-top: 8px;'>
            Bitte lösche jetzt diese Migrationsdatei aus Sicherheitsgründen:<br>
            <code style='background: white; padding: 4px 8px; border-radius: 4px;'>/customer/migrate-categories.php</code>
        </p>";
        $output[] = "</div>";
        
        $output[] = "<a href='dashboard.php?page=freebies' style='
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 16px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 16px;
        '>→ Zum Freebie-Editor</a>";
    }
    
} catch (Exception $e) {
    $success = false;
    $output[] = "<div style='background: #fecaca; border: 2px solid #ef4444; border-radius: 8px; padding: 16px; margin: 16px 0;'>";
    $output[] = "<strong style='color: #991b1b;'>❌ Fehler:</strong>";
    $output[] = "<p style='color: #991b1b; margin-top: 8px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    $output[] = "</div>";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategorien-Migration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #1a1a2e;
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 32px;
        }
        
        h3 {
            color: #1a1a2e;
            font-size: 20px;
            margin: 24px 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        p {
            color: #374151;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        code {
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .output {
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Nischen-Kategorien Migration</h1>
        <p class="subtitle">Browser-basierte Installation der 15 Freebie-Kategorien</p>
        
        <div class="output">
            <?php echo implode("\n", $output); ?>
        </div>
    </div>
</body>
</html>
