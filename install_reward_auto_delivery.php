<?php
/**
 * Installation: Reward Auto-Delivery System
 * 
 * Dieses Skript erstellt/erweitert:
 * 1. reward_deliveries Tabelle
 * 2. reward_definitions Spalten (falls noch nicht vorhanden)
 * 3. Prüft ob Auto-Delivery aktiv ist
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$errors = [];
$success = [];

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reward Auto-Delivery Installation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #8B5CF6;
            margin-bottom: 10px;
        }
        .success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            color: #991b1b;
        }
        .info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            color: #1e3a8a;
        }
        .step {
            margin: 20px 0;
        }
        .step-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🎁 Reward Auto-Delivery Installation</h1>
        <p style='color: #6b7280; margin-bottom: 30px;'>
            Installation und Konfiguration des automatischen Belohnungsauslieferungs-Systems
        </p>";

// =====================================================
// SCHRITT 1: reward_deliveries Tabelle erstellen
// =====================================================
echo "<div class='step'>
        <div class='step-title'>📦 Schritt 1: reward_deliveries Tabelle</div>";

try {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_deliveries'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        $sql = "CREATE TABLE reward_deliveries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            reward_id INT NOT NULL,
            user_id INT NOT NULL,
            
            reward_type VARCHAR(50),
            reward_title VARCHAR(255) NOT NULL,
            reward_value VARCHAR(100),
            
            delivery_url TEXT,
            access_code VARCHAR(255),
            delivery_instructions TEXT,
            
            delivered_at DATETIME NOT NULL,
            delivery_status ENUM('pending', 'delivered', 'failed') DEFAULT 'delivered',
            
            email_sent TINYINT(1) DEFAULT 0,
            email_sent_at DATETIME NULL,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_lead (lead_id),
            INDEX idx_reward (reward_id),
            INDEX idx_user (user_id),
            INDEX idx_delivered (delivered_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $success[] = "✓ Tabelle <code>reward_deliveries</code> erfolgreich erstellt";
        echo "<div class='success'>✓ Tabelle <code>reward_deliveries</code> erfolgreich erstellt</div>";
    } else {
        $success[] = "✓ Tabelle <code>reward_deliveries</code> existiert bereits";
        echo "<div class='info'>ℹ️ Tabelle <code>reward_deliveries</code> existiert bereits</div>";
    }
} catch (PDOException $e) {
    $errors[] = "Fehler bei reward_deliveries Tabelle: " . $e->getMessage();
    echo "<div class='error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// =====================================================
// SCHRITT 2: reward_definitions Spalten prüfen/hinzufügen
// =====================================================
echo "<div class='step'>
        <div class='step-title'>🔧 Schritt 2: reward_definitions Spalten</div>";

try {
    // Prüfen welche Spalten fehlen
    $stmt = $pdo->query("DESCRIBE reward_definitions");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = [
        'reward_download_url' => "ADD COLUMN reward_download_url TEXT NULL",
        'reward_access_code' => "ADD COLUMN reward_access_code VARCHAR(255) NULL",
        'reward_instructions' => "ADD COLUMN reward_instructions TEXT NULL",
        'auto_deliver' => "ADD COLUMN auto_deliver TINYINT(1) DEFAULT 0"
    ];
    
    $added_columns = [];
    foreach ($required_columns as $column => $alter_sql) {
        if (!in_array($column, $existing_columns)) {
            $pdo->exec("ALTER TABLE reward_definitions $alter_sql");
            $added_columns[] = $column;
        }
    }
    
    if (!empty($added_columns)) {
        $success[] = "✓ Spalten hinzugefügt: " . implode(', ', $added_columns);
        echo "<div class='success'>✓ Spalten hinzugefügt: <code>" . implode('</code>, <code>', $added_columns) . "</code></div>";
    } else {
        $success[] = "✓ Alle Spalten existieren bereits";
        echo "<div class='info'>ℹ️ Alle erforderlichen Spalten existieren bereits</div>";
    }
    
} catch (PDOException $e) {
    $errors[] = "Fehler bei reward_definitions Spalten: " . $e->getMessage();
    echo "<div class='error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// =====================================================
// SCHRITT 3: users Tabelle - Autoresponder Spalten
// =====================================================
echo "<div class='step'>
        <div class='step-title'>📧 Schritt 3: Autoresponder API-Spalten in users</div>";

try {
    $stmt = $pdo->query("DESCRIBE users");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = [
        'autoresponder_webhook_url' => "ADD COLUMN autoresponder_webhook_url TEXT NULL",
        'autoresponder_api_key' => "ADD COLUMN autoresponder_api_key VARCHAR(255) NULL",
        'autoresponder_provider' => "ADD COLUMN autoresponder_provider VARCHAR(50) NULL"
    ];
    
    $added_columns = [];
    foreach ($required_columns as $column => $alter_sql) {
        if (!in_array($column, $existing_columns)) {
            $pdo->exec("ALTER TABLE users $alter_sql");
            $added_columns[] = $column;
        }
    }
    
    if (!empty($added_columns)) {
        $success[] = "✓ Autoresponder-Spalten hinzugefügt: " . implode(', ', $added_columns);
        echo "<div class='success'>✓ Autoresponder-Spalten hinzugefügt: <code>" . implode('</code>, <code>', $added_columns) . "</code></div>";
    } else {
        $success[] = "✓ Autoresponder-Spalten existieren bereits";
        echo "<div class='info'>ℹ️ Autoresponder-Spalten existieren bereits</div>";
    }
    
} catch (PDOException $e) {
    $errors[] = "Fehler bei users Autoresponder-Spalten: " . $e->getMessage();
    echo "<div class='error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// =====================================================
// ZUSAMMENFASSUNG
// =====================================================
echo "<div class='step'>
        <div class='step-title'>📊 Installations-Zusammenfassung</div>";

if (count($errors) === 0) {
    echo "<div class='success'>
            <strong>🎉 Installation erfolgreich abgeschlossen!</strong><br><br>
            <strong>Das Auto-Delivery System ist jetzt einsatzbereit:</strong>
            <ul style='margin: 15px 0;'>
                <li>✓ <code>reward_deliveries</code> Tabelle bereit</li>
                <li>✓ <code>reward_definitions</code> erweitert</li>
                <li>✓ <code>users</code> Autoresponder-Integration bereit</li>
            </ul>
          </div>";
    
    echo "<div class='info'>
            <strong>📋 Nächste Schritte:</strong>
            <ol style='margin: 15px 0; padding-left: 20px;'>
                <li>Gehe zu <strong>Empfehlungsprogramm → Belohnungsstufen</strong></li>
                <li>Erstelle oder bearbeite eine Belohnung</li>
                <li>Fülle die Felder aus:
                    <ul>
                        <li>Download-URL (optional)</li>
                        <li>Zugriffscode (optional)</li>
                        <li>Einlöse-Anweisungen (optional)</li>
                    </ul>
                </li>
                <li>Aktiviere <strong>\"Auto-Zusendung\"</strong> Checkbox</li>
                <li>Speichern!</li>
            </ol>
            
            <strong style='display: block; margin-top: 15px;'>🚀 Workflow:</strong>
            <div style='margin: 10px 0; padding: 10px; background: white; border-radius: 6px;'>
                Lead erreicht X Empfehlungen<br>
                ↓<br>
                System prüft automatisch Belohnungsstufen<br>
                ↓<br>
                Belohnung wird in <code>reward_deliveries</code> gespeichert<br>
                ↓<br>
                Email wird automatisch versendet (wenn <code>auto_deliver</code> aktiv)<br>
                ↓<br>
                Lead sieht Belohnung im Dashboard unter \"Meine Belohnungen\"
            </div>
          </div>";
          
    echo "<div class='info'>
            <strong>🎯 Lead-Dashboard Features:</strong>
            <ul style='margin: 10px 0;'>
                <li>Neue Sektion: <strong>\"Meine Belohnungen\"</strong></li>
                <li>Download-Buttons für URLs</li>
                <li>Kopierbare Zugriffscodes</li>
                <li>Formatierte Einlöse-Anweisungen</li>
                <li>\"NEU\" Badge für Belohnungen &lt; 24h alt</li>
            </ul>
          </div>";
          
} else {
    echo "<div class='error'>
            <strong>⚠️ Installation mit Fehlern abgeschlossen</strong><br><br>
            Bitte prüfe folgende Fehler:
            <ul style='margin: 10px 0;'>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>
          </div>";
}

echo "</div>";

// =====================================================
// SYSTEM-CHECK
// =====================================================
echo "<div class='step'>
        <div class='step-title'>🔍 System-Check</div>";

try {
    // Prüfe lead_dashboard.php
    $dashboard_file = __DIR__ . '/lead_dashboard.php';
    if (file_exists($dashboard_file)) {
        $content = file_get_contents($dashboard_file);
        $has_delivery = strpos($content, 'deliverReward') !== false;
        $has_email = strpos($content, 'sendRewardDeliveryEmail') !== false;
        $has_section = strpos($content, 'Meine Belohnungen') !== false;
        
        if ($has_delivery && $has_email && $has_section) {
            echo "<div class='success'>✓ <code>lead_dashboard.php</code> - Auto-Delivery Code vorhanden</div>";
        } else {
            echo "<div class='error'>⚠️ <code>lead_dashboard.php</code> - Code unvollständig
                    <ul style='margin: 10px 0;'>
                        <li>deliverReward(): " . ($has_delivery ? '✓' : '❌') . "</li>
                        <li>sendRewardDeliveryEmail(): " . ($has_email ? '✓' : '❌') . "</li>
                        <li>Meine Belohnungen Sektion: " . ($has_section ? '✓' : '❌') . "</li>
                    </ul>
                  </div>";
        }
    } else {
        echo "<div class='error'>❌ <code>lead_dashboard.php</code> nicht gefunden</div>";
    }
    
    // Prüfe belohnungsstufen.php
    $belohnungen_file = __DIR__ . '/customer/sections/belohnungsstufen.php';
    if (file_exists($belohnungen_file)) {
        $content = file_get_contents($belohnungen_file);
        $has_fields = strpos($content, 'reward_download_url') !== false &&
                      strpos($content, 'reward_access_code') !== false &&
                      strpos($content, 'auto_deliver') !== false;
        
        if ($has_fields) {
            echo "<div class='success'>✓ <code>belohnungsstufen.php</code> - Alle Felder vorhanden</div>";
        } else {
            echo "<div class='error'>⚠️ <code>belohnungsstufen.php</code> - Felder fehlen</div>";
        }
    } else {
        echo "<div class='error'>❌ <code>belohnungsstufen.php</code> nicht gefunden</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Fehler beim System-Check: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div style='margin-top: 30px; padding-top: 30px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280;'>
        <strong>KI Leadsystem - Reward Auto-Delivery v1.0</strong><br>
        Installation abgeschlossen am " . date('d.m.Y H:i') . " Uhr
      </div>";

echo "</div>
</body>
</html>";
?>