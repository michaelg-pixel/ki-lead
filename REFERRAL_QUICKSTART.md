# 🚀 Referral System - Quick Start Guide

## In 5 Minuten startklar!

### Schritt 1: Installation (2 Minuten)

```bash
# Führe Setup aus
php setup/setup-referral-system.php
```

✅ Das war's! Das System ist jetzt installiert.

---

### Schritt 2: Navigation hinzufügen (1 Minute)

**Customer-Dashboard** (`customer/dashboard.php` oder `customer/includes/navigation.php`):

```php
<li>
    <a href="?section=empfehlungsprogramm" class="<?php echo $section === 'empfehlungsprogramm' ? 'active' : ''; ?>">
        🎁 Empfehlungsprogramm
    </a>
</li>
```

**Admin-Dashboard** (`admin/dashboard.php`):

```php
<li>
    <a href="?section=referral-overview" class="<?php echo $section === 'referral-overview' ? 'active' : ''; ?>">
        🎯 Referral-Übersicht
    </a>
</li>
```

---

### Schritt 3: Tracking aktivieren (2 Minuten)

**Option A: Auto-Tracking (Empfohlen)**

Füge in die `<head>`-Section aller Freebie- und Danke-Seiten ein:

```html
<script src="/assets/js/referral-tracking.js"></script>
```

Das war's! Das Script erkennt automatisch:
- Freebie-Seiten → trackt Klicks
- Danke-Seiten → trackt Conversions

**Option B: Manuelles Tracking**

Für spezielle Seiten:

```javascript
// Klick tracken (Freebie-Seite)
ReferralTracker.trackClick({
    customer_id: <?php echo $customer_id; ?>,
    ref: '<?php echo $_GET['ref'] ?? ''; ?>'
});

// Conversion tracken (Danke-Seite)
ReferralTracker.trackConversion({
    customer_id: <?php echo $customer_id; ?>,
    ref: '<?php echo $_GET['ref'] ?? ''; ?>'
});
```

---

### Schritt 4: Empfehlungsformular auf Danke-Seiten

Füge auf Danke-Seiten ein:

```html
<!-- Container für Formular -->
<div id="referral-form-container"></div>

<!-- Script lädt Formular automatisch -->
<script src="/assets/js/referral-tracking.js"></script>
<script>
<?php if ($customer['referral_enabled']): ?>
    ReferralTracker.showReferralForm({
        customer_id: <?php echo $customer_id; ?>,
        ref: '<?php echo $_GET['ref'] ?? ''; ?>'
    });
<?php endif; ?>
</script>
```

---

## ✅ Fertig! Das System läuft jetzt.

### Was Customers jetzt tun können:

1. **Dashboard → Empfehlungsprogramm aufrufen**
2. **Programm aktivieren** (Toggle)
3. **Firmendaten eingeben** (für E-Mail-Impressum)
4. **Referral-Link kopieren** und teilen!

---

## 📊 So funktioniert's

### Für Customers:

```
1. Kunde aktiviert Programm im Dashboard
   ↓
2. Kunde erhält seinen Referral-Link:
   https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=REF000123ABC
   ↓
3. Kunde teilt Link (E-Mail, Social Media, Website)
   ↓
4. Besucher klickt auf Link → Klick wird getrackt
   ↓
5. Besucher füllt Formular aus → Conversion wird getrackt
   ↓
6. Auf Danke-Seite: Besucher kann sich für Empfehlungsprogramm anmelden
   ↓
7. E-Mail-Bestätigung wird versendet (mit Kundens Impressum!)
   ↓
8. Statistiken werden live im Dashboard angezeigt
```

### Für Admins:

```
Admin-Dashboard → Referral-Übersicht
   ↓
Sieht alle aktiven Programme
   ↓
Kann Details & Fraud-Log einsehen
   ↓
Kann Statistiken als CSV exportieren
```

---

## 🎯 Beispiel-Nutzung

### Szenario: E-Book-Anbieter

**Kunde (Michael) aktiviert Empfehlungsprogramm:**

1. Geht zu Dashboard → Empfehlungsprogramm
2. Toggle auf "Aktiviert"
3. Trägt ein:
   - Firmenname: "Michael's Marketing GmbH"
   - E-Mail: "info@michaels-marketing.de"
   - Impressum: (seine Firmendaten)
4. Kopiert Referral-Link:
   ```
   https://app.mehr-infos-jetzt.de/freebie.php?customer=5&ref=REF000005A1B2C3
   ```

**Michael teilt den Link:**

- Per E-Mail an seine Liste
- Auf Facebook & LinkedIn
- Als QR-Code auf Visitenkarten
- In YouTube-Beschreibung

**Was passiert:**

- **Besucher klickt** → System trackt Klick
- **Besucher lädt E-Book** → System trackt Conversion
- **Besucher meldet sich an** → Lead registriert, E-Mail mit Michaels Impressum versendet
- **Michael sieht live** → Dashboard zeigt Statistiken in Echtzeit

**Ergebnis:**

- Michael weiß genau, wie viele Klicks/Conversions sein Link generiert
- Leads erhalten professionelle E-Mails mit Michaels Branding
- Alles DSGVO-konform und automatisch

---

## 🔧 Optional: Erweiterte Features

### Tracking-Pixel für externe Seiten

Wenn Customer eigene externe Danke-Seite hat:

```html
<!-- Kopiert aus Dashboard -->
<img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=REF000123ABC" 
     width="1" height="1" style="display:none;">
```

### Cron-Jobs einrichten (optional)

**Automatische Belohnungs-E-Mails:**
```bash
# Täglich um 10 Uhr
0 10 * * * php /var/www/scripts/send-reward-emails.php
```

**DSGVO-Datenbereinigung:**
```bash
# Monatlich am 1.
0 2 1 * * php /var/www/scripts/cleanup-old-data.php
```

---

## 🆘 Häufige Fragen

### "Tracking funktioniert nicht"

**Check:**
1. Ist `referral-tracking.js` eingebunden?
2. Ist `?ref=XXX` in URL vorhanden?
3. Ist Programm für Customer aktiviert?
4. Browser-Konsole auf Fehler prüfen

**Fix:**
```javascript
// Browser-Konsole:
ReferralTracker.trackClick({customer_id: 123, ref: 'TEST'});
// Sollte "Klick erfolgreich getrackt" ausgeben
```

### "E-Mails kommen nicht an"

**Check:**
1. PHP-Mail-Funktion aktiviert?
2. Firmendaten im Dashboard eingegeben?
3. Spam-Ordner prüfen

**Fix:**
```bash
# Test-E-Mail senden
php -r "mail('test@example.com', 'Test', 'Test');"
```

### "Verdächtige Conversions zu hoch"

**Normal bei:**
- Test-Klicks (< 5 Sekunden)
- Sehr schnelle Nutzer
- Same-Device-Tests

**Fix:**
```php
// In ReferralHelper.php erhöhen:
private const SUSPICIOUS_CONVERSION_TIME = 10; // statt 5
```

---

## 📞 Support

- 📖 Vollständige Doku: `REFERRAL_SYSTEM_README.md`
- 💻 Code-Kommentare in allen Dateien
- 🐛 Issues: GitHub-Repository

---

## ✨ Das war's!

Dein Empfehlungsprogramm-System ist jetzt einsatzbereit. Customers können sofort starten! 🎉

**Next Steps:**
- Teste das System mit einem Test-Customer
- Aktiviere ein erstes Empfehlungsprogramm
- Teile den ersten Referral-Link
- Beobachte die Statistiken im Dashboard

**Viel Erfolg! 🚀**
