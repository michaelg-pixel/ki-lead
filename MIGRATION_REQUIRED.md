# 🚀 Migrations-Anleitung - WICHTIG!

## ⚠️ AKTION ERFORDERLICH

Nach dem letzten Update muss eine Datenbank-Migration ausgeführt werden, um die volle Funktionalität wiederherzustellen.

## 📋 Was wurde geändert?

1. **Empfehlungsprogramm** zeigt jetzt auch freigeschaltete Template-Freebies
2. **Datenbank-Struktur** wurde erweitert für Freebie-Verknüpfung mit Belohnungen

## 🔧 Migration ausführen

### Schnellste Methode (empfohlen):
```
https://app.mehr-infos-jetzt.de/database/run-migrations.php
```

### Alternative: Direkt in der Datenbank
Führe diese SQL-Datei aus:
```
database/migrations/2025-11-04_add_freebie_id_to_reward_definitions.sql
```

## ✅ Verifizierung

Nach der Migration:

1. **Dashboard testen:**
   - Gehe zu: Dashboard → Empfehlungsprogramm
   - Prüfe ob neu freigeschaltete Freebies sichtbar sind

2. **Belohnungen testen:**
   - Erstelle eine neue Belohnungsstufe
   - Sollte ohne Fehler speichern

3. **Datenbank prüfen:**
   ```sql
   DESCRIBE reward_definitions;
   -- Spalte "freebie_id" sollte existieren
   ```

## 📖 Detaillierte Dokumentation

Siehe: [EMPFEHLUNGSPROGRAMM_FIX_2025-11-04.md](./EMPFEHLUNGSPROGRAMM_FIX_2025-11-04.md)

## 🐛 Bei Problemen

1. Prüfe Error-Logs
2. Stelle sicher, dass die Migration vollständig durchgelaufen ist
3. Bei SQL-Fehlern: Migration ist idempotent, kann erneut ausgeführt werden

---

**Status nach Migration:**
- [x] Empfehlungsprogramm zeigt alle Freebies
- [x] Belohnungen können mit Freebies verknüpft werden
- [x] Keine Datenbank-Konflikte mehr
