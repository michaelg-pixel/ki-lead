# 🚀 KI Lead-System - Deployment & Troubleshooting

## ✅ Letzte Fixes (30. Oktober 2025)

### Behobene Probleme:
1. ✅ **URL-Protokoll**: `https://` zu allen URLs in `config/settings.php` hinzugefügt
2. ✅ **Responsive Design**: Customer Dashboard ist jetzt vollständig responsive
3. ✅ **Pfad-Auflösung**: Verbesserte Include-Pfade in allen Section-Dateien
4. ✅ **Error-Handling**: Bessere Fehlerbehandlung in Einstellungen-Seite
5. ✅ **Debug-Modus**: Temporär aktiviert für bessere Fehlermeldungen

### Vorhandene Features:
- 📱 **Responsive Customer Dashboard** mit Mobile-Navigation
- ⚙️ **Einstellungen-Seite** mit Passwort-Änderung
- 🎓 **Kurse-Übersicht**
- 📈 **Fortschritt-Tracking**
- 🎁 **Freebies-Verwaltung**

---

## 🔧 Deployment-Status prüfen

### 1. GitHub Actions überprüfen
Öffnen Sie: https://github.com/michaelg-pixel/ki-lead/actions

**✅ Erfolgreich:** Alle Schritte grün  
**❌ Fehlgeschlagen:** Siehe Logs für Details

### 2. Manuelle Server-Prüfung (SSH)

```bash
# Mit Server verbinden
ssh mehr-infos-jetzt-app@31.97.39.234

# Zum Projekt wechseln
cd /home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de

# Neuesten Code holen
git fetch origin main
git log -1 --oneline

# Prüfen ob Dateien vorhanden sind
ls -la customer/sections/
```

**Erwartetes Ergebnis:**
```
einstellungen.php
fortschritt.php
freebies.php
kurse.php
```

### 3. Dateien-Prüfung via Browser

Testen Sie die folgenden URLs:
- https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=overview ✅
- https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=einstellungen ✅
- https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=kurse ✅
- https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=fortschritt ✅

---

## 🐛 Troubleshooting

### Problem: "File not found" Fehler

**Lösung 1 - Automatisches Deployment:**
```bash
# GitHub Actions Trigger
git commit --allow-empty -m "Trigger Deployment"
git push origin main
```

**Lösung 2 - Manuelles Deployment via SSH:**
```bash
ssh mehr-infos-jetzt-app@31.97.39.234
cd /home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de
git fetch origin main
git reset --hard origin/main
chmod -R 755 customer/sections/
```

**Lösung 3 - Via Hostinger File Manager:**
1. CloudPanel / Hostinger Dashboard öffnen
2. File Manager öffnen
3. Zu `/home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/` navigieren
4. Ordner löschen und neu von GitHub clonen

### Problem: "Database connection failed"

**Prüfen Sie:**
1. `config/database.php` - Sind die Zugangsdaten korrekt?
2. CloudPanel → Databases → Credentials anzeigen
3. MySQL-Service läuft: `systemctl status mysql`

### Problem: PHP-Fehler werden nicht angezeigt

**Debug-Modus aktivieren:**
In `config/settings.php`:
```php
define('DEBUG_MODE', true);
```

### Problem: Session-Fehler

**Lösung:**
```bash
# Session-Verzeichnis Rechte prüfen
chmod 1733 /var/lib/php/sessions
```

---

## 📞 Deployment-Hilfe

**Option 1 - GitHub Actions funktioniert nicht:**
→ Manuelles SSH-Deployment (siehe oben)

**Option 2 - Kein SSH-Zugang:**
→ Hostinger File Manager verwenden

**Option 3 - Weitere Probleme:**
→ Bitte teilen Sie mir Screenshots der Fehlermeldungen mit

---

## 📝 Nächste Schritte

Nach erfolgreichem Deployment:
1. ✅ Responsive Design testen (Desktop, Tablet, Mobile)
2. ✅ Alle Seiten durchklicken
3. ✅ Passwort-Änderung testen
4. ⚠️ Debug-Modus wieder deaktivieren: `define('DEBUG_MODE', false);`

---

*Letzte Aktualisierung: 30. Oktober 2025, 21:45 Uhr*
