# 📄 Rechtstexte-System (Legal Texts)

## Übersicht

Das Rechtstexte-System ermöglicht es Kunden, ihr **Impressum** und ihre **Datenschutzerklärung** zentral zu verwalten. Die Texte werden automatisch auf allen Freebie-Seiten im Footer verlinkt und sind somit DSGVO-konform und rechtlich korrekt für jeden Kunden individuell verfügbar.

## ✨ Features

- ✅ **Zentrale Verwaltung** von Impressum und Datenschutzerklärung
- ✅ **Kundenspezifische Rechtstexte** - jeder Kunde hat seine eigenen Texte
- ✅ **Automatische Footer-Integration** auf allen Freebie-Seiten
- ✅ **E-Recht24 Integration** mit direkten Links zu kostenlosen Generatoren
- ✅ **Aktuelle DSGVO-Mustertexte** als Vorlage
- ✅ **Einfache Navigation** - "Rechtstexte" an 3. Position im Menü

## 🎯 Wo findet man die Rechtstexte?

Im Kunden-Dashboard unter:
```
Dashboard → Rechtstexte (3. Position im Menü)
```

## 🚀 Installation

### Option 1: Automatisches Setup (Empfohlen)

Rufe einfach das Setup-Script auf:
```
https://app.mehr-infos-jetzt.de/setup/setup-legal-texts.php
```

Das Script:
- Erstellt die `legal_texts` Tabelle
- Fügt `customer_id` zur `freebies` Tabelle hinzu (falls nicht vorhanden)
- Zeigt Erfolgsmeldung und nächste Schritte

### Option 2: Manuelle Installation

Führe das SQL-Script aus:
```sql
-- In phpMyAdmin oder über die Konsole
mysql -u username -p database_name < setup/legal-texts-setup.sql
```

## 📋 Datenbank-Struktur

### Tabelle: `legal_texts`

```sql
CREATE TABLE `legal_texts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `impressum` TEXT,
    `datenschutz` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_customer` (`customer_id`)
)
```

## 🔗 Wie funktioniert die Verlinkung?

### 1. Freebie-Seiten (view.php)
Die customer_id wird automatisch ermittelt:
```php
// Direktes Feld oder über customer_freebies Relation
$customer_id = $freebie['customer_id'] ?? get_from_relation();

// Footer-Links werden generiert
$impressum_link = "/impressum.php?customer=" . $customer_id;
$datenschutz_link = "/datenschutz.php?customer=" . $customer_id;
```

### 2. Thank-You-Seiten (thankyou.php)
Gleiche Logik wie bei den Freebie-Seiten

### 3. Layout-Templates (layout1.php, layout2.php, layout3.php)
Die Templates verwenden die gleiche customer_id Logik

## 📝 Für Kunden: So nutzt man die Rechtstexte

### Schritt 1: Texte generieren

Nutze die **kostenlosen e-recht24 Generatoren**:

**Impressum Generator:**
```
https://www.e-recht24.de/impressum-generator.html
```

**Datenschutz Generator:**
```
https://www.e-recht24.de/muster-datenschutzerklaerung.html
```

### Schritt 2: Texte einfügen

1. Gehe zu: `Dashboard → Rechtstexte`
2. Kopiere die generierten Texte in die entsprechenden Felder
3. Klicke auf "Rechtstexte speichern"

### Schritt 3: Fertig! ✅

Die Texte werden automatisch auf **allen deinen Freebie-Seiten** im Footer verlinkt:
- Freebie-Landingpages (`/freebie/view.php`)
- Thank-You-Seiten (`/freebie/thankyou.php`)
- Alle Template-Varianten

## 🎨 Features der Rechtstexte-Seite

### Prominente e-recht24 Box
```
┌─────────────────────────────────────────┐
│ 🎯 Kostenlose Rechtstexte mit e-recht24 │
│                                         │
│ [Impressum Generator] [Datenschutz Gen.]│
└─────────────────────────────────────────┘
```

### Mustertext-Buttons
- **"Mustertext laden"** - Lädt aktuelle DSGVO-konforme Vorlage
- Platzhalter müssen durch echte Daten ersetzt werden
- Warnung vor dem Überschreiben

### Wichtige Hinweise
- ⚠️ Rechtliche Hinweise zur Bindungswirkung
- ✅ Empfehlungen für professionelle Generatoren
- 📋 Checklisten: "Was muss ins Impressum/Datenschutz?"

### Vorschau-Links
Direkte Links zu den eigenen Rechtstexten:
```
/impressum.php?customer=123
/datenschutz.php?customer=123
```

## 🔐 Sicherheit & Datenschutz

- ✅ **Kundenspezifisch**: Jeder Kunde sieht nur seine eigenen Rechtstexte
- ✅ **DSGVO-konform**: Mustertexte nach aktuellen Standards
- ✅ **Eindeutige Zuordnung**: Via customer_id in URL-Parameter
- ✅ **Sichere Speicherung**: UTF-8 encoding, prepared statements

