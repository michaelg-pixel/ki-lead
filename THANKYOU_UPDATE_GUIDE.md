# Thankyou.php Update für Customer Freebie Kurse

## Änderungen in `/freebie/thankyou.php`

### Zeile ~40-60: Customer Freebie Kurs-Check hinzufügen

**Ersetze:**
```php
// Kurs-URL aus verknüpftem Kurs generieren
$video_course_url = '';
if (!empty($freebie['course_id'])) {
    $video_course_url = '/customer/course-view.php?id=' . $freebie['course_id'];
} elseif (!empty($freebie['video_course_url'])) {
    $video_course_url = $freebie['video_course_url'];
}
```

**Durch:**
```php
// 🆕 CUSTOMER FREEBIE KURS-CHECK
$video_course_url = '';
$lead_email = $_GET['email'] ?? '';

// Erst Customer Freebie Kurs prüfen
if ($is_customer_freebie && $freebie['has_course']) {
    $stmt = $pdo->prepare("SELECT id FROM freebie_courses WHERE freebie_id = ? AND is_active = TRUE");
    $stmt->execute([$freebie_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($course) {
        $video_course_url = '/customer/freebie-course-player.php?freebie_id=' . $freebie_id;
        if ($lead_email) {
            $video_course_url .= '&email=' . urlencode($lead_email);
        }
    }
}

// Fallback: Template-Kurs
if (empty($video_course_url) && !empty($freebie['course_id'])) {
    $video_course_url = '/customer/course-view.php?id=' . $freebie['course_id'];
} elseif (empty($video_course_url) && !empty($freebie['video_course_url'])) {
    $video_course_url = $freebie['video_course_url'];
}
```

### Zeile ~95-105: Mockup-Logik erweitern

**Ersetze:**
```php
// KORREKTUR: Mockup-Bild Logik
if ($is_customer_freebie) {
    $mockup_image = $freebie['mockup_image_url'] ?? $freebie['course_mockup'] ?? '';
} else {
    $mockup_image = $freebie['course_mockup'] ?? $freebie['mockup_image_url'] ?? '';
}
```

**Durch:**
```php
// 🆕 MOCKUP-LOGIK mit course_mockup_url
if ($is_customer_freebie) {
    // Priorität: course_mockup_url (Danke-Seite) > mockup_image_url (Optin) > course_mockup
    $mockup_image = $freebie['course_mockup_url'] ?? $freebie['mockup_image_url'] ?? $freebie['course_mockup'] ?? '';
} else {
    $mockup_image = $freebie['course_mockup'] ?? $freebie['mockup_image_url'] ?? '';
}
```

## Zusammenfassung

Diese Änderungen ermöglichen:
1. ✅ Automatische Erkennung von Customer Freebie-Kursen
2. ✅ Korrekte Player-URL mit E-Mail-Parameter
3. ✅ Mockup aus course_mockup_url (falls gesetzt)
4. ✅ Backward-kompatibel mit Template-Kursen

## Test-URLs

```
// Ohne Kurs (wie bisher)
https://app.mehr-infos-jetzt.de/freebie/thankyou.php?id=123&customer=4

// Mit Customer Freebie-Kurs
https://app.mehr-infos-jetzt.de/freebie/thankyou.php?id=123&customer=4&email=test@example.com

// Erwartetes Verhalten:
// - Button "Zum Videokurs" erscheint
// - Klick leitet weiter zu: /customer/freebie-course-player.php?freebie_id=123&email=test@example.com
```

## Installation

1. Öffne `/freebie/thankyou.php` in Editor
2. Suche die beiden Code-Blöcke oben
3. Ersetze sie durch die neuen Versionen
4. Speichern
5. Testen mit einem Freebie, das einen Kurs hat
