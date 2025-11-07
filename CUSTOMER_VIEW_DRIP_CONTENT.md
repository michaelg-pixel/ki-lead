# 📱 Customer View: Drip Content Integration

## Übersicht
Diese Anleitung zeigt, wie du das Drip Content Feature (zeitbasierte Freischaltung) in der Kunden-Ansicht implementierst.

---

## 🎯 Funktionen

1. **Gesperrte Lektionen anzeigen** mit Countdown
2. **Verbleibende Tage** bis zur Freischaltung
3. **Mehrere Videos** pro Lektion anzeigen
4. **Automatische Freischaltung** nach X Tagen

---

## 📝 Implementierung

### Schritt 1: Einschreibungsdatum erfassen

Wenn ein Kunde einen Kurs startet, muss das Einschreibungsdatum gespeichert werden.

Füge folgenden Code in die Datei ein, die den Kurszugriff gewährt (z.B. `customer/course.php` oder `freebie/course.php`):

```php
<?php
// Am Anfang der Datei, nachdem $user_id und $course_id bekannt sind

// Prüfen ob bereits eingeschrieben, wenn nicht -> einschreiben
$stmt = $conn->prepare("
    SELECT id, enrolled_at 
    FROM course_enrollments 
    WHERE user_id = ? AND course_id = ?
");
$stmt->execute([$user_id, $course_id]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enrollment) {
    // Erste Anmeldung - Einschreibungsdatum speichern
    $stmt = $conn->prepare("
        INSERT INTO course_enrollments (user_id, course_id, enrolled_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $course_id]);
    $enrollment_date = date('Y-m-d H:i:s');
} else {
    $enrollment_date = $enrollment['enrolled_at'];
}

// Tage seit Einschreibung berechnen
$days_since_enrollment = floor((time() - strtotime($enrollment_date)) / (60 * 60 * 24));
?>
```

---

### Schritt 2: Lektionen mit Freischaltungs-Status laden

Erweitere deine Lektion-Abfrage um den Freischaltungs-Status:

```php
<?php
// Lektionen mit Freischaltungs-Status laden
$stmt = $conn->prepare("
    SELECT 
        l.*,
        CASE 
            WHEN l.unlock_after_days IS NULL THEN 1
            WHEN l.unlock_after_days = 0 THEN 1
            WHEN DATEDIFF(NOW(), ce.enrolled_at) >= l.unlock_after_days THEN 1
            ELSE 0
        END as is_unlocked,
        CASE 
            WHEN l.unlock_after_days IS NOT NULL 
            AND DATEDIFF(NOW(), ce.enrolled_at) < l.unlock_after_days 
            THEN (l.unlock_after_days - DATEDIFF(NOW(), ce.enrolled_at))
            ELSE 0
        END as days_until_unlock
    FROM lessons l
    JOIN modules m ON l.module_id = m.id
    JOIN course_enrollments ce ON m.course_id = ce.course_id
    WHERE ce.user_id = ? 
    AND m.course_id = ?
    AND m.id = ?
    ORDER BY l.sort_order
");
$stmt->execute([$user_id, $course_id, $module_id]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Zusätzliche Videos pro Lektion laden
foreach ($lessons as $key => $lesson) {
    $stmt = $conn->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order");
    $stmt->execute([$lesson['id']]);
    $lessons[$key]['additional_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
```

---

### Schritt 3: Lektion mit Sperrstatus anzeigen

Beispiel HTML/PHP für die Lektions-Anzeige:

