# 📄 Rechtstexte-System (Legal Texts)

## Übersicht

Das Rechtstexte-System ermöglicht es Kunden, ihr **Impressum** und ihre **Datenschutzerklärung** zentral zu verwalten. Die Texte werden **automatisch auf allen Freebie-Seiten im Footer verlinkt** und sind somit DSGVO-konform und rechtlich korrekt für jeden Kunden individuell verfügbar.

## ✨ Features

- ✅ **Zentrale Verwaltung** von Impressum und Datenschutzerklärung
- ✅ **Kundenspezifische Rechtstexte** - jeder Kunde hat seine eigenen Texte
- ✅ **AUTOMATISCHE Footer-Integration** auf allen Freebie-Seiten
- ✅ **Keine manuelle Konfiguration** - alles geschieht automatisch beim Speichern
- ✅ **E-Recht24 Integration** mit direkten Links zu kostenlosen Generatoren
- ✅ **Aktuelle DSGVO-Mustertexte** als Vorlage
- ✅ **Einfache Navigation** - "Rechtstexte" an 3. Position im Menü

## 🎯 Wie funktioniert die automatische Verknüpfung?

### Workflow:

1. **Kunde speichert Rechtstexte** unter `Dashboard → Rechtstexte`
   - Impressum und Datenschutzerklärung werden in `legal_texts` Tabelle gespeichert
   - Mit `customer_id` verknüpft

2. **Kunde bearbeitet ein Freebie** im Editor
   - Beim Speichern wird automatisch die `customer_id` im Template gesetzt
   - Keine zusätzliche Konfiguration nötig

3. **Freebie-Seite wird aufgerufen**
   - System liest `customer_id` aus dem Freebie
   - Footer-Links werden automatisch generiert:
     - `/impressum.php?customer=123`
     - `/datenschutz.php?customer=123`

4. **Rechtstexte werden angezeigt**
   - Besucher klickt auf "Impressum" oder "Datenschutz"
   - System lädt die kundenspezifischen Texte aus der Datenbank

### Das bedeutet:

✅ **Einmal einrichten** → Funktioniert überall  
✅ **Automatische Verknüpfung** → Kein manuelles Eintragen von IDs  
✅ **Immer aktuell** → Änderungen erscheinen sofort auf allen Seiten  
✅ **DSGVO-konform** → Jeder Kunde hat seine eigenen rechtlichen Texte  

## 🚀 Für Kunden: So einfach geht's

### Schritt 1: Rechtstexte einrichten (einmalig)

