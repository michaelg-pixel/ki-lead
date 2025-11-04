<?php
/**
 * BROWSER-MIGRATION: Font-Felder für Customer Freebies
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/database/migrate-fonts-browser.php
 * 
 * WICHTIG: Nach erfolgreicher Migration diese Datei aus Sicherheitsgründen löschen!
 */

session_start();

// Sicherheitscheck: Nur für eingeloggte Admins
$isAdmin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';

// Wenn nicht eingeloggt, versuche Admin-Login
if (!$isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute(['admin@mehr-infos-jetzt.de']); // Passe die E-Mail an
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($_POST['admin_password'], $admin['password'] ?? '')) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            $isAdmin = true;
        } else {
            $loginError = 'Falsches Passwort';
        }
    } catch (Exception $e) {
        $loginError = 'Fehler beim Login: ' . $e->getMessage();
    }
}

// Migration ausführen
$migrationResult = null;
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $pdo = getDBConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $migrationResult = [
            'success' => true,
            'steps' => [],
            'stats' => []
        ];
        
        // Schritt 1: Prüfen ob Felder bereits existieren
        $migrationResult['steps'][] = ['title' => 'Schritt 1: Struktur prüfen', 'status' => 'running'];
        
        $tableInfo = $pdo->query("DESCRIBE customer_freebies")->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = array_column($tableInfo, 'Field');
        
        $needsHeadlineFont = !in_array('headline_font', $existingColumns);
        $needsPreheadlineFont = !in_array('preheadline_font', $existingColumns);
        
        if (!$needsHeadlineFont && !$needsPreheadlineFont) {
            $migrationResult['steps'][0] = [
                'title' => 'Schritt 1: Struktur prüfen', 
                'status' => 'success',
                'message' => 'Font-Felder existieren bereits ✓'
            ];
        } else {
            // Schritt 2: Font-Felder hinzufügen
            $migrationResult['steps'][0] = [
                'title' => 'Schritt 1: Struktur prüfen', 
                'status' => 'success',
                'message' => 'Felder fehlen - werden jetzt hinzugefügt'
            ];
            
            $migrationResult['steps'][] = ['title' => 'Schritt 2: Font-Felder hinzufügen', 'status' => 'running'];
            
            $alterSQL = "ALTER TABLE customer_freebies ";
            $alterParts = [];
            
            if ($needsPreheadlineFont) {
                $alterParts[] = "ADD COLUMN preheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER raw_code";
                $alterParts[] = "ADD COLUMN preheadline_size INT DEFAULT 14 AFTER preheadline_font";
            }
            
            if ($needsHeadlineFont) {
                $alterParts[] = "ADD COLUMN headline_font VARCHAR(100) DEFAULT 'Poppins' AFTER preheadline_size";
                $alterParts[] = "ADD COLUMN headline_size INT DEFAULT 48 AFTER headline_font";
                $alterParts[] = "ADD COLUMN subheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER headline_size";
                $alterParts[] = "ADD COLUMN subheadline_size INT DEFAULT 20 AFTER subheadline_font";
                $alterParts[] = "ADD COLUMN bulletpoints_font VARCHAR(100) DEFAULT 'Poppins' AFTER subheadline_size";
                $alterParts[] = "ADD COLUMN bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font";
            }
            
            if (!empty($alterParts)) {
                $alterSQL .= implode(", ", $alterParts);
                $pdo->exec($alterSQL);
                
                $migrationResult['steps'][1] = [
                    'title' => 'Schritt 2: Font-Felder hinzufügen', 
                    'status' => 'success',
                    'message' => count($alterParts) . ' Spalten erfolgreich hinzugefügt ✓'
                ];
            }
        }
        
        // Schritt 3: Bestehende Daten aktualisieren
        $migrationResult['steps'][] = ['title' => 'Schritt 3: Bestehende Daten aktualisieren', 'status' => 'running'];
        
        $updateSQL = "
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
        ";
        
        $stmt = $pdo->prepare($updateSQL);
        $stmt->execute();
        $updatedRows = $stmt->rowCount();
        
        $migrationResult['steps'][2] = [
            'title' => 'Schritt 3: Bestehende Daten aktualisieren', 
            'status' => 'success',
            'message' => $updatedRows . ' Customer Freebies mit Font-Einstellungen aktualisiert ✓'
        ];
        
        // Schritt 4: Statistiken sammeln
        $migrationResult['steps'][] = ['title' => 'Schritt 4: Statistiken sammeln', 'status' => 'running'];
        
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN headline_font IS NOT NULL THEN 1 ELSE 0 END) as with_fonts,
                COUNT(DISTINCT headline_font) as unique_fonts
            FROM customer_freebies
        ")->fetch(PDO::FETCH_ASSOC);
        
        $migrationResult['stats'] = [
            'total_freebies' => $stats['total'],
            'with_fonts' => $stats['with_fonts'],
            'unique_fonts' => $stats['unique_fonts']
        ];
        
        $migrationResult['steps'][3] = [
            'title' => 'Schritt 4: Statistiken sammeln', 
            'status' => 'success',
            'message' => 'Statistiken erfolgreich gesammelt ✓'
        ];
        
        // Schritt 5: Verifikation
        $migrationResult['steps'][] = ['title' => 'Schritt 5: Verifikation', 'status' => 'running'];
        
        $verification = $pdo->query("
            SELECT COUNT(*) as count
            FROM customer_freebies
            WHERE headline_font IS NULL OR headline_size IS NULL
        ")->fetch(PDO::FETCH_ASSOC);
        
        if ($verification['count'] > 0) {
            $migrationResult['steps'][4] = [
                'title' => 'Schritt 5: Verifikation', 
                'status' => 'warning',
                'message' => $verification['count'] . ' Freebies haben noch NULL-Werte (möglicherweise ohne Template)'
            ];
        } else {
            $migrationResult['steps'][4] = [
                'title' => 'Schritt 5: Verifikation', 
                'status' => 'success',
                'message' => 'Alle Freebies haben gültige Font-Einstellungen ✓'
            ];
        }
        
    } catch (PDOException $e) {
        $migrationResult['success'] = false;
        $migrationResult['error'] = 'Datenbankfehler: ' . $e->getMessage();
        $migrationResult['steps'][] = [
            'title' => 'Fehler',
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } catch (Exception $e) {
        $migrationResult['success'] = false;
        $migrationResult['error'] = 'Allgemeiner Fehler: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Font-Migration - Browser-Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin-bottom: 24px;
        }
        
        h1 {
            font-size: 32px;
            color: #1a202c;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .subtitle {
            color: #718096;
            margin-bottom: 32px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .warning {
            background: #fef3cd;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin-bottom: 24px;
            border-radius: 8px;
        }
        
        .warning-title {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .warning-text {
            color: #78350f;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin-bottom: 24px;
            border-radius: 8px;
        }
        
        .info-title {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .info-text {
            color: #1e3a8a;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .button {
            width: 100%;
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .button-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .steps {
            margin-top: 32px;
        }
        
        .step {
            background: #f9fafb;
            border-left: 4px solid #d1d5db;
            padding: 16px 20px;
            margin-bottom: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
        }
        
        .step.success {
            background: #d1fae5;
            border-color: #10b981;
        }
        
        .step.error {
            background: #fee2e2;
            border-color: #ef4444;
        }
        
        .step.warning {
            background: #fef3cd;
            border-color: #f59e0b;
        }
        
        .step.running {
            background: #dbeafe;
            border-color: #3b82f6;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .step-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 4px;
            font-size: 15px;
        }
        
        .step-message {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            color: white;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .success-message {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin-top: 24px;
        }
        
        .success-message h3 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .success-message p {
            opacity: 0.95;
            line-height: 1.6;
        }
        
        .error-message {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .actions {
            margin-top: 32px;
            display: flex;
            gap: 12px;
        }
        
        .button-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .button-secondary:hover {
            background: #f3f4f6;
        }
        
        .code-block {
            background: #1f2937;
            color: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 12px;
        }
        
        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$isAdmin): ?>
            <!-- Login-Formular -->
            <div class="card">
                <h1>🔐 Admin-Login erforderlich</h1>
                <p class="subtitle">Bitte melde dich als Admin an, um die Migration durchzuführen.</p>
                
                <?php if (isset($loginError)): ?>
                    <div class="error-message">
                        <strong>❌ Fehler:</strong> <?php echo htmlspecialchars($loginError); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label class="form-label">Admin-Passwort</label>
                        <input type="password" name="admin_password" class="form-input" 
                               placeholder="Dein Admin-Passwort" required autofocus>
                    </div>
                    <button type="submit" class="button">Einloggen</button>
                </form>
            </div>
            
        <?php elseif ($migrationResult === null): ?>
            <!-- Migrations-Start -->
            <div class="card">
                <h1>🎨 Font-Migration für Customer Freebies</h1>
                <p class="subtitle">
                    Diese Migration fügt Font-Felder zur customer_freebies Tabelle hinzu und 
                    aktualisiert bestehende Daten mit den Schrifteinstellungen aus den Templates.
                </p>
                
                <div class="warning">
                    <div class="warning-title">⚠️ Wichtiger Hinweis</div>
                    <div class="warning-text">
                        Diese Migration ändert die Datenbankstruktur. Es wird empfohlen, vorher ein Backup zu erstellen.
                        Nach erfolgreicher Migration sollte diese Datei aus Sicherheitsgründen gelöscht werden.
                    </div>
                </div>
                
                <div class="info">
                    <div class="info-title">📋 Was wird gemacht?</div>
                    <div class="info-text">
                        <ul style="margin-left: 20px; margin-top: 8px; line-height: 1.8;">
                            <li>Prüfung der Datenbankstruktur</li>
                            <li>Hinzufügen von 8 Font-Feldern (falls nicht vorhanden)</li>
                            <li>Aktualisierung bestehender Customer Freebies mit Template-Fonts</li>
                            <li>Statistiken und Verifikation</li>
                        </ul>
                    </div>
                </div>
                
                <form method="POST">
                    <button type="submit" name="run_migration" class="button">
                        🚀 Migration jetzt ausführen
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Migrations-Ergebnis -->
            <div class="card">
                <h1>
                    <?php if ($migrationResult['success']): ?>
                        ✅ Migration erfolgreich!
                    <?php else: ?>
                        ❌ Migration fehlgeschlagen
                    <?php endif; ?>
                </h1>
                <p class="subtitle">
                    <?php if ($migrationResult['success']): ?>
                        Die Datenbank wurde erfolgreich aktualisiert.
                    <?php else: ?>
                        Es ist ein Fehler aufgetreten.
                    <?php endif; ?>
                </p>
                
                <?php if (!$migrationResult['success'] && isset($migrationResult['error'])): ?>
                    <div class="error-message">
                        <strong>Fehler:</strong> <?php echo htmlspecialchars($migrationResult['error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="steps">
                    <?php foreach ($migrationResult['steps'] as $step): ?>
                        <div class="step <?php echo $step['status']; ?>">
                            <div class="step-icon">
                                <?php 
                                if ($step['status'] === 'success') echo '✅';
                                elseif ($step['status'] === 'error') echo '❌';
                                elseif ($step['status'] === 'warning') echo '⚠️';
                                elseif ($step['status'] === 'running') echo '⏳';
                                else echo '📋';
                                ?>
                            </div>
                            <div class="step-content">
                                <div class="step-title"><?php echo htmlspecialchars($step['title']); ?></div>
                                <?php if (isset($step['message'])): ?>
                                    <div class="step-message"><?php echo htmlspecialchars($step['message']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($migrationResult['success'] && !empty($migrationResult['stats'])): ?>
                    <div class="stats">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $migrationResult['stats']['total_freebies']; ?></div>
                            <div class="stat-label">Gesamt Freebies</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $migrationResult['stats']['with_fonts']; ?></div>
                            <div class="stat-label">Mit Font-Einstellungen</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $migrationResult['stats']['unique_fonts']; ?></div>
                            <div class="stat-label">Verschiedene Schriftarten</div>
                        </div>
                    </div>
                    
                    <div class="success-message">
                        <h3>🎉 Migration abgeschlossen!</h3>
                        <p>
                            Die Font-Einstellungen sind jetzt aktiv. Du kannst im Admin-Bereich 
                            Templates mit verschiedenen Schriftarten erstellen, die automatisch in 
                            Customer Freebies übernommen werden.
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="actions">
                    <a href="/admin/dashboard.php?page=freebies" class="button button-secondary" style="text-decoration: none; text-align: center;">
                        ← Zurück zum Admin Dashboard
                    </a>
                    <?php if ($migrationResult['success']): ?>
                        <a href="/admin/dashboard.php?page=freebie-edit&id=1" class="button" style="text-decoration: none; text-align: center;">
                            Template bearbeiten →
                        </a>
                    <?php else: ?>
                        <form method="POST" style="flex: 1;">
                            <button type="submit" name="run_migration" class="button button-danger">
                                🔄 Migration erneut versuchen
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <?php if ($migrationResult['success']): ?>
                    <div class="info" style="margin-top: 32px;">
                        <div class="info-title">🗑️ Wichtig: Cleanup</div>
                        <div class="info-text">
                            Aus Sicherheitsgründen solltest du diese Datei jetzt löschen:
                            <div class="code-block">rm /home/u163674869/domains/app.mehr-infos-jetzt.de/public_html/database/migrate-fonts-browser.php</div>
                            Oder über FTP: <code>/database/migrate-fonts-browser.php</code> löschen
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
