# 🏪 Marktplatz-System - Vollständige Dokumentation

## 📋 Übersicht

Das Marktplatz-System ermöglicht es Customers, ihre eigenen Freebies über DigiStore24 zu verkaufen und Freebies von anderen Customers zu kaufen. Das System ist vollständig automatisiert und integriert mit dem bestehenden DigiStore24-Webhook.

## 🎯 Features

### Für Verkäufer:
- ✅ Eigene Freebies für den Marktplatz vorbereiten
- ✅ DigiStore24 Produkt-ID hinterlegen
- ✅ Preis, Beschreibung und Kurs-Details festlegen
- ✅ Freebies im Marktplatz aktivieren/deaktivieren
- ✅ Verkaufsstatistiken verfolgen

### Für Käufer:
- ✅ Marktplatz nach Nischen durchsuchen
- ✅ Freebies über DigiStore24 kaufen
- ✅ Automatisches Kopieren in eigenen Account
- ✅ Links werden automatisch angepasst
- ✅ Sofort nutzbar nach Kauf

## 📁 Dateien

### Datenbank-Migration
- `migrations/browser/add_marketplace_fields.html` - Fügt Marktplatz-Felder zur customer_freebies Tabelle hinzu

### API-Endpunkte
- `api/marketplace-update.php` - Aktualisiert Marktplatz-Einstellungen eines Freebies
- `api/marketplace-list.php` - Lädt alle Marktplatz-Freebies (mit Nischen-Filter)
- `api/marketplace-purchase.php` - Kopiert ein gekauftes Freebie (für manuelle Käufe)
- `api/get-freebie.php` - Erweitert um Customer-Support und Marktplatz-Felder

### Frontend
- `customer/dashboard.php` - Menüeintrag "Marktplatz" hinzugefügt
- `customer/sections/marktplatz.php` - Vollständige Marktplatz-Seite mit 2 Tabs

### Webhook
- `webhook/digistore24.php` - Erweitert um automatische Marktplatz-Käufe

## 🚀 Installation

### Schritt 1: Datenbank-Migration ausführen

Öffne in deinem Browser:
```
https://app.mehr-infos-jetzt.de/migrations/browser/add_marketplace_fields.html
```

Klicke auf "Migration starten" und warte bis die Felder hinzugefügt wurden.

### Schritt 2: Neue Dateien testen

Die Dateien wurden bereits via GitHub Actions deployed. Teste:

1. Login als Customer: https://app.mehr-infos-jetzt.de/customer/dashboard.php
2. Klicke auf "Marktplatz" im Menü
3. Du solltest 2 Tabs sehen:
   - "Meine Freebies vorbereiten"
   - "Marktplatz durchsuchen"

## 📖 Verwendung

### Als Verkäufer (Freebie anbieten)

1. **Freebie erstellen**
   - Gehe zu "Landingpages" und erstelle ein Freebie
   - Oder nutze ein bestehendes Freebie

2. **Für Marktplatz vorbereiten**
   - Gehe zu "Marktplatz" → Tab "Meine Freebies vorbereiten"
   - Klicke bei deinem Freebie auf "⚙️ Marktplatz-Einstellungen"

3. **Einstellungen konfigurieren**
   - ✅ "Im Marktplatz anzeigen" aktivieren
   - 💰 Preis festlegen (z.B. 19.99 €)
   - 🔗 DigiStore24 Produkt-ID eingeben
   - 📝 Beschreibung hinzufügen
   - 📚 Anzahl Lektionen angeben
   - ⏱️ Kursdauer angeben
   - Speichern

4. **DigiStore24 Produkt erstellen**
   - Gehe zu DigiStore24
   - Erstelle ein neues Produkt
   - Notiere dir die Produkt-ID (z.B. "12345")
   - Konfiguriere Webhook (falls noch nicht geschehen):
     - URL: `https://app.mehr-infos-jetzt.de/webhook/digistore24.php`
     - Events: payment.success, subscription.created

