# 🔧 SMTP E-Mail-Versand einrichten

## Problem erkannt ✅

PHP `mail()` funktioniert nicht zuverlässig bei Hostinger.
**Lösung:** SMTP mit PHPMailer nutzen!

---

## 📋 Schritt-für-Schritt Anleitung

### 1️⃣ PHPMailer installieren

Verbinde dich per **SSH** zu deinem Hostinger Server:

```bash
cd /home/u123456789/domains/mehr-infos-jetzt.de/public_html
composer require phpmailer/phpmailer
```

Falls `composer` nicht installiert ist:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar require phpmailer/phpmailer
```

---

### 2️⃣ E-Mail-Adresse bei Hostinger erstellen

1. Gehe zu **Hostinger Panel**
2. Klicke auf **E-Mails**
3. Erstelle E-Mail: `noreply@mehr-infos-jetzt.de`
4. Setze ein starkes Passwort
5. **WICHTIG**: Notiere das Passwort!

---

### 3️⃣ SMTP-Zugangsdaten konfigurieren

Bearbeite die Datei: `includes/email_smtp.php`

Ändere diese Zeilen:

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@mehr-infos-jetzt.de');
define('SMTP_PASSWORD', 'DEIN_EMAIL_PASSWORT_HIER'); // ← HIER PASSWORT EINTRAGEN!
define('SMTP_FROM_EMAIL', 'noreply@mehr-infos-jetzt.de');
define('SMTP_FROM_NAME', 'Optinpilot');
```

**Trage dein E-Mail-Passwort ein!**

---

### 4️⃣ password-reset-request.php aktualisieren

Ändere Zeile 9 von:
```php
require_once '../includes/quentn_api.php';
```

Zu:
```php
require_once '../includes/email_smtp.php';
```

---

### 5️⃣ Testen!

Rufe auf:
```
https://app.mehr-infos-jetzt.de/public/password-reset-request.php
```

Gib deine E-Mail ein und teste!

---

## 🎯 Alternative: Schnell-Setup (OHNE Composer)

Falls Composer nicht funktioniert, kannst du PHPMailer auch manuell installieren:

### Option A: Download PHPMailer

1. Gehe zu: https://github.com/PHPMailer/PHPMailer/releases
2. Lade die neueste Version herunter (z.B. `PHPMailer-6.x.zip`)
3. Entpacke in: `/home/u123456789/domains/mehr-infos-jetzt.de/public_html/vendor/phpmailer/phpmailer/`
4. Stelle sicher, dass die Pfade stimmen:
   - `/vendor/phpmailer/phpmailer/src/PHPMailer.php`
   - `/vendor/phpmailer/phpmailer/src/SMTP.php`
   - `/vendor/phpmailer/phpmailer/src/Exception.php`

---

## 🔍 Hostinger SMTP Settings

**Für Hostinger E-Mails:**

```
SMTP Host: smtp.hostinger.com
SMTP Port: 587 (TLS) oder 465 (SSL)
SMTP Username: deine@domain.de (vollständige E-Mail)
SMTP Password: Dein E-Mail Passwort
Verschlüsselung: TLS (STARTTLS)
```

**Wichtig:**
- Nutze die **vollständige E-Mail-Adresse** als Username
- Port **587** mit **TLS** ist empfohlen
- Das Passwort ist das **E-Mail-Passwort**, nicht das Hostinger-Login!

---

## ✅ Checkliste

- [ ] PHPMailer installiert (via Composer oder manuell)
- [ ] E-Mail-Adresse bei Hostinger erstellt
- [ ] SMTP-Zugangsdaten in `includes/email_smtp.php` eingetragen
- [ ] `password-reset-request.php` aktualisiert (require-Zeile)
- [ ] Getestet!

---

## 🆘 Troubleshooting

### Problem: "Class 'PHPMailer' not found"
**Lösung:** PHPMailer nicht installiert → Schritt 1 wiederholen

### Problem: "SMTP connect() failed"
**Lösung:** 
- SMTP-Zugangsdaten prüfen
- Port prüfen (587 oder 465)
- Bei Hostinger: Vollständige E-Mail als Username nutzen

### Problem: "Authentication failed"
**Lösung:**
- E-Mail-Passwort prüfen (nicht Hostinger-Login!)
- E-Mail-Adresse muss bei Hostinger existieren

### Problem: E-Mail landet im Spam
**Lösung:**
- Normal bei neuen Domains
- SPF-Record setzen (siehe unten)
- DKIM aktivieren bei Hostinger

---

## 📧 SPF-Record setzen (Optional, aber empfohlen)

Im Hostinger DNS-Manager:

**Typ:** TXT  
**Name:** @  
**Wert:** `v=spf1 include:_spf.hosting.hostinger.com ~all`

Dies verbessert die E-Mail-Zustellbarkeit erheblich!

---

## 🎉 Fertig!

Nach dem Setup sollten die Passwort-Reset-E-Mails zuverlässig ankommen!

**Fragen?** Die Debug-Seite hilft:
```
https://app.mehr-infos-jetzt.de/public/test-quentn-api.php
```
