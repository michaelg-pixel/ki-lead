# 🚀 Mailgun Empfehlungsprogramm

Automatisches Belohnungs-Email-System für das KI Leadsystem basierend auf Mailgun.

## 📋 Was ist das?

Dieses System ersetzt die komplexe Kunden-API-Integration durch ein eigenes, unabhängiges Mailgun-basiertes Email-System. Leads erhalten automatisch Belohnungs-Emails, wenn sie bestimmte Empfehlungsstufen erreichen - komplett unabhängig vom Autoresponder des Kunden.

## ✨ Features

- ✅ **Mailgun-Integration** - Professioneller Email-Versand über Mailgun API
- ✅ **Automatische Belohnungs-Emails** - Bei Erreichen von Stufen automatisch
- ✅ **Vereinfachtes Setup** - Kunde braucht nur Impressum, keine API-Config
- ✅ **IP-Tracking & Rate Limiting** - Betrugsschutz integriert
- ✅ **DSGVO-konform** - Unsubscribe-Links, Datenschutz, Account-Löschung
- ✅ **Event-Tracking** - Email-Öffnungen und Klicks tracken
- ✅ **Responsive HTML-Templates** - Professionelle, mobile-optimierte Emails

## 🚀 Schnellstart

### Automatisches Deployment (Empfohlen)

```bash
cd mailgun/
./DEPLOY.sh
```

### Manuell

```bash
# 1. Dateien kopieren
cp config/mailgun.php /pfad/zum/projekt/config/
cp includes/MailgunService.php /pfad/zum/projekt/includes/
cp templates/emails/* /pfad/zum/projekt/templates/emails/

# 2. Migration ausführen
php migrate_mailgun.php

# 3. Testen
php test_mailgun.php
```

## 📖 Dokumentation

- **[IMPLEMENTIERUNGS_GUIDE.md](IMPLEMENTIERUNGS_GUIDE.md)** - Vollständige Schritt-für-Schritt Anleitung
- **[ZUSAMMENFASSUNG.md](ZUSAMMENFASSUNG.md)** - Projekt-Zusammenfassung
- **[DATEI_UEBERSICHT.md](DATEI_UEBERSICHT.md)** - Dateistruktur-Dokumentation

## 💰 Kosten

- **Free Tier:** 5.000 Emails/Monat kostenlos
- **Pay-as-you-go:** $0.80 pro 1.000 Emails
- **Bei 1000 Leads:** ~$2-5/Monat

## 📞 Support

Bei Problemen siehe [IMPLEMENTIERUNGS_GUIDE.md](IMPLEMENTIERUNGS_GUIDE.md) → Troubleshooting-Sektion

---

**Entwickelt für:** Opt-in Pilot / Michael  
**Version:** 1.0.0  
**Stand:** November 2025
