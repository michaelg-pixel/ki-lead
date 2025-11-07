# 🔒 Drip-Content System - Installation & Aktivierung

## 📋 Übersicht

Dieses System ermöglicht zeitgesteuerte Freischaltung von Kurs-Lektionen basierend auf dem Zeitpunkt der Zugangserteilung.

**Beispiel:**
- User bekommt Zugang am 1. Januar
- Lektion 1: Sofort verfügbar (Tag 0)
- Lektion 2: Verfügbar ab 2. Januar (Tag 1)
- Lektion 3: Verfügbar ab 8. Januar (Tag 7)

---

## 🚀 Installation in 2 Schritten

### Step 1: Migration ausführen
Rufe im Browser auf:
```
https://app.mehr-infos-jetzt.de/database/migrate-drip-content.php
```

**Was passiert:**
- ✅ Fügt `granted_at` Spalte zu `course_access` hinzu
- ✅ Setzt Datum für bestehende Zugangsberechtigungen
- ✅ Zeigt Statistiken über Kurse und Nutzer
- ✅ Erstellt Lockfile (Migration nur 1x ausführbar)

### Step 2: Drip-Content aktivieren
Rufe im Browser auf:
```
https://app.mehr-infos-jetzt.de/database/activate-drip-content.php
```

**Was passiert:**
- ✅ Aktiviert Drip-Content Logik in `course-view.php`
- ✅ Implementiert `granted_at` basierte Berechnungen
- ✅ Aktiviert Lektions-Sperrung mit 🔒 Icons

---

## 🎯 Verwendung

### 1. Drip-Content für Lektionen einrichten

Im Admin-Panel beim Bearbeiten einer Lektion:

**Sofort verfügbar:**
```php
unlock_after_days = 0  // Sofort nach Kurs-Zugang
```

**Verzögerte Freischaltung:**
```php
unlock_after_days = 1   // Nach 1 Tag
unlock_after_days = 7   // Nach 7 Tagen
unlock_after_days = 30  // Nach 30 Tagen
```

### 2. Nutzer Zugang geben

Wenn ein Nutzer Zugang zu einem Kurs bekommt:
```sql
INSERT INTO course_access (user_id, course_id, access_source, granted_at)
VALUES (123, 456, 'purchase', NOW());
```

Das System berechnet automatisch:
- `granted_at` = Zeitpunkt der Zugangserteilung
- Für jede Lektion: `unlock_date = granted_at + unlock_after_days`

---

## 🎨 UI Features

### Für gesperrte Lektionen:

**Sidebar:**
- 🔒 Lock-Icon statt Play-Icon
- Ausgegraut & nicht klickbar
- Badge: "🕐 Tag X"
- Alert bei Klick: "Freischaltung in Y Tagen"

**Video-Player:**
- Großer Lock-Screen mit 🔒 Icon
- "Diese Lektion ist noch gesperrt"
- "Freischaltung in X Tagen"
- Badge: "🕐 Freischaltung: Tag X"

---

## 📊 Datenbank-Schema

### course_access Tabelle (nach Migration)

```sql
CREATE TABLE course_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    access_source VARCHAR(50),
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- ← NEU!
    
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    UNIQUE KEY unique_access (user_id, course_id)
);
```

### course_lessons Tabelle (bereits vorhanden)

```sql
CREATE TABLE course_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255),
    video_url VARCHAR(500),
    unlock_after_days INT DEFAULT 0,  -- ← Drip-Content Feld
    -- ... weitere Felder
);
```

---

## 🔍 Beispiel-Queries

### Alle gesperrten Lektionen für einen User finden:

```sql
SELECT 
    l.id,
    l.title,
    l.unlock_after_days,
    DATE_ADD(ca.granted_at, INTERVAL l.unlock_after_days DAY) as unlock_date,
    DATEDIFF(DATE_ADD(ca.granted_at, INTERVAL l.unlock_after_days DAY), NOW()) as days_until_unlock
FROM course_lessons l
JOIN course_modules m ON l.module_id = m.id
JOIN course_access ca ON m.course_id = ca.course_id
WHERE ca.user_id = 123
  AND l.unlock_after_days > 0
  AND NOW() < DATE_ADD(ca.granted_at, INTERVAL l.unlock_after_days DAY);
```

