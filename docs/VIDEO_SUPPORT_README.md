# Video-Support für Customer Freebies

## Übersicht
Ab sofort können Kunden in ihren eigenen Freebies **Videos** statt Mockup-Bildern verwenden.

## Neue Funktionen

### 1. Video oder Bild wählen
- Radio Buttons: **Bild** oder **Video**
- Beim Wechsel werden die entsprechenden Felder ein-/ausgeblendet

### 2. Video-Einbindung
- **Video-URL**: YouTube, Vimeo, oder direkte MP4/WebM URLs
- **Video-Format**: 
  - **16:9** (Querformat) - Standard für YouTube/Vimeo
  - **9:16** (Hochformat) - Für TikTok/Instagram Reels

### 3. Automatische Erkennung
- YouTube URLs werden automatisch zu Embeds konvertiert
- Vimeo URLs werden automatisch zu Embeds konvertiert
- Direkte Video-URLs werden als HTML5 Video eingebettet

## Datenbankänderungen

```sql
ALTER TABLE customer_freebies 
ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER mockup_image_url,
ADD COLUMN video_format ENUM('16:9', '9:16') DEFAULT '16:9' AFTER video_url;
```

## Beispiel Video-URLs

### YouTube
```
https://www.youtube.com/watch?v=dQw4w9WgXcQ
https://youtu.be/dQw4w9WgXcQ
```

### Vimeo
```
https://vimeo.com/123456789
```

### Direkte Videos
```
https://example.com/video.mp4
https://example.com/video.webm
```

## Video-Formate

### 16:9 (Querformat)
- Breite: 560px
- Höhe: 315px
- Ideal für: YouTube, Vimeo, Standard-Videos

### 9:16 (Hochformat)
- Breite: 360px
- Höhe: 640px
- Ideal für: TikTok, Instagram Reels, Stories

## Änderungen in den Dateien

1. **customer/custom-freebie-editor.php**
   - Radio Buttons für Bild/Video Auswahl
   - Video-URL Eingabefeld
   - Video-Format Auswahl (16:9 / 9:16)
   - Live-Preview mit Video-Unterstützung

2. **freebie/index.php**
   - Video-Anzeige statt Mockup
   - YouTube/Vimeo Embed-Unterstützung
   - HTML5 Video für direkte URLs
   - Responsive Video-Container

## Migration

Führe das SQL-Script aus:
```bash
mysql -u username -p database_name < scripts/migrations/add_video_support_to_freebies.sql
```

## Hinweise

- Videos und Bilder schließen sich gegenseitig aus
- Wenn eine Video-URL eingegeben wird, wird das Mockup-Bild ignoriert
- Videos werden automatisch responsive eingebettet
- Bei YouTube/Vimeo werden die Tracking-Parameter entfernt
