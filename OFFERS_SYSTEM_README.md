# 🎯 Offers-System - Angebots-Laufschrift

## 📋 Übersicht

Das Offers-System ermöglicht es dir, ansprechende Angebots-Laufschriften im Customer Dashboard anzuzeigen. Die Laufschrift erscheint prominent zwischen dem Welcome-Banner und den Statistik-Boxen.

## ✨ Features

- **Admin-Verwaltung**: Erstelle, bearbeite und verwalte Angebote über das Admin-Dashboard
- **Laufschrift mit Fade-Effekt**: Sanfte transparente Ränder für bessere Lesbarkeit
- **CTA-Button**: Prominenter Button links neben der Laufschrift
- **Aktivierung/Deaktivierung**: Schalte Angebote schnell an/aus
- **Responsive Design**: Optimiert für Desktop und Mobile
- **Vorschau-Funktion**: Sieh dir an, wie das Angebot aussehen wird

## 🚀 Installation

### Schritt 1: Datenbanktabelle erstellen

Rufe folgende URL in deinem Browser auf:

```
https://app.mehr-infos-jetzt.de/install-offers-system.php
```

Das Skript wird automatisch:
- Die `offers` Tabelle in der Datenbank erstellen
- Ein Standard-Beispielangebot hinzufügen
- Den Installationsstatus anzeigen

### Schritt 2: Admin-Zugriff prüfen

Nach der Installation findest du im Admin-Dashboard einen neuen Menüpunkt:

```
Admin Dashboard → Angebote
```

## 📝 Angebote verwalten

### Neues Angebot erstellen

1. Gehe zu `Admin Dashboard → Angebote`
2. Fülle das Formular aus:
   - **Titel**: Kurze, prägnante Überschrift (z.B. "Neu: KI Avatar Business Masterclass")
   - **Beschreibung**: Der Text, der als Laufschrift angezeigt wird
   - **Button-Text**: Text auf dem CTA-Button (z.B. "Jetzt starten")
   - **Button-Link**: Ziel-URL des Buttons
   - **Aktiv**: Häkchen setzen, um das Angebot sofort anzuzeigen

3. Klicke auf "Angebot erstellen"

### Angebot bearbeiten

1. Klicke auf das Bearbeiten-Icon (✏️) neben einem Angebot
2. Ändere die gewünschten Felder
3. Klicke auf "Speichern"

### Angebot aktivieren/deaktivieren

- Klicke auf das Power-Icon (⚡) um ein Angebot schnell an/auszuschalten
- Nur **ein aktives Angebot** wird gleichzeitig im Customer Dashboard angezeigt
- Das neueste aktive Angebot wird bevorzugt

### Angebot löschen

1. Klicke auf das Papierkorb-Icon (🗑️)
2. Bestätige die Löschung

## 🎨 Design & Darstellung

### Im Customer Dashboard

Die Laufschrift erscheint:
- **Position**: Zwischen Welcome-Banner und Statistik-Boxen
- **Layout**: CTA-Button links, Laufschrift rechts daneben
- **Animation**: Langsame, kontinuierliche Bewegung von rechts nach links
- **Effekte**: 
  - Transparente Fade-Ränder links und rechts
  - Gradient-Hintergrund (Purple → Blue)
  - Hover-Effekt zum Pausieren

### Responsive Verhalten

- **Desktop**: Button und Laufschrift nebeneinander
- **Mobile**: Optimierte Größen und Abstände
- **Touch-Geräte**: Tap auf Button funktioniert einwandfrei

## 🔧 Datenbank-Schema

```sql
CREATE TABLE offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    button_text VARCHAR(100) NOT NULL DEFAULT 'Jetzt ansehen',
    button_link VARCHAR(500) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

## 💡 Best Practices

### Titel

- Halte ihn kurz und prägnant (max. 50 Zeichen)
- Nutze aktionsorientierte Wörter: "Neu", "Limitiert", "Exklusiv"
- Verwende Emojis sparsam (max. 1-2)

**Gut:**
```
Neu: KI Avatar Business Masterclass
Limitiert: 50% Rabatt bis Montag 🔥
Exklusiv: Kostenloser Bonus für Mitglieder
```

**Schlecht:**
```
Hier ist unser neues tolles Angebot für dich
Schaue dir mal unseren neuen Kurs an!!!
```

### Beschreibung (Lauftext)

- Ideal: 100-150 Zeichen
- Klare Nutzenversprechen
- Call-to-Action integrieren
- Dringlichkeit schaffen (optional)

**Gut:**
```
Lerne, wie du mit KI-Avataren automatisierte Geschäfte aufbaust. Jetzt 50% Rabatt für Mitglieder!
Erhalte Zugang zu 10+ Premium-Templates. Nur noch 48 Stunden verfügbar!
```

**Schlecht:**
```
Wir haben etwas Neues für dich.
Klicke hier für mehr Informationen über unser Angebot.
```

### Button-Text

- Kurz und aktionsorientiert
- Max. 20 Zeichen
- Imperativ verwenden

**Gut:**
```
Jetzt starten
Mehr erfahren
Kostenlos testen
Angebot sichern
```

**Schlecht:**
```
Hier klicken für weitere Informationen
Vielleicht interessiert dich das
```

## 📊 Anzeige-Logik

- Nur **aktive** Angebote (`is_active = 1`) werden angezeigt
- Bei mehreren aktiven Angeboten: **Neuestes** wird gewählt
- Kein aktives Angebot: Laufschrift wird nicht angezeigt
- Update in Echtzeit: Änderungen sind sofort sichtbar

## 🐛 Troubleshooting

### Problem: Laufschrift wird nicht angezeigt

**Lösung:**
1. Prüfe, ob mindestens ein Angebot auf "Aktiv" gesetzt ist
2. Überprüfe in phpMyAdmin, ob die `offers` Tabelle existiert
3. Teste mit einem neuen Angebot
4. Leere Browser-Cache (Strg + F5)

### Problem: Button-Link funktioniert nicht

**Lösung:**
1. Stelle sicher, dass die URL mit `http://` oder `https://` beginnt
2. Teste den Link in einem neuen Tab
3. Prüfe auf Tippfehler in der URL

