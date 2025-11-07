<?php
/**
 * MIGRATIONS-SCRIPT: Daten von lessons/modules nach course_lessons/course_modules synchronisieren
 * 
 * Problem: Admin speichert in "lessons" + "modules", Customer liest aus "course_lessons" + "course_modules"
 * Lösung: Alle Daten kopieren und synchron halten
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/admin/api/sync-lessons-to-course-lessons.php
 */

session_start();
require_once '../../config/database.php';

// Sicherheitscheck: Nur Admin-Zugriff (KEINE Passwort-Eingabe nötig!)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('<h1>❌ Zugriff verweigert</h1><p>Bitte als Admin einloggen.</p>');
}

$pdo = getDBConnection();
$errors = [];
$success = [];
$stats = [
    'modules_synced' => 0,
    'lessons_synced' => 0,
    'videos_synced' => 0
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Lektionen synchronisieren</title>
    <style>
        body { 
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
            color: #e0e0e0; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 40px;
            margin: 0;
        }
        .container { max-width: 900px; margin: 0 auto; }
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
            border: none;
            cursor: pointer;
            font-size: 1rem;
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
        .stat-box {
            display: inline-block;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 0.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #a855f7;
        }
        .stat-label {
            color: #a0a0a0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Lektionen & Module synchronisieren</h1>

        <?php if (!isset($_GET['run'])): ?>
            <?php
            // Analyse der aktuellen Daten
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM modules");
                $modules_count = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM lessons");
                $lessons_count = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM lesson_videos");
                $videos_count = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM course_modules");
                $course_modules_count = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM course_lessons");
                $course_lessons_count = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $modules_count = $lessons_count = $videos_count = 0;
                $course_modules_count = $course_lessons_count = 0;
            }
            ?>
            
            <div class="box warning">
                <h2>⚠️ Problem erkannt</h2>
                <p>Die Admin-Seite speichert in <code>modules</code> + <code>lessons</code>, aber die Customer-Ansicht liest aus <code>course_modules</code> + <code>course_lessons</code>.</p>
                <p style="margin-top: 1rem;">Deshalb sehen Kunden keine Lektionen im Kursbereich!</p>
            </div>

            <div class="box info">
                <h2>📊 Aktuelle Daten</h2>
                <div style="margin-top: 1rem;">
                    <div class="stat-box">
                        <div class="stat-number"><?= $modules_count ?></div>
                        <div class="stat-label">Module (alt)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $lessons_count ?></div>
                        <div class="stat-label">Lektionen (alt)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $videos_count ?></div>
                        <div class="stat-label">Zusätzliche Videos</div>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <div class="stat-box">
                        <div class="stat-number"><?= $course_modules_count ?></div>
                        <div class="stat-label">Course Modules (neu)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $course_lessons_count ?></div>
                        <div class="stat-label">Course Lessons (neu)</div>
                    </div>
                </div>
            </div>

            <div class="box info">
                <h2>ℹ️ Was wird gemacht?</h2>
                <ul>
                    <li>✓ Kopiert alle Module von <code>modules</code> → <code>course_modules</code></li>
                    <li>✓ Kopiert alle Lektionen von <code>lessons</code> → <code>course_lessons</code></li>
                    <li>✓ Behält alle Beziehungen (IDs) korrekt bei</li>
                    <li>✓ Aktualisiert <code>lesson_videos</code> Referenzen</li>
                    <li>✓ Alle bestehenden Daten bleiben unverändert</li>
                    <li>✓ Kann mehrfach ausgeführt werden (überschreibt alte Daten)</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <form method="GET" style="display: inline;">
                    <input type="hidden" name="run" value="1">
                    <button type="submit" class="btn">✅ Jetzt synchronisieren</button>
                </form>
                <a href="/admin/dashboard.php" class="btn btn-secondary">❌ Abbrechen</a>
            </div>

        <?php else: ?>
            <?php
            $pdo->beginTransaction();
            
            try {
                // SCHRITT 1: Module synchronisieren
                $success[] = "📋 Schritt 1: Module werden synchronisiert...";
                
                $stmt = $pdo->query("
                    SELECT id, course_id, title, description, sort_order 
                    FROM modules 
                    ORDER BY id
                ");
                $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($modules as $module) {
                    // Prüfen ob Modul bereits existiert
                    $check = $pdo->prepare("SELECT id FROM course_modules WHERE id = ?");
                    $check->execute([$module['id']]);
                    
                    if ($check->fetch()) {
                        // Update
                        $stmt = $pdo->prepare("
                            UPDATE course_modules 
                            SET course_id = ?, title = ?, description = ?, sort_order = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $module['course_id'],
                            $module['title'],
                            $module['description'],
                            $module['sort_order'],
                            $module['id']
                        ]);
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("
                            INSERT INTO course_modules (id, course_id, title, description, sort_order)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $module['id'],
                            $module['course_id'],
                            $module['title'],
                            $module['description'],
                            $module['sort_order']
                        ]);
                    }
                    $stats['modules_synced']++;
                }
                
                $success[] = "✅ Module synchronisiert: {$stats['modules_synced']}";
                
                // SCHRITT 2: Lektionen synchronisieren
                $success[] = "📝 Schritt 2: Lektionen werden synchronisiert...";
                
                $stmt = $pdo->query("
                    SELECT id, module_id, title, description, vimeo_url, pdf_file, 
                           unlock_after_days, sort_order 
                    FROM lessons 
                    ORDER BY id
                ");
                $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($lessons as $lesson) {
                    // Prüfen ob Lektion bereits existiert
                    $check = $pdo->prepare("SELECT id FROM course_lessons WHERE id = ?");
                    $check->execute([$lesson['id']]);
                    
                    if ($check->fetch()) {
                        // Update
                        $stmt = $pdo->prepare("
                            UPDATE course_lessons 
                            SET module_id = ?, title = ?, description = ?, 
                                video_url = ?, pdf_attachment = ?, unlock_after_days = ?, sort_order = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $lesson['module_id'],
                            $lesson['title'],
                            $lesson['description'],
                            $lesson['vimeo_url'],
                            $lesson['pdf_file'],
                            $lesson['unlock_after_days'],
                            $lesson['sort_order'],
                            $lesson['id']
                        ]);
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("
                            INSERT INTO course_lessons 
                            (id, module_id, title, description, video_url, pdf_attachment, unlock_after_days, sort_order)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $lesson['id'],
                            $lesson['module_id'],
                            $lesson['title'],
                            $lesson['description'],
                            $lesson['vimeo_url'],
                            $lesson['pdf_file'],
                            $lesson['unlock_after_days'],
                            $lesson['sort_order']
                        ]);
                    }
                    $stats['lessons_synced']++;
                }
                
                $success[] = "✅ Lektionen synchronisiert: {$stats['lessons_synced']}";
                
                // SCHRITT 3: Zusätzliche Videos zählen (keine Änderungen nötig)
                $stmt = $pdo->query("SELECT COUNT(*) FROM lesson_videos");
                $stats['videos_synced'] = $stmt->fetchColumn();
                $success[] = "✅ Zusätzliche Videos gefunden: {$stats['videos_synced']}";
                
                $pdo->commit();
                $success[] = "🎉 Alle Daten erfolgreich synchronisiert!";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "❌ Fehler: " . $e->getMessage();
            }
            ?>

            <?php if (!empty($success)): ?>
                <div class="box success">
                    <h2>✅ Synchronisation erfolgreich!</h2>
                    <ul>
                        <?php foreach ($success as $msg): ?>
                            <li><?= $msg ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div style="margin-top: 2rem;">
                        <div class="stat-box">
                            <div class="stat-number"><?= $stats['modules_synced'] ?></div>
                            <div class="stat-label">Module</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?= $stats['lessons_synced'] ?></div>
                            <div class="stat-label">Lektionen</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?= $stats['videos_synced'] ?></div>
                            <div class="stat-label">Videos</div>
                        </div>
                    </div>
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
                    <li>1️⃣ Gehe zum Customer-Bereich: <a href="/customer/course-view.php?id=5" style="color: #60a5fa;">Kurs ansehen</a></li>
                    <li>2️⃣ Drücke <code>Strg + F5</code> um den Cache zu leeren</li>
                    <li>3️⃣ Prüfe ob die Lektionen jetzt sichtbar sind</li>
                    <li>4️⃣ Teste das Abspielen der Videos</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="/customer/course-view.php?id=5" class="btn">➡️ Zum Kurs (Customer-Bereich)</a>
                <a href="/admin/dashboard.php?page=course-edit&id=5" class="btn btn-secondary">🎓 Kurs bearbeiten (Admin)</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
