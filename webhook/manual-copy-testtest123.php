<?php
// 🔧 MANUELL: Marktplatz-Freebie für testtest123@web.de kopieren
// VERSION 2: Korrigiert für customer_freebies Struktur
header('Content-Type: text/html; charset=utf-8');

$config_path = dirname(__DIR__) . '/config/database.php';
require_once $config_path;

$test_email = 'testtest123@web.de';
$product_id = '639493';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Manuelles Freebie kopieren</title></head><body>";
echo "<h1>🔧 Manuelles Freebie kopieren v2</h1>";
echo "<hr>";

// 1. User finden
echo "<h2>1️⃣ USER FINDEN</h2>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ User nicht gefunden: $test_email");
}

echo "✅ User gefunden!<br>";
echo "ID: {$user['id']}<br>";
echo "Name: {$user['name']}<br>";
echo "Email: {$user['email']}<br>";
echo "<hr>";

// 2. Marktplatz-Freebie in customer_freebies suchen
echo "<h2>2️⃣ MARKTPLATZ-FREEBIE SUCHEN</h2>";
echo "Suche in customer_freebies nach Produkt-ID: <strong>$product_id</strong><br><br>";

$stmt = $pdo->prepare("
    SELECT * FROM customer_freebies 
    WHERE digistore_product_id = ? 
    AND marketplace_enabled = 1
    LIMIT 1
");
$stmt->execute([$product_id]);
$source_freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$source_freebie) {
    echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 15px; margin: 10px 0;'>";
    echo "<strong>❌ NICHT GEFUNDEN!</strong><br>";
    echo "Es gibt kein Marktplatz-Freebie mit Produkt-ID $product_id!<br><br>";
    
    // Alle verfügbaren anzeigen
    $stmt = $pdo->query("SELECT id, customer_id, headline, digistore_product_id FROM customer_freebies WHERE marketplace_enabled = 1");
    $all_mp = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_mp)) {
        echo "❌ KEINE Marktplatz-Freebies gefunden!<br>";
    } else {
        echo "<strong>Verfügbare Marktplatz-Freebies:</strong><br>";
        echo "<ul>";
        foreach ($all_mp as $mp) {
            echo "<li>ID: {$mp['id']} | Headline: {$mp['headline']} | Produkt-ID: {$mp['digistore_product_id']}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 15px; margin: 10px 0;'>";
echo "<strong>✅ GEFUNDEN!</strong><br>";
echo "Freebie ID: {$source_freebie['id']}<br>";
echo "Verkäufer (Customer ID): {$source_freebie['customer_id']}<br>";
echo "Headline: {$source_freebie['headline']}<br>";
echo "Template ID: {$source_freebie['template_id']}<br>";
echo "Produkt-ID: {$source_freebie['digistore_product_id']}<br>";
echo "Preis: {$source_freebie['marketplace_price']} €<br>";
echo "</div>";
echo "<hr>";

// 3. Prüfen ob bereits kopiert
echo "<h2>3️⃣ DUPLIKAT-CHECK</h2>";
$stmt = $pdo->prepare("
    SELECT id FROM customer_freebies 
    WHERE customer_id = ? AND copied_from_freebie_id = ?
");
$stmt->execute([$user['id'], $source_freebie['id']]);
$existing = $stmt->fetch();

if ($existing) {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px;'>";
    echo "⚠️ Freebie wurde bereits kopiert!<br>";
    echo "Kopiertes Freebie ID: {$existing['id']}<br>";
    echo "<a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies' style='display: inline-block; background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Zum Dashboard</a>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "✅ Noch nicht kopiert - kann fortfahren!<br>";
echo "<hr>";

// 4. FREEBIE KOPIEREN
echo "<h2>4️⃣ FREEBIE KOPIEREN</h2>";

$uniqueId = bin2hex(random_bytes(16));
$urlSlug = ($source_freebie['url_slug'] ?? 'freebie') . '-' . substr($uniqueId, 0, 8);

try {
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebies (
            customer_id,
            template_id,
            freebie_type,
            headline,
            subheadline,
            preheadline,
            mockup_image_url,
            background_color,
            primary_color,
            cta_text,
            bullet_points,
            bullet_icon_style,
            layout,
            email_field_text,
            button_text,
            privacy_checkbox_text,
            thank_you_headline,
            thank_you_message,
            unique_id,
            url_slug,
            niche,
            original_creator_id,
            copied_from_freebie_id,
            marketplace_enabled,
            created_at
        ) VALUES (
            ?, ?, 'purchased', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW()
        )
    ");
    
    $stmt->execute([
        $user['id'],                                    // customer_id
        $source_freebie['template_id'],                 // template_id
        $source_freebie['headline'],                    // headline
        $source_freebie['subheadline'],                 // subheadline
        $source_freebie['preheadline'],                 // preheadline
        $source_freebie['mockup_image_url'],            // mockup_image_url
        $source_freebie['background_color'],            // background_color
        $source_freebie['primary_color'],               // primary_color
        $source_freebie['cta_text'],                    // cta_text
        $source_freebie['bullet_points'],               // bullet_points
        $source_freebie['bullet_icon_style'] ?? 'standard', // bullet_icon_style
        $source_freebie['layout'],                      // layout
        $source_freebie['email_field_text'],            // email_field_text
        $source_freebie['button_text'],                 // button_text
        $source_freebie['privacy_checkbox_text'],       // privacy_checkbox_text
        $source_freebie['thank_you_headline'],          // thank_you_headline
        $source_freebie['thank_you_message'],           // thank_you_message
        $uniqueId,                                      // unique_id
        $urlSlug,                                       // url_slug
        $source_freebie['niche'] ?? 'sonstiges',        // niche
        $source_freebie['customer_id'],                 // original_creator_id
        $source_freebie['id']                           // copied_from_freebie_id
    ]);
    
    $copiedId = $pdo->lastInsertId();
    
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px; margin: 10px 0;'>";
    echo "<h3>✅ FREEBIE ERFOLGREICH KOPIERT!</h3>";
    echo "Neue Freebie ID: <strong>$copiedId</strong><br>";
    echo "Käufer (Customer ID): {$user['id']}<br>";
    echo "Source Freebie ID: {$source_freebie['id']}<br>";
    echo "Verkäufer (Customer ID): {$source_freebie['customer_id']}<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 3px solid #dc3545; padding: 20px;'>";
    echo "<h3>❌ FEHLER BEIM KOPIEREN!</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "<hr>";

// 5. VIDEOKURS KOPIEREN (falls vorhanden)
echo "<h2>5️⃣ VIDEOKURS PRÜFEN UND KOPIEREN</h2>";

$stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
$stmt->execute([$source_freebie['id']]);
$sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sourceCourse) {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px;'>";
    echo "ℹ️ Kein Videokurs vorhanden beim Source-Freebie<br>";
    echo "Das Freebie wurde ohne Videokurs kopiert.";
    echo "</div>";
} else {
    echo "<div style='background: #e7f3ff; border: 2px solid #0066cc; padding: 15px;'>";
    echo "✅ Videokurs gefunden: <strong>{$sourceCourse['title']}</strong><br>";
    echo "Beschreibung: {$sourceCourse['description']}<br>";
    echo "</div><br>";
    
    // Kurs-Container kopieren
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (
            freebie_id, customer_id, title, description, is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $copiedId,
        $user['id'],
        $sourceCourse['title'],
        $sourceCourse['description'],
        $sourceCourse['is_active']
    ]);
    
    $newCourseId = $pdo->lastInsertId();
    echo "✅ Kurs-Container kopiert (ID: $newCourseId)<br><br>";
    
    // Module kopieren
    $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
    $stmt->execute([$sourceCourse['id']]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $moduleCount = 0;
    $lessonCount = 0;
    $moduleMapping = [];
    
    echo "<strong>Module kopieren:</strong><br>";
    foreach ($modules as $module) {
        $stmt = $pdo->prepare("
            INSERT INTO freebie_course_modules (
                course_id, title, description, sort_order, unlock_after_days, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
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
        
        echo "- Modul {$moduleCount}: {$module['title']}<br>";
        
        // Lektionen kopieren
        $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
        $stmt->execute([$module['id']]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($lessons as $lesson) {
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons (
                    module_id, title, description, video_url, pdf_url, sort_order, 
                    unlock_after_days, button_text, button_url, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
            echo "&nbsp;&nbsp;• Lektion: {$lesson['title']}<br>";
        }
    }
    
    echo "<br>";
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px; margin: 10px 0;'>";
    echo "<h3>✅ VIDEOKURS KOMPLETT KOPIERT!</h3>";
    echo "Kurs ID: $newCourseId<br>";
    echo "Module: $moduleCount<br>";
    echo "Lektionen: $lessonCount<br>";
    echo "</div>";
}

echo "<hr>";

// 6. Verkaufszähler erhöhen
echo "<h2>6️⃣ VERKAUFSZÄHLER ERHÖHEN</h2>";
$stmt = $pdo->prepare("
    UPDATE customer_freebies 
    SET marketplace_sales_count = marketplace_sales_count + 1
    WHERE id = ?
");
$stmt->execute([$source_freebie['id']]);

echo "✅ Verkaufszähler beim Original-Freebie erhöht<br>";
echo "<hr>";

// 7. FERTIG!
echo "<h2>7️⃣ FERTIG!</h2>";
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 3px solid #667eea; padding: 40px; text-align: center; border-radius: 12px;'>";
echo "<h1 style='color: white; margin: 0;'>🎉 ERFOLGREICH!</h1>";
echo "<p style='color: white; font-size: 18px; margin: 20px 0;'>Das Marktplatz-Freebie wurde komplett kopiert!</p>";
echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p style='margin: 5px 0;'><strong>Käufer:</strong> $test_email</p>";
echo "<p style='margin: 5px 0;'><strong>Freebie ID:</strong> $copiedId</p>";
echo "<p style='margin: 5px 0;'><strong>Module:</strong> " . ($moduleCount ?? 0) . "</p>";
echo "<p style='margin: 5px 0;'><strong>Lektionen:</strong> " . ($lessonCount ?? 0) . "</p>";
echo "</div>";
echo "<a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies' style='display: inline-block; background: white; color: #667eea; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>🚀 Zum Customer Dashboard</a>";
echo "</div>";

echo "</body></html>";
?>
