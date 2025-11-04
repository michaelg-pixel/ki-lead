<?php
/**
 * EINFACHES BROWSER-MIGRATIONS-TOOL
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/database/run-font-migration.php
 * 
 * Setze ein Passwort für die Ausführung:
 */
define('MIGRATION_PASSWORD', 'change-me-123'); // ⚠️ ÄNDERE DIESES PASSWORT!

$isAuthorized = false;
$passwordError = null;

// Passwort-Check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === MIGRATION_PASSWORD) {
        $isAuthorized = true;
    } else {
        $passwordError = 'Falsches Passwort!';
    }
}

// Migration ausführen
$result = null;
if ($isAuthorized && isset($_POST['execute'])) {
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $pdo = getDBConnection();
        $result = ['success' => true, 'steps' => []];
        
        // Schritt 1: Struktur prüfen
        $columns = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '%font%'")->fetchAll();
        $hasColumns = count($columns) > 0;
        
        if ($hasColumns) {
            $result['steps'][] = [
                'icon' => '✅',
                'title' => 'Struktur prüfen',
                'message' => 'Font-Felder existieren bereits'
            ];
        } else {
            // Schritt 2: Felder hinzufügen
            $pdo->exec("
                ALTER TABLE customer_freebies 
                ADD COLUMN preheadline_font VARCHAR(100) DEFAULT 'Poppins',
                ADD COLUMN preheadline_size INT DEFAULT 14,
                ADD COLUMN headline_font VARCHAR(100) DEFAULT 'Poppins',
                ADD COLUMN headline_size INT DEFAULT 48,
                ADD COLUMN subheadline_font VARCHAR(100) DEFAULT 'Poppins',
                ADD COLUMN subheadline_size INT DEFAULT 20,
                ADD COLUMN bulletpoints_font VARCHAR(100) DEFAULT 'Poppins',
                ADD COLUMN bulletpoints_size INT DEFAULT 16
            ");
            
            $result['steps'][] = [
                'icon' => '✅',
                'title' => 'Font-Felder hinzufügen',
                'message' => '8 Spalten erfolgreich hinzugefügt'
            ];
        }
        
        // Schritt 3: Daten aktualisieren
        $stmt = $pdo->prepare("
            UPDATE customer_freebies cf
            INNER JOIN freebies f ON cf.template_id = f.id
            SET 
                cf.preheadline_font = COALESCE(f.preheadline_font, 'Poppins'),
                cf.preheadline_size = COALESCE(f.preheadline_size, 14),
                cf.headline_font = COALESCE(f.headline_font, 'Poppins'),
                cf.headline_size = COALESCE(f.headline_size, 48),
                cf.subheadline_font = COALESCE(f.subheadline_font, 'Poppins'),
                cf.subheadline_size = COALESCE(f.subheadline_size, 20),
                cf.bulletpoints_font = COALESCE(f.bulletpoints_font, 'Poppins'),
                cf.bulletpoints_size = COALESCE(f.bulletpoints_size, 16)
            WHERE cf.template_id IS NOT NULL
        ");
        $stmt->execute();
        
        $result['steps'][] = [
            'icon' => '✅',
            'title' => 'Daten aktualisieren',
            'message' => $stmt->rowCount() . ' Freebies aktualisiert'
        ];
        
        // Statistik
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN headline_font IS NOT NULL THEN 1 ELSE 0 END) as with_fonts
            FROM customer_freebies
        ")->fetch();
        
        $result['stats'] = $stats;
        
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Font-Migration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            font-size: 28px; 
            margin-bottom: 8px;
            color: #1a202c;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .warning {
            background: #fef3cd;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            color: #78350f;
            font-size: 14px;
            line-height: 1.6;
        }
        .error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            color: #991b1b;
        }
        .step {
            background: #f9fafb;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .step-icon {
            font-size: 24px;
        }
        .success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin-top: 24px;
        }
        .success h2 {
            margin-bottom: 8px;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 24px;
        }
        .stat {
            background: rgba(255,255,255,0.2);
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 800;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        .info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!$isAuthorized): ?>
            <h1>🔐 Font-Migration</h1>
            <p class="subtitle">Gib das Passwort ein, um die Migration zu starten.</p>
            
            <?php if ($passwordError): ?>
                <div class="error"><?php echo htmlspecialchars($passwordError); ?></div>
            <?php endif; ?>
            
            <div class="warning">
                <strong>⚠️ Wichtig:</strong> Stelle sicher, dass du ein Backup der Datenbank hast, bevor du fortfährst.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Passwort:</label>
                    <input type="password" name="password" required autofocus>
                </div>
                <button type="submit">Einloggen</button>
            </form>
            
            <div class="info">
                💡 Das Passwort ist in Zeile 9 dieser Datei definiert:<br>
                <code>define('MIGRATION_PASSWORD', 'dein-passwort');</code>
            </div>
            
        <?php elseif ($result === null): ?>
            <h1>🚀 Bereit zur Migration</h1>
            <p class="subtitle">Klicke auf den Button, um die Font-Felder zur customer_freebies Tabelle hinzuzufügen.</p>
            
            <div class="warning">
                <strong>Was wird gemacht:</strong><br>
                • Font-Felder werden hinzugefügt (falls nicht vorhanden)<br>
                • Bestehende Freebies werden mit Template-Fonts aktualisiert<br>
                • Statistiken werden gesammelt
            </div>
            
            <form method="POST">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                <button type="submit" name="execute" value="1">Migration ausführen</button>
            </form>
            
        <?php elseif ($result['success']): ?>
            <h1>✅ Migration erfolgreich!</h1>
            
            <?php foreach ($result['steps'] as $step): ?>
                <div class="step">
                    <div class="step-icon"><?php echo $step['icon']; ?></div>
                    <div>
                        <strong><?php echo htmlspecialchars($step['title']); ?></strong><br>
                        <small><?php echo htmlspecialchars($step['message']); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (isset($result['stats'])): ?>
                <div class="success">
                    <h2>🎉 Fertig!</h2>
                    <p>Die Font-Einstellungen sind jetzt aktiv.</p>
                    <div class="stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo $result['stats']['total']; ?></div>
                            <div class="stat-label">Gesamt Freebies</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $result['stats']['with_fonts']; ?></div>
                            <div class="stat-label">Mit Fonts</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="info">
                <strong>🗑️ Wichtig:</strong> Lösche diese Datei jetzt aus Sicherheitsgründen:<br>
                <code>/database/run-font-migration.php</code>
            </div>
            
        <?php else: ?>
            <h1>❌ Fehler</h1>
            <div class="error">
                <strong>Fehler bei der Migration:</strong><br>
                <?php echo htmlspecialchars($result['error']); ?>
            </div>
            <form method="POST">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                <button type="submit">← Zurück</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