5. **Fertig!**
   - Dein Freebie erscheint jetzt im Marktplatz
   - Bei jedem Verkauf wird das Freebie automatisch kopiert

### Als Käufer (Freebie kaufen)

1. **Marktplatz durchsuchen**
   - Gehe zu "Marktplatz" → Tab "Marktplatz durchsuchen"
   - Optional: Nach Nische filtern

2. **Freebie kaufen**
   - Klicke bei einem Freebie auf "💳 Jetzt kaufen"
   - Du wirst zu DigiStore24 weitergeleitet
   - Schließe den Kauf ab

3. **Automatische Verarbeitung**
   - Nach dem Kauf sendet DigiStore24 einen Webhook
   - Das System erkennt den Marktplatz-Kauf
   - Das Freebie wird automatisch in deinen Account kopiert
   - Du erhältst eine E-Mail mit den Zugangsdaten (falls neu)
   - Du erhältst eine E-Mail über den erfolgreichen Kauf

4. **Freebie nutzen**
   - Gehe zu "Landingpages"
   - Das gekaufte Freebie ist jetzt in deiner Liste
   - Du kannst es bearbeiten und anpassen
   - Die Links sind automatisch auf dich angepasst

## 🔧 Datenbank-Struktur

### Neue Felder in `customer_freebies`

```sql
marketplace_enabled BOOLEAN DEFAULT FALSE
  -- Ob das Freebie im Marktplatz sichtbar ist

marketplace_price DECIMAL(10,2) DEFAULT NULL
  -- Preis für das Freebie im Marktplatz

digistore_product_id VARCHAR(255) DEFAULT NULL
  -- DigiStore24 Produkt-ID

marketplace_description TEXT DEFAULT NULL
  -- Beschreibung für Marktplatz

course_lessons_count INT DEFAULT NULL
  -- Anzahl der Lektionen

course_duration VARCHAR(100) DEFAULT NULL
  -- Dauer des Kurses (z.B. "2 Stunden", "5 Wochen")

marketplace_sales_count INT DEFAULT 0
  -- Anzahl Verkäufe (wird automatisch erhöht)

original_creator_id INT DEFAULT NULL
  -- Bei kopierten Freebies: User-ID des Original-Erstellers

copied_from_freebie_id INT DEFAULT NULL
  -- Bei kopierten Freebies: ID des Original-Freebies

marketplace_updated_at TIMESTAMP NULL DEFAULT NULL
  -- Letzte Aktualisierung der Marktplatz-Daten
```

## 🔄 Webhook-Flow

### Marktplatz-Kauf über DigiStore24

```
1. Kunde B kauft Freebie von Kunde A über DigiStore24
   ↓
2. DigiStore24 sendet Webhook an webhook/digistore24.php
   ↓
3. Webhook erkennt Marktplatz-Kauf anhand der Produkt-ID
   ↓
4. System prüft: Existiert Kunde B bereits?
   ├─ Nein → Neuen Account erstellen mit Standard-Limits
   └─ Ja → Bestehenden Account verwenden
   ↓
5. Freebie wird komplett kopiert:
   - Neues unique_id generiert
   - Alle Inhalte werden übernommen
   - Links werden auf Kunde B angepasst
   - Email-Provider wird zurückgesetzt (muss neu konfiguriert werden)
   - Original-Ersteller-ID wird gespeichert (Kunde A)
   - copied_from_freebie_id wird gespeichert
   ↓
6. Verkaufszähler bei Original-Freebie +1
   ↓
7. E-Mails werden versendet:
   - Willkommens-E-Mail an Kunde B (falls neu)
   - Kauf-Bestätigung an Kunde B
   ↓
8. Fertig! Kunde B kann Freebie sofort nutzen
```

## 📧 E-Mail-Templates

### Neue E-Mails

1. **Marktplatz-Käufer Willkommens-E-Mail**
   - Wird gesendet, wenn ein neuer Käufer einen Account erhält
   - Enthält Zugangsdaten (Email, Passwort, RAW-Code)
   - Link zum Login

