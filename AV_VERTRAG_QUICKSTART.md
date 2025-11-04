# 🚀 AV-Vertrag System - Quick Start

## Installation in 3 Schritten

### ⚡ Schritt 1: Installation ausführen

Öffne in deinem Browser:
```
https://app.mehr-infos-jetzt.de/install-av-vertrag.php
```

1. Klicke auf "Installation starten"
2. Warte auf die Bestätigung "✅ Installation erfolgreich!"
3. **Wichtig:** Lösche die Datei danach per FTP oder FileManager

### ✅ Schritt 2: Testen

1. Melde dich als Customer an
2. Gehe zu: **Dashboard → Einstellungen**
3. Scrolle zu **"Auftragsverarbeitungsvertrag (AV-Vertrag)"**
4. Fülle das Formular aus (Firmenname, Adresse, PLZ, Stadt)
5. Klicke **"Firmendaten speichern"**
6. Klicke **"AV-Vertrag herunterladen"**

### 🎉 Fertig!

Der personalisierte AV-Vertrag öffnet sich in einem neuen Tab.
Du kannst ihn direkt drucken oder als PDF speichern.

---

## 📋 Was wurde installiert?

✅ Datenbank-Tabelle `user_company_data`  
✅ API-Endpunkte für Speichern/Abrufen  
✅ Formular in Einstellungsseite  
✅ Download-Seite für AV-Vertrag  

## 🔒 Sicherheit

- ✅ Nur eingeloggte Kunden haben Zugriff
- ✅ Jeder Customer sieht nur seine Daten
- ✅ Alle Daten werden sicher verschlüsselt gespeichert
- ✅ SQL-Injection geschützt (Prepared Statements)
- ✅ XSS-Schutz durch htmlspecialchars()

## 📱 Features

- ✅ Vollständig responsive (Mobile, Tablet, Desktop)
- ✅ Dashboard-konformes Design
- ✅ AJAX-basiertes Speichern (keine Page-Reloads)
- ✅ Drucken/PDF-Export Funktion
- ✅ Personalisiert mit Firmendaten

## ❓ Häufige Fragen

**Q: Muss ich die Daten bei jedem Kunde neu eingeben?**  
A: Nein! Jeder Customer gibt seine Daten einmal ein. Sie werden gespeichert und können jederzeit aktualisiert werden.

**Q: Können mehrere User die gleichen Firmendaten nutzen?**  
A: Jeder User hat seine eigenen Firmendaten. Es ist aber möglich, dass mehrere User die gleiche Firma eintragen.

**Q: Wird der AV-Vertrag automatisch erstellt?**  
A: Ja! Sobald Firmendaten hinterlegt sind, wird der AV-Vertrag automatisch mit diesen Daten personalisiert.

**Q: Kann ich den AV-Vertrag nachträglich ändern?**  
A: Ja! Ändere einfach die Firmendaten in den Einstellungen und lade den Vertrag neu herunter.

**Q: Ist das DSGVO-konform?**  
A: Ja! Der AV-Vertrag entspricht den Anforderungen von Art. 28 DSGVO.

## 🐛 Probleme?

**"Tabelle existiert nicht"**  
→ Führe `install-av-vertrag.php` erneut aus

**"Nicht autorisiert"**  
→ Melde dich als Customer an (nicht als Admin)

**"Daten werden nicht gespeichert"**  
→ Prüfe die Browser-Konsole (F12) auf Fehler

**"Download-Button erscheint nicht"**  
→ Speichere erst die Firmendaten und lade dann die Seite neu (F5)

## 📞 Support

Bei weiteren Fragen siehe die ausführliche Dokumentation:
`AV_VERTRAG_README.md`

---

## 📝 Checkliste nach Installation

- [ ] `install-av-vertrag.php` aufgerufen
- [ ] Installation erfolgreich
- [ ] Datei gelöscht
- [ ] Als Customer angemeldet
- [ ] Firmendaten eingegeben
- [ ] Daten gespeichert
- [ ] AV-Vertrag heruntergeladen
- [ ] Funktioniert auf Desktop ✓
- [ ] Funktioniert auf Mobile ✓
- [ ] Funktioniert auf Tablet ✓

## 🎯 Nächste Schritte

1. ✅ Informiere deine Customers über das neue Feature
2. ✅ Teste mit verschiedenen Browsern
3. ✅ Teste auf verschiedenen Geräten
4. ✅ Erstelle Backup der Datenbank
5. ✅ Dokumentiere für dein Team

---

**Viel Erfolg! 🚀**

_Erstellt: 04.11.2025_  
_Version: 1.0.0_