### Problem: Laufschrift ist zu schnell/langsam

**Lösung:**
Passe die Animation in `customer/sections/overview.php` an:

```css
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

.marquee-content {
    animation: marquee 25s linear infinite; /* Ändere 25s auf gewünschte Geschwindigkeit */
}
```

- **Langsamer**: Höhere Zahl (z.B. 30s, 40s)
- **Schneller**: Niedrigere Zahl (z.B. 15s, 20s)

## 📱 Tracking (Optional)

Du kannst Klicks auf den Offer-Button tracken, indem du das Tracking-System erweiterst:

```javascript
// Bereits implementiert in overview.php
document.querySelector('[data-track="offer-button"]')
```

## 🎯 Use Cases

### 1. Neue Kurs-Launches

```
Titel: 🚀 Neu: Instagram Reels Masterclass
Beschreibung: Lerne, wie du virale Reels erstellst und deine Reichweite verdoppelst. Jetzt mit Early-Bird-Rabatt!
Button: Zum Kurs
Link: https://mehr-infos-jetzt.de/courses/instagram-reels
```

### 2. Limitierte Angebote

```
Titel: ⏰ 48h Flash-Sale: 50% auf alles!
Beschreibung: Sichere dir jetzt alle Premium-Templates zum halben Preis. Nur noch 48 Stunden!
Button: Jetzt sparen
Link: https://mehr-infos-jetzt.de/sale
```

### 3. Feature-Announcements

```
Titel: ✨ Neu: KI-Assistent verfügbar
Beschreibung: Erstelle jetzt automatisch Texte, Bilder und Designs mit unserem neuen KI-Tool!
Button: Ausprobieren
Link: https://app.mehr-infos-jetzt.de/?page=ki-prompt
```

### 4. Event-Promotion

```
Titel: 🎓 Live-Webinar am 15. Dezember
Beschreibung: Erfahre die Geheimnisse erfolgreicher Lead-Generierung. Kostenlos für alle Mitglieder!
Button: Jetzt anmelden
Link: https://mehr-infos-jetzt.de/webinar
```

## 📈 Erfolgsmessung

Um die Performance deiner Angebote zu messen, kannst du:

1. **UTM-Parameter** zum Link hinzufügen:
   ```
   https://mehr-infos-jetzt.de/kurs?utm_source=dashboard&utm_medium=banner&utm_campaign=launch
   ```

2. **Tracking-Events** auswerten (siehe Tracking-System in overview.php)

3. **Conversion-Rate** berechnen:
   ```
   Klicks auf Offer-Button / Seitenaufrufe Dashboard
   ```

## 🔐 Sicherheit

- Admin-Zugriff erforderlich zum Erstellen/Bearbeiten
- XSS-Schutz durch `htmlspecialchars()`
- SQL-Injection-Schutz durch Prepared Statements
- URL-Validierung im Frontend

## 🆕 Updates & Erweiterungen

### Geplante Features

- [ ] A/B-Testing von Angeboten
- [ ] Zeitgesteuerte Angebote (Start/End-Datum)
- [ ] Zielgruppen-Targeting (basierend auf User-Rolle)
- [ ] Analytics-Dashboard
- [ ] Multiple aktive Angebote (Rotation)

### Anpassungen

Wenn du die Laufschrift an anderer Stelle anzeigen möchtest, kopiere einfach den entsprechenden Code-Block aus `customer/sections/overview.php`:

```php
<?php if ($active_offer): ?>
<div class="offer-banner">
    <!-- ... Laufschrift-Code ... -->
</div>
<?php endif; ?>
```

## 📞 Support

Bei Fragen oder Problemen:
- GitHub Issues: [michaelg-pixel/ki-lead](https://github.com/michaelg-pixel/ki-lead)
- E-Mail: support@mehr-infos-jetzt.de

---

**Version:** 1.0  
**Datum:** 20. November 2025  
**Erstellt von:** Claude AI für Michael G.
