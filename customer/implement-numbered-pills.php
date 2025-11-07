<?php
/**
 * IMPLEMENTIERUNG: Kompakte Numbered Pills für Video-Tabs
 * Option 2 - Perfekte Balance zwischen Übersicht und Kompaktheit
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden");
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Numbered Pills Implementation</title>
<style>body{font-family:sans-serif;padding:40px;background:#0a0a16;color:#e5e7eb;}</style></head><body>";
echo "<h1 style='color:#a855f7;'>🎬 Implementiere Kompakte Numbered Pills</h1>";

$content = file_get_contents($file);
$backup = $content;

// Schritt 1: Ersetze das komplette .video-tabs CSS
$old_tabs_css = '        /* VIDEO TABS - IMMER SICHTBAR & PROMINENT */
        .video-tabs {
            background: linear-gradient(180deg, #0a0a16, #1a1532);
            border-top: 2px solid var(--border);
            padding: 20px;
            display: flex;
            gap: 12px;
            overflow: hidden;      /* KEIN Scrollbalken - Tabs wrappen */
            flex-wrap: wrap;
            min-height: 80px;
        }';

$new_tabs_css = '        /* VIDEO TABS - KOMPAKTE NUMBERED PILLS */
        .video-tabs {
            background: linear-gradient(180deg, #0a0a16, #1a1532);
            border-top: 2px solid var(--border);
            padding: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }';

$content = str_replace($old_tabs_css, $new_tabs_css, $content);

// Schritt 2: Ersetze .video-tab CSS für kompakte Pills
$old_tab_css = '        .video-tab {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: rgba(168, 85, 247, 0.08);
            border: 2px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            flex-shrink: 0;
        }';

$new_tab_css = '        .video-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: rgba(168, 85, 247, 0.1);
            border: 2px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .video-tab-number {
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        
        .video-tab-label {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }';

$content = str_replace($old_tab_css, $new_tab_css, $content);

// Schritt 3: Update video-tab hover & active states
$old_hover = '        .video-tab:hover {
            background: rgba(168, 85, 247, 0.15);
            border-color: rgba(168, 85, 247, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.2);
        }';

$new_hover = '        .video-tab:hover {
            background: rgba(168, 85, 247, 0.2);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(168, 85, 247, 0.3);
        }';

$content = str_replace($old_hover, $new_hover, $content);

$old_active = '        .video-tab.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-color: var(--primary);
            color: white;
            box-shadow: 0 6px 24px rgba(168, 85, 247, 0.4);
        }';

$new_active = '        .video-tab.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-color: var(--primary);
            color: white;
            box-shadow: 0 6px 24px rgba(168, 85, 247, 0.4);
        }
        
        .video-tab.active .video-tab-number {
            background: rgba(255, 255, 255, 0.2);
        }';

$content = str_replace($old_active, $new_active, $content);

// Schritt 4: Entferne .video-tab-icon (alt)
$content = str_replace('        .video-tab-icon {
            font-size: 20px;
        }
        
        ', '', $content);

// Schritt 5: Update PHP Template für neue Struktur
$old_php_template = "                            <a href=\"?id=<?php echo \$course_id; ?>&lesson=<?php echo \$current_lesson['id']; ?>&video=0&_t=<?php echo \$cache_bust; ?>\" 
                               class=\"video-tab <?php echo \$selected_video_index === 0 ? 'active' : ''; ?>\">
                                <span class=\"video-tab-icon\">🎬</span>
                                <span>Hauptvideo</span>
                            </a>";

$new_php_template = "                            <a href=\"?id=<?php echo \$course_id; ?>&lesson=<?php echo \$current_lesson['id']; ?>&video=0&_t=<?php echo \$cache_bust; ?>\" 
                               class=\"video-tab <?php echo \$selected_video_index === 0 ? 'active' : ''; ?>\">
                                <div class=\"video-tab-number\">🎬</div>
                                <span class=\"video-tab-label\">Haupt</span>
                            </a>";

$content = str_replace($old_php_template, $new_php_template, $content);

// Update additional videos template
$old_additional = "                                <a href=\"?id=<?php echo \$course_id; ?>&lesson=<?php echo \$current_lesson['id']; ?>&video=<?php echo \$index + 1; ?>&_t=<?php echo \$cache_bust; ?>\" 
                                   class=\"video-tab <?php echo \$selected_video_index === (\$index + 1) ? 'active' : ''; ?>\">
                                    <span class=\"video-tab-icon\">📹</span>
                                    <span><?php echo htmlspecialchars(\$video['video_title'] ?: 'Video ' . (\$index + 1)); ?></span>
                                </a>";

