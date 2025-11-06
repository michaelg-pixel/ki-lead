# 🎓 Videokurs-System - Installations-Guide

## ✅ Status: Alle Dateien hochgeladen!

Die folgenden Dateien wurden erfolgreich deployed:

1. ✅ **videokurs-player.php** → `/public/videokurs-player.php`
2. ✅ **freebie-danke.php** → `/public/freebie-danke.php`
3. ✅ **custom-freebie-editor.php** → `/customer/custom-freebie-editor.php`
4. ✅ **videokurs-system-updates.sql** → `/database/videokurs-system-updates.sql`

---

## 🚀 Nächste Schritte

### 1. Datenbank-Migration ausführen

```bash
# WICHTIG: Backup erstellen!
mysqldump -u username -p database_name > backup.sql

# SQL-Script ausführen
mysql -u username -p database_name < database/videokurs-system-updates.sql
```

**Kritischer Fix:** Falls `customer_id` fehlt:
```sql
UPDATE freebie_courses fc
JOIN customer_freebies cf ON fc.freebie_id = cf.id
SET fc.customer_id = cf.customer_id;
```

### 2. System testen

1. Als Kunde anmelden
2. Freebie öffnen → Tab "🎥 Videokurs"
3. Videokurs aktivieren
4. Modul + Lektion erstellen
5. Player testen

---

## 🎯 Features

**Für Kunden:**
- Tab-Navigation (Einstellungen | Videokurs)
- Module & Lektionen verwalten
- Video-URLs (YouTube/Vimeo)
- PDF-Downloads pro Lektion

**Für Teilnehmer:**
- Netflix-Style Player (Dark Theme)
- Fortschrittsbalken
- Navigation (Prev/Next)
- Mobile-Responsive

---

## 🔐 Sicherheit

**Token-System:**
```php
$token = hash('sha256', $freebie['id'] . $freebie['unique_id']);
$url = "/public/videokurs-player.php?id={$id}&token={$token}";
```

**Fortschritt:** Session-basiert (temporär) oder optional in DB speichern

---

## 🐛 Troubleshooting

### "customer_id cannot be null"
```sql
ALTER TABLE freebie_courses ADD COLUMN customer_id INT(11) NOT NULL AFTER freebie_id;
UPDATE freebie_courses fc JOIN customer_freebies cf ON fc.freebie_id = cf.id SET fc.customer_id = cf.customer_id;
```

### "Token ungültig"
- Prüfen: `has_course = 1` in customer_freebies
- Token neu generieren und vergleichen

### Video nicht sichtbar
- URL-Format: `https://youtube.com/watch?v=...` ✅
- Keine YouTube Shorts oder private Videos ❌

---

## 📞 Support

Bei Problemen bitte folgende Infos bereitstellen:
- Fehlermeldung
- PHP-Version (`php -v`)
- Browser + Version
- Screenshot

**Version:** 1.0.0 | **Datum:** November 2025
