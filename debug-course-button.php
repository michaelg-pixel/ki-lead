<?php
/**
 * Debug Script: Course Button Felder prüfen
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$course_id = 25;

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>Debug: Course Button</title>
    <style>
        body { 
            font-family: monospace; 
            background: #0a0a16; 
            color: #0f0; 
            padding: 40px; 
            line-height: 1.8;
        }
        h1 { color: #a855f7; }
        h2 { color: #c084fc; margin-top: 30px; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        pre { 
            background: #1a1532; 
            padding: 20px; 
            border: 2px solid #a855f7; 
            border-radius: 8px; 
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #a855f7;
            padding: 12px;
            text-align: left;
        }
        th {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
        }
    </style>
</head>
<body>";

echo "<h1>🔍 Debug: Course Button für Kurs #$course_id</h1>";

// 1. Prüfe ob Spalten existieren
echo "<h2>1️⃣ Datenbank-Spalten prüfen</h2>";
$stmt = $pdo->query("DESCRIBE courses");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasButtonText = false;
$hasButtonUrl = false;
$hasButtonNewWindow = false;

echo "<table>";
echo "<tr><th>Spalte</th><th>Typ</th><th>Status</th></tr>";
foreach ($columns as $col) {
    if ($col['Field'] === 'button_text') {
        $hasButtonText = true;
        echo "<tr><td>button_text</td><td>{$col['Type']}</td><td class='success'>✅ Vorhanden</td></tr>";
    }
    if ($col['Field'] === 'button_url') {
        $hasButtonUrl = true;
        echo "<tr><td>button_url</td><td>{$col['Type']}</td><td class='success'>✅ Vorhanden</td></tr>";
    }
    if ($col['Field'] === 'button_new_window') {
        $hasButtonNewWindow = true;
        echo "<tr><td>button_new_window</td><td>{$col['Type']}</td><td class='success'>✅ Vorhanden</td></tr>";
    }
}
echo "</table>";

if (!$hasButtonText || !$hasButtonUrl || !$hasButtonNewWindow) {
    echo "<p class='error'>❌ Nicht alle Button-Spalten existieren! Migration erneut ausführen.</p>";
}

// 2. Kurs laden
echo "<h2>2️⃣ Kurs aus Datenbank laden</h2>";
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    echo "<p class='error'>❌ Kurs nicht gefunden!</p>";
    exit;
}

echo "<table>";
echo "<tr><th>Feld</th><th>Wert</th><th>Status</th></tr>";
echo "<tr><td>ID</td><td>{$course['id']}</td><td>-</td></tr>";
echo "<tr><td>Titel</td><td>{$course['title']}</td><td>-</td></tr>";
echo "<tr><td>Typ</td><td>{$course['type']}</td><td>" . ($course['type'] === 'video' ? "<span class='success'>✅ Video-Kurs</span>" : "<span class='warning'>⚠️ PDF-Kurs (Button wird nur bei Video-Kursen angezeigt!)</span>") . "</td></tr>";

if (isset($course['button_text'])) {
    $hasText = !empty($course['button_text']);
    echo "<tr><td>button_text</td><td>" . ($hasText ? htmlspecialchars($course['button_text']) : "<em>LEER</em>") . "</td><td>" . ($hasText ? "<span class='success'>✅</span>" : "<span class='error'>❌ LEER</span>") . "</td></tr>";
} else {
    echo "<tr><td>button_text</td><td>-</td><td class='error'>❌ Spalte fehlt!</td></tr>";
}

if (isset($course['button_url'])) {
    $hasUrl = !empty($course['button_url']);
    echo "<tr><td>button_url</td><td>" . ($hasUrl ? htmlspecialchars($course['button_url']) : "<em>LEER</em>") . "</td><td>" . ($hasUrl ? "<span class='success'>✅</span>" : "<span class='error'>❌ LEER</span>") . "</td></tr>";
} else {
    echo "<tr><td>button_url</td><td>-</td><td class='error'>❌ Spalte fehlt!</td></tr>";
}

if (isset($course['button_new_window'])) {
    echo "<tr><td>button_new_window</td><td>{$course['button_new_window']}</td><td><span class='success'>✅</span></td></tr>";
} else {
    echo "<tr><td>button_new_window</td><td>-</td><td class='error'>❌ Spalte fehlt!</td></tr>";
}

echo "</table>";

// 3. Vollständige Rohdaten
echo "<h2>3️⃣ Vollständige Kurs-Daten (Rohdaten)</h2>";
echo "<pre>";
print_r($course);
echo "</pre>";

// 4. Bedingungsprüfung
echo "<h2>4️⃣ Anzeige-Bedingungen prüfen</h2>";

$showButton = false;
$reasons = [];

if ($course['type'] !== 'video') {
    $reasons[] = "❌ Kurs ist kein Video-Kurs (type = '{$course['type']}')";
} else {
    $reasons[] = "✅ Kurs ist ein Video-Kurs";
}

if (empty($course['button_text'])) {
    $reasons[] = "❌ button_text ist leer";
} else {
    $reasons[] = "✅ button_text ist ausgefüllt: '" . htmlspecialchars($course['button_text']) . "'";
}

if (empty($course['button_url'])) {
    $reasons[] = "❌ button_url ist leer";
} else {
    $reasons[] = "✅ button_url ist ausgefüllt: '" . htmlspecialchars($course['button_url']) . "'";
}

$showButton = ($course['type'] === 'video' && !empty($course['button_text']) && !empty($course['button_url']));

echo "<p><strong>Anzeige-Bedingung im Code:</strong></p>";
echo "<pre>if (!empty(\$course['button_text']) && !empty(\$course['button_url']))</pre>";

echo "<p><strong>Ergebnis:</strong></p>";
echo "<ul>";
foreach ($reasons as $reason) {
    echo "<li>$reason</li>";
}
echo "</ul>";

if ($showButton) {
    echo "<p class='success'><strong>✅ BUTTON SOLLTE ANGEZEIGT WERDEN!</strong></p>";
    
    echo "<h2>5️⃣ Vorschau des Buttons</h2>";
    echo "<div style='background: #1a1532; padding: 30px; border: 2px solid #a855f7; border-radius: 12px; margin: 20px 0;'>";
    echo "<div style='max-width: 600px;'>";
    echo "<a href='" . htmlspecialchars($course['button_url']) . "' 
             style='display: inline-flex; align-items: center; justify-content: center; gap: 12px; padding: 16px 40px; background: linear-gradient(135deg, #ec4899, #f59e0b); border: none; border-radius: 12px; color: white; font-size: 16px; font-weight: 700; text-decoration: none; box-shadow: 0 8px 24px rgba(236, 72, 153, 0.4);'
             " . ($course['button_new_window'] ? "target='_blank' rel='noopener noreferrer'" : "") . ">
            " . htmlspecialchars($course['button_text']) . " →
          </a>";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<p class='error'><strong>❌ BUTTON WIRD NICHT ANGEZEIGT</strong></p>";
    echo "<p class='warning'>Bitte fülle die fehlenden Felder aus und speichere den Kurs erneut!</p>";
}

// 6. Module und Lektionen prüfen
if ($course['type'] === 'video') {
    echo "<h2>6️⃣ Module & Lektionen prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $module_count = $stmt->fetchColumn();
    
    echo "<p>Module: <strong>$module_count</strong></p>";
    
    if ($module_count > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_lessons cl 
                              JOIN course_modules cm ON cl.module_id = cm.id 
                              WHERE cm.course_id = ?");
        $stmt->execute([$course_id]);
        $lesson_count = $stmt->fetchColumn();
        
        echo "<p>Lektionen: <strong>$lesson_count</strong></p>";
        
        if ($lesson_count > 0) {
            echo "<p class='success'>✅ Kurs hat Module und Lektionen</p>";
            echo "<p class='warning'>⚠️ Hinweis: Der Button wird nur angezeigt, wenn eine Lektion ausgewählt und freigeschaltet ist!</p>";
        } else {
            echo "<p class='error'>❌ Keine Lektionen vorhanden - Button kann nicht angezeigt werden</p>";
        }
    } else {
        echo "<p class='error'>❌ Keine Module vorhanden - Button kann nicht angezeigt werden</p>";
    }
}

// 7. Direkt-Links
echo "<h2>7️⃣ Nützliche Links</h2>";
echo "<ul>";
echo "<li><a href='/admin/dashboard.php?page=course-edit&id=$course_id' style='color: #60a5fa;'>Kurs bearbeiten (Admin)</a></li>";
echo "<li><a href='/customer/course-player.php?id=$course_id' style='color: #60a5fa;'>Course-Player öffnen</a></li>";
echo "<li><a href='/migrations/migrate_course_buttons.html' style='color: #60a5fa;'>Migration erneut ausführen</a></li>";
echo "</ul>";

echo "</body></html>";
?>
