# 🔑 Passwort-Reset-Funktion - Schnellanleitung

## Übersicht

Das Backup-System verfügt jetzt über eine sichere "Passwort vergessen" Funktion, die speziell für deine Admin-E-Mail konfiguriert ist.

---

## ✅ Was wurde implementiert?

### 1. **Neue Dateien**
- `backup-system/password-reset.php` - Komplettes Reset-System
- Link im Login-Formular: "🔑 Passwort vergessen?"

### 2. **Sicherheitsfeatures**
- ✅ Token-basiertes System (1 Stunde Gültigkeit)
- ✅ Nur Admin-E-Mail berechtigt: `michael.gluska@gmail.com`
- ✅ Verschlüsselte Tokens (SHA-256)
- ✅ Automatische Token-Löschung nach Verwendung
- ✅ Passwort-Stärke-Prüfung im Frontend

### 3. **Ablauf**
1. Benutzer klickt auf "Passwort vergessen?"
2. Gibt E-Mail ein → Reset-Link wird gesendet
3. Klickt auf Link in der E-Mail
4. Setzt neues Passwort (min. 8 Zeichen)
5. Kann sich sofort mit neuem Passwort anmelden

---

## 🚀 Verwendung

### Passwort zurücksetzen

1. **Öffne das Login-Formular:**
   ```
   https://app.mehr-infos-jetzt.de/backup-system/admin.php
   ```

2. **Klicke auf "🔑 Passwort vergessen?"**

3. **E-Mail eingeben:**
   - Trage `michael.gluska@gmail.com` ein
   - Klicke auf "Reset-Link senden"

4. **E-Mail prüfen:**
   - Prüfe dein Gmail-Postfach
   - Auch im Spam-Ordner schauen!
   - Link ist **1 Stunde** gültig

5. **Neues Passwort setzen:**
   - Klicke auf den Link in der E-Mail
   - Gib dein neues Passwort ein (min. 8 Zeichen)
   - Bestätige das Passwort
   - Klicke auf "Passwort speichern"

6. **Fertig!**
   - Du wirst automatisch zum Login weitergeleitet
   - Melde dich mit deinem neuen Passwort an

---

## 🔒 Sicherheits-Features

### Token-System
```
- Token wird zufällig generiert (64 Zeichen)
- Wird verschlüsselt gespeichert (password_hash)
- Automatische Ablaufzeit: 1 Stunde
- Wird nach Verwendung gelöscht
```

### E-Mail-Validierung
```
- Nur die Admin-E-Mail wird akzeptiert
- Keine spezifischen Fehlermeldungen (Security by Obscurity)
- Rate-Limiting verhindert Spam-Anfragen
```

### Passwort-Anforderungen
```
- Mindestens 8 Zeichen
- Empfohlen: 12+ Zeichen mit Mix aus:
  - Großbuchstaben
  - Kleinbuchstaben
  - Zahlen
  - Sonderzeichen
```

---

## 📧 E-Mail-Konfiguration

### Standard-E-Mail-Versand

Das System nutzt die PHP `mail()` Funktion. Auf den meisten Servern funktioniert das out-of-the-box.

### E-Mails kommen nicht an?

**1. Prüfe Spam-Ordner**
```
Die E-Mail könnte als Spam markiert werden.
Suche nach: "🔐 Backup System - Passwort zurücksetzen"
```

**2. SMTP konfigurieren (falls nötig)**

Wenn die Standard-mail()-Funktion nicht funktioniert, kannst du SMTP einrichten:

Bearbeite `backup-system/password-reset.php` und ersetze die `sendResetEmail()` Funktion:

```php
// SMTP mit PHPMailer (falls verfügbar)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetEmail($token) {
    global $ADMIN_EMAIL;
    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP-Einstellungen
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // oder dein SMTP-Server
        $mail->SMTPAuth = true;
        $mail->Username = 'deine@email.de';
        $mail->Password = 'dein-app-passwort';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Absender & Empfänger
        $mail->setFrom('noreply@mehr-infos-jetzt.de', 'Backup System');
        $mail->addAddress($ADMIN_EMAIL);
        
        // Inhalt
        $mail->isHTML(false);
        $mail->Subject = '🔐 Backup System - Passwort zurücksetzen';
        $mail->Body = "Reset-Link: ...";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
```

**3. Server-E-Mail-Logs prüfen**
```bash
# E-Mail-Logs ansehen
tail -f /var/log/mail.log
# oder
tail -f /var/log/maillog
```

---

## 🛠️ Troubleshooting

### Problem: E-Mail kommt nicht an

