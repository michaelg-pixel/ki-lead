<?php
/**
 * COPY COURSE FROM ORIGINAL OWNER
 * Kopiert den kompletten Videokurs vom Original-Owner (customer_id 4) zum Käufer (customer_id 17)
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$sourceCustomerId = 4;  // Original-Owner (Freebie 7)
$targetCustomerId = 17; // Käufer (Freebie 53)

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Copy Course to Customer</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        .success { background: #10b981; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .error { background: #ff4444; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .info { background: #3b82f6; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .warning { background: #f59e0b; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; color: #000; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; }
        .btn { display: inline-block; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .btn-primary { background: #10b981; color: white; }
        .btn-secondary { background: #667eea; color: white; }
    </style>
</head>
<body>
<h1>🎓 Videokurs kopieren: Customer $sourceCustomerId → $targetCustomerId</h1>";

try {
    // SCHRITT 1: Source-Customer prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 1: Original-Owner (Customer $sourceCustomerId)</h2>";
    
    $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE id = ?");
    $stmt->execute([$sourceCustomerId]);
    $sourceCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCustomer) {
        echo "<p class='error'>❌ Customer $sourceCustomerId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Customer gefunden: " . htmlspecialchars($sourceCustomer['name']) . " ({$sourceCustomer['email']})</p>";
    echo "</div>";
    
    // SCHRITT 2: Target-Customer prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: Käufer (Customer $targetCustomerId)</h2>";
    
    $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE id = ?");
    $stmt->execute([$targetCustomerId]);
    $targetCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetCustomer) {
        echo "<p class='error'>❌ Customer $targetCustomerId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Käufer gefunden: " . htmlspecialchars($targetCustomer['name']) . " ({$targetCustomer['email']})</p>";
    echo "</div>";
    
    // SCHRITT 3: Kurs(e) des Original-Owners finden
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Kurs(e) des Original-Owners suchen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE customer_id = ?");
    $stmt->execute([$sourceCustomerId]);
    $sourceCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$sourceCourses) {
        echo "<p class='error'>❌ Kein Kurs gefunden für Customer $sourceCustomerId</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ " . count($sourceCourses) . " Kurs(e) gefunden:</p>";
    
    foreach ($sourceCourses as $course) {
        // Spalten dynamisch finden
        $courseName = $course['course_name'] ?? $course['title'] ?? $course['name'] ?? 'Unbenannter Kurs';
        
        echo "<table>";
        echo "<tr><th>Feld</th><th>Wert</th></tr>";
        echo "<tr><td>id</td><td>{$course['id']}</td></tr>";
        echo "<tr><td>Name</td><td>" . htmlspecialchars($courseName) . "</td></tr>";
        echo "<tr><td>customer_id</td><td>{$course['customer_id']}</td></tr>";
        echo "</table>";
        
        // Module zählen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $moduleCount = $stmt->fetchColumn();
        echo "<p class='info'>📦 $moduleCount Module</p>";
        
        // Lektionen zählen
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM course_lessons l
            JOIN course_modules m ON l.module_id = m.id
            WHERE m.course_id = ?
        ");
        $stmt->execute([$course['id']]);
        $lessonCount = $stmt->fetchColumn();
        echo "<p class='info'>📚 $lessonCount Lektionen</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 4: Prüfen ob Käufer bereits Kurse hat
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Ziel-Customer auf existierende Kurse prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE customer_id = ?");
    $stmt->execute([$targetCustomerId]);
    $existingCourses = $stmt->fetchColumn();
    
    if ($existingCourses > 0) {
        echo "<p class='warning'>⚠️ Customer $targetCustomerId hat bereits $existingCourses Kurs(e)!</p>";
    } else {
        echo "<p class='success'>✓ Customer $targetCustomerId hat noch keine Kurse</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 5: KOPIEREN
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<div class='box'>";
        echo "<h2>SCHRITT 5: KURSE KOPIEREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            $totalModules = 0;
            $totalLessons = 0;
            $totalVideos = 0;
            
            foreach ($sourceCourses as $sourceCourse) {
                // Kurs kopieren
                $courseColumns = array_keys($sourceCourse);
                $excludedFields = ['id', 'created_at', 'updated_at'];
                
                $fieldsToInsert = [];
                $values = [];
                
                foreach ($sourceCourse as $field => $value) {
                    if (!in_array($field, $excludedFields)) {
                        if ($field === 'customer_id') {
                            $fieldsToInsert[] = $field;
                            $values[] = $targetCustomerId;
                        } else {
                            $fieldsToInsert[] = $field;
                            $values[] = $value;
                        }
                    }
                }
                
                $fieldsToInsert[] = 'created_at';
                $values[] = date('Y-m-d H:i:s');
                
                $placeholders = array_fill(0, count($values), '?');
                $sql = "INSERT INTO courses (" . implode(', ', $fieldsToInsert) . ") 
                        VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                $newCourseId = $pdo->lastInsertId();
                
                $courseName = $sourceCourse['course_name'] ?? $sourceCourse['title'] ?? $sourceCourse['name'] ?? 'Kurs';
                echo "<p class='success'>✓ Kurs kopiert: " . htmlspecialchars($courseName) . " (ID {$sourceCourse['id']} → $newCourseId)</p>";
                
                // Module kopieren
                $stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY module_order");
                $stmt->execute([$sourceCourse['id']]);
                $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $moduleMapping = [];
                
                foreach ($modules as $module) {
                    $moduleColumns = array_keys($module);
                    $fieldsToInsert = [];
                    $values = [];
                    
                    foreach ($module as $field => $value) {
                        if (!in_array($field, $excludedFields)) {
                            if ($field === 'course_id') {
                                $fieldsToInsert[] = $field;
                                $values[] = $newCourseId;
                            } else {
                                $fieldsToInsert[] = $field;
                                $values[] = $value;
                            }
                        }
                    }
                    
                    $fieldsToInsert[] = 'created_at';
                    $values[] = date('Y-m-d H:i:s');
                    
                    $placeholders = array_fill(0, count($values), '?');
                    $sql = "INSERT INTO course_modules (" . implode(', ', $fieldsToInsert) . ") 
                            VALUES (" . implode(', ', $placeholders) . ")";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values);
                    $newModuleId = $pdo->lastInsertId();
                    
                    $moduleMapping[$module['id']] = $newModuleId;
                    $totalModules++;
                }
                
                // Lektionen kopieren
                foreach ($modules as $module) {
                    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY lesson_order");
                    $stmt->execute([$module['id']]);
                    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($lessons as $lesson) {
                        $fieldsToInsert = [];
                        $values = [];
                        
                        foreach ($lesson as $field => $value) {
                            if (!in_array($field, $excludedFields)) {
                                if ($field === 'module_id') {
                                    $fieldsToInsert[] = $field;
                                    $values[] = $moduleMapping[$module['id']];
                                } else {
                                    $fieldsToInsert[] = $field;
                                    $values[] = $value;
                                }
                            }
                        }
                        
                        $fieldsToInsert[] = 'created_at';
                        $values[] = date('Y-m-d H:i:s');
                        
                        $placeholders = array_fill(0, count($values), '?');
                        $sql = "INSERT INTO course_lessons (" . implode(', ', $fieldsToInsert) . ") 
                                VALUES (" . implode(', ', $placeholders) . ")";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($values);
                        $newLessonId = $pdo->lastInsertId();
                        
                        $totalLessons++;
                        
                        // Videos kopieren (falls Tabelle existiert)
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM course_lesson_videos WHERE lesson_id = ? ORDER BY video_order");
                            $stmt->execute([$lesson['id']]);
                            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($videos as $video) {
                                $fieldsToInsert = [];
                                $values = [];
                                
                                foreach ($video as $field => $value) {
                                    if (!in_array($field, $excludedFields)) {
                                        if ($field === 'lesson_id') {
                                            $fieldsToInsert[] = $field;
                                            $values[] = $newLessonId;
                                        } else {
                                            $fieldsToInsert[] = $field;
                                            $values[] = $value;
                                        }
                                    }
                                }
                                
                                $fieldsToInsert[] = 'created_at';
                                $values[] = date('Y-m-d H:i:s');
                                
                                $placeholders = array_fill(0, count($values), '?');
                                $sql = "INSERT INTO course_lesson_videos (" . implode(', ', $fieldsToInsert) . ") 
                                        VALUES (" . implode(', ', $placeholders) . ")";
                                
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute($values);
                                $totalVideos++;
                            }
                        } catch (Exception $e) {
                            // Video-Tabelle existiert nicht oder ist leer
                        }
                    }
                }
            }
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH KOPIERT!</p>";
            echo "<p class='info'>📦 $totalModules Module kopiert</p>";
            echo "<p class='info'>📚 $totalLessons Lektionen kopiert</p>";
            if ($totalVideos > 0) {
                echo "<p class='info'>🎬 $totalVideos Videos kopiert</p>";
            }
            
            echo "<p><a href='/customer/dashboard.php?page=kurse' class='btn btn-secondary'>→ Zu Meine Kurse</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        
    } else {
        // BESTÄTIGUNG
        echo "<div class='box'>";
        echo "<h2>🚀 BEREIT ZUM KOPIEREN?</h2>";
        echo "<p><strong>Das wird passieren:</strong></p>";
        echo "<ul>";
        echo "<li>✅ " . count($sourceCourses) . " Kurs(e) kopieren</li>";
        
        foreach ($sourceCourses as $course) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
            $stmt->execute([$course['id']]);
            $moduleCount = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM course_lessons l
                JOIN course_modules m ON l.module_id = m.id
                WHERE m.course_id = ?
            ");
            $stmt->execute([$course['id']]);
            $lessonCount = $stmt->fetchColumn();
            
            echo "<li>✅ Kurs: $moduleCount Module + $lessonCount Lektionen</li>";
        }
        
        if ($existingCourses > 0) {
            echo "<li>ℹ️ Existierende Kurse bleiben erhalten (keine Überschreibung)</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='?confirm=yes' class='btn btn-primary'>🎓 JETZT KOPIEREN</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>