<?php
/**
 * Browser-basierte Migration für Video-Support
 * Datum: 2025-11-04
 * 
 * Dieses Script fügt Video-URL und Video-Format Felder zur customer_freebies Tabelle hinzu.
 * Einfach im Browser aufrufen: https://app.mehr-infos-jetzt.de/database/migrate-video-support.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video-Support Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #1a1a2e;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .status-box {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .status-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .status-content {
            flex: 1;
        }
        
        .status-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .status-message {
            font-size: 14px;
            line-height: 1.5;
        }
        
        .success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            color: #047857;
        }
        
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        .info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.3);
            color: #1e40af;
        }
        
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid rgba(245, 158, 11, 0.3);
            color: #b45309;
        }
        
        .migration-steps {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        
        .step:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
            padding-top: 2px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Video-Support Migration</h1>
        <p class="subtitle">Migration für Video-Funktionen im Custom Freebie Editor</p>
        
        <?php
        try {
            $pdo = getDBConnection();
            
            // Prüfen ob die Spalten bereits existieren
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'video_url'");
            $videoUrlExists = $stmt->rowCount() > 0;
            
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'video_format'");
            $videoFormatExists = $stmt->rowCount() > 0;
            
            if ($videoUrlExists && $videoFormatExists) {
                echo '<div class="status-box warning">';
                echo '<div class="status-icon">⚠️</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Migration bereits durchgeführt</div>';
                echo '<div class="status-message">Die Video-Felder existieren bereits in der Datenbank. Eine erneute Migration ist nicht erforderlich.</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="status-box info">';
                echo '<div class="status-icon">ℹ️</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Status</div>';
                echo '<div class="status-message">';
                echo '<strong>✓</strong> video_url Spalte existiert<br>';
                echo '<strong>✓</strong> video_format Spalte existiert';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            } else {
                // Migration durchführen
                $pdo->beginTransaction();
                
                $migrations = [];
                
                if (!$videoUrlExists) {
                    $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN video_url VARCHAR(500) NULL AFTER mockup_image_url");
                    $migrations[] = "video_url Spalte hinzugefügt";
                }
                
                if (!$videoFormatExists) {
                    $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN video_format ENUM('portrait', 'widescreen') DEFAULT 'widescreen' AFTER video_url");
                    $migrations[] = "video_format Spalte hinzugefügt";
                }
                
                // Index erstellen (falls noch nicht vorhanden)
                try {
                    $pdo->exec("CREATE INDEX idx_customer_freebies_video ON customer_freebies(video_url)");
                    $migrations[] = "Index für Video-URLs erstellt";
                } catch (PDOException $e) {
                    // Index existiert bereits, ignorieren
                }
                
                $pdo->commit();
                
                echo '<div class="status-box success">';
                echo '<div class="status-icon">✅</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Migration erfolgreich abgeschlossen!</div>';
                echo '<div class="status-message">';
                echo 'Folgende Änderungen wurden vorgenommen:<br>';
                foreach ($migrations as $migration) {
                    echo '<strong>✓</strong> ' . htmlspecialchars($migration) . '<br>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            // Informationen zur Nutzung anzeigen
            echo '<div class="status-box info">';
            echo '<div class="status-icon">💡</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Video-Support aktiviert</div>';
            echo '<div class="status-message">';
            echo 'Du kannst jetzt im Custom Freebie Editor Video-Links hinterlegen:<br>';
            echo '<strong>1.</strong> Öffne den Freebie Editor<br>';
            echo '<strong>2.</strong> Füge eine YouTube oder Vimeo URL ein<br>';
            echo '<strong>3.</strong> Wähle das Format: Widescreen (16:9) oder Hochformat (9:16)<br>';
            echo '<strong>4.</strong> Das Video wird automatisch eingebettet';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="status-box error">';
            echo '<div class="status-icon">❌</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Fehler bei der Migration</div>';
            echo '<div class="status-message">' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div class="migration-steps">
            <strong style="display: block; margin-bottom: 16px; color: #1a1a2e;">Was wurde geändert?</strong>
            
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <strong>video_url Spalte</strong><br>
                    Speichert die URL von YouTube, Vimeo oder anderen Video-Plattformen (max. 500 Zeichen)
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <strong>video_format Spalte</strong><br>
                    Legt das Anzeigeformat fest: <code>widescreen</code> (16:9) oder <code>portrait</code> (9:16)
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <strong>Performance-Index</strong><br>
                    Index auf <code>video_url</code> für schnellere Abfragen bei Video-Freebies
                </div>
            </div>
        </div>
        
        <a href="/customer/dashboard.php?page=freebies" class="btn">
            Zum Freebie Editor →
        </a>
    </div>
</body>
</html>
