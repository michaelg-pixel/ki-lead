<?php
/**
 * Quick Activation Script
 * Aktiviert die neue thankyou.php automatisch
 */

// Sicherheitscheck - lösche diese Datei nach Verwendung!
$activation_key = isset($_GET['key']) ? $_GET['key'] : '';
$correct_key = 'activate-new-dashboard-2025'; // Ändere dies für Sicherheit

if ($activation_key !== $correct_key) {
    die('⛔ Ungültiger Aktivierungsschlüssel! Verwende: ?key=' . $correct_key);
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivierung - Neue Danke-Seite</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 12px;
            color: #1a1a1a;
        }
        
        .status {
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            font-weight: 600;
        }
        
        .status.info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        
        .status.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .status.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .status.warning {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #f59e0b;
        }
        
        .button {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-size: 16px;
            transition: transform 0.2s;
            width: 100%;
            margin-top: 16px;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .button.secondary {
            background: #6b7280;
        }
        
        .code {
            background: #1a1a1a;
            color: #10b981;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 12px 0;
            overflow-x: auto;
        }
        
        .step {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin: 12px 0;
            border-left: 4px solid #667eea;
        }
        
        .step h3 {
            margin-bottom: 8px;
            color: #667eea;
        }
        
        ul {
            margin-left: 20px;
            margin-top: 12px;
        }
        
        li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Aktivierung: Neue Danke-Seite</h1>
        
        <?php
        $freebie_dir = __DIR__ . '/freebie';
        $old_file = $freebie_dir . '/thankyou.php';
        $new_file = $freebie_dir . '/thankyou-new.php';
        $backup_file = $freebie_dir . '/thankyou-old-backup.php';
        
        // Prüfe ob Dateien existieren
        $old_exists = file_exists($old_file);
        $new_exists = file_exists($new_file);
        $backup_exists = file_exists($backup_file);
        
        if (!$new_exists) {
            echo '<div class="status error">';
            echo '<strong>❌ Fehler:</strong> Die neue Datei <code>thankyou-new.php</code> wurde nicht gefunden!<br><br>';
            echo 'Bitte stelle sicher, dass die Datei existiert:<br>';
            echo '<code>' . $new_file . '</code>';
            echo '</div>';
            
            echo '<div class="step">';
            echo '<h3>Lösung via FTP/SSH:</h3>';
            echo '<ol>';
            echo '<li>Lade <code>thankyou-new.php</code> in den <code>/freebie/</code> Ordner hoch</li>';
            echo '<li>Führe dieses Script erneut aus</li>';
            echo '</ol>';
            echo '</div>';
            
            exit;
        }
        
        // Wenn noch nicht aktiviert
        if (isset($_GET['activate']) && $_GET['activate'] === 'yes') {
            echo '<div class="status info">';
            echo '<strong>⏳ Aktivierung läuft...</strong>';
            echo '</div>';
            
            $errors = [];
            $success = true;
            
            // Schritt 1: Alte Datei sichern
            if ($old_exists && !$backup_exists) {
                if (!rename($old_file, $backup_file)) {
                    $errors[] = 'Konnte alte Datei nicht sichern (Schreibrechte prüfen!)';
                    $success = false;
                } else {
                    echo '<div class="status success">';
                    echo '✅ Schritt 1: Alte thankyou.php gesichert als thankyou-old-backup.php';
                    echo '</div>';
                }
            } elseif ($backup_exists) {
                echo '<div class="status info">';
                echo '✅ Schritt 1: Backup existiert bereits';
                echo '</div>';
            }
            
            // Schritt 2: Neue Datei aktivieren
            if ($success && file_exists($new_file)) {
                if (!rename($new_file, $old_file)) {
                    $errors[] = 'Konnte neue Datei nicht aktivieren (Schreibrechte prüfen!)';
                    $success = false;
                } else {
                    echo '<div class="status success">';
                    echo '✅ Schritt 2: Neue thankyou.php aktiviert!';
                    echo '</div>';
                }
            }
            
            if ($success) {
                echo '<div class="status success">';
                echo '<strong>🎉 Aktivierung erfolgreich!</strong><br><br>';
                echo 'Die neue Danke-Seite ist jetzt aktiv.<br>';
                echo 'Du kannst jetzt testen: <a href="/freebie/thankyou.php?id=7&customer=4" target="_blank">Danke-Seite öffnen</a>';
                echo '</div>';
                
                echo '<div class="status warning">';
                echo '<strong>⚠️ Wichtig:</strong> Lösche dieses Aktivierungs-Script aus Sicherheitsgründen:<br>';
                echo '<code>freebie/activate-new-thankyou.php</code>';
                echo '</div>';
                
                echo '<a href="/freebie/thankyou.php?id=7&customer=4" class="button">🎯 Danke-Seite testen</a>';
                
            } else {
                echo '<div class="status error">';
                echo '<strong>❌ Fehler bei der Aktivierung:</strong><br><br>';
                foreach ($errors as $error) {
                    echo '• ' . $error . '<br>';
                }
                echo '</div>';
                
                echo '<div class="step">';
                echo '<h3>Manuelle Aktivierung via FTP/SSH:</h3>';
                echo '<div class="code">';
                echo 'cd freebie/<br>';
                echo 'mv thankyou.php thankyou-old-backup.php<br>';
                echo 'mv thankyou-new.php thankyou.php';
                echo '</div>';
                echo '</div>';
            }
            
        } else {
            // Zeige Status und Aktivierungs-Button
            echo '<div class="status info">';
            echo '<strong>Status-Check:</strong>';
            echo '</div>';
            
            echo '<ul>';
            echo '<li>' . ($old_exists ? '✅' : '❌') . ' thankyou.php (alte Version) existiert</li>';
            echo '<li>' . ($new_exists ? '✅' : '❌') . ' thankyou-new.php (neue Version) existiert</li>';
            echo '<li>' . ($backup_exists ? '✅' : '⏳') . ' thankyou-old-backup.php (Backup)</li>';
            echo '</ul>';
            
            if ($old_exists && $new_exists) {
                echo '<div class="status warning">';
                echo '<strong>⚠️ Bereit zur Aktivierung</strong><br><br>';
                echo 'Die alte thankyou.php wird gesichert und die neue aktiviert.';
                echo '</div>';
                
                echo '<div class="step">';
                echo '<h3>Was passiert beim Aktivieren?</h3>';
                echo '<ol>';
                echo '<li>Alte <code>thankyou.php</code> wird umbenannt zu <code>thankyou-old-backup.php</code></li>';
                echo '<li>Neue <code>thankyou-new.php</code> wird umbenannt zu <code>thankyou.php</code></li>';
                echo '<li>Das neue Dashboard mit One-Click-Login ist aktiv</li>';
                echo '</ol>';
                echo '</div>';
                
                echo '<form method="get">';
                echo '<input type="hidden" name="key" value="' . htmlspecialchars($activation_key) . '">';
                echo '<input type="hidden" name="activate" value="yes">';
                echo '<button type="submit" class="button">🚀 Jetzt aktivieren</button>';
                echo '</form>';
                
            } else {
                echo '<div class="status error">';
                echo '<strong>❌ Dateien fehlen!</strong><br><br>';
                echo 'Stelle sicher, dass thankyou-new.php existiert.';
                echo '</div>';
            }
        }
        ?>
        
        <div class="step" style="margin-top: 24px;">
            <h3>🔧 Alternative: Manuelle Aktivierung via FTP/SSH</h3>
            <p>Falls das automatische Script nicht funktioniert:</p>
            <div class="code">
cd freebie/<br>
mv thankyou.php thankyou-old-backup.php<br>
mv thankyou-new.php thankyou.php
            </div>
        </div>
    </div>
</body>
</html>