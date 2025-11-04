# Video-Support für Custom Freebie Editor

## 📹 Überblick

Der Custom Freebie Editor unterstützt jetzt die Integration von Videos (YouTube, Vimeo) in zwei Formaten:
- **Widescreen (16:9)** - Standard-Format für horizontale Videos
- **Hochformat (9:16)** - Optimiert für vertikale Videos (Stories, Reels, TikTok, etc.)

## ✅ Was wurde implementiert?

### 1. Datenbank-Erweiterungen

**Neue Spalten in `customer_freebies`:**
```sql
- video_url VARCHAR(500) NULL          -- Speichert die Video-URL
- video_format ENUM('portrait', 'widescreen') DEFAULT 'widescreen'  -- Bestimmt das Anzeigeformat
```

**Migration:**
- SQL-Datei: `database/migrations/2025-11-04_add_video_support_to_customer_freebies.sql`
- Browser-Script: `database/migrate-video-support.php`

### 2. Editor-Funktionen

**Custom Freebie Editor (`customer/custom-freebie-editor.php`):**

#### Neue Formularfelder:
- **Video URL**: Eingabefeld für YouTube/Vimeo Links
- **Video-Format-Auswahl**: Radio-Buttons für Widescreen (16:9) oder Hochformat (9:16)

#### Live-Vorschau:
- Videos werden in Echtzeit im Editor angezeigt
- Format-Änderungen werden sofort übernommen
- Video-Preview mit korrekter Aspect Ratio

#### Unterstützte Video-Plattformen:
- **YouTube**: `https://www.youtube.com/watch?v=...` oder `https://youtu.be/...`
- **Vimeo**: `https://vimeo.com/...`

## 🎨 Layout-Verhalten

### Widescreen (16:9)
```
Größe: 100% Breite, max. 560px
Höhe: 315px
Ideal für: Standard YouTube-Videos, Tutorials, Webinare
```

### Hochformat (9:16)
```
Breite: 315px
Höhe: 560px
Ideal für: Stories, Reels, TikTok-Videos, Shorts
```

## 🚀 Verwendung

### 1. Migration durchführen

**Option A - Browser (empfohlen):**
```
https://app.mehr-infos-jetzt.de/database/migrate-video-support.php
```

**Option B - SQL direkt:**
```bash
mysql -u username -p database < database/migrations/2025-11-04_add_video_support_to_customer_freebies.sql
```

### 2. Video im Editor hinzufügen

1. Öffne den Custom Freebie Editor: `https://app.mehr-infos-jetzt.de/customer/custom-freebie-editor.php`
2. Scrolle zum Abschnitt **"🎥 Video"**
3. Füge die Video-URL ein (YouTube oder Vimeo)
4. Wähle das Format:
   - **🖥️ Widescreen (16:9)** für horizontale Videos
   - **📱 Hochformat (9:16)** für vertikale Videos
5. Die Live-Vorschau zeigt das Video sofort an
6. Speichere das Freebie

### 3. Video entfernen

- Klicke auf **"🗑️ Video entfernen"** unter der Vorschau
- Oder lösche die URL im Eingabefeld

## 📝 Beispiele

### YouTube Video (Widescreen)
```
Video-URL: https://www.youtube.com/watch?v=dQw4w9WgXcQ
Format: Widescreen (16:9)
Ergebnis: Standard YouTube-Embed mit 560x315px
```

### Vimeo Video (Hochformat)
```
Video-URL: https://vimeo.com/123456789
Format: Hochformat (9:16)
Ergebnis: Vertikales Video mit 315x560px
```

## 🎯 Feature-Priorität

Im Freebie werden Medien in dieser Reihenfolge priorisiert:
1. **Video** (wenn vorhanden)
2. **Mockup-Bild** (wenn kein Video)
3. **Standard-Icon** 🎁 (wenn weder Video noch Mockup)

## 🔧 Technische Details

### Video-URL-Erkennung

**JavaScript-Funktion im Editor:**
```javascript
function getVideoEmbedUrl(url) {
    // YouTube-Erkennung
    let youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
    if (youtubeMatch) {
        return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
    }
    
    // Vimeo-Erkennung
    let vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
    if (vimeoMatch) {
        return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
    }
    
    return null;
}
```

### Datenbank-Speicherung

```php
// In customer/custom-freebie-editor.php
$video_url = trim($_POST['video_url'] ?? '');
$video_format = $_POST['video_format'] ?? 'widescreen';

$stmt = $pdo->prepare("
    INSERT INTO customer_freebies (..., video_url, video_format, ...)
    VALUES (..., ?, ?, ...)
");
$stmt->execute([..., $video_url, $video_format, ...]);
```

## 📱 Responsive Design

### Desktop
- Widescreen: Volle Breite bis max. 560px
- Hochformat: Zentriert mit fester Größe 315x560px

### Tablet (< 968px)
- Beide Formate werden zentriert angezeigt
- Maximale Breite angepasst

### Mobile (< 768px)
- Videos werden auf 100% Breite skaliert
- Aspect Ratio bleibt erhalten
- Hochformat-Videos bleiben vertikal

## ⚠️ Wichtige Hinweise

1. **Video-Links müssen gültig sein**: Stelle sicher, dass die URL von YouTube oder Vimeo ist
2. **Format-Auswahl**: Wähle das richtige Format für dein Video
3. **Mockup-Bilder**: Videos haben Vorrang vor Mockup-Bildern
4. **Performance**: Videos werden als iframe eingebettet (externe Ressource)

## 🔄 Kompatibilität

### Unterstützte Browser:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Mobile Geräte:
- iOS 14+
- Android 8+

## 🐛 Troubleshooting

### Video wird nicht angezeigt?
1. Prüfe, ob die Video-URL korrekt ist
2. Teste die URL im Browser
3. Stelle sicher, dass das Video öffentlich zugänglich ist
4. Prüfe die Browser-Konsole auf Fehler

### Falsches Format?
1. Gehe zurück zum Editor
2. Wähle das richtige Format
3. Speichere das Freebie erneut

### Migration-Fehler?
1. Prüfe Datenbankverbindung
2. Stelle sicher, dass du Schreibrechte hast
3. Führe die Migration erneut aus

## 📚 Weitere Ressourcen

- [YouTube Embed API](https://developers.google.com/youtube/iframe_api_reference)
- [Vimeo Player API](https://developer.vimeo.com/player/sdk)
- [Custom Freebie Editor Dokumentation](../CUSTOMER_FREEBIES_README.md)

## 🎉 Changelog

**Version 1.1.0** (2025-11-04)
- ✅ Video-URL Feld hinzugefügt
- ✅ Video-Format-Auswahl (Widescreen/Hochformat)
- ✅ Live-Vorschau im Editor
- ✅ YouTube und Vimeo Support
- ✅ Responsive Design
- ✅ Datenbank-Migration
- ✅ Browser-basiertes Migrations-Script

**Version 1.0.0** (Ursprüngliche Version)
- Video-Support Basis-Implementierung

---

**Entwickelt für:** KI Leadsystem  
**Datum:** 04.11.2025  
**Version:** 1.1.0
