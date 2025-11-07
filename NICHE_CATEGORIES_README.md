# 🎯 Nischen-Kategorie System für Freebie-Templates

## Überblick

Das Nischen-Kategoriesystem ermöglicht es Admins, Freebie-Templates nach lukrativen Nischen zu organisieren. Diese Kategorisierung hilft Kunden, passende Templates für ihre jeweilige Branche zu finden.

## Features

### ✨ Für Admins

1. **Nischen-Auswahl beim Template-Erstellen**
   - Dropdown-Menü mit 15 profitablen Nischen + "Sonstiges"
   - Pflichtfeld bei der Template-Erstellung
   - Automatische Standard-Kategorie: "Sonstiges"

2. **Verfügbare Nischen**

| Icon | Kategorie | Slug |
|------|-----------|------|
| 💼 | Online Business & Marketing | online-business |
| 💪 | Gesundheit & Fitness | gesundheit-fitness |
| 🧠 | Persönliche Entwicklung | persoenliche-entwicklung |
| 💰 | Finanzen & Investment | finanzen-investment |
| 🏠 | Immobilien | immobilien |
| 🛒 | E-Commerce & Dropshipping | e-commerce |
| 📈 | Affiliate Marketing | affiliate-marketing |
| 📱 | Social Media Marketing | social-media |
| 🤖 | KI & Automation | ki-automation |
| 👔 | Coaching & Consulting | coaching-consulting |
| ✨ | Spiritualität & Mindfulness | spiritualitaet |
| ❤️ | Beziehungen & Dating | beziehungen-dating |
| 👨‍👩‍👧 | Eltern & Familie | eltern-familie |
| 🎯 | Karriere & Beruf | karriere-beruf |
| 🎨 | Hobbys & Freizeit | hobbys-freizeit |
| 📂 | Sonstiges | sonstiges |

### 👥 Für Kunden

1. **Nischen-Badge in Template-Karten**
   - Visuelle Kennzeichnung jedes Templates mit Nischen-Icon
   - Farb-kodierte Badges zur schnellen Orientierung
   - Anzeige sowohl bei Templates als auch eigenen Freebies

2. **Bessere Übersicht**
   - Schnelle Identifikation passender Templates
   - Professionelle Kategorisierung
   - Klare visuelle Differenzierung

## Installation

### 1. Datenbank-Migration ausführen

Rufe folgende URL im Browser auf:

```
https://app.mehr-infos-jetzt.de/database/migrate-niche-categories.php
```

Das Script:
- ✅ Prüft, ob Spalten bereits existieren
- ✅ Fügt `niche` Spalte zu `freebies` Tabelle hinzu
- ✅ Fügt `niche` Spalte zu `customer_freebies` Tabelle hinzu
- ✅ Setzt Standard-Wert "sonstiges" für alle bestehenden Einträge
- ✅ Gibt detailliertes Feedback über alle Schritte

**WICHTIG:** Nach erfolgreicher Migration das Script löschen!

### 2. Verwendung

#### Als Admin:

1. Gehe zu Admin Dashboard → Freebies → Template erstellen
2. Wähle die passende Nische aus dem Dropdown
3. Fülle alle weiteren Felder aus
4. Speichere das Template

#### Als Kunde:

- Die Nischen-Kategorie wird automatisch als Badge auf jedem Template angezeigt
- Keine Konfiguration erforderlich

## Technische Details

### Datenbankstruktur

```sql
-- freebies Tabelle
ALTER TABLE freebies 
ADD COLUMN niche VARCHAR(50) DEFAULT 'sonstiges' AFTER name;

-- customer_freebies Tabelle
ALTER TABLE customer_freebies 
ADD COLUMN niche VARCHAR(50) DEFAULT 'sonstiges' AFTER customer_id;
```

### Datei-Änderungen

| Datei | Änderung | Zweck |
|-------|----------|-------|
| `admin/sections/freebie-create.php` | Dropdown hinzugefügt | Nischen-Auswahl im Editor |
| `customer/sections/freebies.php` | Badge-Anzeige | Visuelle Kennzeichnung |
| `api/save-freebie.php` | Feld hinzugefügt | Speichern der Nische |
| `database/migrate-niche-categories.php` | Migrations-Script | Einmalige DB-Anpassung |

### Code-Beispiele

#### Nischen-Array (PHP):