### Statistik: Wie viele Lektionen nutzen Drip-Content?

```sql
SELECT 
    COUNT(*) as total_lessons,
    SUM(CASE WHEN unlock_after_days = 0 THEN 1 ELSE 0 END) as immediate_lessons,
    SUM(CASE WHEN unlock_after_days > 0 THEN 1 ELSE 0 END) as drip_lessons,
    AVG(unlock_after_days) as avg_delay_days
FROM course_lessons;
```

---

## ⚠️ Wichtige Hinweise

### Für bestehende Nutzer:
- Migration setzt `granted_at = NOW()` für alle bestehenden Zugangsberechtigungen
- Das bedeutet: **Bestehende Nutzer sehen alle Lektionen sofort freigeschaltet**
- Nur neue Nutzer erleben die zeitgesteuerte Freischaltung

### Um bestehende Nutzer auch zu sperren:
```sql
-- Setze granted_at auf ein Datum in der Zukunft
UPDATE course_access 
SET granted_at = DATE_SUB(NOW(), INTERVAL 30 DAY)
WHERE user_id = 123 AND course_id = 456;
-- Dieser User hat "seit 30 Tagen" Zugang
```

### Sicherheit:
- Migration ist nur 1x ausführbar (Lockfile-Schutz)
- Bei Problemen: Lösche `/database/drip-content-migration.lock` und führe erneut aus
- Backup der Datenbank vor Migration empfohlen

---

## 🛠 Troubleshooting

### Problem: "Migration bereits ausgeführt"
**Lösung:** Lösche die Lockfile:
```bash
rm /home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/database/drip-content-migration.lock
```

### Problem: "Spalte granted_at nicht gefunden"
**Lösung:** Führe Migration erneut aus:
```
https://app.mehr-infos-jetzt.de/database/migrate-drip-content.php
```

### Problem: Lektionen sind nicht gesperrt
**Prüfungen:**
1. Wurde Aktivierungs-Script ausgeführt?
2. Ist `unlock_after_days > 0` für die Lektion?
3. Hat der User ein `granted_at` Datum in `course_access`?
4. Ist `granted_at` in der Vergangenheit?

**Debug-Query:**
```sql
SELECT 
    l.title,
    l.unlock_after_days,
    ca.granted_at,
    DATE_ADD(ca.granted_at, INTERVAL l.unlock_after_days DAY) as unlock_date,
    NOW() as current_time,
    CASE 
        WHEN NOW() < DATE_ADD(ca.granted_at, INTERVAL l.unlock_after_days DAY) 
        THEN 'LOCKED' 
        ELSE 'UNLOCKED' 
    END as status
FROM course_lessons l
JOIN course_modules m ON l.module_id = m.id
JOIN course_access ca ON m.course_id = ca.course_id
WHERE ca.user_id = 123;
```

---

## 📝 Changelog

### Version 3.2 - Drip-Content System
- ✅ `granted_at` Spalte in `course_access`
- ✅ Zeitbasierte Lektions-Sperrung
- ✅ Lock-Screen UI für gesperrte Videos
- ✅ "Tag X" Badges in Sidebar
- ✅ Web-basierte Migration (kein Passwort)
- ✅ Automatische Aktivierung

### Version 3.1 - Modern UI
- ✅ Video links, Sidebar rechts
- ✅ Prominente Video-Tabs
- ✅ Smooth Animations
- ⏸️ Drip-Content temporär deaktiviert

---

## 🎉 Fertig!

Das Drip-Content System ist jetzt einsatzbereit!

**Nächste Schritte:**
1. ✅ Migration ausgeführt
2. ✅ System aktiviert
3. 🎯 Teste mit einem Kurs
4. 📊 Überwache Nutzer-Feedback

**Support:** Bei Fragen schaue in die Dokumentation oder kontaktiere den Entwickler.
