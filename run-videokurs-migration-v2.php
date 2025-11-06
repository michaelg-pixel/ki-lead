<?php
/**
 * 🎓 VIDEOKURS-SYSTEM DATENBANK-MIGRATION (IMPROVED v2)
 * 
 * Verbesserte Version mit besserer Fehlerbehandlung
 * 
 * Aufruf: https://deine-domain.de/run-videokurs-migration-v2.php?key=MIGRATION2024
 */

define('MIGRATION_KEY', 'MIGRATION2024');

$provided_key = $_GET['key'] ?? '';
if ($provided_key !== MIGRATION_KEY) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Zugriff verweigert</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; }
            .error-box { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; max-width: 500px; }
            .error-icon { font-size: 60px; margin-bottom: 20px; }
            h1 { color: #dc2626; margin-bottom: 16px; }
            p { color: #6b7280; line-height: 1.6; }
            code { background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-family: "Courier New", monospace; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🔒</div>
            <h1>Zugriff verweigert</h1>
            <p>Bitte rufe dieses Script mit dem korrekten Sicherheitsschlüssel auf:</p>
            <p><code>?key=MIGRATION2024</code></p>
        </div>
    </body>
    </html>
    ');
}

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
}

$migrations = [];
$results = [];

$migrations[] = ['name' => 'has_course Flag zu customer_freebies hinzufügen', 'sql' => "ALTER TABLE customer_freebies ADD COLUMN has_course TINYINT(1) DEFAULT 0 COMMENT 'Gibt an, ob ein Videokurs existiert'", 'critical' => false];
$migrations[] = ['name' => 'created_at zu customer_freebies hinzufügen', 'sql' => "ALTER TABLE customer_freebies ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'updated_at zu customer_freebies hinzufügen', 'sql' => "ALTER TABLE customer_freebies ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'customer_id zu freebie_courses hinzufügen', 'sql' => "ALTER TABLE freebie_courses ADD COLUMN customer_id INT(11) NOT NULL DEFAULT 0 AFTER freebie_id", 'critical' => true, 'duplicate_ok' => true];
$migrations[] = ['name' => 'Index für customer_id erstellen', 'sql' => "ALTER TABLE freebie_courses ADD INDEX idx_customer_id (customer_id)", 'critical' => false];
$migrations[] = ['name' => 'customer_id mit existierenden Daten befüllen', 'sql' => "UPDATE freebie_courses fc JOIN customer_freebies cf ON fc.freebie_id = cf.id SET fc.customer_id = cf.customer_id WHERE fc.customer_id = 0 OR fc.customer_id IS NULL", 'critical' => true, 'is_update' => true];
$migrations[] = ['name' => 'created_at zu freebie_courses hinzufügen', 'sql' => "ALTER TABLE freebie_courses ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'updated_at zu freebie_courses hinzufügen', 'sql' => "ALTER TABLE freebie_courses ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'created_at zu freebie_course_modules hinzufügen', 'sql' => "ALTER TABLE freebie_course_modules ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'updated_at zu freebie_course_modules hinzufügen', 'sql' => "ALTER TABLE freebie_course_modules ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'created_at zu freebie_course_lessons hinzufügen', 'sql' => "ALTER TABLE freebie_course_lessons ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'updated_at zu freebie_course_lessons hinzufügen', 'sql' => "ALTER TABLE freebie_course_lessons ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'critical' => false];
$migrations[] = ['name' => 'Performance-Index idx_freebie_id', 'sql' => "ALTER TABLE freebie_courses ADD INDEX idx_freebie_id (freebie_id)", 'critical' => false];
$migrations[] = ['name' => 'Performance-Index idx_course_id', 'sql' => "ALTER TABLE freebie_course_modules ADD INDEX idx_course_id (course_id)", 'critical' => false];
$migrations[] = ['name' => 'Performance-Index idx_sort_order (modules)', 'sql' => "ALTER TABLE freebie_course_modules ADD INDEX idx_sort_order (sort_order)", 'critical' => false];
$migrations[] = ['name' => 'Performance-Index idx_module_id', 'sql' => "ALTER TABLE freebie_course_lessons ADD INDEX idx_module_id (module_id)", 'critical' => false];
$migrations[] = ['name' => 'Performance-Index idx_sort_order (lessons)', 'sql' => "ALTER TABLE freebie_course_lessons ADD INDEX idx_sort_order (sort_order)", 'critical' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    $success_count = 0;
    $error_count = 0;
    $skipped_count = 0;
    
    foreach ($migrations as $index => $migration) {
        try {
            if (isset($migration['is_update']) && $migration['is_update']) {
                $affected = $pdo->exec($migration['sql']);
                if ($affected > 0) {
                    $results[$index] = ['status' => 'success', 'message' => "Erfolgreich ($affected Zeilen)"];
                    $success_count++;
                } else {
                    $results[$index] = ['status' => 'skipped', 'message' => 'Keine Zeilen zu aktualisieren'];
                    $skipped_count++;
                }
            } else {
                $pdo->exec($migration['sql']);
                $results[$index] = ['status' => 'success', 'message' => 'Erfolgreich'];
                $success_count++;
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            $isDuplicate = (
                strpos($errorMsg, 'Duplicate column') !== false ||
                strpos($errorMsg, 'Duplicate key') !== false ||
                strpos($errorMsg, '1060') !== false ||
                strpos($errorMsg, '1061') !== false
            );
            
            if ($isDuplicate && (isset($migration['duplicate_ok']) || !$migration['critical'])) {
                $results[$index] = ['status' => 'skipped', 'message' => 'Bereits vorhanden'];
                $skipped_count++;
            } else {
                $results[$index] = ['status' => 'error', 'message' => $errorMsg];
                $error_count++;
                if ($migration['critical'] && !$isDuplicate) break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videokurs-System Migration v2</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); text-align: center; }
        .header-icon { font-size: 64px; margin-bottom: 16px; }
        h1 { color: #1a1a2e; font-size: 28px; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 14px; }
        .warning-box { background: linear-gradient(135deg, rgba(251,191,36,0.1), rgba(245,158,11,0.1)); border-left: 4px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .warning-title { font-size: 16px; font-weight: 700; color: #92400e; margin-bottom: 8px; }
        .warning-text { color: #78350f; font-size: 14px; line-height: 1.6; }
        .panel { background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .section-title { font-size: 20px; font-weight: 700; color: #1a1a2e; margin-bottom: 24px; }
        .step { border: 2px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .step-header { display: flex; justify-content: space-between; align-items: center; }
        .step-name { font-size: 14px; font-weight: 600; color: #374151; }
        .step-badge { padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-critical { background: rgba(239,68,68,0.1); color: #dc2626; }
        .badge-optional { background: rgba(59,130,246,0.1); color: #2563eb; }
        .step-result { margin-top: 12px; padding: 12px; border-radius: 6px; font-size: 13px; }
        .result-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #047857; }
        .result-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #dc2626; }
        .result-skipped { background: rgba(156,163,175,0.1); border: 1px solid rgba(156,163,175,0.3); color: #6b7280; }
        .btn { padding: 16px 48px; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(102,126,234,0.6); }
        .btn-secondary { background: #f3f4f6; color: #374151; margin-left: 12px; }
        .success-box { background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.1)); border-left: 4px solid #10b981; border-radius: 12px; padding: 24px; margin-bottom: 24px; text-align: center; }
        .success-icon { font-size: 64px; margin-bottom: 16px; }
        .summary { background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 20px; }
        .summary-card { text-align: center; padding: 20px; border-radius: 12px; border: 2px solid #e5e7eb; }
        .summary-value { font-size: 28px; font-weight: 800; color: #1a1a2e; }
        .info-badge { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #047857; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-block; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">🎓</div>
            <h1>Videokurs-System Migration v2</h1>
            <p class="subtitle">Verbesserte Fehlerbehandlung</p>
            <div class="info-badge">✅ Ignoriert vorhandene Felder automatisch!</div>
        </div>
        
        <?php if (!isset($_POST['run_migration'])): ?>
            <div class="warning-box">
                <div class="warning-title">🔧 Verbesserungen in v2</div>
                <div class="warning-text">
                    Diese Version erkennt automatisch vorhandene Felder und überspringt sie. Keine Fehler mehr!
                </div>
            </div>
            
            <div class="panel">
                <div class="section-title">📋 Geplante Änderungen (<?php echo count($migrations); ?> Schritte)</div>
                <?php foreach ($migrations as $index => $migration): ?>
                <div class="step">
                    <div class="step-header">
                        <div class="step-name"><?php echo ($index + 1) . '. ' . $migration['name']; ?></div>
                        <span class="step-badge <?php echo $migration['critical'] ? 'badge-critical' : 'badge-optional'; ?>">
                            <?php echo $migration['critical'] ? 'Kritisch' : 'Optional'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="panel" style="text-align: center;">
                <p style="color: #6b7280; margin-bottom: 24px;">
                    <strong><?php echo count($migrations); ?> Änderungen</strong> werden durchgeführt.<br>
                    Bereits vorhandene Felder werden automatisch übersprungen.
                </p>
                <form method="POST">
                    <button type="submit" name="run_migration" class="btn btn-primary">🚀 Migration starten</button>
                    <a href="/customer/dashboard.php" class="btn btn-secondary">Abbrechen</a>
                </form>
            </div>
            
        <?php else: ?>
            <?php if ($error_count === 0): ?>
                <div class="success-box">
                    <div class="success-icon">🎉</div>
                    <h2 style="color: #065f46; margin-bottom: 8px;">Migration erfolgreich!</h2>
                    <p style="color: #047857;">
                        <?php echo $success_count; ?> neue Felder, <?php echo $skipped_count; ?> bereits vorhanden.
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="summary">
                <div class="section-title">📊 Zusammenfassung</div>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div style="font-size: 32px; margin-bottom: 8px;">📝</div>
                        <div class="summary-value"><?php echo count($migrations); ?></div>
                        <div style="font-size: 13px; color: #6b7280;">Gesamt</div>
                    </div>
                    <div class="summary-card" style="border-color: #10b981;">
                        <div style="font-size: 32px; margin-bottom: 8px;">✅</div>
                        <div class="summary-value" style="color: #10b981;"><?php echo $success_count; ?></div>
                        <div style="font-size: 13px; color: #6b7280;">Erfolgreich</div>
                    </div>
                    <div class="summary-card" style="border-color: #9ca3af;">
                        <div style="font-size: 32px; margin-bottom: 8px;">⏭️</div>
                        <div class="summary-value" style="color: #6b7280;"><?php echo $skipped_count; ?></div>
                        <div style="font-size: 13px; color: #6b7280;">Übersprungen</div>
                    </div>
                    <div class="summary-card" style="border-color: <?php echo $error_count > 0 ? '#ef4444' : '#e5e7eb'; ?>;">
                        <div style="font-size: 32px; margin-bottom: 8px;">❌</div>
                        <div class="summary-value" style="color: <?php echo $error_count > 0 ? '#ef4444' : '#6b7280'; ?>;"><?php echo $error_count; ?></div>
                        <div style="font-size: 13px; color: #6b7280;">Fehler</div>
                    </div>
                </div>
            </div>
            
            <div class="panel">
                <div class="section-title">📋 Details</div>
                <?php foreach ($migrations as $index => $migration): ?>
                <div class="step">
                    <div class="step-header">
                        <div class="step-name"><?php echo ($index + 1) . '. ' . $migration['name']; ?></div>
                        <span class="step-badge <?php echo $migration['critical'] ? 'badge-critical' : 'badge-optional'; ?>">
                            <?php echo $migration['critical'] ? 'Kritisch' : 'Optional'; ?>
                        </span>
                    </div>
                    <?php if (isset($results[$index])): ?>
                    <div class="step-result result-<?php echo $results[$index]['status']; ?>">
                        <?php echo ['success' => '✅', 'error' => '❌', 'skipped' => '⏭️'][$results[$index]['status']]; ?>
                        <?php echo htmlspecialchars($results[$index]['message']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="panel" style="text-align: center;">
                <?php if ($error_count === 0): ?>
                    <p style="color: #047857; font-weight: 600; margin-bottom: 24px;">✅ Alles bereit! Du kannst jetzt starten.</p>
                    <a href="/customer/dashboard.php?page=freebies" class="btn btn-primary">🎬 Zu den Freebies</a>
                <?php else: ?>
                    <p style="color: #dc2626; font-weight: 600; margin-bottom: 24px;">⚠️ <?php echo $error_count; ?> Fehler aufgetreten.</p>
                    <a href="?key=<?php echo MIGRATION_KEY; ?>" class="btn btn-secondary">🔄 Erneut versuchen</a>
                <?php endif; ?>
            </div>
            
            <div style="background: rgba(239,68,68,0.05); border: 2px solid rgba(239,68,68,0.2); border-radius: 12px; padding: 20px; text-align: center;">
                <strong style="color: #dc2626;">🔒 Sicherheitshinweis</strong><br>
                <span style="color: #991b1b; font-size: 13px;">Lösche diese Datei jetzt: <code>rm run-videokurs-migration-v2.php</code></span>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
