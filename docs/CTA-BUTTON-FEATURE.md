# 🔘 CTA-Button Feature für Videokurse

## Übersicht
Mit diesem Feature kannst du Call-to-Action Buttons in deinen Videokursen anzeigen. Der Button erscheint unterhalb der Lektion-Info im Course-Player und ist vollständig mobile-optimiert.

## 🚀 Installation

### 1. Datenbank-Migration ausführen
Öffne im Browser:
```
https://app.mehr-infos-jetzt.de/migrations/migrate_course_buttons.html
```

Klicke auf **"Migration ausführen"**. Die folgenden Datenbankfelder werden hinzugefügt:
- `button_text` - Text des Buttons (max. 100 Zeichen)
- `button_url` - Link/URL des Buttons
- `button_new_window` - Button in neuem Fenster öffnen (Standard: Ja)

### 2. Fertig!
Nach der Migration ist das Feature sofort einsatzbereit.

## 📝 Verwendung

### Im Admin-Editor
1. Gehe zu **Kursverwaltung** → Wähle einen Kurs → **Bearbeiten**
2. Scrolle zu **"Call-to-Action Button (Optional)"**
3. Fülle die Felder aus:
   - **Button-Text**: z.B. "Jetzt kaufen", "Mehr erfahren", "Zum Produkt"
   - **Button-Link**: Die Ziel-URL (z.B. Digistore-Link, Landing Page)
   - **In neuem Fenster öffnen**: Checkbox (Standard: aktiviert)
4. Klicke auf **"Änderungen speichern"**

### Im Course-Player
Der Button wird automatisch angezeigt, wenn:
- `button_text` UND `button_url` ausgefüllt sind
- Eine Lektion freigeschaltet und angezeigt wird

Position: Unterhalb der Lektion-Info, nach dem "Als abgeschlossen markieren" Button

## 🎨 Design-Features

### Desktop
- Volle Breite unter der Lektion-Info
- Gradient-Hintergrund (Pink → Orange)
- Hover-Animation mit Lift-Effekt
- Pfeil-Icon am Ende (→)
- Schatteneffekt für Tiefe

### Mobile
- Vollständig responsive
- Touch-optimiert (größere Tap-Areas)
- Automatische Größenanpassung

## 💡 Anwendungsbeispiele

### 1. Produkt-Verkauf
```
Button-Text: "Jetzt das Vollversion kaufen"
Button-Link: https://www.digistore24.com/product/12345
```

### 2. Affiliate-Marketing
```
Button-Text: "Tool kostenlos testen"
Button-Link: https://partner-link.com/?ref=deincode
```

### 3. Lead-Generierung
```
Button-Text: "Kostenloses Beratungsgespräch buchen"
Button-Link: https://calendly.com/dein-link
```

### 4. Upsell
```
Button-Text: "Premium-Zugang freischalten"
Button-Link: /customer/upgrade.php
```

## 🔧 Technische Details

### Datenbankstruktur
```sql
ALTER TABLE courses
ADD COLUMN button_text VARCHAR(100) DEFAULT NULL,
ADD COLUMN button_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN button_new_window TINYINT(1) DEFAULT 1;
```

### Angepasste Dateien
1. `admin/sections/course-edit.php` - Editor-Felder
2. `admin/api/courses/update.php` - API zum Speichern
3. `customer/course-player.php` - Button-Anzeige

### CSS-Klassen
- `.cta-button-container` - Container mit Trennlinie
- `.cta-button` - Button-Styles mit Hover-Effekten

## 📱 Mobile-Optimierung

Der Button ist vollständig responsive und passt sich automatisch an:
- **Desktop**: Volle Breite, große Padding
- **Tablet**: Angepasste Größe
- **Mobile**: Touch-optimiert, 100% Breite

## ✅ Best Practices

### Button-Texte
- ✅ **Gut**: Kurz, klar, handlungsorientiert
  - "Jetzt kaufen"
  - "Mehr erfahren"
  - "Kostenlos testen"
  
- ❌ **Schlecht**: Lang, vage
  - "Klicken Sie hier für mehr Informationen über unser Angebot"
  - "Weiter"

### URLs
- Immer vollständige URLs verwenden: `https://...`
- UTM-Parameter für Tracking: `?utm_source=course&utm_campaign=button`
- Bei externen Links: "In neuem Fenster" aktiviert lassen

## 🔒 Sicherheit
- URLs werden mit `htmlspecialchars()` escaped
- `rel="noopener noreferrer"` bei externen Links (target="_blank")
- Admin-Rechte erforderlich zum Bearbeiten

## 🎯 Conversion-Tipps

1. **Klare Value Proposition**: Button-Text soll Nutzen vermitteln
2. **Konsistenz**: Gleiche Botschaft in Kurs und auf Zielseite
3. **A/B-Testing**: Verschiedene Texte testen
4. **Tracking**: UTM-Parameter für Erfolgsmessung
5. **Urgency**: "Jetzt", "Heute noch", "Zeitlich begrenzt"

## 📊 Tracking

Empfohlen: UTM-Parameter in Button-URL:
```
https://produkt.de/?utm_source=videokurs&utm_medium=button&utm_campaign=kurs22
```

Tracking in Google Analytics / Matomo möglich.

## 🆘 Troubleshooting

### Button wird nicht angezeigt
- ✓ Prüfe ob `button_text` UND `button_url` ausgefüllt sind
- ✓ Leere Browser-Cache
- ✓ Prüfe ob Lektion freigeschaltet ist
- ✓ Datenbankfelder vorhanden? (Migration ausgeführt?)

### Button öffnet sich nicht im neuen Fenster
- Stelle sicher, dass Checkbox "In neuem Fenster öffnen" aktiviert ist
- Speichere Änderungen erneut

### Style-Probleme
- Leere Browser-Cache (Strg+F5)
- Prüfe Browser-Konsole auf CSS-Fehler

## 📞 Support

Bei Fragen oder Problemen:
- Überprüfe Browser-Konsole auf Fehler
- Teste Migration erneut
- Prüfe Datenbankfelder mit phpMyAdmin

---

**Version**: 1.0
**Datum**: 11. November 2025
**Kompatibel mit**: KI Leadsystem v2.0+