$new_additional = "                                <a href=\"?id=<?php echo \$course_id; ?>&lesson=<?php echo \$current_lesson['id']; ?>&video=<?php echo \$index + 1; ?>&_t=<?php echo \$cache_bust; ?>\" 
                                   class=\"video-tab <?php echo \$selected_video_index === (\$index + 1) ? 'active' : ''; ?>\">
                                    <div class=\"video-tab-number\"><?php echo \$index + 1; ?></div>
                                    <span class=\"video-tab-label\"><?php echo htmlspecialchars(\$video['video_title'] ?: 'Video ' . (\$index + 1)); ?></span>
                                </a>";

$content = str_replace($old_additional, $new_additional, $content);

// Speichern
if ($content !== $backup) {
    file_put_contents($file . '.backup_pills_' . date('YmdHis'), $backup);
    file_put_contents($file, $content);
    
    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h2 style='color:#155724;'>✅ Numbered Pills erfolgreich implementiert!</h2>";
    echo "<h3 style='color:#155724;margin-top:15px;'>Was sich geändert hat:</h3>";
    echo "<ul style='color:#155724;'>";
    echo "<li><strong>Kompakte Pills:</strong> Kleinere Padding, enge Abstände</li>";
    echo "<li><strong>Nummerierte Badges:</strong> Runde Nummern (1, 2, 3) oder 🎬</li>";
    echo "<li><strong>Kürzere Labels:</strong> Max 150px Breite mit Ellipsis</li>";
    echo "<li><strong>Wrappen:</strong> Funktioniert perfekt ohne Scrollbalken</li>";
    echo "<li><strong>Hauptvideo:</strong> 🎬 Badge + 'Haupt' Label</li>";
    echo "<li><strong>Weitere Videos:</strong> Nummerierte Badges (1, 2, 3...)</li>";
    echo "</ul>";
    echo "<p style='color:#155724;margin-top:15px;'><strong>Backup erstellt:</strong> {$file}.backup_pills_" . date('YmdHis') . "</p>";
    echo "</div>";
    
    echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3 style='color:#856404;'>⚡ Cache leeren:</h3>";
    echo "<ol style='color:#856404;'>";
    echo "<li><strong>Strg + Shift + R</strong> (Hard Refresh)</li>";
    echo "<li>Oder: <strong>Privates Fenster</strong> (Strg + Shift + N)</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<a href='course-view.php?id=10&lesson=19&_cache=" . time() . "' style='display:inline-block;background:#a855f7;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-weight:bold;margin:20px 0;'>→ Neue Pills testen!</a>";
    
} else {
    echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;'>";
    echo "<h2 style='color:#856404;'>⚠️ Keine Änderungen</h2>";
    echo "<p style='color:#856404;'>Patterns nicht gefunden oder bereits geändert.</p>";
    echo "</div>";
}

echo "<hr style='margin:40px 0;border:1px solid rgba(168,85,247,0.2);'>";
echo "<h3 style='color:#a855f7;'>📋 Vorschau der neuen Struktur:</h3>";
echo "<div style='background:#1a1532;padding:20px;border-radius:8px;margin-top:20px;'>";
echo "<div style='display:flex;gap:10px;flex-wrap:wrap;'>";
echo "<div style='display:flex;align-items:center;gap:8px;padding:10px 16px;background:linear-gradient(135deg,#a855f7,#8b40d1);border-radius:8px;'>";
echo "<div style='width:28px;height:28px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;'>🎬</div>";
echo "<span style='color:white;font-weight:600;font-size:14px;'>Haupt</span>";
echo "</div>";
echo "<div style='display:flex;align-items:center;gap:8px;padding:10px 16px;background:rgba(168,85,247,0.1);border:2px solid rgba(168,85,247,0.3);border-radius:8px;'>";
echo "<div style='width:28px;height:28px;background:rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#e5e7eb;font-weight:700;font-size:13px;'>1</div>";
echo "<span style='color:#e5e7eb;font-weight:600;font-size:14px;'>Video 1</span>";
echo "</div>";
echo "<div style='display:flex;align-items:center;gap:8px;padding:10px 16px;background:rgba(168,85,247,0.1);border:2px solid rgba(168,85,247,0.3);border-radius:8px;'>";
echo "<div style='width:28px;height:28px;background:rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#e5e7eb;font-weight:700;font-size:13px;'>2</div>";
echo "<span style='color:#e5e7eb;font-weight:600;font-size:14px;'>Video 2</span>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>