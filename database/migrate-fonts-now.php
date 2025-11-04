<?php
/**
 * DIREKTE FONT-MIGRATION (OHNE PASSWORT)
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/database/migrate-fonts-now.php
 * 
 * ⚠️ WICHTIG: Diese Datei führt die Migration SOFORT aus beim Aufruf!
 * ⚠️ NACH der Migration: DATEI SOFORT LÖSCHEN!
 */

$result = null;
$executeNow = isset($_GET['execute']) || $_SERVER['REQUEST_METHOD'] === 'POST';

if ($executeNow) {
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $pdo = getDBConnection();
        $result = ['success' => true, 'steps' => []];
        
        // Schritt 1: Struktur prüfen
        $result['steps'][] = ['icon' => '⏳', 'title' => 'Schritt 1: Struktur prüfen', 'status' => 'running'];
        
        $columns = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '%font%'")->fetchAll();
        $hasColumns = count($columns) > 0;
        
        if ($hasColumns) {
            $result['steps'][0] = [
                'icon' => '✅',
                'title' => 'Schritt 1: Struktur prüfen',
                'message' => 'Font-Felder existieren bereits',
                'status' => 'success'
            ];
        } else {
            $result['steps'][0] = [
                'icon' => '✅',
                'title' => 'Schritt 1: Struktur prüfen',
                'message' => 'Felder fehlen - werden hinzugefügt',
                'status' => 'success'
            ];
            
            // Schritt 2: Felder hinzufügen
            $result['steps'][] = ['icon' => '⏳', 'title' => 'Schritt 2: Font-Felder hinzufügen', 'status' => 'running'];
            
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
            
            $result['steps'][1] = [
                'icon' => '✅',
                'title' => 'Schritt 2: Font-Felder hinzufügen',
                'message' => '8 Spalten erfolgreich hinzugefügt',
                'status' => 'success'
            ];
        }
        
        // Schritt 3: Daten aktualisieren
        $stepIndex = count($result['steps']);
        $result['steps'][] = ['icon' => '⏳', 'title' => 'Schritt 3: Daten aktualisieren', 'status' => 'running'];
        
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
        $updatedRows = $stmt->rowCount();
        
        $result['steps'][$stepIndex] = [
            'icon' => '✅',
            'title' => 'Schritt 3: Daten aktualisieren',
            'message' => $updatedRows . ' Freebies mit Font-Einstellungen aktualisiert',
            'status' => 'success'
        ];
        
        // Schritt 4: Statistiken sammeln
        $result['steps'][] = ['icon' => '⏳', 'title' => 'Schritt 4: Statistiken sammeln', 'status' => 'running'];
        
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN headline_font IS NOT NULL THEN 1 ELSE 0 END) as with_fonts,
                COUNT(DISTINCT headline_font) as unique_fonts
            FROM customer_freebies
        ")->fetch(PDO::FETCH_ASSOC);
        
        $result['stats'] = $stats;
        
        $result['steps'][count($result['steps'])-1] = [
            'icon' => '✅',
            'title' => 'Schritt 4: Statistiken sammeln',
            'message' => 'Statistiken erfolgreich gesammelt',
            'status' => 'success'
        ];
        
        // Schritt 5: Verifikation
        $result['steps'][] = ['icon' => '⏳', 'title' => 'Schritt 5: Verifikation', 'status' => 'running'];
        
        $verification = $pdo->query("
            SELECT COUNT(*) as count
            FROM customer_freebies
            WHERE headline_font IS NULL
        ")->fetch(PDO::FETCH_ASSOC);
        
        if ($verification['count'] > 0) {
            $result['steps'][count($result['steps'])-1] = [
                'icon' => '⚠️',
                'title' => 'Schritt 5: Verifikation',
                'message' => $verification['count'] . ' Freebies ohne Font-Einstellungen (möglicherweise ohne Template)',
                'status' => 'warning'
            ];
        } else {
            $result['steps'][count($result['steps'])-1] = [
                'icon' => '✅',
                'title' => 'Schritt 5: Verifikation',
                'message' => 'Alle Freebies haben gültige Font-Einstellungen',
                'status' => 'success'
            ];
        }
        
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
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        h1 { 
            font-size: 32px; 
            margin-bottom: 8px;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 32px;
            line-height: 1.6;
            font-size: 16px;
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
        .error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 20px;
            margin-bottom: 24px;
            border-radius: 8px;
            color: #991b1b;
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
        .step.warning {
            background: #fef3cd;
            border-color: #f59e0b;
        }
        .step.running {
            background: #dbeafe;
            border-color: #3b82f6;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .step-icon {
            font-size: 28px;
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
        }
        .success-banner {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 32px;
            border-radius: 12px;
            text-align: center;
        }
        .success-banner h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .success-banner p {
            opacity: 0.95;
            line-height: 1.6;
            font-size: 16px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 24px 0;
        }
        .stat-card {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            display: block;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        .button {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .button-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .button-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.6;
        }
        .info strong {
            display: block;
            margin-bottom: 8px;
            color: #1e40af;
        }
        .code {
            background: #1f2937;
            color: #f9fafb;
            padding: 12px 16px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-top: 8px;
            overflow-x: auto;
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
        <?php if ($result === null): ?>
            <!-- Vor der Migration -->
            <div class="card">
                <h1>🎨 Font-Migration starten</h1>
                <p class="subtitle">
                    Diese Migration fügt Font-Felder zur customer_freebies Tabelle hinzu und 
                    aktualisiert alle bestehenden Freebies mit den Schrifteinstellungen aus den Templates.
                </p>
                
                <div class="warning">
                    <div class="warning-title">⚠️ Wichtige Hinweise</div>
                    <div class="warning-text">
                        <ul style="margin-left: 20px; margin-top: 8px; line-height: 1.8;">
                            <li>Diese Seite ist NICHT passwortgeschützt</li>
                            <li>Die Migration ändert die Datenbankstruktur</li>
                            <li>Nach der Migration diese Datei <strong>SOFORT LÖSCHEN</strong>!</li>
                        </ul>
                    </div>
                </div>
                
                <div class="info">
                    <strong>📋 Was wird gemacht:</strong>
                    • Prüfung der Datenbankstruktur<br>
                    • Hinzufügen von 8 Font-Feldern (falls nicht vorhanden)<br>
                    • Aktualisierung aller Customer Freebies mit Template-Fonts<br>
                    • Statistiken und Verifikation
                </div>
                
                <div class="actions">
                    <a href="?execute=1" class="button">🚀 Migration jetzt ausführen</a>
                    <a href="/admin/dashboard.php" class="button button-secondary">← Abbrechen</a>
                </div>
            </div>
            
        <?php elseif ($result['success']): ?>
            <!-- Nach erfolgreicher Migration -->
            <div class="card">
                <h1>✅ Migration erfolgreich!</h1>
                <p class="subtitle">Die Font-Felder wurden erfolgreich zur Datenbank hinzugefügt.</p>
                
                <div class="step" style="border-left: none; background: transparent; padding: 0; margin-bottom: 24px;">
                    <?php foreach ($result['steps'] as $step): ?>
                        <div class="step <?php echo $step['status']; ?>">
                            <div class="step-icon"><?php echo $step['icon']; ?></div>
                            <div class="step-content">
                                <div class="step-title"><?php echo htmlspecialchars($step['title']); ?></div>
                                <?php if (isset($step['message'])): ?>
                                    <div class="step-message"><?php echo htmlspecialchars($step['message']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($result['stats'])): ?>
                    <div class="success-banner">
                        <h2>🎉 Alles fertig!</h2>
                        <p>Die Font-Einstellungen sind jetzt aktiv und werden automatisch verwendet.</p>
                        
                        <div class="stats">
                            <div class="stat-card">
                                <span class="stat-value"><?php echo $result['stats']['total']; ?></span>
                                <span class="stat-label">Gesamt Freebies</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-value"><?php echo $result['stats']['with_fonts']; ?></span>
                                <span class="stat-label">Mit Font-Einstellungen</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-value"><?php echo $result['stats']['unique_fonts']; ?></span>
                                <span class="stat-label">Verschiedene Schriftarten</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="info" style="margin-top: 24px;">
                    <strong>🗑️ WICHTIG - Datei jetzt löschen!</strong>
                    Lösche diese Datei aus Sicherheitsgründen sofort:
                    <div class="code">/database/migrate-fonts-now.php</div>
                    <div style="margin-top: 12px;">
                        Über FTP, SSH oder den Dateimanager deines Hosters.
                    </div>
                </div>
                
                <div class="actions">
                    <a href="/admin/dashboard.php?page=freebies" class="button">
                        Templates verwalten →
                    </a>
                    <a href="/admin/dashboard.php?page=freebie-edit&id=1" class="button button-secondary">
                        Template bearbeiten
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Bei Fehler -->
            <div class="card">
                <h1>❌ Migration fehlgeschlagen</h1>
                
                <div class="error">
                    <strong>Fehler bei der Migration:</strong><br>
                    <div style="margin-top: 8px; font-family: monospace;">
                        <?php echo htmlspecialchars($result['error']); ?>
                    </div>
                </div>
                
                <div class="info">
                    <strong>💡 Mögliche Lösungen:</strong>
                    • Prüfe die Datenbankverbindung<br>
                    • Stelle sicher, dass die Tabelle customer_freebies existiert<br>
                    • Prüfe die Berechtigungen des Datenbankbenutzers<br>
                    • Kontaktiere den Support falls der Fehler weiterhin auftritt
                </div>
                
                <div class="actions">
                    <a href="?" class="button">🔄 Erneut versuchen</a>
                    <a href="/admin/dashboard.php" class="button button-secondary">← Zurück</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