```php
<div class="space-y-4">
    <?php foreach ($lessons as $lesson): ?>
        <div class="lesson-card p-5 rounded-lg" 
             style="background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3);">
            
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-2">
                        <?= htmlspecialchars($lesson['title']) ?>
                    </h3>
                    <p class="text-gray-400 mb-3">
                        <?= htmlspecialchars($lesson['description']) ?>
                    </p>
                </div>
                
                <!-- Sperrstatus -->
                <div class="ml-4">
                    <?php if ($lesson['is_unlocked']): ?>
                        <div class="px-4 py-2 rounded-full flex items-center gap-2" 
                             style="background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.4); color: #86efac;">
                            <i class="fas fa-unlock"></i>
                            <span>Verfügbar</span>
                        </div>
                    <?php else: ?>
                        <div class="px-4 py-2 rounded-full flex items-center gap-2" 
                             style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171;">
                            <i class="fas fa-lock"></i>
                            <span>Gesperrt</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$lesson['is_unlocked']): ?>
                <!-- Countdown-Anzeige -->
                <div class="mt-4 p-4 rounded-lg" 
                     style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-3xl" style="color: #f87171;"></i>
                        <div>
                            <div class="text-white font-bold text-lg">
                                Noch <?= $lesson['days_until_unlock'] ?> Tag<?= $lesson['days_until_unlock'] != 1 ? 'e' : '' ?>
                            </div>
                            <div class="text-sm" style="color: #f87171;">
                                Verfügbar ab <?= date('d.m.Y', strtotime($enrollment_date . ' + ' . $lesson['unlock_after_days'] . ' days')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Lektion ist freigeschaltet - Videos anzeigen -->
                
                <?php if ($lesson['vimeo_url']): ?>
                    <!-- Hauptvideo -->
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-purple-400 mb-2">
                            <i class="fas fa-video mr-2"></i>Hauptvideo
                        </h4>
                        <div class="video-container">
                            <?php
                            // Vimeo oder YouTube Embed
                            $video_url = $lesson['vimeo_url'];
                            if (strpos($video_url, 'vimeo.com') !== false) {
                                preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches);
                                $video_id = $matches[1] ?? '';
                                if ($video_id) {
                                    echo '<div style="padding:56.25% 0 0 0;position:relative;">';
                                    echo '<iframe src="https://player.vimeo.com/video/' . $video_id . '" ';
                                    echo 'style="position:absolute;top:0;left:0;width:100%;height:100%;" ';
                                    echo 'frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                    echo '</div>';
                                }
                            } elseif (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                // YouTube Video ID extrahieren
                                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                                $video_id = $matches[1] ?? '';
                                if ($video_id) {
                                    echo '<div style="padding:56.25% 0 0 0;position:relative;">';
                                    echo '<iframe src="https://www.youtube.com/embed/' . $video_id . '" ';
                                    echo 'style="position:absolute;top:0;left:0;width:100%;height:100%;" ';
                                    echo 'frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($lesson['additional_videos'])): ?>
                    <!-- Zusätzliche Videos -->
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-purple-400 mb-3">
                            <i class="fas fa-film mr-2"></i>Weitere Videos (<?= count($lesson['additional_videos']) ?>)
                        </h4>
                        <div class="grid grid-cols-1 gap-4">
                            <?php foreach ($lesson['additional_videos'] as $video): ?>
                                <div class="p-4 rounded-lg" 
                                     style="background: rgba(22, 33, 62, 0.6); border: 1px solid rgba(168, 85, 247, 0.2);">
                                    <h5 class="text-white font-semibold mb-2">
                                        <?= htmlspecialchars($video['video_title']) ?>
                                    </h5>
                                    <div class="video-container">
                                        <?php
                                        $video_url = $video['video_url'];
                                        if (strpos($video_url, 'vimeo.com') !== false) {
                                            preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches);
                                            $video_id = $matches[1] ?? '';
                                            if ($video_id) {
                                                echo '<div style="padding:56.25% 0 0 0;position:relative;">';
                                                echo '<iframe src="https://player.vimeo.com/video/' . $video_id . '" ';
                                                echo 'style="position:absolute;top:0;left:0;width:100%;height:100%;" ';
                                                echo 'frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                                echo '</div>';
                                            }
                                        } elseif (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                                            $video_id = $matches[1] ?? '';
                                            if ($video_id) {
                                                echo '<div style="padding:56.25% 0 0 0;position:relative;">';
                                                echo '<iframe src="https://www.youtube.com/embed/' . $video_id . '" ';
                                                echo 'style="position:absolute;top:0;left:0;width:100%;height:100%;" ';
                                                echo 'frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($lesson['pdf_file']): ?>
                    <!-- PDF Download -->
                    <div class="mt-4">
                        <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf_file']) ?>" 
                           target="_blank" 
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-lg font-semibold transition-all"
                           style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); color: white;">
                            <i class="fas fa-file-pdf"></i>
                            <span>Arbeitsblatt herunterladen</span>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
```

---

## 🎨 Styling-Optionen

### Countdown-Timer mit Animation

Füge CSS für animierten Countdown hinzu:

```css
<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.countdown-pulse {
    animation: pulse 2s ease-in-out infinite;
}

.locked-lesson {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.locked-lesson::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(239, 68, 68, 0.05);
    border-radius: 0.5rem;
}
</style>
```

Verwende dann:
```html
<div class="countdown-pulse">
    <i class="fas fa-lock"></i> Gesperrt
</div>
```

---

## 📊 Fortschritts-Tracking

Optional: Zeige dem Kunden seinen Gesamt-Fortschritt:

