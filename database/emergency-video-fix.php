<?php
/**
 * NOTFALL-DIAGNOSE & REPARATUR
 * Prüft und repariert die Video-Support Integration
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$results = [
    'db_video_url' => false,
    'db_video_format' => false,
    'file_patched' => false,
    'errors' => []
];

try {
    $pdo = getDBConnection();
    
    // Prüfe Datenbank-Spalten
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'video_url'");
    $results['db_video_url'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'video_format'");
    $results['db_video_format'] = $stmt->rowCount() > 0;
    
    // Prüfe ob freebie/index.php gepatcht wurde
    $freebieIndexPath = __DIR__ . '/../freebie/index.php';
    if (file_exists($freebieIndexPath)) {
        $content = file_get_contents($freebieIndexPath);
        $results['file_patched'] = strpos($content, 'getVideoEmbedUrl') !== false;
    }
    
} catch (PDOException $e) {
    $results['errors'][] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Notfall-Diagnose & Reparatur</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            margin-bottom: 16px;
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
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid rgba(245, 158, 11, 0.3);
            color: #b45309;
        }
        .info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.3);
            color: #1e40af;
        }
        .diagnostic-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 20px 0;
        }
        .diagnostic-item {
            padding: 16px;
            border-radius: 8px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
        }
        .diagnostic-item.ok {
            border-color: #10B981;
            background: rgba(16, 185, 129, 0.05);
        }
        .diagnostic-item.fail {
            border-color: #EF4444;
            background: rgba(239, 68, 68, 0.05);
        }
        .diagnostic-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .diagnostic-value {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .diagnostic-value.ok { color: #047857; }
        .diagnostic-value.fail { color: #dc2626; }
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
            margin-top: 10px;
            margin-right: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-danger {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        }
        .btn-success {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }
        code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .solution-box {
            background: #fff7ed;
            border: 2px solid #fb923c;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .solution-title {
            font-size: 18px;
            font-weight: 700;
            color: #9a3412;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Notfall-Diagnose</h1>
        <p class="subtitle">Überprüfe den Status der Video-Integration</p>
        
        <div class="diagnostic-grid">
            <div class="diagnostic-item <?php echo $results['db_video_url'] ? 'ok' : 'fail'; ?>">
                <div class="diagnostic-label">Datenbank: video_url</div>
                <div class="diagnostic-value <?php echo $results['db_video_url'] ? 'ok' : 'fail'; ?>">
                    <?php echo $results['db_video_url'] ? '✓ Existiert' : '✗ Fehlt'; ?>
                </div>
            </div>
            
            <div class="diagnostic-item <?php echo $results['db_video_format'] ? 'ok' : 'fail'; ?>">
                <div class="diagnostic-label">Datenbank: video_format</div>
                <div class="diagnostic-value <?php echo $results['db_video_format'] ? 'ok' : 'fail'; ?>">
                    <?php echo $results['db_video_format'] ? '✓ Existiert' : '✗ Fehlt'; ?>
                </div>
            </div>
            
            <div class="diagnostic-item <?php echo $results['file_patched'] ? 'ok' : 'fail'; ?>">
                <div class="diagnostic-label">freebie/index.php</div>
                <div class="diagnostic-value <?php echo $results['file_patched'] ? 'ok' : 'fail'; ?>">
                    <?php echo $results['file_patched'] ? '✓ Gepatcht' : '✗ Nicht gepatcht'; ?>
                </div>
            </div>
            
            <div class="diagnostic-item <?php echo empty($results['errors']) ? 'ok' : 'fail'; ?>">
                <div class="diagnostic-label">Datenbank-Fehler</div>
                <div class="diagnostic-value <?php echo empty($results['errors']) ? 'ok' : 'fail'; ?>">
                    <?php echo empty($results['errors']) ? '✓ Keine' : '✗ ' . count($results['errors']); ?>
                </div>
            </div>
        </div>
        
        <?php
        // PROBLEM IDENTIFIZIERT
        $dbOk = $results['db_video_url'] && $results['db_video_format'];
        $fileOk = $results['file_patched'];
        
        if (!$dbOk && $fileOk) {
            // KRITISCH: Datei gepatcht, aber DB nicht migriert
            echo '<div class="status-box error">';
            echo '<div class="status-icon">🚨</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">KRITISCHES PROBLEM GEFUNDEN</div>';
            echo '<div class="status-message">';
            echo '<strong>freebie/index.php wurde gepatcht, aber die Datenbank-Spalten fehlen!</strong><br>';
            echo 'Das ist der Grund für den Fehler "Datenbankfehler beim Laden des Freebies".<br><br>';
            echo 'Die Datei versucht <code>video_url</code> und <code>video_format</code> abzurufen, aber diese Spalten existieren nicht.';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="solution-box">';
            echo '<div class="solution-title">💡 Lösung</div>';
            echo '<strong>Option 1: Datenbank-Spalten hinzufügen (Empfohlen)</strong><br>';
            echo 'Führe die SQL-Commands manuell aus:<br><br>';
            echo '<code style="display:block;padding:10px;margin:10px 0;white-space:pre-wrap;background:#1a1a2e;color:#10B981;border-radius:6px;">';
            echo 'ALTER TABLE customer_freebies ADD COLUMN video_url VARCHAR(500) NULL AFTER mockup_image_url;' . "\n";
            echo 'ALTER TABLE customer_freebies ADD COLUMN video_format ENUM(\'portrait\', \'widescreen\') DEFAULT \'widescreen\' AFTER video_url;';
            echo '</code>';
            echo '<br><strong>Option 2: Patch rückgängig machen</strong><br>';
            echo 'Stelle die Original-Datei wieder her (Backup sollte existieren)';
            echo '</div>';
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_database'])) {
                echo '<div class="status-box info">';
                echo '<div class="status-icon">⚙️</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Reparatur wird durchgeführt...</div>';
                echo '</div>';
                echo '</div>';
                
                try {
                    $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) NULL AFTER mockup_image_url");
                    $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN IF NOT EXISTS video_format ENUM('portrait', 'widescreen') DEFAULT 'widescreen' AFTER video_url");
                    
                    echo '<div class="status-box success">';
                    echo '<div class="status-icon">✅</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Reparatur erfolgreich!</div>';
                    echo '<div class="status-message">Die Datenbank-Spalten wurden hinzugefügt. Teste jetzt deinen Freebie-Link erneut!</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<a href="' . htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/freebie/') . '" class="btn btn-success">Freebie-Link erneut testen</a>';
                } catch (PDOException $e) {
                    echo '<div class="status-box error">';
                    echo '<div class="status-icon">❌</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Reparatur fehlgeschlagen</div>';
                    echo '<div class="status-message">' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<form method="POST">';
                echo '<button type="submit" name="fix_database" class="btn btn-danger">🔧 Jetzt automatisch reparieren</button>';
                echo '</form>';
            }
            
        } elseif ($dbOk && !$fileOk) {
            // DB ok, aber Datei nicht gepatcht
            echo '<div class="status-box warning">';
            echo '<div class="status-icon">⚠️</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Unvollständige Integration</div>';
            echo '<div class="status-message">';
            echo 'Die Datenbank ist bereit, aber <code>freebie/index.php</code> wurde noch nicht gepatcht.<br>';
            echo 'Videos werden im Editor angezeigt, aber nicht auf öffentlichen Links.';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/database/integrate-video-support.php" class="btn">Zur Video-Integration →</a>';
            
        } elseif (!$dbOk && !$fileOk) {
            // Nichts ist gemacht
            echo '<div class="status-box info">';
            echo '<div class="status-icon">ℹ️</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Video-Support noch nicht installiert</div>';
            echo '<div class="status-message">';
            echo 'Weder die Datenbank noch die Dateien wurden für Video-Support vorbereitet.';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/database/migrate-video-support.php" class="btn">1. Datenbank-Migration →</a>';
            echo '<a href="/database/integrate-video-support.php" class="btn">2. Video-Integration →</a>';
            
        } else {
            // Alles ok!
            echo '<div class="status-box success">';
            echo '<div class="status-icon">✅</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Video-Support vollständig installiert!</div>';
            echo '<div class="status-message">';
            echo 'Datenbank und Dateien sind korrekt konfiguriert.<br>';
            echo 'Videos sollten jetzt im Editor UND auf öffentlichen Links funktionieren.';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/customer/custom-freebie-editor.php" class="btn btn-success">Zum Freebie Editor →</a>';
        }
        
        if (!empty($results['errors'])) {
            echo '<div class="status-box error">';
            echo '<div class="status-icon">❌</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Datenbank-Fehler</div>';
            echo '<div class="status-message">';
            foreach ($results['errors'] as $error) {
                echo htmlspecialchars($error) . '<br>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
