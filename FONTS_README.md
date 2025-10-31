# 🔤 Schriftarten & Schriftgrößen im Freebie-Editor

## ✅ Implementierung abgeschlossen!

Der Freebie-Editor wurde erweitert um vollständige Schriftarten- und Schriftgrößen-Kontrolle.

---

## 📋 Was wurde hinzugefügt?

### **1. Schriftarten-Auswahl**
16 professionelle Google Fonts in 4 Kategorien:

#### Modern & Clean
- Poppins ⭐ (Standard)
- Inter
- Roboto
- Open Sans
- Montserrat
- Lato

#### Bold & Impact
- Anton
- Bebas Neue
- Oswald
- Barlow Condensed

#### Elegant & Light
- Raleway
- Playfair Display
- Lora
- Cormorant

#### Classic & Serif
- Merriweather
- PT Serif
- Crimson Text

#### System Fonts
- Verdana
- Arial
- Georgia
- Times New Roman

### **2. Schriftgrößen-Kontrolle**

Jedes Text-Element hat jetzt eigene Schriftart & Größe:

- **Pre-Headline**: 10-22px (Standard: 14px)
- **Headline**: 24-80px (Standard: 48px)
- **Subheadline**: 14-32px (Standard: 20px)
- **Bulletpoints**: 12-24px (Standard: 16px)

---

## 🚀 Installation

### Schritt 1: Datenbank aktualisieren

Führe das Setup-Script **EINMALIG** aus:

```
https://deine-domain.de/setup/add-font-settings.php
```

Das Script fügt folgende Spalten zur `freebies`-Tabelle hinzu:
- `preheadline_font`, `preheadline_size`
- `headline_font`, `headline_size`
- `subheadline_font`, `subheadline_size`
- `bulletpoints_font`, `bulletpoints_size`

### Schritt 2: Fertig!

Nach der Datenbank-Aktualisierung sind alle Features sofort verfügbar.

---

## 💻 Verwendung im Editor

### Schriftart & Größe einstellen:

1. **Admin-Panel öffnen** → Freebies → Neues Template / Template bearbeiten

2. **Bei jedem Text-Bereich findest du jetzt:**
   - Dropdown für **Schriftart**
   - Dropdown für **Größe (px)**

3. **Live-Vorschau** klicken, um Änderungen zu sehen

4. **Speichern** – Fertig! ✨

### Beispiel-Kombination:

```
Pre-Headline:    Anton, 18px     (Impact-Style)
Headline:        Poppins, 56px   (Modern & groß)
Subheadline:     Inter, 20px     (Clean & lesbar)
Bulletpoints:    Roboto, 16px    (Professionell)
```

---

## 🎨 Vorschau-Funktion

Die Vorschau zeigt alle Font-Einstellungen live:
- Alle Google Fonts werden geladen
- Schriftgrößen werden korrekt dargestellt
- Layouts (Hybrid, Centered, Sidebar) berücksichtigen Fonts

---

## 🗄️ Datenbank-Struktur

### Neue Felder in `freebies`:

```sql
preheadline_font     VARCHAR(100)  DEFAULT 'Poppins'
preheadline_size     INT           DEFAULT 14
headline_font        VARCHAR(100)  DEFAULT 'Poppins'
headline_size        INT           DEFAULT 48
subheadline_font     VARCHAR(100)  DEFAULT 'Poppins'
subheadline_size     INT           DEFAULT 20
bulletpoints_font    VARCHAR(100)  DEFAULT 'Poppins'
bulletpoints_size    INT           DEFAULT 16
```

---

## 📱 Customer-Ansicht

Wenn ein Kunde sein Freebie aufruft:
1. Die gewählten Schriftarten werden automatisch von Google Fonts geladen
2. Alle Schriftgrößen werden korrekt angewendet
3. Die Seite sieht exakt so aus, wie im Editor designt

---

## 🔧 Technische Details

### Dateien:

1. **`/config/fonts.php`**  
   - Zentrale Font-Konfiguration
   - Liste aller verfügbaren Schriftarten
   - Größen-Bereiche für jedes Element
   - Google Fonts URL

2. **`/admin/sections/freebie-create.php`**  
   - Editor mit Font-Auswahl
   - Dropdown-Menüs für Schriftart & Größe
   - Live-Vorschau mit Fonts

3. **`/api/save-freebie.php`**  
   - Speichert alle Font-Einstellungen
   - INSERT & UPDATE mit Font-Feldern

4. **`/setup/add-font-settings.php`**  
   - Einmalige Datenbank-Migration
   - Fügt Font-Spalten hinzu

### Google Fonts Integration:

```html
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&..." rel="stylesheet">
```

---

## ✨ Features

✅ 16 professionelle Schriftarten  
✅ Individuelle Größen-Kontrolle pro Element  
✅ Live-Vorschau im Editor  
✅ Automatisches Google Fonts Laden  
✅ Speicherung in Datenbank  
✅ Customer-seitige Anwendung  
✅ Vollständig responsiv  

---

## 🎯 Best Practices

### Lesbarkeit:
- **Headlines**: Bold & Impact Fonts (48-72px)
- **Subheadlines**: Modern & Clean Fonts (18-24px)
- **Bulletpoints**: Klassische Fonts (16-18px)

### Kombinationen:
```
Modern Look:
- Headline: Poppins Bold 56px
- Subheadline: Inter Regular 20px
- Bullets: Roboto Regular 16px

Elegant Look:
- Headline: Playfair Display 48px
- Subheadline: Raleway 22px
- Bullets: Lora 16px

Bold Look:
- Headline: Anton 64px
- Subheadline: Bebas Neue 24px
- Bullets: Oswald 18px
```

---

## 🐛 Troubleshooting

### Problem: Schriftarten werden nicht angezeigt

**Lösung 1**: Datenbank-Migration ausführen
```
https://deine-domain.de/setup/add-font-settings.php
```

**Lösung 2**: Browser-Cache leeren

**Lösung 3**: Prüfe ob Google Fonts geladen werden (Browser DevTools → Network)

---

## 📞 Support

Bei Fragen oder Problemen:
1. Setup-Script erneut ausführen
2. Browser-Cache leeren
3. Datenbank-Spalten manuell prüfen

---

**Installation erfolgreich! Viel Spaß beim Designen! 🎨**