```php
<?php
// Gesamtanzahl der Lektionen im Kurs
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_lessons,
    SUM(CASE 
        WHEN l.unlock_after_days IS NULL THEN 1
        WHEN l.unlock_after_days = 0 THEN 1
        WHEN DATEDIFF(NOW(), ce.enrolled_at) >= l.unlock_after_days THEN 1
        ELSE 0
    END) as unlocked_lessons
    FROM lessons l
    JOIN modules m ON l.module_id = m.id
    JOIN course_enrollments ce ON m.course_id = ce.course_id
    WHERE ce.user_id = ? AND m.course_id = ?
");
$stmt->execute([$user_id, $course_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

$total = $progress['total_lessons'];
$unlocked = $progress['unlocked_lessons'];
$percentage = $total > 0 ? round(($unlocked / $total) * 100) : 0;
?>

<!-- Fortschrittsanzeige -->
<div class="mb-6 p-5 rounded-lg" style="background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3);">
    <div class="flex items-center justify-between mb-3">
        <span class="text-white font-semibold">Kurs-Fortschritt</span>
        <span class="text-purple-400 font-bold"><?= $unlocked ?> / <?= $total ?> Lektionen</span>
    </div>
    <div class="w-full h-3 rounded-full overflow-hidden" style="background: rgba(168, 85, 247, 0.2);">
        <div class="h-full transition-all duration-500" 
             style="width: <?= $percentage ?>%; background: linear-gradient(90deg, #a855f7 0%, #ec4899 100%);"></div>
    </div>
    <div class="text-sm text-gray-400 mt-2">
        <?= $percentage ?>% freigeschaltet
    </div>
</div>
```

---

## ⚡ Performance-Tipps

### Caching für häufige Abfragen

```php
<?php
// Session-Cache für Einschreibungsdatum (reduziert DB-Queries)
if (!isset($_SESSION['course_enrollment_' . $course_id])) {
    $stmt = $conn->prepare("SELECT enrolled_at FROM course_enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['course_enrollment_' . $course_id] = $enrollment['enrolled_at'];
}

$enrollment_date = $_SESSION['course_enrollment_' . $course_id];
?>
```

---

## 🔔 Optional: Email-Benachrichtigungen

Sende dem Kunden eine Email, wenn neue Lektionen freigeschaltet werden:

```php
<?php
// Cronjob oder täglich ausführen
// Datei: cron/check-unlocked-lessons.php

require_once '../config/database.php';
$conn = getDBConnection();

// Finde heute neu freigeschaltete Lektionen
$stmt = $conn->query("
    SELECT 
        u.email,
        u.name,
        c.title as course_title,
        l.title as lesson_title,
        ce.enrolled_at
    FROM course_enrollments ce
    JOIN users u ON ce.user_id = u.id
    JOIN courses c ON ce.course_id = c.id
    JOIN modules m ON c.id = m.course_id
    JOIN lessons l ON m.id = l.module_id
    WHERE l.unlock_after_days IS NOT NULL
    AND DATEDIFF(NOW(), ce.enrolled_at) = l.unlock_after_days
");

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notifications as $notification) {
    // Email senden
    $to = $notification['email'];
    $subject = "Neue Lektion freigeschaltet: " . $notification['lesson_title'];
    $message = "Hallo " . $notification['name'] . ",\n\n";
    $message .= "im Kurs '" . $notification['course_title'] . "' wurde die Lektion ";
    $message .= "'" . $notification['lesson_title'] . "' für dich freigeschaltet!\n\n";
    $message .= "Viel Erfolg beim Lernen!";
    
    mail($to, $subject, $message);
}
?>
```

Setup Cronjob:
```bash
0 9 * * * /usr/bin/php /pfad/zu/cron/check-unlocked-lessons.php
```

---

## ✅ Testing-Checkliste

- [ ] Einschreibung wird korrekt gespeichert
- [ ] Gesperrte Lektionen werden angezeigt
- [ ] Countdown zeigt korrekte Tage
- [ ] Freischaltung erfolgt automatisch nach X Tagen
- [ ] Mehrere Videos werden angezeigt
- [ ] PDF-Download funktioniert
- [ ] Fortschrittsanzeige ist korrekt
- [ ] Responsive Design auf Mobile

---

## 🐛 Troubleshooting

### Problem: Alle Lektionen sind gesperrt
**Lösung:** Prüfe ob `course_enrollments` Eintrag existiert für den User.

### Problem: Countdown zeigt falsche Tage
**Lösung:** Prüfe Zeitzone in PHP und MySQL. Stelle sicher dass beide UTC oder dieselbe Zeitzone verwenden.

### Problem: Videos werden nicht angezeigt
**Lösung:** Prüfe ob URL korrekt geparst wird. Teste mit einfachem `var_dump($video_url)`.

---

**Viel Erfolg! 🎓**
