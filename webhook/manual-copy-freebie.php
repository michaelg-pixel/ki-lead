<?php
/**
 * MANUAL FIX: Kopiert Marktplatz-Freebie manuell für Test-User
 */

require_once '../config/database.php';

$userId = 20; // 12345t@abnehmen-fitness.com
$sourceFreebieId = 7; // Marktplatz-Freebie

echo "=== MANUAL FREEBIE COPY ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Source-Freebie laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        die("ERROR: Source freebie not found!\n");
    }
    
    echo "✅ Source Freebie gefunden: " . $source['headline'] . "\n\n";
    
    // 2. Prüfen ob bereits kopiert
    $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE customer_id = ? AND copied_from_freebie_id = ?");
    $stmt->execute([$userId, $sourceFreebieId]);
    
    if ($stmt->fetch()) {
        die("⚠️ Freebie wurde bereits für diesen User kopiert!\n");
    }
    
    // 3. Freebie kopieren
    $uniqueId = bin2hex(random_bytes(16));
    $urlSlug = ($source['url_slug'] ?? '') . '-' . substr($uniqueId, 0, 8);
    $courseId = $source['course_id'] ?? null;
    
    echo "📋 Kopiere Freebie...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebies (
            customer_id, template_id, freebie_type, headline, subheadline, preheadline,
            mockup_image_url, background_color, primary_color, cta_text, bullet_points,
            bullet_icon_style, layout, email_field_text, button_text, privacy_checkbox_text,
            thank_you_headline, thank_you_message, course_id, unique_id, url_slug, niche,
            raw_code, video_url, video_format, optin_display_mode, popup_message,
            cta_animation, font_heading, font_body, font_size, original_creator_id,
            copied_from_freebie_id, marketplace_enabled, created_at
        ) VALUES (
            ?, ?, 'purchased', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW()
        )
    ");
    
    $stmt->execute([
        $userId,
        $source['template_id'],
        $source['headline'],
        $source['subheadline'],
        $source['preheadline'],
        $source['mockup_image_url'],
        $source['background_color'],
        $source['primary_color'],
        $source['cta_text'],
        $source['bullet_points'],
        $source['bullet_icon_style'] ?? 'standard',
        $source['layout'],
        $source['email_field_text'],
        $source['button_text'],
        $source['privacy_checkbox_text'],
        $source['thank_you_headline'],
        $source['thank_you_message'],
        $courseId,
        $uniqueId,
        $urlSlug,
        $source['niche'] ?? 'sonstiges',
        $source['raw_code'] ?? '',
        $source['video_url'] ?? '',
        $source['video_format'] ?? 'widescreen',
        $source['optin_display_mode'] ?? 'direct',
        $source['popup_message'] ?? '',
        $source['cta_animation'] ?? 'none',
        $source['font_heading'] ?? 'Inter',
        $source['font_body'] ?? 'Inter',
        $source['font_size'] ?? null,
        $source['customer_id'],
        $sourceFreebieId
    ]);
    
    $copiedFreebieId = $pdo->lastInsertId();
    
    echo "✅ Freebie kopiert! ID: $copiedFreebieId\n\n";
    
    // 4. Videokurs kopieren (falls vorhanden)
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sourceCourse) {
        echo "📹 Kopiere Videokurs...\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$copiedFreebieId, $userId, $sourceCourse['title'], $sourceCourse['description'], $sourceCourse['is_active']]);
        $newCourseId = $pdo->lastInsertId();
        
        // Module kopieren
        $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
        $stmt->execute([$sourceCourse['id']]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $moduleMapping = [];
        foreach ($modules as $module) {
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_modules (course_id, title, description, sort_order, unlock_after_days, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$newCourseId, $module['title'], $module['description'], $module['sort_order'], $module['unlock_after_days'] ?? 0]);
            $moduleMapping[$module['id']] = $pdo->lastInsertId();
        }
        
        // Lektionen kopieren
        $totalLessons = 0;
        foreach ($moduleMapping as $oldModuleId => $newModuleId) {
            $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
            $stmt->execute([$oldModuleId]);
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
                $totalLessons++;
            }
        }
        
        echo "✅ Videokurs kopiert! Module: " . count($moduleMapping) . ", Lektionen: $totalLessons\n\n";
    } else {
        echo "ℹ️ Kein Videokurs vorhanden\n\n";
    }
    
    // 5. Verkaufszähler erhöhen
    $stmt = $pdo->prepare("UPDATE customer_freebies SET marketplace_sales_count = marketplace_sales_count + 1 WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    
    echo "✅ Verkaufszähler erhöht\n\n";
    
    echo "🎉 FERTIG! User kann jetzt das Freebie sehen.\n\n";
    echo "Freebie-Link: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
