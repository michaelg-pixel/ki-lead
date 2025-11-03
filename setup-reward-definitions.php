<?php
/**
 * Setup: Reward Definitions System installieren
 * 
 * Dieses Skript erstellt die reward_definitions Tabelle
 * und lädt optional Beispieldaten.
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
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { color: #333; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { color: #3b82f6; }
        pre { background: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .step { margin-bottom: 15px; padding: 10px; border-left: 3px solid #667eea; }
    </style>
</head>
<body>
    <h1>🏆 Reward Definitions System Setup</h1>";

try {
    $pdo = getDBConnection();
    echo "<div class='box'><p class='success'>✓ Datenbankverbindung erfolgreich</p></div>";
    
    // Migration-Datei laden und ausführen
    echo "<div class='box'>";
    echo "<h2>Schritt 1: Datenbank-Struktur erstellen</h2>";
    
    $migration_file = __DIR__ . '/database/migrations/006_reward_definitions.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration-Datei nicht gefunden: $migration_file");
    }
    
    $sql = file_get_contents($migration_file);
    
    // SQL in einzelne Statements aufteilen
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $success_count = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $success_count++;
        } catch (PDOException $e) {
            // Ignoriere "table already exists" Fehler
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    if (empty($errors)) {
        echo "<p class='success'>✓ $success_count SQL-Statements erfolgreich ausgeführt</p>";
        echo "<p class='info'>Die Tabelle <code>reward_definitions</code> wurde erstellt.</p>";
    } else {
        echo "<p class='error'>⚠ Einige Fehler aufgetreten:</p>";
        echo "<pre>" . implode("\n", $errors) . "</pre>";
    }
    
    echo "</div>";
    
    // Tabellen-Struktur anzeigen
    echo "<div class='box'>";
    echo "<h2>Schritt 2: Tabellen-Struktur prüfen</h2>";
    
    $tables_to_check = ['reward_definitions', 'referral_reward_tiers', 'referral_claimed_rewards'];
    
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ Tabelle <code>$table</code> existiert</p>";
            
            // Spalten anzeigen
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<details><summary style='cursor: pointer; color: #667eea;'>Spalten anzeigen (" . count($columns) . ")</summary>";
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
    
    // Beispieldaten erstellen (optional)
    echo "<div class='box'>";
    echo "<h2>Schritt 3: Beispieldaten (optional)</h2>";
    
    $create_examples = isset($_GET['examples']) && $_GET['examples'] === 'yes';
    
    if ($create_examples) {
        // Ersten User finden
        $stmt = $pdo->query("SELECT id FROM users ORDER BY id LIMIT 1");
        $user_id = $stmt->fetchColumn();
        
        if ($user_id) {
            echo "<p class='info'>Erstelle Beispiel-Belohnungen für User ID: $user_id</p>";
            
            $examples = [
                [
                    'tier_level' => 1,
                    'tier_name' => 'Bronze',
                    'tier_description' => 'Erste Stufe für 3 Empfehlungen',
                    'required_referrals' => 3,
                    'reward_type' => 'ebook',
                    'reward_title' => 'Starter E-Book',
                    'reward_description' => 'Unser kostenloses Einsteiger-E-Book',
                    'reward_value' => 'Wert: 29€',
                    'reward_icon' => 'fa-book',
                    'reward_color' => '#cd7f32'
                ],
                [
                    'tier_level' => 2,
                    'tier_name' => 'Silber',
                    'tier_description' => 'Zweite Stufe für 5 Empfehlungen',
                    'required_referrals' => 5,
                    'reward_type' => 'consultation',
                    'reward_title' => '30 Min. Gratis-Beratung',
                    'reward_description' => 'Persönliche 1:1 Beratungssession',
                    'reward_value' => 'Wert: 99€',
                    'reward_icon' => 'fa-comments',
                    'reward_color' => '#c0c0c0'
                ],
                [
                    'tier_level' => 3,
                    'tier_name' => 'Gold',
                    'tier_description' => 'Top-Stufe für 10 Empfehlungen',
                    'required_referrals' => 10,
                    'reward_type' => 'course',
                    'reward_title' => 'Premium-Kurs Zugang',
                    'reward_description' => 'Vollzugriff auf unseren Flaggschiff-Kurs',
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
                            tier_description = VALUES(tier_description)
                    ");
                    
                    $stmt->execute([
                        $user_id,
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
                    
                    echo "<p class='success'>✓ {$example['tier_name']}-Stufe erstellt</p>";
                } catch (PDOException $e) {
                    echo "<p class='error'>✗ Fehler bei {$example['tier_name']}: {$e->getMessage()}</p>";
                }
            }
        } else {
            echo "<p class='error'>✗ Kein User gefunden. Bitte zuerst einen User erstellen.</p>";
        }
    } else {
        echo "<p class='info'>Möchtest du Beispieldaten erstellen?</p>";
        echo "<p><a href='?examples=yes' style='display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ja, Beispiele erstellen</a></p>";
    }
    
    echo "</div>";
    
    // Zusammenfassung
    echo "<div class='box'>";
    echo "<h2>✅ Setup abgeschlossen!</h2>";
    echo "<p>Das Reward Definitions System wurde erfolgreich installiert.</p>";
    echo "<h3>Nächste Schritte:</h3>";
    echo "<ol>";
    echo "<li>Gehe zum <strong>Customer Dashboard</strong></li>";
    echo "<li>Navigiere zu <strong>Belohnungsstufen</strong></li>";
    echo "<li>Erstelle deine ersten Belohnungen</li>";
    echo "</ol>";
    echo "<p><a href='/customer/dashboard.php?section=belohnungsstufen' style='display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>→ Zu den Belohnungsstufen</a></p>";
    echo "</div>";
    
    // Cleanup-Info
    echo "<div class='box' style='background: #fef3c7; border-left: 3px solid #f59e0b;'>";
    echo "<h3>⚠️ Sicherheitshinweis</h3>";
    echo "<p>Nach erfolgreichem Setup solltest du diese Datei aus Sicherheitsgründen löschen:</p>";
    echo "<pre>rm " . __FILE__ . "</pre>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>✗ Fehler: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
