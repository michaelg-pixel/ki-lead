# 🚀 Flexibles Multi-Webhook-System

## Übersicht

Das neue Webhook-System erweitert die bestehende Digistore24-Integration um **unbegrenzt viele flexible Webhooks** mit folgenden Features:

### ✨ Features

- ✅ **Unbegrenzt viele Webhooks** erstellen
- ✅ **Mehrere Digistore24 Produkt-IDs** pro Webhook
- ✅ **Flexible Ressourcen**: Freebies + eigene Freebies + Videokurse + Empfehlungs-Slots
- ✅ **Upsell-Support**: Bestehende Kunden bekommen zusätzliche Ressourcen
- ✅ **3 Upsell-Modi**: ADD (addieren), UPGRADE (nur höhere Werte), REPLACE (ersetzen)
- ✅ **Aktivitäts-Tracking**: Alle Webhook-Aktivitäten werden geloggt
- ✅ **Rückwärtskompatibel**: Alte `digistore_products` Webhooks bleiben funktional

## 📦 Installation

### Schritt 1: Datenbank-Migration

1. Öffne im Browser: `https://app.mehr-infos-jetzt.de/database/migrate-webhook-system.html`
2. Klicke auf **"Migration starten"**
3. Warte bis die Migration abgeschlossen ist

Die Migration erstellt folgende Tabellen:
- `webhook_configurations` - Haupttabelle für Webhooks
- `webhook_product_ids` - M:N Verknüpfung Webhook ↔ Produkt-IDs
- `webhook_course_access` - M:N Verknüpfung Webhook ↔ Kurse
- `webhook_ready_freebies` - M:N Verknüpfung Webhook ↔ Template-Freebies
- `webhook_activity_log` - Aktivitäts-Tracking

### Schritt 2: Admin-Interface aufrufen

Gehe zu: **Admin Dashboard > Digistore24 > Webhooks** (neuer Tab)

Oder direkt: `https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=webhooks`

## 🎯 Verwendung

### Neuen Webhook erstellen

1. Klicke auf **"➕ Neuer Webhook"**
2. Fülle das Formular aus:
   - **Name**: Interner Name (z.B. "Premium Paket 2025")
   - **Beschreibung**: Optional
   - **Produkt-IDs**: Eine oder mehrere Digistore24 IDs (z.B. `639493`, `PREMIUM2025`)
   - **Eigene Freebies**: Anzahl der Freebies die Kunden erstellen können
   - **Fertige Freebies**: Anzahl fertiger Templates
   - **Empfehlungs-Slots**: Anzahl der Empfehlungsprogramm-Slots
   - **Kurszugang**: Wähle Kurse aus, die gewährt werden sollen
   - **Upsell**: Aktiviere wenn es ein Upsell ist (addiert zu bestehenden Ressourcen)
   - **Upsell-Verhalten**: 
     - **ADD**: Addiert die Werte (z.B. 5 + 10 = 15 Freebies)
     - **UPGRADE**: Nimmt den höheren Wert (z.B. max(5, 10) = 10)
     - **REPLACE**: Ersetzt komplett (z.B. 5 → 10)

3. Klicke auf **"💾 Speichern"**

### Webhook-URL in Digistore24

Die Webhook-URL ist dieselbe wie vorher:
```
https://app.mehr-infos-jetzt.de/webhook/digistore24.php
```

**WICHTIG**: Der Webhook-Handler erkennt automatisch, ob eine Produkt-ID zum neuen oder alten System gehört!

## 🔄 Upsell-Beispiele

### Beispiel 1: Starter → Pro Upgrade

**Starter Webhook:**
- Produkt-ID: `STARTER`
- Eigene Freebies: `5`
- Empfehlungs-Slots: `1`
- Upsell: `Nein`

**Pro Upsell Webhook:**
- Produkt-ID: `PRO_UPGRADE`
- Eigene Freebies: `15`
- Empfehlungs-Slots: `5`
- Upsell: `Ja` (ADD)

**Resultat für Kunden:**
- Freebies: 5 + 15 = **20 Freebies**
- Slots: 1 + 5 = **6 Slots**

### Beispiel 2: Premium Bundle

**Main Webhook:**
- Produkt-IDs: `MAIN_BUNDLE`, `MAIN_ANNUAL`
- Eigene Freebies: `10`
- Kurse: `Kurs 1`, `Kurs 2`
- Empfehlungs-Slots: `3`
- Upsell: `Nein`