2. **Marktplatz-Kauf-Bestätigung**
   - Wird nach jedem Kauf gesendet
   - Bestätigt das gekaufte Freebie
   - Link zu "Meine Freebies"

## 🔐 Sicherheit

### Berechtigungen

- ✅ Nur eigene Freebies können für Marktplatz vorbereitet werden
- ✅ Nur Freebies mit `marketplace_enabled = 1` erscheinen im Marktplatz
- ✅ Gekaufte Freebies werden als `freebie_type = 'purchased'` markiert
- ✅ Käufer können gekaufte Freebies nicht wieder im Marktplatz verkaufen (marketplace_enabled = 0)
- ✅ Email-Provider-Daten werden beim Kopieren zurückgesetzt

### Duplikat-Schutz

- ✅ System prüft ob Freebie bereits gekauft wurde
- ✅ Verhindert mehrfaches Kopieren desselben Freebies
- ✅ Webhook-Logs für Debugging

## 🎨 UI-Features

### Tab 1: Meine Freebies vorbereiten

- Grid-Layout mit allen eigenen Freebies
- Status-Badge: "✓ Aktiv" oder "○ Inaktiv"
- Nischen-Badge
- Vorschau-Bild
- Meta-Daten (Preis, Lektionen, Dauer, Verkäufe)
- Beschreibung
- Button: "⚙️ Marktplatz-Einstellungen"

### Tab 2: Marktplatz durchsuchen

- Nischen-Filter (Dropdown)
- Grid-Layout mit allen Marktplatz-Freebies
- Badges für Nische
- Creator-Name sichtbar
- Verkaufsstatistiken
- Button-Zustände:
  - "💳 Jetzt kaufen" (mit DigiStore24-Link)
  - "👤 Dein eigenes Freebie" (disabled)
  - "✓ Bereits gekauft" (disabled)
  - "⚠️ Kein Kauflink verfügbar" (disabled, falls keine Produkt-ID)

### Modal: Marktplatz-Einstellungen

- Checkbox: Im Marktplatz anzeigen
- Input: Preis (Decimal)
- Input: DigiStore24 Produkt-ID
- Textarea: Marktplatz-Beschreibung
- Input: Anzahl Lektionen (Integer)
- Input: Kursdauer (Text)
- Buttons: Speichern / Abbrechen

## 📊 API-Dokumentation

### POST /api/marketplace-update.php

Aktualisiert Marktplatz-Einstellungen eines Freebies.

**Request Body:**
```json
{
  "freebie_id": 123,
  "marketplace_enabled": true,
  "marketplace_price": 19.99,
  "digistore_product_id": "12345",
  "marketplace_description": "Beschreibung...",
  "course_lessons_count": 10,
  "course_duration": "2 Stunden"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Marktplatz-Einstellungen gespeichert"
}
```

### GET /api/marketplace-list.php

Lädt alle Marktplatz-Freebies (mit optionalem Nischen-Filter).

**Query Parameter:**
- `niche` (optional): Nischen-Filter (z.B. "online-business")

**Response:**
```json
{
  "success": true,
  "freebies": [
    {
      "id": 123,
      "customer_id": 45,
      "headline": "...",
      "marketplace_price": 19.99,
      "digistore_product_id": "12345",
      "is_own": false,
      "already_purchased": false,
      "creator_name": "Max Mustermann"
    }
  ],
  "total": 1
}
```

### POST /api/marketplace-purchase.php

Kopiert ein Freebie (für manuelle Käufe ohne DigiStore24).

**Request Body:**
```json
{
  "freebie_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Freebie erfolgreich kopiert",
  "freebie_id": 456,
  "freebie_link": "https://...",
  "thankyou_link": "https://..."
}
```

## 🐛 Troubleshooting

### Freebie erscheint nicht im Marktplatz

1. Prüfe ob `marketplace_enabled = 1`
2. Prüfe ob `digistore_product_id` gesetzt ist
3. Prüfe Webhook-Logs: `/webhook/webhook-logs.txt`

