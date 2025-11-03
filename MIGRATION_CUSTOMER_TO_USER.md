# 🔄 MIGRATION: customer_id → user_id

Komplette Anleitung zur Umstellung des gesamten Systems von `customer_id` auf `user_id`.

---

## 📋 Übersicht

Diese Migration benennt folgendes um:

### 🗄️ Datenbank-Tabellen:
- `customer_freebies` → `user_freebies`
- `customer_freebie_limits` → `user_freebie_limits`
- `customer_courses` → `user_courses`
- `customer_progress` → `user_progress`
- `customer_tutorials` → `user_tutorials`

### 📊 Datenbank-Spalten:
- `customer_id` → `user_id` (in ALLEN Tabellen)

### 💻 PHP-Code:
- `$_SESSION['customer_id']` → `$_SESSION['user_id']`
- `$customer_id` → `$userId`
- `$customer` → `$user`

### 🌐 API-Endpoints:
- `/api/referral/get-customer-details.php` → `/api/referral/get-user-details.php`
- URL-Parameter: `?customer_id=` → `?user_id=`

---

## ⚠️ WICHTIGE VORBEREITUNG

### 1. **Backup erstellen!**
```bash
# Datenbank-Backup
mysqldump -u root -p ki_lead > backup_$(date +%Y%m%d_%H%M%S).sql

# Code-Backup (optional)
tar -czf backup_code_$(date +%Y%m%d_%H%M%S).tar.gz .
```

### 2. **Wartungsmodus aktivieren**
```bash
# Erstelle Wartungsmodus-Seite
touch maintenance.html
# In .htaccess: Leite alle Requests zu maintenance.html um
```

### 3. **Alle Scripts herunterladen**
```bash
chmod +x find-frontend-files.sh
chmod +x update-frontend-customer-to-user.sh
chmod +x migrate-database-customer-to-user.sh
```

---

## 🚀 MIGRATIONS-PROZESS

### **Schritt 1: Analyse durchführen**

```bash
./find-frontend-files.sh
```

Dieser Befehl zeigt dir:
- Alle betroffenen Dateien
- Anzahl der Vorkommen
- Was geändert werden muss

**Output-Beispiel:**
```
🔍 SUCHE NACH FRONTEND-DATEIEN...
📄 admin/users.php
   → 'customer_id' gefunden (23x)
📄 customer/dashboard.php
   → 'customer_id' gefunden (15x)
...
📊 Statistik:
  • Betroffene Dateien: 15
  • Gefundene Matches: 247
```

---

### **Schritt 2: Backend/API aktualisieren**

✅ **Bereits erledigt!** Die API-Dateien wurden bereits aktualisiert:

- ✅ `api/referral/export-stats.php`
- ✅ `api/referral/get-user-details.php` (umbenannt)
- ✅ `api/referral/get-fraud-log.php`
- ✅ `api/referral/get-stats.php`
- ✅ `api/referral/register-lead.php`
- ✅ `api/referral/toggle.php`
- ✅ `api/referral/track.php`
- ✅ `api/referral/update-company.php`

---

### **Schritt 3: Frontend aktualisieren**

```bash
./update-frontend-customer-to-user.sh
```

**Was passiert:**
- Scannt alle `.php` und `.js` Dateien
- Ersetzt automatisch:
  - `customer_id` → `user_id`
  - `customerId` → `userId`
  - `$customer_id` → `$userId`
  - API-Endpoints
- Erstellt `.backup` Dateien

**Output:**
```
🔄 AUTOMATISCHE FRONTEND-ANPASSUNG
📝 Bearbeite: admin/users.php
   ✅ 23 Ersetzungen
📝 Bearbeite: customer/dashboard.php
   ✅ 15 Ersetzungen
...
🎉 FERTIG!
📊 Statistik:
  • Bearbeitete Dateien: 15
  • Durchgeführte Änderungen: 247
```

---

### **Schritt 4: Datenbank migrieren**

```bash
./migrate-database-customer-to-user.sh
```

**Was passiert:**
1. Erstellt SQL-Migrationsdatei
2. Benennt Tabellen um
3. Benennt Spalten um
4. Aktualisiert Foreign Keys
5. Aktualisiert Indizes

**WICHTIG:** 
- Script fragt nach Bestätigung
- Benötigt MySQL-Credentials
- Kann manuell ausgeführt werden mit:
  ```bash
  mysql -u root -p ki_lead < migrate-customer-to-user.sql
  ```

---

### **Schritt 5: Testen!**

**Checkliste:**
- [ ] Login funktioniert
- [ ] Dashboard lädt korrekt
- [ ] Freebies werden angezeigt
- [ ] Referral-System funktioniert
- [ ] Admin-Panel funktioniert
- [ ] API-Endpoints antworten
- [ ] Keine PHP-Fehler in Logs
- [ ] Keine JavaScript-Fehler

