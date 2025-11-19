<?php
// 🔧 FINAL COPY: Mit ENUM-Check
header('Content-Type: text/html; charset=utf-8');

$config_path = dirname(__DIR__) . '/config/database.php';
require_once $config_path;

$test_email = 'testtest123@web.de';
$product_id = '639493';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Final Copy</title></head><body>";
echo "<h1>🔧 Final Copy - Mit ENUM-Check</h1>";
echo "<hr>";

// 1. User finden
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("❌ User nicht gefunden");

echo "<h2>✅ USER</h2>ID: {$user['id']}<br><hr>";

// 2. Source finden
$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE digistore_product_id = ? AND marketplace_enabled = 1 LIMIT 1");
$stmt->execute([$product_id]);
$source = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$source) die("❌ Source nicht gefunden");

echo "<h2>✅ SOURCE</h2>Freebie ID: {$source['id']}<br><hr>";

// 3. ENUM-Werte für freebie_type prüfen
echo "<h2>3️⃣ ENUM-WERTE PRÜFEN</h2>";
$stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'freebie_type'");
$typeInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($typeInfo) {
    preg_match("/^enum\(\'(.*)\'\)$/", $typeInfo['Type'], $matches);
    $enumValues = explode("','", $matches[1] ?? '');
    echo "Erlaubte Werte für freebie_type: " . implode(', ', $enumValues) . "<br>";
    
    // Wähle passenden Wert
    if (in_array('marketplace', $enumValues)) {
        $freebieType = 'marketplace';
    } elseif (in_array('custom', $enumValues)) {
        $freebieType = 'custom';
    } else {
        $freebieType = $enumValues[0]; // Erster verfügbarer Wert
    }
    echo "Gewählter Wert: <strong>$freebieType</strong><br>";
} else {
    $freebieType = 'custom'; // Fallback
    echo "⚠️ Konnte ENUM nicht ermitteln, verwende: $freebieType<br>";
}

echo "<hr>";

// 4. Duplikat-Check
$stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE customer_id = ? AND copied_from_freebie_id = ?");
$stmt->execute([$user['id'], $source['id']]);
if ($stmt->fetch()) {
    die("<div style='background: #fff3cd; padding: 20px;'>⚠️ Bereits kopiert!</div>");
}

// 5. Tabellen-Struktur ermitteln
$stmt = $pdo->query("DESCRIBE customer_freebies");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'Field');

$skipColumns = ['id', 'created_at', 'updated_at'];
$copyableColumns = array_diff($columnNames, $skipColumns);

// 6. Werte vorbereiten
$uniqueId = bin2hex(random_bytes(16));
$urlSlug = ($source['url_slug'] ?? 'freebie') . '-' . substr($uniqueId, 0, 8);

$values = [];
foreach ($copyableColumns as $col) {
    if ($col === 'customer_id') {
        $values[$col] = $user['id'];
    } elseif ($col === 'unique_id') {
        $values[$col] = $uniqueId;
    } elseif ($col === 'url_slug') {
        $values[$col] = $urlSlug;
    } elseif ($col === 'freebie_type') {
        $values[$col] = $freebieType; // KORRIGIERT!
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

echo "<h2>4️⃣ KOPIERE FREEBIE</h2>";

try {
    $cols = array_keys($values);
    $placeholders = array_fill(0, count($cols), '?');
    
    $sql = "INSERT INTO customer_freebies (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($values));
    
    $copiedId = $pdo->lastInsertId();
    
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px;'>";
    echo "<h3>✅ FREEBIE KOPIERT!</h3>";
    echo "ID: <strong>$copiedId</strong><br>";
    echo "Type: $freebieType<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    die("<div style='background: #f8d7da; padding: 20px;'>❌ " . $e->getMessage() . "</div>");
}

echo "<hr>";

// 7. VIDEOKURS
echo "<h2>5️⃣ VIDEOKURS</h2>";
$stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
$stmt->execute([$source['id']]);
$sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sourceCourse) {
    echo "ℹ️ Kein Videokurs<br>";
} else {
    $stmt = $pdo->prepare("INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$copiedId, $user['id'], $sourceCourse['title'], $sourceCourse['description'], $sourceCourse['is_active']]);
    $newCourseId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
    $stmt->execute([$sourceCourse['id']]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $moduleCount = 0;
    $lessonCount = 0;
    $moduleMapping = [];
    
    foreach ($modules as $module) {
        $stmt = $pdo->prepare("INSERT INTO freebie_course_modules (course_id, title, description, sort_order, unlock_after_days, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$newCourseId, $module['title'], $module['description'], $module['sort_order'], $module['unlock_after_days'] ?? 0]);
        $moduleMapping[$module['id']] = $pdo->lastInsertId();
        $moduleCount++;
        
        $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
        $stmt->execute([$module['id']]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($lessons as $lesson) {
            $stmt = $pdo->prepare("INSERT INTO freebie_course_lessons (module_id, title, description, video_url, pdf_url, sort_order, unlock_after_days, button_text, button_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$moduleMapping[$module['id']], $lesson['title'], $lesson['description'], $lesson['video_url'], $lesson['pdf_url'] ?? null, $lesson['sort_order'], $lesson['unlock_after_days'] ?? 0, $lesson['button_text'] ?? null, $lesson['button_url'] ?? null]);
            $lessonCount++;
        }
    }
    
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px;'>";
    echo "<h3>✅ VIDEOKURS KOPIERT!</h3>";
    echo "Kurs ID: $newCourseId<br>";
    echo "Module: $moduleCount<br>";
    echo "Lektionen: $lessonCount<br>";
    echo "</div>";
}

echo "<hr>";

// Verkaufszähler
$stmt = $pdo->prepare("UPDATE customer_freebies SET marketplace_sales_count = marketplace_sales_count + 1 WHERE id = ?");
$stmt->execute([$source['id']]);
echo "<h2>6️⃣ VERKAUFSZÄHLER ERHÖHT</h2>";

// FERTIG
echo "<hr>";
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; border-radius: 12px;'>";
echo "<h1 style='color: white; margin: 0;'>🎉 ALLES FERTIG!</h1>";
echo "<p style='color: white; font-size: 20px; margin: 20px 0;'>Freebie + Videokurs erfolgreich kopiert!</p>";
echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>Email:</strong> $test_email</p>";
echo "<p><strong>Freebie ID:</strong> $copiedId</p>";
if (isset($moduleCount)) {
    echo "<p><strong>Module:</strong> $moduleCount</p>";
    echo "<p><strong>Lektionen:</strong> $lessonCount</p>";
}
echo "</div>";
echo "<a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies' style='display: inline-block; background: white; color: #667eea; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>🚀 ZUM DASHBOARD</a>";
echo "</div>";

echo "</body></html>";
?>
