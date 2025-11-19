<?php
// 🔧 SMART COPY: Kopiert nur existierende Spalten
header('Content-Type: text/html; charset=utf-8');

$config_path = dirname(__DIR__) . '/config/database.php';
require_once $config_path;

$test_email = 'testtest123@web.de';
$product_id = '639493';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Smart Freebie Copy</title></head><body>";
echo "<h1>🔧 Smart Freebie Copy v3</h1>";
echo "<hr>";

// 1. User finden
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ User nicht gefunden");
}

echo "<h2>✅ 1. USER GEFUNDEN</h2>";
echo "ID: {$user['id']}<br>";
echo "<hr>";

// 2. Source Freebie finden
$stmt = $pdo->prepare("
    SELECT * FROM customer_freebies 
    WHERE digistore_product_id = ? AND marketplace_enabled = 1
    LIMIT 1
");
$stmt->execute([$product_id]);
$source = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$source) {
    die("❌ Marktplatz-Freebie nicht gefunden");
}

echo "<h2>✅ 2. SOURCE FREEBIE GEFUNDEN</h2>";
echo "Freebie ID: {$source['id']}<br>";
echo "<hr>";

// 3. Tabellen-Struktur ermitteln
echo "<h2>3️⃣ TABELLEN-STRUKTUR ERMITTELN</h2>";
$stmt = $pdo->query("DESCRIBE customer_freebies");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'Field');

echo "Verfügbare Spalten: " . count($columnNames) . "<br>";
echo "<details><summary>Alle Spalten anzeigen</summary>";
echo implode(', ', $columnNames);
echo "</details><br><hr>";

// 4. Nur kopierbare Spalten filtern
$skipColumns = ['id', 'created_at', 'updated_at'];
$copyableColumns = array_diff($columnNames, $skipColumns);

// 5. Werte vorbereiten
$uniqueId = bin2hex(random_bytes(16));
$urlSlug = ($source['url_slug'] ?? 'freebie') . '-' . substr($uniqueId, 0, 8);

// Spezielle Werte überschreiben
$values = [];
foreach ($copyableColumns as $col) {
    if ($col === 'customer_id') {
        $values[$col] = $user['id'];
    } elseif ($col === 'unique_id') {
        $values[$col] = $uniqueId;
    } elseif ($col === 'url_slug') {
        $values[$col] = $urlSlug;
    } elseif ($col === 'freebie_type') {
        $values[$col] = 'purchased';
    } elseif ($col === 'original_creator_id') {
        $values[$col] = $source['customer_id'];
    } elseif ($col === 'copied_from_freebie_id') {
        $values[$col] = $source['id'];
    } elseif ($col === 'marketplace_enabled') {
        $values[$col] = 0;
    } elseif ($col === 'marketplace_sales_count') {
        $values[$col] = 0;
    } else {
        $values[$col] = $source[$col] ?? null;
    }
}

echo "<h2>4️⃣ FREEBIE KOPIEREN</h2>";

try {
    // SQL dynamisch bauen
    $cols = array_keys($values);
    $placeholders = array_fill(0, count($cols), '?');
    
    $sql = "INSERT INTO customer_freebies (" . implode(', ', $cols) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($values));
    
    $copiedId = $pdo->lastInsertId();
    
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px;'>";
    echo "<h3>✅ FREEBIE KOPIERT!</h3>";
    echo "Neue Freebie ID: <strong>$copiedId</strong><br>";
    echo "Kopierte Spalten: " . count($cols) . "<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 3px solid #dc3545; padding: 20px;'>";
    echo "<h3>❌ FEHLER!</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "</div>";
    die();
}

echo "<hr>";

// 5. VIDEOKURS KOPIEREN
echo "<h2>5️⃣ VIDEOKURS KOPIEREN</h2>";

$stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
$stmt->execute([$source['id']]);
$sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sourceCourse) {
    echo "ℹ️ Kein Videokurs vorhanden<br>";
} else {
    echo "✅ Videokurs gefunden: {$sourceCourse['title']}<br><br>";
    
    // Kurs kopieren
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $copiedId,
        $user['id'],
        $sourceCourse['title'],
        $sourceCourse['description'],
        $sourceCourse['is_active']
    ]);
    
    $newCourseId = $pdo->lastInsertId();
    echo "✅ Kurs-Container kopiert (ID: $newCourseId)<br>";
    
    // Module kopieren
    $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
    $stmt->execute([$sourceCourse['id']]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $moduleCount = 0;
    $lessonCount = 0;
    $moduleMapping = [];
    
    foreach ($modules as $module) {
        $stmt = $pdo->prepare("
            INSERT INTO freebie_course_modules (course_id, title, description, sort_order, unlock_after_days, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $newCourseId,
            $module['title'],
            $module['description'],
            $module['sort_order'],
            $module['unlock_after_days'] ?? 0
        ]);
        
        $newModuleId = $pdo->lastInsertId();
        $moduleMapping[$module['id']] = $newModuleId;
        $moduleCount++;
        
        // Lektionen kopieren
        $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
        $stmt->execute([$module['id']]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($lessons as $lesson) {
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons (module_id, title, description, video_url, pdf_url, sort_order, unlock_after_days, button_text, button_url, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $newModuleId,
                $lesson['title'],
                $lesson['description'],
                $lesson['video_url'],
                $lesson['pdf_url'] ?? null,
                $lesson['sort_order'],
                $lesson['unlock_after_days'] ?? 0,
                $lesson['button_text'] ?? null,
                $lesson['button_url'] ?? null
            ]);
            $lessonCount++;
        }
    }
    
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px;'>";
    echo "<h3>✅ VIDEOKURS KOPIERT!</h3>";
    echo "Module: $moduleCount<br>";
    echo "Lektionen: $lessonCount<br>";
    echo "</div>";
}

echo "<hr>";

// 6. Verkaufszähler
$stmt = $pdo->prepare("UPDATE customer_freebies SET marketplace_sales_count = marketplace_sales_count + 1 WHERE id = ?");
$stmt->execute([$source['id']]);

echo "<h2>6️⃣ VERKAUFSZÄHLER ERHÖHT</h2>";
echo "✅ Beim Original-Freebie aktualisiert<br>";

echo "<hr>";

// FERTIG
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 3px solid #667eea; padding: 40px; text-align: center; border-radius: 12px;'>";
echo "<h1 style='color: white; margin: 0;'>🎉 KOMPLETT FERTIG!</h1>";
echo "<p style='color: white; font-size: 18px; margin: 20px 0;'>Das Freebie + Videokurs wurden erfolgreich kopiert!</p>";
echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p style='margin: 5px 0;'><strong>Käufer:</strong> $test_email</p>";
echo "<p style='margin: 5px 0;'><strong>User ID:</strong> {$user['id']}</p>";
echo "<p style='margin: 5px 0;'><strong>Freebie ID:</strong> $copiedId</p>";
if (!empty($moduleCount)) {
    echo "<p style='margin: 5px 0;'><strong>Module:</strong> $moduleCount</p>";
    echo "<p style='margin: 5px 0;'><strong>Lektionen:</strong> $lessonCount</p>";
}
echo "</div>";
echo "<a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies' style='display: inline-block; background: white; color: #667eea; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>🚀 Zum Dashboard</a>";
echo "</div>";

echo "</body></html>";
?>