```php
$niches = [
    'online-business' => '💼 Online Business & Marketing',
    'gesundheit-fitness' => '💪 Gesundheit & Fitness',
    'persoenliche-entwicklung' => '🧠 Persönliche Entwicklung',
    // ... weitere Nischen
    'sonstiges' => '📂 Sonstiges'
];
```

#### Badge-Anzeige (HTML/PHP):

```php
$nicheLabel = $nicheLabels[$freebie['niche'] ?? 'sonstiges'] ?? '📂 Sonstiges';
```

```html
<span class="freebie-badge badge-niche">
    <?php echo htmlspecialchars($nicheLabel); ?>
</span>
```

## Warum diese 15 Nischen?

Die Auswahl basiert auf:

1. **Marktgröße** - Große und wachsende Märkte
2. **Zahlungsbereitschaft** - Kunden, die in Lösungen investieren
3. **Online-Affinität** - Branchen mit hoher Digital-Nutzung
4. **Evergreen-Potenzial** - Zeitlose Themen
5. **Lead-Magnet-Tauglichkeit** - Gut geeignet für Freebies

### Top 5 Lukrative Nischen:

1. **💰 Finanzen & Investment** - Hohe Zahlungsbereitschaft, große Zielgruppe
2. **💼 Online Business & Marketing** - Ständig wachsender Markt
3. **🏠 Immobilien** - Hochpreisige Transaktionen
4. **🤖 KI & Automation** - Zukunftstrend mit hoher Nachfrage
5. **👔 Coaching & Consulting** - Premium-Preise möglich

## Best Practices

### Für Admins:

1. **Passende Nische wählen**
   - Überlege, welche Zielgruppe das Template ansprechen soll
   - Bei Unsicherheit: "Sonstiges" wählen

2. **Konsistenz**
   - Ähnliche Templates in derselben Nische gruppieren
   - Eindeutige Namen für Templates verwenden

3. **Testing**
   - Nach dem Erstellen: Template in der Vorschau prüfen
   - Badge-Anzeige im Customer-Dashboard kontrollieren

### Für Kunden:

1. **Orientierung**
   - Nutze die Nischen-Badges zur schnellen Identifikation
   - Passende Templates für deine Branche finden

2. **Anpassung**
   - Templates können individuell angepasst werden
   - Nische ist nur zur Orientierung

## Troubleshooting

### Problem: Nische wird nicht angezeigt

**Lösung:**
1. Prüfe, ob Migration durchgeführt wurde
2. Cache leeren (Browser + Server)
3. Datenbank prüfen: `SELECT niche FROM freebies LIMIT 1;`

### Problem: Migration schlägt fehl

**Lösung:**
1. Datenbankverbindung in `config/database.php` prüfen
2. Berechtigungen prüfen (ALTER TABLE)
3. Logs checken

### Problem: Alte Templates haben keine Nische

**Lösung:**
Das ist normal! Alle alten Templates haben automatisch "Sonstiges" als Standard-Nische. Du kannst diese manuell im Admin-Bereich anpassen.

## Wartung

### Regelmäßige Aufgaben:

1. **Nischen-Verteilung prüfen**
   ```sql
   SELECT niche, COUNT(*) as count 
   FROM freebies 
   GROUP BY niche 
   ORDER BY count DESC;
   ```

2. **Ungenutzte Templates aufräumen**
   - Templates ohne Nische oder mit "Sonstiges" überprüfen
   - Ggf. passende Nische zuweisen

## Updates & Erweiterungen

### Weitere Nischen hinzufügen:

1. Bearbeite `admin/sections/freebie-create.php`
2. Füge Nische zum `$niches` Array hinzu
3. Bearbeite `customer/sections/freebies.php`
4. Füge Nische zum `$nicheLabels` Array hinzu

**Beispiel:**

```php
// In beiden Dateien hinzufügen:
'neue-nische' => '🔥 Neue Nische'
```

## Support

Bei Fragen oder Problemen:

1. Prüfe diese Dokumentation
2. Checke die Datenbank-Logs
3. Kontaktiere den Support

## Version

- **Version:** 1.0
- **Datum:** 07. November 2025
- **Autor:** System
- **Status:** ✅ Produktiv

---

**Hinweis:** Nach erfolgreicher Migration das Migrations-Script (`database/migrate-niche-categories.php`) aus Sicherheitsgründen löschen!