### Kauf wird nicht automatisch verarbeitet

1. Prüfe DigiStore24 Webhook-Konfiguration
2. URL muss sein: `https://app.mehr-infos-jetzt.de/webhook/digistore24.php`
3. Events aktiviert: payment.success, subscription.created
4. Prüfe Webhook-Logs: `/webhook/webhook-logs.txt`
5. Suche nach `[marketplace]` Einträgen

### Freebie wird doppelt kopiert

- System hat Duplikat-Schutz
- Prüfe `copied_from_freebie_id` in Datenbank
- Sollte nicht vorkommen

## 📝 Changelog

### Version 1.0 (2025-11-10)

- ✅ Datenbank-Migration erstellt
- ✅ API-Endpunkte implementiert
- ✅ Marktplatz-Seite erstellt (mit 2 Tabs)
- ✅ Dashboard-Menü erweitert
- ✅ Webhook-Integration für automatische Käufe
- ✅ E-Mail-Templates für Käufer
- ✅ Nischen-Filter im Marktplatz
- ✅ Verkaufsstatistiken

## 🎯 Nächste Schritte (Optional)

### Erweiterungen (Future Features)

1. **Bewertungssystem**
   - Käufer können Freebies bewerten
   - Durchschnittliche Bewertung anzeigen

2. **Provisionen**
   - Plattform-Provision bei Verkäufen
   - Automatische Provisionsabrechnung

3. **Featured Freebies**
   - Hervorgehobene Freebies auf Startseite
   - Empfehlungen basierend auf Nische

4. **Preview-Modus**
   - Vorschau des Freebies vor dem Kauf
   - Screenshots/Demo-Videos

5. **Kategorien/Tags**
   - Zusätzliche Kategorisierung
   - Mehrere Tags pro Freebie

6. **Verkäufer-Profile**
   - Öffentliches Profil mit allen Freebies
   - Verkäufer-Bewertungen

7. **Analytics für Verkäufer**
   - Detaillierte Verkaufsstatistiken
   - Umsatz-Dashboard

## 💡 Best Practices

### Für Verkäufer

1. **Hochwertige Beschreibung**
   - Erkläre den Nutzen klar
   - Beschreibe was der Käufer bekommt
   - Hebe Besonderheiten hervor

2. **Realistischer Preis**
   - Vergleiche mit ähnlichen Freebies
   - Berücksichtige den Umfang
   - Teste verschiedene Preispunkte

3. **Professionelles Mockup**
   - Nutze ein ansprechendes Vorschaubild
   - Erste Eindruck zählt

4. **Nische auswählen**
   - Ordne dein Freebie der passenden Nische zu
   - Erleichtert Käufern das Finden

### Für Käufer

1. **Email-Provider konfigurieren**
   - Nach dem Kauf musst du deinen eigenen Email-Provider einrichten
   - Gehe zu Einstellungen → Email-Integration

2. **Anpassen und Personalisieren**
   - Das gekaufte Freebie kannst du vollständig bearbeiten
   - Passe Farben, Texte und Bilder an

3. **Testen vor Veröffentlichung**
   - Teste alle Links und Funktionen
   - Prüfe die Email-Integration

## 📞 Support

Bei Fragen oder Problemen:
- Prüfe die Webhook-Logs: `/webhook/webhook-logs.txt`
- Kontaktiere den Support

## ✅ Deployment-Status

- ✅ Alle Dateien wurden via GitHub Actions deployed
- ✅ Datenbank-Migration ist bereit
- ✅ APIs sind einsatzbereit
- ✅ Frontend ist live
- ✅ Webhook ist aktiv

## 🎉 Fertig!

Das Marktplatz-System ist vollständig implementiert und einsatzbereit.

**Nächster Schritt:** Migration ausführen unter:
https://app.mehr-infos-jetzt.de/migrations/browser/add_marketplace_fields.html

Viel Erfolg! 🚀