<?php
/**
 * MIGRATIONS-SCRIPT: Foreign Key von lesson_videos entfernen
 * 
 * Aufruf: https://deine-domain.de/admin/api/fix-lesson-videos-fk.php
 */

session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert! Bitte als Admin einloggen.');
}

$pdo = getDBConnection();
$errors = [];
$success = [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Foreign Key Fix</title>
    <style>
        body { 
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
            color: #e0e0e0; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 40px;
            margin: 0;
        }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #fff; text-align: center; margin-bottom: 2rem; }
        .box { 
            background: rgba(26, 26, 46, 0.9); 
            border: 1px solid rgba(168, 85, 247, 0.3); 
            border-radius: 12px; 
            padding: 2rem; 
            margin: 1rem 0; 
        }
        .success { 
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.4); 
            color: #86efac; 
        }
        .error { 
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.4); 
            color: #f87171; 
        }
        .warning {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.4);
            color: #fbbf24;
        }
        .info {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.4);
            color: #60a5fa;
        }
        .btn { 
            display: inline-block; 
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: white; 
            padding: 1rem 2rem; 
            border-radius: 8px; 
            text-decoration: none; 
            margin: 1rem 0.5rem 0 0;
            font-weight: 600;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(168, 85, 247, 0.4);
        }
        .btn-secondary {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
        }
        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        ul { list-style: none; padding: 0; }
        li { padding: 0.5rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        li:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Foreign Key Konflikt beheben</h1>

        <?php if (!isset($_GET['run'])): ?>
            <?php
            // Analyse der aktuellen Struktur
            try {
                $stmt = $pdo->query("
                    SELECT 
                        CONSTRAINT_NAME,
                        TABLE_NAME,
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'lesson_videos'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $foreignKeys = [];
            }
            ?>
            
            <div class="box warning">
                <h2>⚠️ Problem erkannt</h2>
                <p>Die Tabelle <code>lesson_videos</code> hat einen Foreign Key Constraint auf <code>lessons</code>, aber dein System verwendet <code>course_lessons</code>.</p>
                <p style="margin-top: 1rem;">Dieser Konflikt verhindert das Speichern von zusätzlichen Videos.</p>
            </div>

            <?php if (!empty($foreignKeys)): ?>
                <div class="box info">
                    <h2>🔍 Gefundene Foreign Keys</h2>
                    <ul>
                        <?php foreach ($foreignKeys as $fk): ?>
                            <li>
                                <strong><?= htmlspecialchars($fk['CONSTRAINT_NAME']) ?></strong><br>
                                <code><?= htmlspecialchars($fk['TABLE_NAME']) ?>.<?= htmlspecialchars($fk['COLUMN_NAME']) ?></code>
                                →
                                <code><?= htmlspecialchars($fk['REFERENCED_TABLE_NAME']) ?>.<?= htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="box info">
                <h2>ℹ️ Was wird gemacht?</h2>
                <ul>
                    <li>✓ Foreign Key von <code>lesson_videos</code> wird entfernt</li>
                    <li>✓ Index auf <code>lesson_id</code> bleibt erhalten (für Performance)</li>
                    <li>✓ Alle bestehenden Daten bleiben unverändert</li>
                    <li>✓ Die Tabelle funktioniert danach mit beiden: <code>lessons</code> UND <code>course_lessons</code></li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="?run=1" class="btn">✅ Foreign Key jetzt entfernen</a>
                <a href="/admin/dashboard.php" class="btn btn-secondary">❌ Abbrechen</a>
            </div>

        <?php else: ?>
            <?php
            // Foreign Keys entfernen
            try {
                // Hole alle Foreign Keys der Tabelle
                $stmt = $pdo->query("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'lesson_videos'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $constraints = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($constraints)) {
                    foreach ($constraints as $constraint) {
                        try {
                            $sql = "ALTER TABLE lesson_videos DROP FOREIGN KEY " . $constraint;
                            $pdo->exec($sql);
                            $success[] = "✅ Foreign Key '" . htmlspecialchars($constraint) . "' entfernt";
                        } catch (PDOException $e) {
                            $errors[] = "❌ Fehler beim Entfernen von '" . htmlspecialchars($constraint) . "': " . $e->getMessage();
                        }
                    }
                } else {
                    $success[] = "ℹ️ Keine Foreign Keys gefunden (bereits entfernt oder nicht vorhanden)";
                }
                
                // Prüfe ob Index auf lesson_id existiert
                $stmt = $pdo->query("SHOW INDEX FROM lesson_videos WHERE Column_name = 'lesson_id'");
                $hasIndex = $stmt->rowCount() > 0;
                
                if (!$hasIndex) {
                    try {
                        $pdo->exec("ALTER TABLE lesson_videos ADD INDEX idx_lesson_id (lesson_id)");
                        $success[] = "✅ Index auf lesson_id erstellt (für Performance)";
                    } catch (PDOException $e) {
                        $success[] = "ℹ️ Index konnte nicht erstellt werden (möglicherweise existiert er bereits)";
                    }
                } else {
                    $success[] = "✅ Index auf lesson_id existiert bereits";
                }
                
            } catch (PDOException $e) {
                $errors[] = "❌ Fehler: " . $e->getMessage();
            }

            // Verifikation
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as fk_count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'lesson_videos'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $result = $stmt->fetch();
                
                if ($result['fk_count'] == 0) {
                    $success[] = "✅ Verifikation: Keine Foreign Keys mehr vorhanden";
                } else {
                    $errors[] = "⚠️ Warnung: Es wurden noch " . $result['fk_count'] . " Foreign Key(s) gefunden";
                }
            } catch (PDOException $e) {
                $errors[] = "⚠️ Verifikation fehlgeschlagen: " . $e->getMessage();
            }
            ?>

            <?php if (!empty($success)): ?>
                <div class="box success">
                    <h2>✅ Erfolgreich!</h2>
                    <ul>
                        <?php foreach ($success as $msg): ?>
                            <li><?= $msg ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="box error">
                    <h2>❌ Fehler aufgetreten</h2>
                    <ul>
                        <?php foreach ($errors as $msg): ?>
                            <li><?= $msg ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="box info">
                <h2>📋 Nächste Schritte</h2>
                <ul>
                    <li>1️⃣ Führe die Haupt-Migration aus: <a href="migrate-course-lessons-drip.php" style="color: #60a5fa;">migrate-course-lessons-drip.php</a></li>
                    <li>2️⃣ Gehe zu einem Kurs im Admin-Bereich</li>
                    <li>3️⃣ Drücke <code>Strg + F5</code> um den Cache zu leeren</li>
                    <li>4️⃣ Teste das Hinzufügen einer neuen Lektion mit zusätzlichen Videos</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="migrate-course-lessons-drip.php" class="btn">➡️ Zur Haupt-Migration</a>
                <a href="/admin/dashboard.php?page=templates" class="btn btn-secondary">🎓 Zu den Kursen</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