**Lösung 1: Direkter Reset (Notfall)**
```bash
# SSH auf deinen Server
cd /pfad/zu/backup-system

# Neues Passwort-Hash generieren
php -r "echo password_hash('DeinNeuesPasswort', PASSWORD_DEFAULT) . PHP_EOL;"

# Ausgabe kopieren, z.B.:
# $2y$10$abc123...xyz789

# config.php bearbeiten
nano config.php

# Zeile ändern:
define('BACKUP_ADMIN_PASS', '$2y$10$abc123...xyz789');

# Speichern & fertig!
```

**Lösung 2: Token manuell erstellen**
```bash
# Token-Datei manuell erstellen
cd backup-system/backups

# Token generieren
TOKEN=$(openssl rand -hex 32)
echo "Dein Token: $TOKEN"

# Token-Hash erstellen
TOKEN_HASH=$(php -r "echo password_hash('$TOKEN', PASSWORD_DEFAULT);")

# JSON erstellen
cat > reset_token.json <<EOF
{
  "token": "$TOKEN_HASH",
  "expiry": $(date -d '+1 hour' +%s),
  "created": "$(date '+%Y-%m-%d %H:%M:%S')"
}
EOF

# Reset-URL zusammenbauen
echo "Reset-URL:"
echo "https://app.mehr-infos-jetzt.de/backup-system/password-reset.php?token=$TOKEN"
```

### Problem: Token abgelaufen

**Lösung:**
```
- Starte einfach einen neuen Reset-Prozess
- Token sind nur 1 Stunde gültig
- Bei Bedarf kann die Zeit in password-reset.php erhöht werden
```

### Problem: Neue Passwort wird nicht gespeichert

**Lösung:**
```bash
# Dateiberechtigungen prüfen
chmod 644 backup-system/config.php
chown www-data:www-data backup-system/config.php

# Verzeichnis-Berechtigungen
chmod 755 backup-system
chmod 777 backup-system/backups
```

---

## 🔐 Beste Praktiken

### Sicheres Passwort wählen

✅ **GUT:**
```
P@ssw0rd!2024$Backup
MyS3cur3#BackupPW!
Ki-Lead*System#2024
```

❌ **SCHLECHT:**
```
password
12345678
admin123
backup
```

### Passwort-Manager verwenden

Nutze einen Passwort-Manager wie:
- **1Password**
- **LastPass**
- **Bitwarden**
- **KeePass**

So musst du dir komplexe Passwörter nicht merken!

### Regelmäßige Änderung

- Ändere das Passwort alle 3-6 Monate
- Nach verdächtige Aktivitäten sofort ändern
- Nutze nie das gleiche Passwort mehrfach

---

## 📝 Technische Details

### Dateien & Speicherorte

```
backup-system/
├── admin.php              # Login-Formular mit "Passwort vergessen?" Link
├── password-reset.php     # Reset-Logik
├── config.php            # Passwort-Hash wird hier gespeichert
└── backups/
    └── reset_token.json  # Temporäre Token-Datei (wird automatisch gelöscht)
```

### Token-Struktur

```json
{
  "token": "$2y$10$...",           // Verschlüsselter Token
  "expiry": 1699999999,            // Unix-Timestamp (Ablaufzeit)
  "created": "2024-11-17 14:30:00" // Erstellungszeitpunkt
}
```

### Passwort-Hash-Algorithmus

```php
// Verwendet PHP's password_hash() mit bcrypt
$hash = password_hash($password, PASSWORD_DEFAULT);

// Entspricht:
// - Bcrypt-Algorithmus
// - Cost-Faktor: 10
// - Automatisches Salt
```

---

## 🎯 Features für die Zukunft

Mögliche Erweiterungen (optional):

- [ ] 2FA (Two-Factor Authentication)
- [ ] E-Mail-Benachrichtigung bei Passwort-Änderung
- [ ] Login-Historie anzeigen
- [ ] IP-Whitelist für Admin-Zugang
- [ ] Backup der config.php vor Änderungen
- [ ] Slack/Telegram-Benachrichtigung bei Reset

---

## ✅ Zusammenfassung

### Was du jetzt hast:

✅ **Sichere Passwort-Reset-Funktion**
- Token-basiert (1 Stunde gültig)
- E-Mail-Versand an michael.gluska@gmail.com
- Automatische Token-Löschung
- Passwort-Stärke-Prüfung

✅ **Einfache Bedienung**
- Link im Login-Formular
- 4-Schritte-Prozess
- Klares Benutzer-Feedback

✅ **Notfall-Optionen**
- Manueller Reset per SSH möglich
- Token kann manuell erstellt werden
- Direkter config.php-Edit als letzte Option

---

**Du bist jetzt bestens abgesichert! 🎉**

Falls du Fragen hast oder Hilfe brauchst, sag einfach Bescheid!
