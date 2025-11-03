# 📋 Checklist System - Dokumentation

## 🎯 Übersicht

Das Checklist-System ermöglicht es Benutzern, ihren Fortschritt im Startplan zu speichern. Jeder gesetzte Haken wird automatisch in der Datenbank gespeichert und beim nächsten Login wiederhergestellt.

## ✅ Features

- ✅ **Automatisches Speichern**: Checkbox-States werden sofort beim Klick gespeichert
- ✅ **Persistent**: Fortschritt bleibt beim Logout/Login erhalten
- ✅ **Pro Benutzer**: Jeder Kunde hat seinen eigenen Fortschritt
- ✅ **Echtzeit-Fortschrittsbalken**: Visuelles Feedback über den Abschluss-Status
- ✅ **Tracking-Integration**: Checkbox-Änderungen werden im Tracking-System erfasst

## 🗄️ Datenbank-Schema

### Tabelle: `customer_checklist`

```sql
CREATE TABLE IF NOT EXISTS `customer_checklist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `task_id` VARCHAR(50) NOT NULL,
  `completed` TINYINT(1) DEFAULT 0,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_task` (`user_id`, `task_id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Spalten-Beschreibung

- **id**: Primärschlüssel (Auto-Increment)
- **user_id**: Referenz zum Benutzer (Foreign Key → customers.id)
- **task_id**: Eindeutige Aufgaben-ID (z.B. 'videos', 'freebie', 'template')
- **completed**: Status (0 = nicht erledigt, 1 = erledigt)
- **completed_at**: Zeitstempel wann die Aufgabe abgeschlossen wurde
- **created_at**: Erstellungszeitpunkt
- **updated_at**: Letztes Update

## 📡 API-Endpunkte

### GET /customer/api/checklist.php

Ruft den gespeicherten Fortschritt des angemeldeten Benutzers ab.

**Response:**
```json
{
  "success": true,
  "progress": {
    "videos": true,
    "rechtstexte": false,
    "freebie": true,
    "template": false,
    "lead": false
  }
}
```

### POST /customer/api/checklist.php

Speichert den Status einer einzelnen Aufgabe.

**Request Body:**
```json
{
  "task_id": "videos",
  "completed": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Progress saved"
}
```

## 🎨 Frontend-Integration

### HTML-Struktur

Die Checkboxen befinden sich in `/customer/sections/overview.php`:

```html
<input type="checkbox" 
       class="checkbox-custom" 
       data-task="videos"
       onchange="updateProgress()">
```

### JavaScript-Funktionen

#### `loadProgress()`
Lädt den gespeicherten Fortschritt beim Seitenaufruf:

```javascript
function loadProgress() {
    fetch('/customer/api/checklist.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.progress) {
                Object.keys(data.progress).forEach(task => {
                    const checkbox = document.querySelector(`[data-task="${task}"]`);
                    if (checkbox) {
                        checkbox.checked = data.progress[task];
                    }
                });
            }
            updateProgress();
        });
}
```

#### `updateProgress()`
Berechnet und aktualisiert den Fortschrittsbalken:

```javascript
function updateProgress() {
    const checkboxes = document.querySelectorAll('#checklist input[type="checkbox"]');
    const total = checkboxes.length;
    let checked = 0;
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) checked++;
    });
    
    const percentage = Math.round((checked / total) * 100);
    document.getElementById('progress-bar').style.width = percentage + '%';
    document.getElementById('progress-percentage').textContent = percentage + '%';
}
```

#### Auto-Save bei Checkbox-Änderung
```javascript
checkbox.addEventListener('change', function() {
    const task = this.getAttribute('data-task');
    const completed = this.checked;
    
    // In Datenbank speichern
    fetch('/customer/api/checklist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            task_id: task,
            completed: completed
        })
    });
    
    // Tracking-Event
    TrackingSystem.trackEvent('checklist_update', {
        task: task,
        checked: completed
    });
});
```

## 📝 Verfügbare Tasks

Aktuell sind folgende Tasks im Startplan implementiert:

| Task ID | Beschreibung |
|---------|--------------|
| `videos` | Anleitungsvideos ansehen |
| `rechtstexte` | Rechtstexte erstellen |
| `freebie` | Erstes Freebie erstellen |
| `template` | Template veröffentlichen |
| `lead` | Ersten Lead generieren |

## 🔧 Setup & Installation

### 1. Automatisches Setup (empfohlen)

Rufe im Browser auf:
```
https://app.mehr-infos-jetzt.de/setup/setup-checklist-system.php
```

Das Script:
- ✅ Prüft ob die Tabelle existiert
- ✅ Führt die Migration automatisch aus
- ✅ Zeigt den aktuellen Status an
- ✅ Testet die API-Integration
- ✅ Verifiziert die Frontend-Integration