## 📂 Dateien & Struktur

### Backend (Verwaltung)
```
customer/
├── dashboard.php           # Navigation mit "Rechtstexte" an 3. Pos.
├── legal-texts.php         # Hauptseite für Rechtstexte-Verwaltung
└── includes/
    └── navigation.php      # Kann auch Rechtstexte-Link enthalten
```

### Frontend (Anzeige)
```
/
├── impressum.php           # Zeigt kundenspezifisches Impressum
├── datenschutz.php         # Zeigt kundenspezifische Datenschutzerklärung
└── freebie/
    ├── view.php            # Freebie-Seite mit Footer-Links
    ├── thankyou.php        # Danke-Seite mit Footer-Links
    └── templates/
        ├── layout1.php     # Template 1 mit Footer
        ├── layout2.php     # Template 2 mit Footer
        └── layout3.php     # Template 3 mit Footer
```

### Setup
```
setup/
├── legal-texts-setup.sql       # SQL-Script
└── setup-legal-texts.php       # Automatisches PHP-Setup
```

## 🛠️ Technische Details

### Customer-ID Ermittlung

Das System unterstützt zwei Ansätze:

**Variante 1: Direktes Feld (empfohlen)**
```php
$customer_id = $freebie['customer_id'];
```

**Variante 2: Über Relation**
```php
$stmt = $pdo->prepare("SELECT customer_id FROM customer_freebies WHERE freebie_id = ?");
$stmt->execute([$freebie_id]);
$customer_id = $stmt->fetchColumn();
```

### Footer-Integration

Alle Templates verwenden:
```html
<footer>
    <a href="/impressum.php?customer=<?= $customer_id ?>">Impressum</a>
    <a href="/datenschutz.php?customer=<?= $customer_id ?>">Datenschutz</a>
</footer>
```

## 📖 DSGVO-Compliance

### Was muss ins Impressum?
- Name und Anschrift
- Kontaktmöglichkeiten (E-Mail, Telefon)
- Bei Unternehmen: Rechtsform, Vertretungsberechtigte, Handelsregister
- Umsatzsteuer-ID (falls vorhanden)
- Verantwortlich nach § 55 Abs. 2 RStV

### Was muss in die Datenschutzerklärung?
- Verantwortlicher für Datenverarbeitung
- Welche Daten werden erhoben? (E-Mail, Name, IP, etc.)
- Zu welchem Zweck?
- Rechtsgrundlage (Art. 6 DSGVO)
- Speicherdauer
- Betroffenenrechte (Auskunft, Löschung, etc.)
- Cookies und Drittanbieter
- Widerrufsrecht

## 🆘 Troubleshooting

### Problem: "Ungültige Kunden-ID"
**Lösung:** Prüfe ob:
- Die `customer_id` in der `freebies` Tabelle gesetzt ist
- Oder die Zuordnung über `customer_freebies` existiert

### Problem: Footer-Links funktionieren nicht
**Lösung:** 
1. Prüfe ob `impressum.php` und `datenschutz.php` existieren
2. Stelle sicher, dass die customer_id korrekt übergeben wird
3. Teste die Links manuell: `/impressum.php?customer=1`

### Problem: Rechtstexte werden nicht gespeichert
**Lösung:**
1. Prüfe ob die `legal_texts` Tabelle existiert
2. Führe das Setup-Script aus: `/setup/setup-legal-texts.php`
3. Prüfe Datenbank-Berechtigungen

## ✅ Checkliste für neue Kunden

- [ ] Kunde hat sich registriert
- [ ] `legal_texts` Eintrag wurde automatisch erstellt
- [ ] Kunde hat Rechtstexte-Seite besucht
- [ ] Kunde hat e-recht24 Generatoren genutzt
- [ ] Impressum eingefügt und gespeichert
- [ ] Datenschutzerklärung eingefügt und gespeichert
- [ ] Links im Footer getestet
- [ ] Vorschau der Rechtstexte geprüft

## 🎓 Best Practices

1. **Empfehle e-recht24**: Die Generatoren sind kostenlos und DSGVO-konform
2. **Regelmäßige Updates**: Erinnere Kunden daran, ihre Texte zu aktualisieren
3. **Vollständigkeit prüfen**: Alle Platzhalter müssen ersetzt werden
4. **Professionelle Beratung**: Bei geschäftlicher Nutzung rechtliche Beratung empfehlen

## 🔄 Updates & Wartung

### Version 1.0 (November 2025)
- ✅ Initiale Implementation
- ✅ e-recht24 Integration
- ✅ Automatische Footer-Links
- ✅ DSGVO-konforme Mustertexte
- ✅ Navigation an 3. Position

---

**Entwickler:** Michael (Hostinger MCP Bridge)  
**Stand:** November 2025  
**Support:** Bei Fragen bitte im Repository ein Issue erstellen