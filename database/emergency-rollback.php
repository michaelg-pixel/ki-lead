<?php
/**
 * NOTFALL-ROLLBACK - Stellt funktionierende freebie/index.php wieder her
 * Entfernt Video-Support komplett, damit öffentliche Links wieder funktionieren
 */

header('Content-Type: text/html; charset=utf-8');

$targetFile = __DIR__ . '/../freebie/index.php';
$backupPattern = __DIR__ . '/../freebie/index.php.backup_*';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 Notfall-Rollback</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
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
            color: #DC2626;
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
        .status-icon { font-size: 24px; flex-shrink: 0; }
        .status-content { flex: 1; }
        .status-title { font-weight: 600; margin-bottom: 4px; }
        .status-message { font-size: 14px; line-height: 1.5; }
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
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
        .btn:hover { transform: translateY(-2px); }
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Notfall-Rollback</h1>
        <p class="subtitle">Stelle die funktionierende Version wieder her</p>
        
        <div class="status-box error">
            <div class="status-icon">🚨</div>
            <div class="status-content">
                <div class="status-title">Aktuelles Problem</div>
                <div class="status-message">
                    Öffentliche Freebie-Links zeigen "Datenbankfehler".<br>
                    <strong>Ursache:</strong> freebie/index.php wurde für Video-Support gepatcht, 
                    aber die Datenbank-Integration ist fehlgeschlagen.
                </div>
            </div>
        </div>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_rollback'])) {
            echo '<div class="status-box warning">';
            echo '<div class="status-icon">⚙️</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Rollback wird durchgeführt...</div>';
            echo '</div></div>';
            
            // Suche nach Backup-Dateien
            $backups = glob($backupPattern);
            
            if (!empty($backups)) {
                // Nimm das neueste Backup
                rsort($backups);
                $latestBackup = $backups[0];
                
                if (copy($latestBackup, $targetFile)) {
                    echo '<div class="status-box success">';
                    echo '<div class="status-icon">✅</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Rollback erfolgreich!</div>';
                    echo '<div class="status-message">';
                    echo 'Die Original-Version wurde wiederhergestellt.<br>';
                    echo '<strong>Wiederhergestellt von:</strong> <code>' . basename($latestBackup) . '</code><br><br>';
                    echo '✓ Öffentliche Freebie-Links funktionieren wieder<br>';
                    echo '✓ Bestehende Freebies sind unberührt<br>';
                    echo '✓ Video-Support wurde entfernt (kann später neu installiert werden)';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="status-box warning">';
                    echo '<div class="status-icon">ℹ️</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Was jetzt?</div>';
                    echo '<div class="status-message">';
                    echo '1. Teste deine Freebie-Links - sie sollten wieder funktionieren<br>';
                    echo '2. Der Video-Support ist NICHT mehr aktiv<br>';
                    echo '3. Freebies OHNE Video funktionieren normal<br>';
                    echo '4. Wir können Video-Support später korrekt neu installieren';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<a href="/freebie/index.php?id=961dabe119ef58fee387d484b6f6fd38" class="btn btn-success">Freebie-Link testen</a>';
                } else {
                    echo '<div class="status-box error">';
                    echo '<div class="status-icon">❌</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Rollback fehlgeschlagen</div>';
                    echo '<div class="status-message">Konnte Backup nicht wiederherstellen. Bitte Schreibrechte prüfen.</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="status-box error">';
                echo '<div class="status-icon">❌</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Kein Backup gefunden</div>';
                echo '<div class="status-message">';
                echo 'Es wurde kein Backup von freebie/index.php gefunden.<br><br>';
                echo '<strong>Manuelle Lösung:</strong><br>';
                echo '1. Gehe zu GitHub: <code>freebie/index.php</code><br>';
                echo '2. Suche in der History nach der Version VOR dem Video-Patch<br>';
                echo '3. Kopiere den Inhalt und überschreibe die aktuelle Datei';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            
        } else {
            // Zeige verfügbare Backups
            $backups = glob($backupPattern);
            
            if (!empty($backups)) {
                rsort($backups);
                
                echo '<div class="status-box warning">';
                echo '<div class="status-icon">💾</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Backup gefunden</div>';
                echo '<div class="status-message">';
                echo 'Es wurde ein Backup gefunden:<br>';
                echo '<code>' . basename($backups[0]) . '</code><br><br>';
                echo 'Dieses Backup wird wiederhergestellt, wenn du auf den Button klickst.';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="status-box warning">';
                echo '<div class="status-icon">⚠️</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Was wird gemacht?</div>';
                echo '<div class="status-message">';
                echo '✓ Die Original-Version von <code>freebie/index.php</code> wird wiederhergestellt<br>';
                echo '✓ Video-Support wird entfernt<br>';
                echo '✓ Öffentliche Links funktionieren wieder<br>';
                echo '✓ Keine Datenbank-Änderungen (Spalten bleiben bestehen)<br>';
                echo '✗ Videos werden NICHT mehr angezeigt (kann später neu installiert werden)';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<form method="POST">';
                echo '<button type="submit" name="execute_rollback" class="btn">🔄 Jetzt Rollback durchführen</button>';
                echo '</form>';
            } else {
                echo '<div class="status-box error">';
                echo '<div class="status-icon">❌</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Kein Backup verfügbar</div>';
                echo '<div class="status-message">';
                echo 'Es wurde kein automatisches Backup gefunden.<br><br>';
                echo '<strong>Alternative Lösung:</strong><br>';
                echo 'Ich erstelle jetzt ein Script, das die Original-Version von GitHub holt...';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                // GitHub-Rollback Option
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['github_rollback'])) {
                    echo '<div class="status-box warning">';
                    echo '<div class="status-icon">⚙️</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">GitHub-Rollback wird durchgeführt...</div>';
                    echo '</div></div>';
                    
                    // Hole die Original-Version vor dem Video-Patch
                    $githubUrl = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/00ad64859516801e4ff0662a0a486e32a696d01f/freebie/index.php';
                    
                    $originalContent = @file_get_contents($githubUrl);
                    
                    if ($originalContent !== false) {
                        // Erstelle Backup der aktuellen (kaputten) Version
                        $brokenBackup = $targetFile . '.broken_' . date('Y-m-d_H-i-s');
                        copy($targetFile, $brokenBackup);
                        
                        // Schreibe Original-Version
                        if (file_put_contents($targetFile, $originalContent)) {
                            echo '<div class="status-box success">';
                            echo '<div class="status-icon">✅</div>';
                            echo '<div class="status-content">';
                            echo '<div class="status-title">GitHub-Rollback erfolgreich!</div>';
                            echo '<div class="status-message">';
                            echo 'Die funktionierende Original-Version wurde von GitHub geholt.<br><br>';
                            echo '✓ Öffentliche Freebie-Links funktionieren wieder<br>';
                            echo '✓ Kaputte Version wurde als Backup gespeichert<br>';
                            echo '✓ Video-Support ist entfernt';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            
                            echo '<a href="/freebie/index.php?id=961dabe119ef58fee387d484b6f6fd38" class="btn btn-success">Freebie-Link testen</a>';
                        } else {
                            echo '<div class="status-box error">';
                            echo '<div class="status-icon">❌</div>';
                            echo '<div class="status-content">';
                            echo '<div class="status-title">Schreibfehler</div>';
                            echo '<div class="status-message">Konnte Datei nicht schreiben. Bitte Schreibrechte prüfen.</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="status-box error">';
                        echo '<div class="status-icon">❌</div>';
                        echo '<div class="status-content">';
                        echo '<div class="status-title">GitHub-Fehler</div>';
                        echo '<div class="status-message">Konnte Original-Version nicht von GitHub laden.</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<form method="POST">';
                    echo '<button type="submit" name="github_rollback" class="btn">🔄 Original von GitHub holen</button>';
                    echo '</form>';
                }
            }
        }
        ?>
    </div>
</body>
</html>
