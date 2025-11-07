<?php
/**
 * SQL-Fix Script für falsche Modulnamen
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Nur für Admins');
}

$pdo = getDBConnection();
$course_id = $_GET['id'] ?? 10;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modul-Namen korrigieren</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a16;
            color: #e5e7eb;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #1a1532;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        h1 {
            color: #a855f7;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid rgba(168, 85, 247, 0.3);
            padding: 12px;
            text-align: left;
        }
        th {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
            font-weight: 700;
        }
        tr:nth-child(even) {
            background: rgba(168, 85, 247, 0.05);
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 10px 10px 0;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        input[type="text"] {
            background: #0f0f1e;
            border: 1px solid rgba(168, 85, 247, 0.3);
            color: #e5e7eb;
            padding: 8px 12px;
            border-radius: 6px;
            width: 200px;
        }
        form {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Modul-Namen korrigieren (Kurs ID: <?php echo $course_id; ?>)</h1>
        
        <?php
        // Fix durchführen wenn requested
        if (isset($_POST['fix_names'])) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE course_modules 
                    SET title = ? 
                    WHERE id = ?
                ");
                
                foreach ($_POST['titles'] as $module_id => $new_title) {
                    $stmt->execute([$new_title, $module_id]);
                }
                
                echo '<div class="success">✅ Module erfolgreich aktualisiert!</div>';
            } catch (Exception $e) {
                echo '<div class="error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        
        // Aktuelle Module laden
        $stmt = $pdo->prepare("
            SELECT id, title, description, sort_order 
            FROM course_modules 
            WHERE course_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$course_id]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <h2>Aktuelle Module in der Datenbank:</h2>
        
        <?php if (count($modules) > 0): ?>
            <form method="POST">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Reihenfolge</th>
                        <th>Aktueller Titel</th>
                        <th>Neuer Titel (bearbeiten)</th>
                        <th>Beschreibung</th>
                    </tr>
                    <?php foreach ($modules as $index => $module): ?>
                        <tr>
                            <td><?php echo $module['id']; ?></td>
                            <td><?php echo $module['sort_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($module['title']); ?></strong></td>
                            <td>
                                <input type="text" 
                                       name="titles[<?php echo $module['id']; ?>]" 
                                       value="<?php echo htmlspecialchars($module['title']); ?>"
                                       placeholder="z.B. Modul <?php echo ($index + 1); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($module['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <div class="warning">
                    ⚠️ <strong>Wichtig:</strong> Bitte korrigieren Sie die Titel in der Spalte "Neuer Titel" und klicken Sie dann auf "Namen aktualisieren".
                </div>
                
                <button type="submit" name="fix_names" class="btn btn-success">
                    ✓ Namen aktualisieren
                </button>
            </form>
        <?php else: ?>
            <div class="error">❌ Keine Module gefunden!</div>
        <?php endif; ?>
        
        <hr style="margin: 40px 0; border-color: rgba(168, 85, 247, 0.2);">
        
        <a href="preview_fresh.php?id=<?php echo $course_id; ?>" class="btn">
            🔍 Zur Vorschau
        </a>
        
        <a href="dashboard.php?page=course-edit&id=<?php echo $course_id; ?>" class="btn">
            ✏️ Kurs bearbeiten
        </a>
        
        <a href="debug-course.php?id=<?php echo $course_id; ?>" class="btn">
            🐛 Debug-Info
        </a>
    </div>
</body>
</html>