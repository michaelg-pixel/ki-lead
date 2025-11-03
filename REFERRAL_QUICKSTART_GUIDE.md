# 🚀 EMPFEHLUNGSPROGRAMM QUICKSTART
## Schnelleinstieg für Admins & Customers

---

## FÜR ADMINS

### 1. System-Check durchführen

```bash
# 1. Prüfe ob Migration gelaufen ist
mysql -u lumisaas52 -p lumisaas
> SHOW TABLES LIKE 'referral_%';
# Sollte 7 Tabellen zeigen

# 2. Prüfe Cron-Job
crontab -l | grep reward
# Sollte: 0 10 * * * php /path/to/scripts/send-reward-emails.php

# 3. Teste E-Mail-Versand
php scripts/send-reward-emails.php
```

### 2. Monitoring-Dashboard aufrufen

```
URL: https://app.mehr-infos-jetzt.de/admin/dashboard.php?section=referral-overview

Oder erweitert:
URL: https://app.mehr-infos-jetzt.de/admin/sections/referral-monitoring-extended.php
```

### 3. Customer-Support-Tipps

**Häufige Fragen:**

**Q: "Warum werden meine Klicks nicht getrackt?"**
A: Prüfe:
- ref-Parameter in URL vorhanden?
- JavaScript aktiviert?
- Browser-Console auf Fehler checken
- 24h-Limit (gleiche IP)?

**Q: "Warum ist meine Conversion verdächtig?"**
A: Mögliche Gründe:
- Freebie → Danke < 5 Sekunden
- Gleiche IP wie vorheriger Klick
- Identischer Fingerprint

**Q: "E-Mails werden nicht versendet"**
A: Checke:
```bash
# Cron-Log prüfen
tail -f logs/reward-emails-$(date +%Y-%m-%d).log

# Test-Mail senden
php scripts/send-reward-emails.php
```

---

## FÜR CUSTOMERS

### 1. Programm aktivieren

1. Login: `https://app.mehr-infos-jetzt.de/customer/dashboard.php`
2. Menü: **"Empfehlungsprogramm"**
3. Toggle oben rechts auf **"Aktiviert"** stellen

### 2. Firmendaten hinterlegen

**Wichtig für DSGVO-konforme E-Mails!**

```
✓ Firmenname: Ihre Firma GmbH
✓ E-Mail: info@ihre-firma.de
✓ Impressum: 
  Ihre Firma GmbH
  Musterstraße 123
  12345 Musterstadt
  E-Mail: info@ihre-firma.de
```

### 3. Referral-Link teilen

**Option A: Direkter Link**
```
https://app.mehr-infos-jetzt.de/freebie.php?customer={IHRE_ID}&ref={IHR_CODE}
```

**Option B: Ref-Parameter anhängen**
```
https://ihre-website.de/seite?ref={IHR_CODE}
```

**Option C: Tracking-Pixel einbauen**
```html
<!-- In Ihre externe Danke-Seite einfügen -->
<img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer={ID}&ref={CODE}" 
     width="1" height="1" style="display:none;">
```

### 4. Statistiken ansehen

Im Dashboard finden Sie:

- **Klicks**: Wie oft wurde Ihr Link geklickt?
- **Conversions**: Wie viele Besucher haben die Danke-Seite erreicht?
- **Leads**: Wie viele haben sich für das Programm registriert?
- **Conversion Rate**: Ihr Erfolgs-Prozentsatz

### 5. Leads erfassen

**Automatisch auf Danke-Seite:**

Wenn Empfehlungsprogramm aktiv ist, sehen Besucher auf der Danke-Seite ein Formular:

```
🎁 Jetzt am Empfehlungsprogramm teilnehmen
[E-Mail eingeben]
[✓] Datenschutz zustimmen
[Jetzt Teilnehmen]
```

**Manuell über Pixel:**

Bauen Sie den Tracking-Pixel auf Ihrer eigenen Danke-Seite ein.

---

## BEISPIEL-SZENARIEN

### Szenario 1: Social Media Campaign

**Ziel**: Instagram-Post mit Referral-Link

**Setup:**
1. Aktiviere Programm
2. Kopiere Freebie-Link:
   ```
   https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=ABC123
   ```
3. Poste auf Instagram:
   ```
   🎁 Gratis Download! 
   Link in Bio → [Link]
   ```

**Tracking:**
- Klicks werden automatisch gezählt
- Conversions auf Danke-Seite getrackt
- Leads erfasst

### Szenario 2: E-Mail-Marketing

**Ziel**: Newsletter mit Referral-Link

**Setup:**
1. Erstelle E-Mail-Template
2. Füge Link ein:
   ```
   <a href="https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=ABC123">
     Jetzt herunterladen
   </a>
   ```
3. Versende Newsletter

**Tracking:**
- Jeder Klick = 1 Impression
- Danke-Seite = 1 Conversion
- Formular = 1 Lead

### Szenario 3: Landing Page mit Pixel

**Ziel**: Eigene Landing Page mit Tracking

