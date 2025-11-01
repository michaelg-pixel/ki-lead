# Kundengesteuerte Freebie-Erstellung - Dokumentation

## 🎯 Übersicht

Dieses System ermöglicht es Kunden, eigene Freebie-Seiten zu erstellen, basierend auf ihrem Tarif. Die Anzahl der erlaubten Freebies wird durch Digistore24 Webhooks automatisch gesteuert.

## 📊 Funktionsweise

### 1. Tarif-basierte Limitierung

Jeder Kunde erhält basierend auf seinem gekauften Produkt ein Limit für eigene Freebies:

| Produkt-ID | Produkt Name | Freebie-Limit |
|-----------|-------------|---------------|
| STARTER_001 | Starter Paket | 5 |
| PROFESSIONAL_002 | Professional Paket | 10 |
| ENTERPRISE_003 | Enterprise Paket | 25 |
| UNLIMITED_004 | Unlimited Paket | 999 |

### 2. Webhook-Integration

Wenn ein Kunde über Digistore24 kauft:
1. Webhook empfängt Kauf-Event
2. System erstellt/aktualisiert Kunde
3. **NEU:** System setzt Freebie-Limit basierend auf Produkt-ID
4. Kurs-Zugang wird gewährt

```php
// Beispiel Webhook-Flow
Digistore24 → webhook/digistore24.php → setFreebieLimit() → customer_freebie_limits
```

### 3. Kundenfunktionen

#### Dashboard (dashboard.php?page=freebies)
- Zeigt verfügbare Templates
- Zeigt eigene erstellte Freebies
- Zeigt aktuelles Limit und Nutzung
- Button "Eigenes Freebie erstellen" (nur wenn Limit nicht erreicht)

#### Custom Freebie Editor (customer/custom-freebie-editor.php)
Kunden können:
- Interner Name festlegen
- Überschriften (Pre-, Main-, Subheadline) definieren
- Button-Text anpassen
- E-Mail Optin-Code einfügen
- Danke-Nachricht erstellen
- Farben (Hintergrund & Primär) wählen
- Layout auswählen (Zentriert, Sidebar, Hybrid)
- Bullet Points hinzufügen/entfernen
- Kurs verknüpfen (optional)
- **Live-Vorschau** während der Bearbeitung

#### Automatische Link-Generierung
Nach dem Speichern erhält der Kunde automatisch:
- **Freebie-Link**: `https://domain.de/freebie/index.php?id={unique_id}`
- **Danke-Seiten-Link**: `https://domain.de/freebie/thankyou.php?id={id}&customer={customer_id}`

## 🗄️ Datenbank-Struktur

### Neue Tabellen

