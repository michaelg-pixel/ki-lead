<?php
/**
 * DRIP-CONTENT AKTIVIERUNGS-SCRIPT
 * Aktiviert Drip-Content nach erfolgreicher Migration
 * Aufruf: https://app.mehr-infos-jetzt.de/database/activate-drip-content.php
 */

require_once '../config/database.php';

// Check ob Migration gelaufen ist
$migration_lock = __DIR__ . '/drip-content-migration.lock';
if (!file_exists($migration_lock)) {
    die("⚠️ Fehler: Bitte zuerst die Migration ausführen!<br><a href='migrate-drip-content.php'>→ Zur Migration</a>");
}

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>Drip-Content Aktivierung</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #11998e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .step h3 {
            color: #11998e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step p {
            color: #555;
            line-height: 1.6;
            margin: 8px 0;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .success h3 { color: #28a745; }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin: 10px 0;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>✅ Drip-Content Aktivierung</h1>
    <p style='color: #666; margin-bottom: 30px;'>Aktiviere das zeitgesteuerte Freischaltungs-System</p>
";

try {
    $pdo = getDBConnection();
    
    // Check ob granted_at existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM course_access LIKE 'granted_at'");
    if ($stmt->rowCount() === 0) {
        die("<div class='step' style='background: #f8d7da; border-left-color: #dc3545;'>
            <h3 style='color: #dc3545;'>❌ Spalte 'granted_at' nicht gefunden!</h3>
            <p>Bitte zuerst die Migration ausführen:</p>
            <a href='migrate-drip-content.php' class='btn'>→ Zur Migration</a>
        </div></div></body></html>");
    }
    
    echo "<div class='step success'>
        <h3>✓ Datenbank bereit</h3>
        <p>granted_at Spalte gefunden in course_access</p>
    </div>";
    
    // Aktiviere Drip-Content in course-view.php durch Update
    $source_file = __DIR__ . '/../customer/course-view.php';
    
    if (!file_exists($source_file)) {
        die("<div class='step' style='background: #f8d7da; border-left-color: #dc3545;'>
            <h3 style='color: #dc3545;'>❌ Datei nicht gefunden!</h3>
            <p>customer/course-view.php existiert nicht</p>
        </div></div></body></html>");
    }
    
    $content = file_get_contents($source_file);
    
    // Check ob bereits aktiviert
    if (strpos($content, 'Drip-Content temporarily disabled') === false) {
        echo "<div class='step success'>
            <h3>✓ Drip-Content bereits aktiviert</h3>
            <p>Die course-view.php nutzt bereits Drip-Content</p>
        </div>";
    } else {
        echo "<div class='step'>
            <h3>🔄 Aktiviere Drip-Content...</h3>
            <p>→ Ersetze temporären Code mit aktivem Drip-Content</p>
            <p>→ Implementiere granted_at Logik</p>
            <p>→ Aktiviere Lektions-Sperrung</p>
        </div>";
        
        // Replace the disabled drip content code with active code
        $content = str_replace(
            "// Drip-Content temporarily disabled pending DB schema",
            "// Drip-Content ACTIVE - Using granted_at from course_access",
            $content
        );
        
        // Replace the temporary lock code
        $content = str_replace(
            "\$modules[\$i]['lessons'][\$j]['is_locked'] = false; // Temporarily disabled",
            "// Drip-Content Check based on granted_at
        \$lesson_unlocked = true;
        \$unlock_in_days = 0;
        
        if (\$is_logged_in && \$access_date && isset(\$modules[\$i]['lessons'][\$j]['unlock_after_days'])) {
            \$unlock_after_days = (int)\$modules[\$i]['lessons'][\$j]['unlock_after_days'];
            if (\$unlock_after_days > 0) {
                \$unlock_date = clone \$access_date;
                \$unlock_date->modify(\"+{\$unlock_after_days} days\");
                
                \$now = new DateTime();
                if (\$now < \$unlock_date) {
                    \$lesson_unlocked = false;
                    \$interval = \$now->diff(\$unlock_date);
                    \$unlock_in_days = \$interval->days + 1;
                }
            }
        }
        
        \$modules[\$i]['lessons'][\$j]['is_locked'] = !\$lesson_unlocked;
        \$modules[\$i]['lessons'][\$j]['unlock_in_days'] = \$unlock_in_days;",
            $content
        );
        
        // Add access_date loading
        $content = str_replace(
            "SELECT c.*, ca.access_source",
            "SELECT c.*, ca.access_source, ca.granted_at as access_date",
            $content
        );
        
        // Add access_date parsing
        $old_pattern = "if (\$is_logged_in) {
    // Eingeloggte User: Mit Zugangs-Check
    \$stmt = \$pdo->prepare(\"
        SELECT c.*, ca.access_source, ca.granted_at as access_date
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
        WHERE c.id = ? AND (c.is_freebie = TRUE OR ca.id IS NOT NULL)
    \");
    \$stmt->execute([\$user_id, \$course_id]);
    \$course = \$stmt->fetch();";
        
        $new_pattern = "// Access date for drip content
\$access_date = null;
if (\$is_logged_in) {
    // Eingeloggte User: Mit Zugangs-Check und Access Date
    \$stmt = \$pdo->prepare(\"
        SELECT c.*, ca.access_source, ca.granted_at as access_date
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
        WHERE c.id = ? AND (c.is_freebie = TRUE OR ca.id IS NOT NULL)
    \");
    \$stmt->execute([\$user_id, \$course_id]);
    \$course = \$stmt->fetch();
    
    if (\$course && isset(\$course['access_date'])) {
        \$access_date = new DateTime(\$course['access_date']);
    }";
        
        $content = str_replace($old_pattern, $new_pattern, $content);
        
        // Update version number
        $content = str_replace(
            "Version 3.1 - Modern UI (Drip-Content temporarily disabled",
            "Version 3.2 - Modern UI with ACTIVE Drip-Content",
            $content
        );
        
        // Save updated file
        file_put_contents($source_file, $content);
        
        echo "<div class='step success'>
            <h3>✅ Drip-Content erfolgreich aktiviert!</h3>
            <p>✓ course-view.php wurde aktualisiert</p>
            <p>✓ granted_at Logik implementiert</p>
            <p>✓ Lektions-Sperrung aktiv</p>
        </div>";
    }
    
    // Final instructions
    echo "<div class='step'>
        <h3>🎯 Nächste Schritte</h3>
        <p>1. Teste einen Kurs mit Drip-Content Lektionen</p>
        <p>2. Setze unlock_after_days für Lektionen im Admin-Panel</p>
        <p>3. Neue Nutzer sehen automatisch gesperrte Lektionen</p>
    </div>";
    
    echo "<div class='step success'>
        <h3>🚀 System bereit!</h3>
        <a href='/customer/dashboard.php?page=kurse' class='btn'>→ Zu den Kursen</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='step' style='background: #f8d7da; border-left-color: #dc3545;'>
        <h3 style='color: #dc3545;'>❌ Fehler!</h3>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}

echo "</div></body></html>";
?>