**Setup:**
1. Erstelle externe Landing Page
2. Baue Tracking-Pixel in Danke-Seite ein:
   ```html
   <img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=ABC123" 
        width="1" height="1" style="display:none;">
   ```
3. Teile Landing Page URL

**Tracking:**
- Klicks: Manuell tracken oder über Analytics
- Conversions: Automatisch via Pixel
- Leads: Über Freebie-System

---

## FEHLERSUCHE (TROUBLESHOOTING)

### Problem: Dashboard zeigt 0 Klicks

**Check-Liste:**
1. ✓ Programm aktiviert?
2. ✓ ref-Parameter in URL?
3. ✓ Browser-Console: Fehler?
4. ✓ Ad-Blocker deaktiviert?

**Test:**
```javascript
// Browser-Console öffnen (F12)
console.log(REFERRAL_CONFIG);
// Sollte: { customerId: 123, refCode: "ABC123" }
```

### Problem: Conversions nicht getrackt

**Check-Liste:**
1. ✓ Von Freebie zu Danke navigiert?
2. ✓ ref in sessionStorage?
3. ✓ Netzwerk-Tab: API-Call erfolgreich?

**Test:**
```javascript
// Browser-Console
console.log(sessionStorage.getItem('pending_ref_code'));
// Sollte: "ABC123"
```

### Problem: E-Mails kommen nicht an

**Check-Liste:**
1. ✓ Firmendaten hinterlegt?
2. ✓ goal_referrals erreicht?
3. ✓ auto_send_reward aktiviert?
4. ✓ Cron-Job läuft?

**Test:**
```bash
# Manuell E-Mail-Skript ausführen
php scripts/send-reward-emails.php

# Output prüfen
# Sollte: "Sent: 1 | Errors: 0"
```

---

## BEST PRACTICES

### ✅ DO's

- **Kurze Links verwenden**: Nutze URL-Shortener für Social Media
- **A/B-Testing**: Teste verschiedene ref-Codes für verschiedene Kampagnen
- **Regelmäßig prüfen**: Checke Dashboard mindestens 1x pro Woche
- **Leads bestätigen**: Nutze Double-Opt-In für bessere Qualität
- **Impressum pflegen**: Halte Firmendaten aktuell

### ❌ DON'Ts

- **Keine Spam-Links**: Versende keine unsolicited E-Mails
- **Kein Fake-Traffic**: Keine Bots oder Auto-Clicker
- **Keine Duplicate-Accounts**: Ein Customer-Account pro Firma
- **Keine sensiblen Daten in ref**: Keine Kundendaten im Referral-Code
- **Keine Testklicks**: Nutze Inkognito-Modus für Tests

---

## FORTGESCHRITTENE TIPPS

### 1. Multi-Campaign-Tracking

Nutze unterschiedliche ref-Codes für verschiedene Kampagnen:

```
Instagram: ?ref=IG2025
Facebook: ?ref=FB2025
Newsletter: ?ref=NL2025
```

### 2. UTM-Parameter kombinieren

```
https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=ABC123&utm_source=instagram&utm_medium=social&utm_campaign=winter2025
```

### 3. QR-Codes generieren

```
Link: https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=ABC123

QR-Generator: https://www.qr-code-generator.com/
```

### 4. Retargeting-Integration

Nutze Pixel für Facebook/Google Ads Retargeting:

```html
<!-- Facebook Pixel -->
<script>
  fbq('track', 'Lead', {
    ref_code: 'ABC123',
    customer_id: 123
  });
</script>

<!-- KI-Lead Referral Pixel -->
<img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=ABC123" 
     width="1" height="1" style="display:none;">
```

---

## SUPPORT & HILFE

### Dokumentation
- **Vollständig**: `/REFERRAL_SYSTEM_COMPLETE.md`
- **Architektur**: `/REFERRAL_ARCHITECTURE.md`
- **API-Docs**: `/REFERRAL_QUICKSTART.md`

### Kontakt
- **E-Mail**: support@mehr-infos-jetzt.de
- **Dashboard**: Ticket-System im Admin-Bereich

### Logs
```bash
# Reward-E-Mails
tail -f logs/reward-emails-$(date +%Y-%m-%d).log

# Cron-Jobs
tail -f logs/cron.log

# PHP-Errors
tail -f /var/log/php_errors.log
```

---

## CHECKLISTE: SYSTEM READY?

### Admin-Checkliste
- [ ] Datenbank-Migration durchgeführt
- [ ] Cron-Job für E-Mails eingerichtet
- [ ] Logs-Ordner erstellt und beschreibbar
- [ ] E-Mail-Versand getestet
- [ ] Monitoring-Dashboard erreichbar
- [ ] SSL/HTTPS aktiv

### Customer-Checkliste
- [ ] Programm aktiviert
- [ ] Firmendaten hinterlegt
- [ ] Referral-Link kopiert
- [ ] Test-Klick durchgeführt
- [ ] Dashboard geprüft
- [ ] Impressum aktuell

---

**Version**: 1.0  
**Letzte Aktualisierung**: 03.11.2025  
**Support**: support@mehr-infos-jetzt.de

🎉 **Viel Erfolg mit Ihrem Empfehlungsprogramm!**