**Test-Commands:**
```bash
# PHP-Fehler checken
tail -f /var/log/apache2/error.log

# JavaScript-Fehler: Browser Console öffnen (F12)
```

---

### **Schritt 6: Aufräumen**

```bash
# Backup-Dateien löschen (wenn alles funktioniert)
find . -name '*.backup' -delete

# SQL-Datei archivieren
mv migrate-customer-to-user.sql ./database/migrations/
```

---

## 🔄 ROLLBACK (Falls nötig)

### **Option 1: Backup wiederherstellen**
```bash
# Datenbank wiederherstellen
mysql -u root -p ki_lead < backup_TIMESTAMP.sql

# Code-Backup wiederherstellen
rm -rf * && tar -xzf backup_code_TIMESTAMP.tar.gz
```

### **Option 2: Backup-Dateien nutzen**
```bash
# Alle .backup Dateien wiederherstellen
for file in $(find . -name "*.backup"); do
    original="${file%.backup}"
    cp "$file" "$original"
    echo "Wiederhergestellt: $original"
done
```

### **Option 3: Git Reset**
```bash
git reset --hard COMMIT_BEFORE_MIGRATION
git push -f origin main
```

---

## 📊 Betroffene Dateien

### **Backend (API):**
- `api/referral/export-stats.php` ✅
- `api/referral/get-user-details.php` ✅
- `api/referral/get-fraud-log.php` ✅
- `api/referral/get-stats.php` ✅
- `api/referral/register-lead.php` ✅
- `api/referral/toggle.php` ✅
- `api/referral/track.php` ✅
- `api/referral/update-company.php` ✅

### **Frontend:**
- `admin/users.php` 🔄
- `admin/dashboard.php` 🔄
- `customer/dashboard.php` 🔄
- `customer/freebies.php` 🔄
- `customer/my-freebies.php` 🔄
- `includes/auth.php` 🔄
- `includes/ReferralHelper.php` 🔄

### **Datenbank:**
- `user_freebies` 🔄
- `user_freebie_limits` 🔄
- `referral_clicks` 🔄
- `referral_conversions` 🔄
- `referral_leads` 🔄
- `referral_stats` 🔄
- `referral_fraud_log` 🔄

Legende:
- ✅ = Bereits aktualisiert
- 🔄 = Wird durch Scripts aktualisiert

---

## 🆘 Häufige Probleme

### **Problem 1: "Foreign key constraint fails"**
**Lösung:**
```sql
-- Foreign Keys temporär deaktivieren
SET FOREIGN_KEY_CHECKS=0;
-- Migration durchführen
SOURCE migrate-customer-to-user.sql;
-- Foreign Keys wieder aktivieren
SET FOREIGN_KEY_CHECKS=1;
```

### **Problem 2: "Table doesn't exist"**
**Lösung:**
```sql
-- Prüfe welche Tabellen existieren
SHOW TABLES;
-- Passe SQL-Script entsprechend an
```

### **Problem 3: "Access denied"**
**Lösung:**
```bash
# Prüfe MySQL-User und -Passwort
mysql -u root -p

# Falls nötig: Berechtigungen erteilen
GRANT ALL PRIVILEGES ON ki_lead.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

### **Problem 4: "Session lost nach Migration"**
**Lösung:**
```php
// In includes/auth.php:
// Setze beide Session-Variablen temporär
$_SESSION['user_id'] = $userId;
$_SESSION['customer_id'] = $userId; // Fallback für alte Code-Teile
```

---

## 📞 Support

Bei Problemen:
1. Prüfe die Logs: `/var/log/apache2/error.log`
2. Prüfe Browser Console (F12)
3. Erstelle ein Backup vor jedem Versuch
4. Kontaktiere Support mit:
   - Fehler-Logs
   - Screenshots
   - Welcher Schritt fehlgeschlagen ist

---

## ✅ Erfolgs-Bestätigung

Nach erfolgreicher Migration solltest du sehen:

```bash
mysql> SHOW TABLES;
+----------------------------+
| Tables_in_ki_lead          |
+----------------------------+
| user_freebies              |  ✅
| user_freebie_limits        |  ✅
| user_courses               |  ✅
| referral_clicks            |  ✅
| referral_conversions       |  ✅
+----------------------------+
```

```bash
mysql> DESCRIBE user_freebies;
+-----------+-------------+
| Field     | Type        |
+-----------+-------------+
| id        | int(11)     |
| user_id   | int(11)     |  ✅ (war: customer_id)
| ...       | ...         |
+-----------+-------------+
```

---

## 🎉 Migration abgeschlossen!

Wenn alles funktioniert:
1. ✅ Wartungsmodus deaktivieren
2. ✅ Monitoring aktivieren
3. ✅ Team informieren
4. ✅ Backup-Dateien archivieren
5. ✅ Dokumentation aktualisieren

**Viel Erfolg! 🚀**