#### `customer_freebie_limits`
Speichert die Freebie-Limits pro Kunde:
```sql
- id (INT, Primary Key)
- customer_id (INT, Foreign Key → users.id)
- freebie_limit (INT, Default: 0)
- product_id (VARCHAR)
- product_name (VARCHAR)
- granted_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `product_freebie_config`
Konfiguration der Produkt-Limits:
```sql
- id (INT, Primary Key)
- product_id (VARCHAR, Unique)
- product_name (VARCHAR)
- freebie_limit (INT, Default: 5)
- is_active (TINYINT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Erweiterte Tabelle

#### `customer_freebies` (erweitert)
Neues Feld:
- `freebie_type` ENUM('template', 'custom') - Unterscheidet zwischen Template-basierten und eigenen Freebies

## 🔧 Installation

### 1. Datenbank Setup
```bash
# SQL-Skript ausführen
mysql -u username -p database < setup/setup-customer-freebie-limits.sql
```

### 2. Produkt-Konfiguration anpassen
Falls nötig, passe die Produkt-IDs und Limits an:
```sql
INSERT INTO product_freebie_config (product_id, product_name, freebie_limit) 
VALUES ('YOUR_PRODUCT_ID', 'Dein Paket Name', 15)
ON DUPLICATE KEY UPDATE freebie_limit = 15;
```

### 3. Webhook konfigurieren
In Digistore24:
1. Gehe zu Einstellungen → IPN/Webhook
2. Trage ein: `https://deine-domain.de/webhook/digistore24.php`
3. Wähle Events: `payment.success`, `subscription.created`, `refund.created`

## 📁 Dateistruktur

```
ki-lead/
├── customer/
│   ├── dashboard.php                    # Hauptdashboard
│   ├── custom-freebie-editor.php       # Editor für eigene Freebies (NEU)
│   └── sections/
│       └── freebies.php                 # Freebie-Übersicht (erweitert)
├── api/
│   ├── save-custom-freebie.php         # API: Speichern (NEU)
│   └── delete-custom-freebie.php       # API: Löschen (NEU)
├── webhook/
│   └── digistore24.php                  # Webhook Handler (erweitert)
└── setup/
    └── setup-customer-freebie-limits.sql # DB Setup (NEU)
```

## 🎨 Features

### Für Kunden
✅ Eigene Freebie-Seiten erstellen  
✅ Unbegrenztes Bearbeiten  
✅ Live-Vorschau im Editor  
✅ Automatische Link-Generierung  
✅ Limit-Anzeige im Dashboard  
✅ Template-Freebies ODER eigene Freebies  
✅ Farben & Layouts anpassbar  
✅ Bullet Points Management  
✅ Kurs-Verknüpfung  

### Für Admins
✅ Tarif-basierte Limitierung  
✅ Automatische Verwaltung via Webhook  
✅ Flexibles Produkt-Konfigurations-System  
✅ Upgrade-Logic (höheres Limit überschreibt niedrigeres)  
✅ Refund-Handling (Limit auf 0)  

## 🔄 Workflow-Beispiel

### Neuer Kunde kauft "Professional Paket"
1. ✅ Digistore24 sendet Webhook
2. ✅ System erstellt Kunde-Account
3. ✅ System gewährt Kurs-Zugang
4. ✅ **System setzt Freebie-Limit auf 10**
5. ✅ Kunde kann bis zu 10 eigene Freebies erstellen

### Kunde erstellt Freebie
1. ✅ Klickt "Eigenes Freebie erstellen"
2. ✅ Füllt Formular aus (Headline, Farben, etc.)
3. ✅ Sieht Live-Vorschau
4. ✅ Klickt "Speichern"
5. ✅ System erstellt Freebie mit `freebie_type = 'custom'`
6. ✅ Generiert unique_id
7. ✅ Zeigt Links für Marketing

### Kunde erreicht Limit
1. ❌ Button "Eigenes Freebie erstellen" wird deaktiviert
2. 💡 Zeigt "Limit erreicht"
3. ✅ Kunde kann bestehende Freebies bearbeiten
4. ✅ Kunde kann bestehende Freebies löschen (gibt Platz frei)

## 🛡️ Sicherheit

- ✅ Session-basierte Authentifizierung
- ✅ Customer-ID-Validierung bei allen Operationen
- ✅ Limit-Prüfung vor jeder Erstellung
- ✅ SQL-Injection-Schutz durch Prepared Statements
- ✅ XSS-Schutz durch htmlspecialchars()
- ✅ Nur Eigentümer kann eigene Freebies bearbeiten/löschen

## 🐛 Troubleshooting

### Problem: Limit wird nicht gesetzt
**Lösung:** 
1. Prüfe `product_freebie_config` Tabelle
2. Stelle sicher, dass Produkt-ID korrekt ist
3. Prüfe Webhook-Logs in `webhook/webhook-logs.txt`

### Problem: Kunde sieht "Limit erreicht" obwohl nicht erreicht
**Lösung:**
```sql
-- Prüfe aktuelles Limit
SELECT * FROM customer_freebie_limits WHERE customer_id = X;

-- Prüfe Anzahl Custom Freebies
SELECT COUNT(*) FROM customer_freebies 
WHERE customer_id = X AND freebie_type = 'custom';
```

### Problem: Links funktionieren nicht
**Lösung:**
1. Prüfe ob `unique_id` gesetzt ist
2. Stelle sicher dass Freebie gespeichert wurde
3. Prüfe `/freebie/index.php` und `/freebie/thankyou.php` Dateien

## 📈 Zukünftige Erweiterungen

- [ ] Freebie-Templates duplizieren
- [ ] Statistiken pro Freebie (Views, Conversions)
- [ ] A/B-Testing für Freebies
- [ ] Bild-Upload für Freebies
- [ ] Export-Funktion (HTML)
- [ ] WhatsApp/SMS Optin Integration
- [ ] Erweiterte Design-Optionen

## 📞 Support

Bei Fragen oder Problemen:
1. Prüfe Webhook-Logs: `webhook/webhook-logs.txt`
2. Prüfe Datenbank-Konsistenz
3. Kontaktiere Support mit Fehlerdetails

---

**Version:** 1.0.0  
**Erstellt:** November 2025  
**Autor:** KI Leadsystem Team
