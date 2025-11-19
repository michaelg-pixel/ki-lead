<?php
/**
 * MANUAL FIX: Kopiert Marktplatz-Freebie manuell für Test-User
 * VERSION 2.0 - Mit korrekten Spalten!
 */

require_once '../config/database.php';

$userId = 20; // 12345t@abnehmen-fitness.com
$sourceFreebieId = 7; // Marktplatz-Freebie

echo "=== MANUAL FREEBIE COPY v2.0 ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Source-Freebie laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        die("ERROR: Source freebie not found!\n");
    }
    
    echo "✅ Source Freebie gefunden: " . $source['headline'] . "\n";
    echo "   Course ID: " . ($source['course_id'] ?? 'NULL') . "\n\n";
    
    // 2. Prüfen ob bereits kopiert
    $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE customer_id = ? AND copied_from_freebie_id = ?");
    $stmt->execute([$userId, $sourceFreebieId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "⚠️ Freebie wurde bereits für diesen User kopiert! ID: " . $existing['id'] . "\n";
        echo "Lösche alte Kopie...\n";
        
        // Alte Kopie löschen
        $stmt = $pdo->prepare("DELETE FROM customer_freebies WHERE id = ?");
        $stmt->execute([$existing['id']]);
        
        echo "✅ Alte Kopie gelöscht\n\n";
    }
    
    // 3. Freebie kopieren - NUR existierende Spalten!
    $uniqueId = bin2hex(random_bytes(16));
    $urlSlug = ($source['url_slug'] ?? '') . '-' . substr($uniqueId, 0, 8);
    
    echo "📋 Kopiere Freebie mit ALLEN Feldern...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebies (
            customer_id,
            niche,
            template_id,
            course_id,
            headline,
            subheadline,
            preheadline,
            bullet_points,
            cta_text,
            layout,
            background_color,
            primary_color,
            raw_code,
            unique_id,
            url_slug,
            mockup_image_url,
            video_url,
            video_format,
            freebie_type,
            thank_you_message,
            preheadline_font,
            preheadline_size,
            headline_font,
            headline_size,
            subheadline_font,
            subheadline_size,
            bulletpoints_font,
            bulletpoints_size,
            optin_display_mode,
            popup_message,
            cta_animation,
            font_heading,
            font_body,
            font_size,
            heading_font_size,
            body_font_size,
            bullet_icon_style,
            original_creator_id,
            copied_from_freebie_id,
            marketplace_enabled,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW()
        )
    ");
    
    $stmt->execute([
        $userId,                                           // customer_id
        $source['niche'] ?? 'sonstiges',                  // niche
        $source['template_id'],                           // template_id
        $source['course_id'],                             // course_id - KRITISCH!
        $source['headline'],                              // headline
        $source['subheadline'],                           // subheadline
        $source['preheadline'],                           // preheadline
        $source['bullet_points'],                         // bullet_points
        $source['cta_text'],                              // cta_text
        $source['layout'],                                // layout
        $source['background_color'],                      // background_color
        $source['primary_color'],                         // primary_color
        $source['raw_code'] ?? '',                        // raw_code
        $uniqueId,                                        // unique_id
        $urlSlug,                                         // url_slug
        $source['mockup_image_url'],                      // mockup_image_url
        $source['video_url'] ?? '',                       // video_url
        $source['video_format'] ?? 'widescreen',          // video_format
        'purchased',                                      // freebie_type
        $source['thank_you_message'],                     // thank_you_message
        $source['preheadline_font'],                      // preheadline_font
        $source['preheadline_size'],                      // preheadline_size
        $source['headline_font'],                         // headline_font
        $source['headline_size'],                         // headline_size
        $source['subheadline_font'],                      // subheadline_font
        $source['subheadline_size'],                      // subheadline_size
        $source['bulletpoints_font'],                     // bulletpoints_font
        $source['bulletpoints_size'],                     // bulletpoints_size
        $source['optin_display_mode'] ?? 'direct',        // optin_display_mode
        $source['popup_message'] ?? '',                   // popup_message
        $source['cta_animation'] ?? 'none',               // cta_animation
        $source['font_heading'] ?? 'Inter',               // font_heading
        $source['font_body'] ?? 'Inter',                  // font_body
        $source['font_size'],                             // font_size (JSON)
        $source['heading_font_size'],                     // heading_font_size
        $source['body_font_size'],                        // body_font_size
        $source['bullet_icon_style'] ?? 'standard',       // bullet_icon_style
        $source['customer_id'],                           // original_creator_id
        $sourceFreebieId                                  // copied_from_freebie_id
    ]);
    
    $copiedFreebieId = $pdo->lastInsertId();
    
    echo "✅ Freebie kopiert! ID: $copiedFreebieId\n";
    echo "   Course ID kopiert: " . ($source['course_id'] ?? 'NULL') . "\n\n";
    
    // 4. Videokurs kopieren (falls vorhanden)
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sourceCourse) {
        echo "📹 Kopiere Videokurs: " . $sourceCourse['title'] . "\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $copiedFreebieId, 
            $userId, 
            $sourceCourse['title'], 
            $sourceCourse['description'], 
            $sourceCourse['is_active']
        ]);
        $newCourseId = $pdo->lastInsertId();
        
        echo "✅ Kurs-Container erstellt: ID $newCourseId\n";
        
        // Module kopieren
        $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
        $stmt->execute([$sourceCourse['id']]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📦 Kopiere " . count($modules) . " Module...\n";
        
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
            $moduleMapping[$module['id']] = $pdo->lastInsertId();
        }
        
        echo "✅ Module kopiert\n";
        
        // Lektionen kopieren
        echo "📚 Kopiere Lektionen...\n";
        $totalLessons = 0;
        
        foreach ($moduleMapping as $oldModuleId => $newModuleId) {
            $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
            $stmt->execute([$oldModuleId]);
            $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($lessons as $lesson) {
                $stmt = $pdo->prepare("
                    INSERT INTO freebie_course_lessons (
                        module_id, title, description, video_url, pdf_url, 
                        sort_order, unlock_after_days, button_text, button_url, 
                        created_at, updated_at
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
                $totalLessons++;
            }
        }
        
        echo "✅ $totalLessons Lektionen kopiert\n\n";
        
    } else {
        echo "ℹ️ Kein Videokurs vorhanden\n\n";
    }
    
    // 5. Verkaufszähler erhöhen
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET marketplace_sales_count = marketplace_sales_count + 1 
        WHERE id = ?
    ");
    $stmt->execute([$sourceFreebieId]);
    
    echo "✅ Verkaufszähler erhöht\n\n";
    
    echo "═══════════════════════════════════════\n";
    echo "🎉 FERTIG! Freebie erfolgreich kopiert!\n";
    echo "═══════════════════════════════════════\n\n";
    
    echo "📊 ZUSAMMENFASSUNG:\n";
    echo "   • Freebie ID: $copiedFreebieId\n";
    echo "   • User ID: $userId\n";
    echo "   • Course ID: " . ($source['course_id'] ?? 'NULL') . "\n";
    echo "   • Videokurs: " . ($sourceCourse ? 'Ja (' . $totalLessons . ' Lektionen)' : 'Nein') . "\n\n";
    
    echo "🔗 LINKS:\n";
    echo "   • Freebie-Übersicht: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies\n";
    if ($sourceCourse) {
        echo "   • Videokurs: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=videokurse\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
