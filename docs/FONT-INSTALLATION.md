# 🎨 Font Installation für KI Leadsystem

## Übersicht
Diese Anleitung hilft dir, alle Schriftarten lokal auf deinem Server zu installieren - komplett DSGVO-konform ohne externe Abhängigkeiten.

## 📋 Aktuelle Situation

**Momentan:** Fonts werden über Bunny Fonts geladen (EU-Server, bereits DSGVO-konform)
**Ziel:** Fonts komplett auf deinem eigenen Server hosten

---

## 🚀 Option 1: Automatischer Download (Empfohlen)

### Via SSH auf deinem Server:

```bash
# 1. Ins Projekt-Verzeichnis wechseln
cd /home/u123456789/domains/app.mehr-infos-jetzt.de/public_html

# 2. Download-Skript ausführbar machen
chmod +x scripts/download-fonts.sh

# 3. Fonts herunterladen
./scripts/download-fonts.sh
```

Das war's! Die Fonts sind jetzt auf deinem Server unter `/assets/fonts/`

---

## 📦 Option 2: Manuelle Installation

Falls das Skript nicht funktioniert, kannst du die Fonts auch manuell herunterladen:

### Schritt 1: Fonts herunterladen

Besuche [Google Webfonts Helper](https://gwfh.mranftl.com/fonts) und lade folgende Fonts herunter:

**Benötigte Fonts:**
- Inter (Gewichte: 300, 400, 500, 600, 700, 800)
- Poppins (Gewichte: 300, 400, 500, 600, 700, 800)
- Roboto (Gewichte: 300, 400, 500, 700, 900)
- Montserrat (Gewichte: 300, 400, 500, 600, 700, 800)
- Playfair Display (Gewichte: 400, 500, 600, 700, 800)
- Open Sans (Gewichte: 300, 400, 500, 600, 700, 800)
- Lato (Gewichte: 300, 400, 700, 900)

### Schritt 2: Fonts hochladen

1. Erstelle den Ordner `/assets/fonts/` auf deinem Server (falls nicht vorhanden)
2. Lade alle `.woff2` Dateien in diesen Ordner hoch
3. Stelle sicher, dass die Dateien die richtige Benennung haben (z.B. `inter-400.woff2`)

### Schritt 3: CSS aktualisieren

Die `fonts.css` ist bereits vorbereitet und zeigt auf die lokalen Dateien. Du musst nichts ändern!

---

## ✅ Überprüfung

Nach der Installation kannst du überprüfen, ob alles funktioniert:

```bash
# Anzahl der Font-Dateien prüfen (sollte ~45 sein)
ls -1 assets/fonts/*.woff2 | wc -l

# Gesamtgröße anzeigen (sollte ~2-3 MB sein)
du -sh assets/fonts/
```

### Browser-Test:
1. Öffne: `https://app.mehr-infos-jetzt.de/admin/freebie-edit.php?id=1`
2. Öffne Browser DevTools (F12) → Network Tab
3. Filtere nach "fonts"
4. Aktualisiere die Seite
5. Du solltest sehen, dass Fonts von deinem Server geladen werden (`/assets/fonts/...`)

---

## 🎯 Benötigte Font-Dateien

### Inter (6 Dateien)
- `inter-300.woff2` (Light)
- `inter-400.woff2` (Regular)
- `inter-500.woff2` (Medium)
- `inter-600.woff2` (SemiBold)
- `inter-700.woff2` (Bold)
- `inter-800.woff2` (ExtraBold)

### Poppins (6 Dateien)
- `poppins-300.woff2`
- `poppins-400.woff2`
- `poppins-500.woff2`
- `poppins-600.woff2`
- `poppins-700.woff2`
- `poppins-800.woff2`

### Roboto (5 Dateien)
- `roboto-300.woff2`
- `roboto-400.woff2`
- `roboto-500.woff2`
- `roboto-700.woff2`
- `roboto-900.woff2`

### Montserrat (6 Dateien)
- `montserrat-300.woff2`
- `montserrat-400.woff2`
- `montserrat-500.woff2`
- `montserrat-600.woff2`
- `montserrat-700.woff2`
- `montserrat-800.woff2`

### Playfair Display (5 Dateien)
- `playfair-400.woff2`
- `playfair-500.woff2`
- `playfair-600.woff2`
- `playfair-700.woff2`
- `playfair-800.woff2`

### Open Sans (6 Dateien)
- `opensans-300.woff2`
- `opensans-400.woff2`
- `opensans-500.woff2`
- `opensans-600.woff2`
- `opensans-700.woff2`
- `opensans-800.woff2`

### Lato (4 Dateien)
- `lato-300.woff2`
- `lato-400.woff2`
- `lato-700.woff2`
- `lato-900.woff2`

**Gesamt: 44 Font-Dateien**

---

## 📊 Speicherplatz

- **Geschätzte Größe:** ~2-3 MB
- **Format:** WOFF2 (beste Kompression)
- **Browser-Support:** Alle modernen Browser (IE11+)

---

## 🔒 DSGVO-Konformität

✅ **Ohne lokale Fonts:** Bunny Fonts (EU-Server, keine Tracking, Open Source)  
✅ **Mit lokalen Fonts:** 100% auf deinem Server, keine externe Verbindung

Beide Varianten sind DSGVO-konform!

---

## 🛠️ Troubleshooting

### Fonts werden nicht geladen
1. Überprüfe die Dateirechte: `chmod 644 assets/fonts/*.woff2`
2. Überprüfe den Pfad in der CSS-Datei
3. Leere den Browser-Cache (Strg+Shift+R)

### Fonts sehen anders aus
- Das ist normal! System-Fonts vs. Web-Fonts haben leichte Unterschiede
- Überprüfe in den DevTools, welche Font-Datei geladen wird

### Download-Skript funktioniert nicht
- Versuche wget: `wget https://fonts.bunny.net/inter/files/inter-latin-400-normal.woff2`
- Alternativ: Manuelle Installation via FTP

---

## 💡 Alternative: Bunny Fonts behalten

**Wichtig zu wissen:** 
Bunny Fonts ist bereits eine DSGVO-konforme Lösung:
- Server in der EU (Frankfurt, Deutschland)
- Keine Cookies
- Kein Tracking
- Open Source
- Kostenlos
- Schnelles CDN

Wenn du die aktuelle Lösung behältst, sparst du:
- Speicherplatz auf deinem Server
- Wartungsaufwand
- Server-Bandbreite

Die Entscheidung liegt bei dir! 🚀

---

## 📞 Support

Bei Fragen oder Problemen, siehe die Haupt-Dokumentation oder kontaktiere den Support.