1. Gehe zu `Dashboard → Rechtstexte` (3. Position im Menü)
2. Nutze die **e-recht24 Generatoren** für professionelle Texte:
   - [Impressum Generator](https://www.e-recht24.de/impressum-generator.html)
   - [Datenschutz Generator](https://www.e-recht24.de/muster-datenschutzerklaerung.html)
3. Kopiere die generierten Texte in die Felder
4. Klicke auf "Rechtstexte speichern"

### Schritt 2: Freebie bearbeiten

1. Gehe zu `Dashboard → Lead-Magneten`
2. Wähle ein Template und klicke auf "Nutzen" oder "Bearbeiten"
3. Passe Texte, Farben und E-Mail-Optin an
4. Klicke auf **"Freebie speichern & Rechtstexte verknüpfen"**

### Schritt 3: Fertig! ✅

**Deine Rechtstexte sind jetzt automatisch verknüpft!**

- Freebie-Seite zeigt deine Links im Footer
- Thank-You-Seite zeigt deine Links im Footer
- Alle Template-Varianten nutzen deine Rechtstexte
- Bei Änderungen werden sie überall aktualisiert

## 📋 Datenbank-Struktur & Technische Details

### Automatische Verknüpfung

**Beim Speichern im Freebie-Editor (`customer/freebie-editor.php`):**

```php
// customer_id wird automatisch gesetzt
UPDATE freebies SET
    customer_id = ?  // Wichtig für Footer-Links!
WHERE id = ?
```

**Bei der Anzeige (`freebie/view.php`, `freebie/thankyou.php`):**

```php
// customer_id wird automatisch gelesen
$customer_id = $freebie['customer_id'];

// Footer-Links werden automatisch generiert
$impressum_link = "/impressum.php?customer=" . $customer_id;
$datenschutz_link = "/datenschutz.php?customer=" . $customer_id;
```

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

### Erweiterte `freebies` Tabelle

```sql
ALTER TABLE `freebies` 
ADD COLUMN `customer_id` INT(11) DEFAULT NULL;
```

## 🆘 Häufige Fragen (FAQ)

### "Muss ich für jedes Freebie die Rechtstexte neu verknüpfen?"

**Nein!** Die Verknüpfung geschieht **automatisch beim Speichern** des Freebies. Du musst nichts Manuelles tun.

### "Was passiert, wenn ich meine Rechtstexte ändere?"

Die Änderungen werden **sofort auf allen Freebie-Seiten** sichtbar. Du musst die Freebies nicht neu speichern.

### "Kann ich unterschiedliche Rechtstexte für verschiedene Freebies haben?"

Aktuell nutzen alle Freebies eines Kunden dieselben Rechtstexte. Das ist rechtlich auch sinnvoll, da die Rechtstexte für dein gesamtes Business gelten.

### "Was zeigt der Footer, wenn ich noch keine Rechtstexte eingegeben habe?"

Die Links werden trotzdem angezeigt, aber die Rechtstexte-Seiten zeigen "Nicht verfügbar". Füge daher am besten sofort nach der Registrierung deine Rechtstexte ein.

### "Sind die Mustertexte rechtssicher?"

Die Mustertexte sind **DSGVO-konforme Vorlagen**, aber du musst alle Platzhalter (z.B. [Dein Name]) durch deine echten Daten ersetzen. Wir empfehlen die Nutzung der **e-recht24 Generatoren** für professionelle, rechtssichere Texte.

## 📂 Dateien & Änderungen

### Geänderte Backend-Dateien:

- ✅ `customer/dashboard.php` - "Rechtstexte" Menüpunkt an 3. Position
- ✅ `customer/legal-texts.php` - Vollständig überarbeitet mit e-recht24 Integration
- ✅ `customer/freebie-editor.php` - **Setzt customer_id automatisch beim Speichern**

### Geänderte Frontend-Dateien:

- ✅ `freebie/view.php` - Liest customer_id und generiert Footer-Links
- ✅ `freebie/thankyou.php` - Liest customer_id und generiert Footer-Links
- ✅ `freebie/templates/layout1.php` - Unterstützt kundenspezifische Links

### Rechtstexte-Anzeige:

- ✅ `impressum.php` - Zeigt kundenspezifisches Impressum
- ✅ `datenschutz.php` - Zeigt kundenspezifische Datenschutzerklärung

## 🚀 Installation

### Automatisches Setup (Empfohlen)

```
https://app.mehr-infos-jetzt.de/setup/setup-legal-texts.php
```

Das Script:
- Erstellt die `legal_texts` Tabelle
- Fügt `customer_id` zur `freebies` Tabelle hinzu
- Zeigt Erfolgsmeldung und nächste Schritte

## ✅ Vorteile des Systems

### Für Kunden:
- ✅ **Keine technischen Kenntnisse** nötig
- ✅ **Einmal einrichten**, überall nutzen
- ✅ **Automatische Aktualisierung** bei Änderungen
- ✅ **DSGVO-konform** und rechtssicher
- ✅ **Professionelle Vorlagen** inklusive

### Für Admins:
- ✅ **Keine manuelle Konfiguration** pro Kunde
- ✅ **Automatische Verknüpfung** beim Speichern
- ✅ **Skalierbar** für viele Kunden
- ✅ **Zentralisierte Verwaltung**

### Für Besucher:
- ✅ **Rechtskonforme Footer-Links** auf jeder Seite
- ✅ **Schneller Zugriff** zu Impressum und Datenschutz
- ✅ **Vertrauen** durch transparente Rechtstexte

## 🎓 Best Practices

1. **Rechtstexte zuerst einrichten**: Füge deine Rechtstexte direkt nach der Registrierung ein
2. **E-recht24 nutzen**: Die Generatoren sind kostenlos und professionell
3. **Regelmäßig aktualisieren**: Prüfe deine Texte jährlich auf Aktualität
4. **Alle Platzhalter ersetzen**: Achte darauf, dass keine [Platzhalter] mehr im Text sind
5. **Bei Änderungen im Business**: Aktualisiere deine Rechtstexte entsprechend

## 📖 DSGVO-Compliance

### Was muss ins Impressum?
- Name und Anschrift (vollständig)
- Kontaktmöglichkeiten (E-Mail, Telefon)
- Bei Unternehmen: Rechtsform, Vertretungsberechtigte, Handelsregister
- Umsatzsteuer-ID (falls vorhanden)
- Verantwortlich nach § 55 Abs. 2 RStV

### Was muss in die Datenschutzerklärung?
- Verantwortlicher für Datenverarbeitung
- Welche Daten werden erhoben? (E-Mail, Name, IP, etc.)
- Zu welchem Zweck? (Newsletter, Lead-Magnet, etc.)
- Rechtsgrundlage (Art. 6 DSGVO)
- Speicherdauer
- Betroffenenrechte (Auskunft, Löschung, etc.)
- Cookies und Drittanbieter (E-Mail-Tools, Analytics)
- Widerrufsrecht

## 🔧 Updates & Wartung

### Version 1.1 (November 2025)
- ✅ **Automatische Verknüpfung** beim Freebie-Speichern
- ✅ customer_id wird automatisch gesetzt
- ✅ Grüner Hinweis im Editor
- ✅ Erfolgs-Nachricht mit Bestätigung

### Version 1.0 (November 2025)
- ✅ Initiale Implementation
- ✅ e-recht24 Integration
- ✅ Manuelle Footer-Links
- ✅ DSGVO-konforme Mustertexte

---

**Entwickler:** Michael (Hostinger MCP Bridge)  
**Stand:** November 2025  
**Support:** Bei Fragen bitte im Repository ein Issue erstellen

## 🎯 Zusammenfassung

Das Rechtstexte-System ist **vollständig automatisiert**:

1. Kunde speichert Rechtstexte → In Datenbank
2. Kunde bearbeitet Freebie → customer_id wird automatisch gesetzt
3. Freebie wird aufgerufen → Footer zeigt automatisch Rechtstexte-Links
4. Besucher klickt Link → Sieht kundenspezifische Rechtstexte

**Keine manuelle Konfiguration. Keine IDs eintragen. Alles automatisch.** 🚀