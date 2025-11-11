# Button-Felder Migration für Videokurse

## Schnellstart

1. **Migration ausführen**: Öffne im Browser
   ```
   https://app.mehr-infos-jetzt.de/migrations/migrate_course_buttons.html
   ```

2. **Button klicken**: "Migration ausführen"

3. **Fertig!** Die Button-Felder sind jetzt verfügbar

## Was wird geändert?

Die Migration fügt 3 neue Spalten zur `courses` Tabelle hinzu:

| Spalte | Typ | Standard | Beschreibung |
|--------|-----|----------|--------------|
| `button_text` | VARCHAR(100) | NULL | Text des CTA-Buttons |
| `button_url` | VARCHAR(500) | NULL | Link/URL des Buttons |
| `button_new_window` | TINYINT(1) | 1 | In neuem Fenster öffnen |

## Verwendung nach Migration

### Im Admin-Editor
1. Gehe zu **Kursverwaltung** → Kurs auswählen → **Bearbeiten**
2. Scrolle zu **"Call-to-Action Button (Optional)"**
3. Fülle aus:
   - Button-Text: z.B. "Jetzt kaufen"
   - Button-URL: z.B. "https://digistore24.com/..."
   - Checkbox: "In neuem Fenster öffnen"
4. Speichern

### Im Course-Player
Der Button wird automatisch unter der Lektion-Info angezeigt, wenn beide Felder (Text + URL) ausgefüllt sind.

## Rollback (Falls nötig)

Falls du die Änderungen rückgängig machen möchtest:

```sql
ALTER TABLE courses
DROP COLUMN button_text,
DROP COLUMN button_url,
DROP COLUMN button_new_window;
```

## Dateien

- `migrate_course_buttons.html` - Browser-UI für Migration
- `migrate_course_buttons_process.php` - PHP-Processing Script
- `add_course_button_fields.sql` - SQL-Migration

## Support

Die Migration ist sicher und kann problemlos mehrfach ausgeführt werden (IF NOT EXISTS).

Bei Problemen:
- Prüfe Datenbank-Rechte
- Schaue in Browser-Konsole (F12)
- Teste mit phpMyAdmin ob Spalten vorhanden sind
