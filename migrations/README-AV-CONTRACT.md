# 🔒 AV-Vertrags-Zustimmungen - Schnellstart

## ⚡ Installation in 3 Schritten

### Schritt 1: Migration ausführen

**Option A - Via Browser:**
```
https://app.mehr-infos-jetzt.de/migrations/migrate_av_contract_acceptances.php
```

**Option B - Via SSH:**
```bash
cd /home/[dein-user]/app.mehr-infos-jetzt.de
php migrations/migrate_av_contract_acceptances.php
```

**Option C - Via phpMyAdmin:**
1. Öffne phpMyAdmin
2. Wähle deine Datenbank
3. Gehe zu "SQL"
4. Kopiere den Inhalt von `migrations/create_av_contract_acceptances.sql`
5. Führe aus

### Schritt 2: Prüfen

Die Migration sollte diese Ausgabe zeigen:
```
✅ Tabelle 'av_contract_acceptances' erfolgreich erstellt!

📋 Tabellenstruktur:
--------------------------------------------------------------------------------
Spalte                    Typ                  NULL       Schlüssel
--------------------------------------------------------------------------------
id                        int(11)              NO         PRI
user_id                   int(11)              NO         MUL
accepted_at               datetime             NO         
ip_address                varchar(45)          NO         
user_agent                text                 NO         
av_contract_version       varchar(20)          YES        
acceptance_type           enum(...)            NO         
created_at                timestamp            NO         
--------------------------------------------------------------------------------

✨ Migration erfolgreich abgeschlossen!
```

### Schritt 3: Testen

1. **Neue Registrierung testen:**
   - Gehe zu: https://app.mehr-infos-jetzt.de/public/register.php
   - Registriere einen Test-Benutzer
   - Prüfe, ob Zustimmung gespeichert wurde

2. **Admin-Ansicht prüfen:**
   - Gehe zu: https://app.mehr-infos-jetzt.de/admin/av-contract-acceptances.php
   - Login als Admin
   - Sieh dir die Zustimmungen an

## 📊 Was wird gespeichert?

Für jeden neuen Benutzer wird AUTOMATISCH gespeichert:

| Feld | Beispiel | Beschreibung |
|------|----------|--------------|
| `user_id` | 42 | Verknüpfung zum Benutzer |
| `accepted_at` | 2025-01-20 14:23:45 | Zeitpunkt der Zustimmung |
| `ip_address` | 185.123.45.67 | IP-Adresse (IPv4/IPv6) |
| `user_agent` | Mozilla/5.0... | Browser-Information |
| `av_contract_version` | 1.0 | Version des AV-Vertrags |
| `acceptance_type` | registration | Art der Zustimmung |

## 🔍 Verwendung im Code

### In register.php (bereits implementiert)
```php
require_once '../includes/av_contract_helpers.php';

// Bei erfolgreicher Registrierung
$av_saved = saveAvContractAcceptance($pdo, $user_id, 'registration', '1.0');
```

### Eigene Verwendung
```php
// Letzte Zustimmung abrufen
$acceptance = getLatestAvContractAcceptance($pdo, $user_id);

// Alle Zustimmungen (Historie)
$all = getAllAvContractAcceptances($pdo, $user_id);

// Prüfen ob Zustimmung existiert
if (hasAvContractAcceptance($pdo, $user_id)) {
    echo "Benutzer hat zugestimmt!";
}

// Anzeige formatieren
echo formatAvAcceptanceDisplay($acceptance);
```

## 📱 Admin-Interface

Zugriff: **https://app.mehr-infos-jetzt.de/admin/av-contract-acceptances.php**

Features:
- ✅ Übersicht aller Zustimmungen
- ✅ Filter nach Typ (Registrierung, Update, Erneuerung)
- ✅ Suche nach Name, E-Mail, IP
- ✅ Pagination (50 pro Seite)
- ✅ Statistiken
- ✅ Export-fähig

## 🛡️ Sicherheit

### IP-Adresse
Die Funktion `getClientIpAddress()` erkennt automatisch:
- CloudFlare Proxy
- Nginx Reverse Proxy
- Standard Proxy
- Direkte Verbindungen

### Transaktion
Die Speicherung erfolgt innerhalb einer Transaktion:
```php
$pdo->beginTransaction();
// User erstellen
// AV-Zustimmung speichern
$pdo->commit(); // Oder rollback() bei Fehler
```

### Foreign Key
Bei Löschung des Users werden automatisch auch die Zustimmungen gelöscht:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

## 📋 Checkliste

- [ ] Migration ausgeführt
- [ ] Tabelle erstellt (siehe phpMyAdmin)
- [ ] Test-Registrierung durchgeführt
- [ ] Eintrag in `av_contract_acceptances` vorhanden
- [ ] Admin-Seite funktioniert
- [ ] IP-Adresse wird korrekt erkannt
- [ ] User-Agent wird gespeichert

## 🆘 Troubleshooting

### "Table doesn't exist"
→ Migration noch nicht ausgeführt
→ Führe `migrate_av_contract_acceptances.php` aus

### "Foreign key constraint fails"
→ `users` Tabelle existiert nicht oder hat andere Struktur
→ Prüfe: `DESCRIBE users;` in phpMyAdmin

### "IP-Adresse ist 'unknown'"
→ Server-Konfiguration blockiert Header
→ Prüfe: `$_SERVER['REMOTE_ADDR']`

### "Keine Einträge sichtbar"
→ Prüfe, ob User-Rolle = 'admin'
→ Prüfe: `SELECT * FROM av_contract_acceptances;`

## 📞 Support

Bei Fragen:
- Dokumentation: `docs/AV-CONTRACT-TRACKING.md`
- GitHub: [Repository Issues]
- E-Mail: support@mehr-infos-jetzt.de

## 🎯 Nächste Schritte

1. **Datenschutzerklärung aktualisieren**
   - Erwähne Speicherung von IP-Adressen
   - Zweck: Nachweispflicht gem. Art. 28 DSGVO

2. **AV-Vertrag versionieren**
   - Bei Änderungen Version erhöhen (z.B. 2.0)
   - Neue Zustimmung einholen: `saveAvContractAcceptance($pdo, $user_id, 'update', '2.0')`

3. **Export-Funktion**
   - Für Audits und rechtliche Prüfungen
   - CSV oder PDF-Export der Zustimmungen

4. **E-Mail-Bestätigung** (optional)
   - Sende E-Mail mit Zustimmungsdetails
   - Zusätzlicher Nachweis

## ✅ Fertig!

Das System ist jetzt einsatzbereit und DSGVO-konform! 🎉