### 2. Manuelle Installation

Falls du die Migration manuell ausführen möchtest:

```bash
# Option 1: Via Browser
https://app.mehr-infos-jetzt.de/database/run-migrations.php

# Option 2: Via PHP CLI
cd /path/to/app
php database/run-migrations.php

# Option 3: Direkte SQL-Ausführung
mysql -u username -p database_name < database/migrations/003_customer_checklist.sql
```

## 🧪 Testing

### Manueller Test

1. **Login** als Testbenutzer
2. **Öffne** `/customer/dashboard.php?page=overview`
3. **Setze** einige Häkchen bei den Aufgaben
4. **Logout** und **Login** erneut
5. **Verifiziere** dass die Häkchen noch gesetzt sind

### Browser-Console Test

```javascript
// Fortschritt abrufen
fetch('/customer/api/checklist.php')
  .then(r => r.json())
  .then(console.log);

// Aufgabe als erledigt markieren
fetch('/customer/api/checklist.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    task_id: 'videos',
    completed: true
  })
}).then(r => r.json()).then(console.log);
```

## 🐛 Troubleshooting

### Problem: Checkboxen werden nicht gespeichert

**Lösung:**
1. Prüfe Browser-Console auf JavaScript-Fehler
2. Verifiziere dass die API-Datei existiert: `/customer/api/checklist.php`
3. Prüfe die Datenbank-Tabelle: `SELECT * FROM customer_checklist;`
4. Überprüfe Session: `$_SESSION['user_id']` muss gesetzt sein

### Problem: "Not authenticated" Fehler

**Lösung:**
- Session ist nicht aktiv oder `user_id` fehlt
- Stelle sicher, dass der Benutzer eingeloggt ist
- Prüfe `session_start()` in der API-Datei

### Problem: Tabelle existiert nicht

**Lösung:**
```bash
# Führe Setup-Script aus
php setup/setup-checklist-system.php

# Oder Migration manuell
php database/run-migrations.php
```

## 📊 Analytics & Tracking

Jede Checkbox-Änderung wird zusätzlich im Tracking-System erfasst:

```javascript
TrackingSystem.trackEvent('checklist_update', {
    task: 'videos',
    checked: true
});
```

Dies ermöglicht Analysen wie:
- Welche Aufgaben werden am häufigsten abgeschlossen?
- Wie lange dauert es im Durchschnitt bis zur ersten Checkbox?
- Welche Tasks werden häufig übersprungen?

## 🔐 Sicherheit

- ✅ **Session-basierte Authentifizierung**: Nur eingeloggte Benutzer können auf die API zugreifen
- ✅ **User-Isolation**: Jeder Benutzer sieht nur seinen eigenen Fortschritt
- ✅ **SQL-Injection Schutz**: Prepared Statements in allen Queries
- ✅ **Input-Validierung**: Task-IDs werden validiert
- ✅ **Foreign Key Constraints**: Automatisches Löschen bei User-Löschung

## 🚀 Erweiterungsmöglichkeiten

### Neue Tasks hinzufügen

1. **Frontend**: Neue Checkbox in `overview.php` hinzufügen:
```html
<input type="checkbox" 
       class="checkbox-custom" 
       data-task="neue_aufgabe"
       onchange="updateProgress()">
```

2. **Keine Backend-Änderung nötig**: Das System speichert automatisch jede Task-ID!

### Benachrichtigungen bei Abschluss

```javascript
if (percentage === 100) {
    // Alle Aufgaben erledigt!
    showSuccessMessage('🎉 Glückwunsch! Alle Aufgaben erledigt!');
}
```

### Fortschritts-Export

```php
// In einer neuen Datei: /customer/api/export-progress.php
$stmt = $pdo->prepare("
    SELECT task_id, completed, completed_at 
    FROM customer_checklist 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv');
echo "Task,Status,Completed At\n";
foreach ($progress as $task) {
    echo "{$task['task_id']},{$task['completed']},{$task['completed_at']}\n";
}
```

## 📚 Verwandte Dateien

- `/customer/sections/overview.php` - Frontend mit Checkboxen
- `/customer/api/checklist.php` - API-Endpunkt
- `/database/migrations/003_customer_checklist.sql` - Datenbank-Schema
- `/setup/setup-checklist-system.php` - Setup & Verifikation
- `/customer/api/tracking.php` - Tracking-Integration

## 🆘 Support

Bei Fragen oder Problemen:
1. Prüfe diese Dokumentation
2. Führe das Setup-Script aus: `/setup/setup-checklist-system.php`
3. Überprüfe die Browser-Console auf Fehler
4. Prüfe PHP Error-Log: `/var/log/php-error.log`

---

**Version:** 1.0  
**Stand:** November 2025  
**Status:** ✅ Produktiv