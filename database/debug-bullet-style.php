<?php
/**
 * 🔍 Debug-Script für Bullet Icon Style
 * Zeigt den aktuellen Status des Freebies an
 */

require_once __DIR__ . '/../config/database.php';

$identifier = $_GET['id'] ?? '04828493b017248c0db10bb82d48754e';

try {
    $pdo = getDBConnection();
    
    // Freebie laden
    $stmt = $pdo->prepare("
        SELECT 
            id,
            headline,
            bullet_points,
            bullet_icon_style,
            created_at,
            updated_at
        FROM customer_freebies 
        WHERE unique_id = ?
    ");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔍 Freebie Debug Info</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 40px 20px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            h1 {
                color: #1a1a2e;
                font-size: 32px;
                margin-bottom: 10px;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
            }
            .info-box {
                background: #f0f9ff;
                border-left: 4px solid #3b82f6;
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 20px;
            }
            .info-box h3 {
                color: #1e40af;
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 8px;
            }
            .info-item {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .info-item:last-child {
                border-bottom: none;
            }
            .label {
                font-weight: 600;
                color: #374151;
            }
            .value {
                color: #6b7280;
                text-align: right;
                max-width: 60%;
                word-break: break-word;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-success {
                background: #d1fae5;
                color: #065f46;
            }
            .status-warning {
                background: #fef3c7;
                color: #92400e;
            }
            .status-error {
                background: #fee2e2;
                color: #991b1b;
            }
            .code-block {
                background: #1f2937;
                color: #f3f4f6;
                padding: 16px;
                border-radius: 8px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                overflow-x: auto;
                margin: 16px 0;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Freebie Debug Info</h1>
            <p class="subtitle">Status-Check für Bullet Icon Style</p>
            
            <?php if ($freebie): ?>
                
                <div class="info-box">
                    <h3>📋 Freebie Informationen</h3>
                    
                    <div class="info-item">
                        <span class="label">ID:</span>
                        <span class="value"><?= htmlspecialchars($freebie['id']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Headline:</span>
                        <span class="value"><?= htmlspecialchars($freebie['headline']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Erstellt am:</span>
                        <span class="value"><?= htmlspecialchars($freebie['created_at']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Zuletzt aktualisiert:</span>
                        <span class="value"><?= htmlspecialchars($freebie['updated_at']) ?></span>
                    </div>
                </div>
                
                <div class="info-box">
                    <h3>🎨 Bullet Icon Style Status</h3>
                    
                    <div class="info-item">
                        <span class="label">Aktueller Wert:</span>
                        <span class="value">
                            <?php if (isset($freebie['bullet_icon_style'])): ?>
                                <span class="status-badge <?= $freebie['bullet_icon_style'] === 'custom' ? 'status-success' : 'status-warning' ?>">
                                    <?= htmlspecialchars($freebie['bullet_icon_style']) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-error">NICHT GESETZT</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Was bedeutet das?</span>
                        <span class="value">
                            <?php if (!isset($freebie['bullet_icon_style'])): ?>
                                ❌ Die Spalte existiert nicht in der Datenbank
                            <?php elseif ($freebie['bullet_icon_style'] === 'standard'): ?>
                                ✓ Standard Checkmarken werden angezeigt
                            <?php elseif ($freebie['bullet_icon_style'] === 'custom'): ?>
                                ✅ Eigene Icons werden verwendet
                            <?php else: ?>
                                ⚠️ Unbekannter Wert
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-box">
                    <h3>📝 Bullet Points</h3>
                    <div class="code-block"><?= htmlspecialchars($freebie['bullet_points']) ?></div>
                </div>
                
                <?php if (!isset($freebie['bullet_icon_style'])): ?>
                    <div class="info-box" style="background: #fef3c7; border-color: #f59e0b;">
                        <h3 style="color: #92400e;">⚠️ Aktion erforderlich</h3>
                        <p style="color: #78350f; margin-top: 8px;">
                            Die Datenbank-Migration wurde noch nicht ausgeführt!<br>
                            <strong>Lösung:</strong> Führe die Migration aus:
                            <a href="/database/run-migration.php" style="color: #92400e;">
                                /database/run-migration.php
                            </a>
                        </p>
                    </div>
                <?php elseif ($freebie['bullet_icon_style'] === 'standard'): ?>
                    <div class="info-box" style="background: #fef3c7; border-color: #f59e0b;">
                        <h3 style="color: #92400e;">💡 Hinweis</h3>
                        <p style="color: #78350f; margin-top: 8px;">
                            Das Freebie ist auf "Standard Checkmarken" eingestellt.<br>
                            <strong>Lösung:</strong> Öffne das Freebie im Editor und wähle "Eigene Icons" aus.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="info-box" style="background: #d1fae5; border-color: #10b981;">
                        <h3 style="color: #065f46;">✅ Alles korrekt konfiguriert!</h3>
                        <p style="color: #047857; margin-top: 8px;">
                            Das Freebie sollte die Emojis ohne zusätzliche Checkmarken anzeigen.
                        </p>
                    </div>
                <?php endif; ?>
                
                <a href="/customer/custom-freebie-editor.php?id=<?= $freebie['id'] ?>" class="button">
                    ✏️ Freebie im Editor bearbeiten
                </a>
                
            <?php else: ?>
                <div class="info-box" style="background: #fee2e2; border-color: #ef4444;">
                    <h3 style="color: #991b1b;">❌ Freebie nicht gefunden</h3>
                    <p style="color: #7f1d1d; margin-top: 8px;">
                        Das Freebie mit der ID "<?= htmlspecialchars($identifier) ?>" wurde nicht gefunden.
                    </p>
                </div>
            <?php endif; ?>
            
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>❌ Fehler</title>
    </head>
    <body style="font-family: sans-serif; padding: 40px; background: #fee2e2;">
        <h1 style="color: #991b1b;">❌ Datenbankfehler</h1>
        <p style="color: #7f1d1d;"><?= htmlspecialchars($e->getMessage()) ?></p>
    </body>
    </html>
    <?php
}
?>