**Bonus Upsell:**
- Produkt-ID: `BONUS_ADDON`
- Eigene Freebies: `5` (werden addiert)
- Kurse: `Kurs 3` (zusätzlich)
- Upsell: `Ja` (ADD)

## 📊 Aktivitäts-Tracking

Jeder Webhook loggt automatisch:
- Datum/Zeit des Kaufs
- Kunde (E-Mail & Name)
- Produkt-ID
- Gewährte Ressourcen
- Upsell (ja/nein)

**Aktivitäten anzeigen**: Klicke auf **"📊 Aktivitäten"** bei einem Webhook

## 🧪 Testing

Teste einen Webhook mit: **"🧪 Testen"** Button

Oder nutze die Test-URL:
```
/webhook/test-digistore.php?product_id=DEINE_PRODUKT_ID
```

## 🔧 Technische Details

### Webhook-Handler Logik

Der Webhook-Handler (`webhook/digistore24.php`) verarbeitet Käufe in folgender Reihenfolge:

1. **Neues System prüfen**: Suche nach Webhook-Konfiguration mit der Produkt-ID
2. **Altes System prüfen**: Falls nicht gefunden, suche in `digistore_products`
3. **Marktplatz prüfen**: Falls nicht gefunden, prüfe ob es ein Marktplatz-Freebie ist
4. **Ressourcen gewähren**: 
   - Bei Upsells: Addiere/Upgrade/Replace basierend auf Konfiguration
   - Bei Neu-Kunden: Gewähre alle Ressourcen
5. **Aktivität loggen**: Speichere die Transaktion

### Upsell-Logik

```php
// ADD Modus
$finalValue = $currentValue + $upsellValue;

// UPGRADE Modus
$finalValue = max($currentValue, $upsellValue);

// REPLACE Modus
$finalValue = $upsellValue;
```

### Source-Tracking

Alle Ressourcen werden mit einer Source markiert:
- `webhook_v4` - Neues flexibles System
- `webhook_v4_upsell` - Upsell über neues System
- `webhook` - Altes System
- `manual` - Manuell vom Admin gesetzt (wird NICHT überschrieben)

## 🔄 Migration von altem zu neuem System

Deine bestehenden Webhooks in `digistore_products` **bleiben vollständig funktional**!

Um auf das neue System umzusteigen:

1. Erstelle neue Webhook-Konfigurationen
2. Kopiere die Produkt-IDs
3. Teste die neuen Webhooks
4. Optional: Deaktiviere die alten Produkte (behalte sie aber als Backup)

## 📝 Best Practices

### 1. Eindeutige Produkt-IDs
Jede Digistore24 Produkt-ID sollte nur in EINEM Webhook sein (entweder alt oder neu).

### 2. Upsell-Struktur
Plane deine Upsells vorher:
- **Main Product** → Upsell: Nein
- **Addon 1** → Upsell: Ja (ADD)
- **Addon 2** → Upsell: Ja (ADD)

### 3. Testing
Teste immer mit der Test-Funktion vor dem Go-Live!

### 4. Naming Convention
Verwende klare Namen:
- ✅ `Starter Paket 2025`
- ✅ `Pro Upgrade - Video Bundle`
- ❌ `Test 123`

## 🆘 Troubleshooting

### Webhook wird nicht gefunden
1. Prüfe ob der Webhook **aktiv** ist
2. Prüfe ob die **Produkt-ID** korrekt ist
3. Schaue in die Webhook-Logs: `/webhook/webhook-logs.txt`

### Ressourcen werden nicht gewährt
1. Schaue in die **Aktivitäten** des Webhooks
2. Prüfe ob die Tabellen existieren (Migration laufen lassen)
3. Prüfe ob Kurse **aktiv** sind

### Upsell funktioniert nicht
1. Stelle sicher dass **"Ist ein Upsell"** aktiviert ist
2. Prüfe das **Upsell-Verhalten** (ADD/UPGRADE/REPLACE)
3. Schaue in die Aktivitäten was tatsächlich passiert ist

## 📚 API-Endpunkte

### Webhook speichern
```
POST /admin/api/webhooks/save.php
```

### Webhook löschen
```
POST /admin/api/webhooks/delete.php
```

### Webhook laden
```
GET /admin/api/webhooks/get.php?webhook_id=X
```

### Aktivitäten anzeigen
```
GET /admin/api/webhooks/activity.php?webhook_id=X
```

## 🎉 Fertig!

Das System ist jetzt einsatzbereit. Viel Erfolg mit deinem flexiblen Webhook-System!

Bei Fragen schaue in die Webhook-Logs oder in die Aktivitäten-Ansicht.
