<?php
/**
 * Setup: Reward Definitions System installieren - VERBESSERT
 * 
 * Dieses Skript erstellt die reward_definitions Tabelle direkt
 * ohne externe SQL-Datei zu parsen.
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/setup-reward-definitions.php
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>Reward Definitions Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { color: #333; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { color: #3b82f6; }
        pre { background: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .step { margin-bottom: 15px; padding: 10px; border-left: 3px solid #667eea; }
        .btn { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }
        .btn-success { background: #10b981; }
    </style>
</head>
<body>
    <h1>🏆 Reward Definitions System Setup</h1>";

try {
    $pdo = getDBConnection();
    echo "<div class='box'><p class='success'>✓ Datenbankverbindung erfolgreich</p></div>";
    
    // Schritt 1: Tabelle erstellen
    echo "<div class='box'>";
    echo "<h2>Schritt 1: Reward Definitions Tabelle erstellen</h2>";
    
    $create_table_sql = "CREATE TABLE IF NOT EXISTS reward_definitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL COMMENT 'Welcher User diese Belohnungen definiert hat',
        
        -- Stufen-Info
        tier_level INT NOT NULL COMMENT 'Stufe: 1, 2, 3, etc.',
        tier_name VARCHAR(100) NOT NULL COMMENT 'z.B. Bronze, Silber, Gold',
        tier_description TEXT DEFAULT NULL,
        
        -- Erforderliche Empfehlungen
        required_referrals INT NOT NULL COMMENT 'Anzahl benötigter erfolgreicher Empfehlungen',
        
        -- Belohnung
        reward_type VARCHAR(50) NOT NULL COMMENT 'ebook, pdf, consultation, course, voucher, etc.',
        reward_title VARCHAR(255) NOT NULL,
        reward_description TEXT DEFAULT NULL,
        reward_value VARCHAR(100) DEFAULT NULL COMMENT 'z.B. 50€, 1h Beratung',
        
        -- Zugriff/Lieferung
        reward_download_url TEXT DEFAULT NULL,
        reward_access_code VARCHAR(100) DEFAULT NULL,
        reward_instructions TEXT DEFAULT NULL COMMENT 'Wie die Belohnung eingelöst wird',
        
        -- Visuals
        reward_icon VARCHAR(100) DEFAULT 'fa-gift' COMMENT 'Font Awesome Icon',
        reward_color VARCHAR(20) DEFAULT '#667eea',
        reward_badge_image VARCHAR(255) DEFAULT NULL,
        
        -- Status
        is_active BOOLEAN DEFAULT TRUE,
        is_featured BOOLEAN DEFAULT FALSE,
        auto_deliver BOOLEAN DEFAULT FALSE COMMENT 'Automatisch zusenden',
        
        -- Email-Benachrichtigung
        notification_subject VARCHAR(255) DEFAULT NULL,
        notification_body TEXT DEFAULT NULL,
        
        -- Sortierung
        sort_order INT DEFAULT 0,
        
        -- Zeitstempel
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Indizes
        INDEX idx_user (user_id),
        INDEX idx_tier_level (tier_level),
        INDEX idx_active (is_active),
        INDEX idx_sort (sort_order),
        
        UNIQUE KEY unique_user_tier (user_id, tier_level),
        
        CONSTRAINT fk_reward_def_user 
            FOREIGN KEY (user_id) 
            REFERENCES users(id) 
            ON DELETE CASCADE
            
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurierbare Belohnungsstufen'";
    
    try {
        $pdo->exec($create_table_sql);
        echo "<p class='success'>✓ Tabelle 'reward_definitions' erfolgreich erstellt</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='info'>ℹ Tabelle 'reward_definitions' existiert bereits</p>";
        } else {
            throw $e;
        }
    }
    
    echo "</div>";
    
    // Schritt 2: View erstellen (optional, kann fehlschlagen wenn VIEW Rechte fehlen)
    echo "<div class='box'>";
    echo "<h2>Schritt 2: Statistik-View erstellen (optional)</h2>";
    
    $create_view_sql = "CREATE OR REPLACE VIEW view_reward_definitions_stats AS
    SELECT 
        rd.id,
        rd.user_id,
        rd.tier_level,
        rd.tier_name,
        rd.required_referrals,
        rd.reward_title,
        rd.is_active,
        COUNT(DISTINCT rrt.lead_id) as leads_achieved,
        COUNT(DISTINCT rcr.id) as times_claimed
    FROM reward_definitions rd
    LEFT JOIN referral_reward_tiers rrt ON rd.tier_level = rrt.tier_id AND rd.user_id = rrt.lead_id
    LEFT JOIN referral_claimed_rewards rcr ON rd.id = rcr.reward_id
    GROUP BY rd.id";
    
    try {
        $pdo->exec($create_view_sql);
        echo "<p class='success'>✓ View 'view_reward_definitions_stats' erstellt</p>";
    } catch (PDOException $e) {
        echo "<p class='info'>ℹ View konnte nicht erstellt werden (nicht kritisch): " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Schritt 3: Tabellen-Struktur prüfen
    echo "<div class='box'>";
    echo "<h2>Schritt 3: Tabellen-Struktur prüfen</h2>";
    
    $tables_to_check = ['reward_definitions', 'referral_reward_tiers', 'referral_claimed_rewards'];
    
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ Tabelle <code>$table</code> existiert</p>";
            
            // Spalten anzeigen
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<details><summary style='cursor: pointer; color: #667eea;'>→ Spalten anzeigen (" . count($columns) . ")</summary>";
            echo "<pre>";
            foreach ($columns as $col) {
                echo sprintf("%-30s %-20s %s\n", $col['Field'], $col['Type'], $col['Key'] ? "[$col[Key]]" : '');
            }
            echo "</pre></details>";
        } else {
            echo "<p class='error'>✗ Tabelle <code>$table</code> fehlt!</p>";
        }
    }
    
    echo "</div>";
    
    // Schritt 4: Beispieldaten erstellen (optional)
    echo "<div class='box'>";
    echo "<h2>Schritt 4: Beispieldaten erstellen</h2>";
    
    $create_examples = isset($_GET['examples']) && $_GET['examples'] === 'yes';
    
    if ($create_examples) {
        // Ersten User finden
        $stmt = $pdo->query("SELECT id, name FROM users ORDER BY id LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p class='info'>Erstelle Beispiel-Belohnungen für User: <strong>" . htmlspecialchars($user['name']) . "</strong> (ID: {$user['id']})</p>";
            
            $examples = [
                [
                    'tier_level' => 1,
                    'tier_name' => 'Bronze',
                    'tier_description' => 'Erste Stufe für 3 erfolgreiche Empfehlungen',
                    'required_referrals' => 3,
                    'reward_type' => 'ebook',
                    'reward_title' => 'Starter E-Book',
                    'reward_description' => 'Unser kostenloses Einsteiger-E-Book mit wertvollen Tipps und Tricks',
                    'reward_value' => 'Wert: 29€',
                    'reward_icon' => 'fa-book',
                    'reward_color' => '#cd7f32'
                ],
                [
                    'tier_level' => 2,
                    'tier_name' => 'Silber',
                    'tier_description' => 'Zweite Stufe für 5 erfolgreiche Empfehlungen',
                    'required_referrals' => 5,
                    'reward_type' => 'consultation',
                    'reward_title' => '30 Min. Gratis-Beratung',
                    'reward_description' => 'Persönliche 1:1 Beratungssession mit unserem Team',
                    'reward_value' => 'Wert: 99€',
                    'reward_icon' => 'fa-comments',
                    'reward_color' => '#c0c0c0'
                ],
                [
                    'tier_level' => 3,
                    'tier_name' => 'Gold',
                    'tier_description' => 'Top-Stufe für 10 erfolgreiche Empfehlungen',
                    'required_referrals' => 10,
                    'reward_type' => 'course',
                    'reward_title' => 'Premium-Kurs Zugang',
                    'reward_description' => 'Vollzugriff auf unseren exklusiven Flaggschiff-Kurs',
                    'reward_value' => 'Wert: 299€',
                    'reward_icon' => 'fa-crown',
                    'reward_color' => '#ffd700'
                ]
            ];
            
            foreach ($examples as $example) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO reward_definitions (
                            user_id, tier_level, tier_name, tier_description,
                            required_referrals, reward_type, reward_title, 
                            reward_description, reward_value, reward_icon, reward_color
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            tier_name = VALUES(tier_name),
                            tier_description = VALUES(tier_description),
                            reward_title = VALUES(reward_title),
                            reward_description = VALUES(reward_description)
                    ");
                    
                    $stmt->execute([
                        $user['id'],
                        $example['tier_level'],
                        $example['tier_name'],
                        $example['tier_description'],
                        $example['required_referrals'],
                        $example['reward_type'],
                        $example['reward_title'],
                        $example['reward_description'],
                        $example['reward_value'],
                        $example['reward_icon'],
                        $example['reward_color']
                    ]);
                    
                    echo "<p class='success'>✓ {$example['tier_name']}-Stufe erstellt ({$example['required_referrals']} Empfehlungen)</p>";
                } catch (PDOException $e) {
                    echo "<p class='error'>✗ Fehler bei {$example['tier_name']}: {$e->getMessage()}</p>";
                }
            }
            
            echo "<p class='success'><strong>✓ Beispieldaten erfolgreich erstellt!</strong></p>";
        } else {
            echo "<p class='error'>✗ Kein User gefunden. Bitte zuerst einen User erstellen.</p>";
        }
    } else {
        echo "<p class='info'>Möchtest du 3 Beispiel-Belohnungsstufen erstellen?</p>";
        echo "<ul style='color: #6b7280;'>";
        echo "<li><strong>Bronze:</strong> 3 Empfehlungen → Starter E-Book</li>";
        echo "<li><strong>Silber:</strong> 5 Empfehlungen → 30 Min. Beratung</li>";
        echo "<li><strong>Gold:</strong> 10 Empfehlungen → Premium-Kurs</li>";
        echo "</ul>";
        echo "<p><a href='?examples=yes' class='btn'>Ja, Beispiele erstellen</a></p>";
    }
    
    echo "</div>";
    
    // Zusammenfassung
    echo "<div class='box' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>";
    echo "<h2 style='color: white;'>✅ Setup abgeschlossen!</h2>";
    echo "<p>Das Reward Definitions System wurde erfolgreich installiert.</p>";
    echo "<h3 style='color: white; margin-top: 20px;'>Nächste Schritte:</h3>";
    echo "<ol style='line-height: 2;'>";
    echo "<li>Gehe zum <strong>Customer Dashboard</strong></li>";
    echo "<li>Navigiere zu <strong>Belohnungsstufen</strong></li>";
    echo "<li>Erstelle oder bearbeite deine Belohnungen</li>";
    echo "<li>Teste das Empfehlungsprogramm</li>";
    echo "</ol>";
    echo "<p><a href='/customer/dashboard.php?page=belohnungsstufen' class='btn btn-success'>→ Zu den Belohnungsstufen</a></p>";
    echo "</div>";
    
    // Cleanup-Info
    echo "<div class='box' style='background: #fef3c7; border-left: 3px solid #f59e0b;'>";
    echo "<h3>⚠️ Sicherheitshinweis</h3>";
    echo "<p>Nach erfolgreichem Setup solltest du diese Datei aus Sicherheitsgründen löschen:</p>";
    echo "<pre>rm " . basename(__FILE__) . "</pre>";
    echo "<p style='font-size: 12px; color: #6b7280;'>Oder über FTP/SSH vom Server entfernen.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='background: #fee; border-left: 3px solid #ef4444;'>";
    echo "<p class='error'>✗ FEHLER: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary style='cursor: pointer; color: #ef4444; margin-top: 10px;'>Stack Trace anzeigen</summary>";
    echo "<pre style='font-size: 11px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</details>";
    echo "</div>";
}

echo "</body></html>";
