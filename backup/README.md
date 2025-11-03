# 💾 Backup-Dokumentation

Dieser Ordner enthält wichtige Backup-Dokumentationen für das KI Lead System.

## 📋 Verfügbare Dokumentationen

- **[BACKUP_ZUSAMMENFASSUNG.md](BACKUP_ZUSAMMENFASSUNG.md)** - Übersicht über gesicherte Komponenten
- **[BACKUP_INDEX.md](BACKUP_INDEX.md)** - Vollständiger Index aller Systemkomponenten
- **[SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)** - Technische Systemübersicht und Architektur

## 🎯 Zweck

Diese Dokumentationen dienen als:
- **Wiederherstellungs-Guide** bei Systemausfällen
- **Übersicht** aller wichtigen Systemkomponenten
- **Referenz** für neue Entwickler
- **Checkliste** für Deployment und Migration

## ⚠️ Wichtiger Sicherheitshinweis

**Sensitive Daten wie Datenbank-Passwörter werden NICHT in diesem Repository gespeichert!**

Aus Sicherheitsgründen müssen folgende Dateien lokal konfiguriert werden:
- `config/database.php` - Datenbank-Zugangsdaten
- `config/settings.php` - SMTP-Passwörter, API-Keys

## 🔄 Schnell-Wiederherstellung

Bei Datenverlust oder Neuaufbau:

```bash
# 1. Repository klonen
git clone https://github.com/michaelg-pixel/ki-lead.git
cd ki-lead

# 2. Konfigurationsdateien anpassen
# config/database.php mit echten DB-Credentials füllen
# config/settings.php mit echten URLs/Keys füllen

# 3. Datenbank-Migrationen ausführen
# Siehe database/migrations/

# 4. Upload-Verzeichnisse erstellen
mkdir -p uploads/mockups uploads/courses uploads/thumbnails

# 5. Berechtigungen setzen
chmod -R 755 uploads/
```

## 📚 Weitere Dokumentation

- **[README.md](../README.md)** - Haupt-Projekt-Dokumentation
- **[DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md)** - Deployment-Anleitung
- **[database/README.md](../database/README.md)** - Datenbank-Migrationen

## 🆘 Support

Bei Fragen zur Wiederherstellung:
- GitHub Issues: https://github.com/michaelg-pixel/ki-lead/issues
- Live-System: https://app.mehr-infos-jetzt.de

---

**Letzte Aktualisierung**: 03. November 2025
