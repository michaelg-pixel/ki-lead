# ⚠️ KRITISCH: Konflikt-Fixes für Limits-System

## 🔴 Gefundene Probleme

### Problem 1: Webhook überschreibt manuelle Admin-Änderungen
**Status:** ❌ KRITISCH  
**Beschreibung:**
- Admin setzt Kunde manuell auf 15 Freebies
- Kunde kauft später ein Produkt mit 20 Freebies
- Webhook überschreibt die manuelle Einstellung

**Lösung:** ✅ Source-Tracking implementiert

---

### Problem 2: Keine globale Tarif-Synchronisation
**Status:** ❌ KRITISCH  
**Beschreibung:**
- Admin ändert in `digistore_products`: Starter von 4 → 6 Freebies
- Bestehende Starter-Kunden haben weiterhin 4
- Keine automatische Aktualisierung

**Lösung:** ✅ Sync-Funktion erstellt

---

### Problem 3: Fehlende Produkt-Referenz
**Status:** ❌ KRITISCH  
**Beschreibung:**
- `customer_referral_slots` hat KEINE `product_id` Spalte
- Kann nicht tracken welches Produkt die Slots gesetzt hat
- Kann nicht synchronisieren bei Tarif-Änderungen

**Lösung:** ✅ Schema erweitert

---

## 🔧 Installation der Fixes (4 Schritte)

### Schritt 1: Datenbank-Schema erweitern
```
https://app.mehr-infos-jetzt.de/database/fix-limits-conflicts.php
```

**Was wird gemacht:**
- Fügt `source` Spalte zu `customer_freebie_limits` hinzu
- Fügt `product_id`, `product_name`, `source` zu `customer_referral_slots` hinzu
- Markiert bestehende Einträge als `source='webhook'`
- Erstellt Unique Constraints

---

### Schritt 2: Webhook aktualisieren
**Datei:** `/webhook/digistore24.php`

**Manuelle Änderung erforderlich!**

Ersetze die Funktionen `setFreebieLimit()` und `setReferralSlots()` mit den verbesserten Versionen aus:
```
/webhook/webhook-source-tracking-update.php
```

**Was wird verbessert:**
- Prüft `source` Spalte vor Update
- Überschreibt NICHT wenn `source='manual'`
- Speichert Produkt-Referenz
- Detailliertes Logging

---

### Schritt 3: Admin-API ist bereits aktualisiert
✅ `/api/customer-update-limits.php` - Setzt `source='manual'`  
✅ `/api/product-sync-limits.php` - Neue Sync-API

Keine weiteren Schritte nötig!

---

### Schritt 4: Admin-Interface erweitern
**Datei:** `/admin/sections/digistore.php`

Füge den Sync-Button zu jedem Produkt hinzu (siehe `/admin/sections/digistore-sync-button.php`)

**Optional:** Automatisches Update-Script erstellen

---

## 📊 Neue Datenbank-Struktur

### `customer_freebie_limits`
```sql
+--------------+------------------------+-------------------------------+
| Spalte       | Typ                    | Beschreibung                  |
+--------------+------------------------+-------------------------------+
| id           | INT                    | Primärschlüssel               |
| customer_id  | INT                    | User-ID                       |
| freebie_limit| INT                    | Max. Anzahl Freebies          |
| product_id   | VARCHAR(100)           | Digistore Produkt-ID          |
| product_name | VARCHAR(255)           | Produktname                   |
| source       | ENUM('webhook',        | Quelle der Limit-Setzung      |
|              |      'manual',         |                               |
|              |      'upgrade')        |                               |
| created_at   | TIMESTAMP              | Erstellt am                   |
| updated_at   | TIMESTAMP              | Aktualisiert am               |
+--------------+------------------------+-------------------------------+
```

### `customer_referral_slots`
```sql
+--------------+------------------------+-------------------------------+
| Spalte       | Typ                    | Beschreibung                  |
+--------------+------------------------+-------------------------------+
| id           | INT                    | Primärschlüssel               |
| customer_id  | INT                    | User-ID (UNIQUE)              |
| product_id   | VARCHAR(100)           | Digistore Produkt-ID          |
| product_name | VARCHAR(255)           | Produktname                   |
| total_slots  | INT                    | Gesamt Slots                  |
| used_slots   | INT                    | Genutzte Slots                |
| source       | ENUM('webhook',        | Quelle der Slot-Setzung       |
|              |      'manual',         |                               |
|              |      'upgrade')        |                               |
| created_at   | TIMESTAMP              | Erstellt am                   |
| updated_at   | TIMESTAMP              | Aktualisiert am               |
+--------------+------------------------+-------------------------------+
```

---

## 🎯 Konflikt-Regeln

### Regel 1: Manuelle Limits haben Vorrang
```
if (source === 'manual') {
    // Webhook darf NICHT überschreiben
    return;
}
```

### Regel 2: Webhook nur Upgrades
```
if (new_limit > existing_limit) {
    // Upgrade erlaubt
    update();
} else {
    // Kein Downgrade
    skip();
}
```

### Regel 3: Sync respektiert Manuelle
```
// Standard-Sync
sync(overwrite_manual: false) // Überspringt manuelle

// Erzwungener Sync (mit Warnung!)
sync(overwrite_manual: true)  // Überschreibt auch manuelle
```

---

## 🔄 Neue Features

### 1. Source-Tracking
Jedes Limit hat jetzt eine Quelle:
- **webhook** - Automatisch vom Webhook gesetzt
- **manual** - Manuell vom Admin gesetzt
- **upgrade** - Vom Upgrade-System (zukünftig)

### 2. Globale Tarif-Synchronisation
Admin kann jetzt:
1. Limits in `digistore_products` ändern
2. Auf "Alle Kunden aktualisieren" klicken
3. ALLE Kunden mit diesem Tarif werden aktualisiert

**Optionen:**
- Manuelle Limits respektieren (Standard)
- Manuelle Limits überschreiben (mit Warnung)

### 3. Detaillierte Statistik
Nach Sync sieht Admin:
```
✅ Synchronisation erfolgreich!

📊 Statistik:
- Betroffene Kunden: 42
- Freebie-Limits aktualisiert: 38
- Referral-Slots aktualisiert: 40
- Manuell gesetzte übersprungen: 4
```

---

## 🧪 Test-Szenarien

### Szenario 1: Manuelle Limits geschützt ✅
```
1. Admin setzt Kunde A manuell auf 15 Freebies
   → source = 'manual'
   
2. Kunde A kauft Pro Abo (8 Freebies)
   → Webhook prüft: source === 'manual'
   → Webhook überschreibt NICHT
   
3. Ergebnis: Kunde A hat weiterhin 15 Freebies ✅
```

### Szenario 2: Webhook-Upgrade ✅
```
1. Kunde B hat Starter (4 Freebies)
   → source = 'webhook'
   
2. Kunde B upgraded zu Pro (8 Freebies)
   → Webhook prüft: 8 > 4 && source === 'webhook'
   → Webhook upgraded
   
3. Ergebnis: Kunde B hat jetzt 8 Freebies ✅
```

### Szenario 3: Downgrade verhindert ✅
```
1. Kunde C hat Business (20 Freebies)
   → source = 'webhook'
   
2. Kunde C kauft versehentlich Starter (4 Freebies)
   → Webhook prüft: 4 < 20
   → Webhook downgradet NICHT
   
3. Ergebnis: Kunde C behält 20 Freebies ✅
```

### Szenario 4: Globaler Tarif-Sync ✅
```
1. Admin ändert Starter: 4 → 6 Freebies

2. Admin klickt "Alle Kunden aktualisieren"
   
3. System prüft:
   - Kunde D: source='webhook' → Update auf 6 ✅
   - Kunde E: source='manual' (hatte 10) → Übersprungen ✅
   - Kunde F: source='webhook' → Update auf 6 ✅
   
4. Statistik:
   - 3 Kunden betroffen
   - 2 aktualisiert
   - 1 übersprungen (manuell)
```

---

## 📝 API-Dokumentation

### POST /api/customer-update-limits.php
**Beschreibung:** Manuelle Limits für einzelnen Kunden setzen

**Parameter:**
```javascript
{
  user_id: 123,
  freebie_limit: 10,      // Optional
  referral_slots: 5        // Optional
}
```

**Response:**
```javascript
{
  success: true,
  message: "Limits erfolgreich manuell aktualisiert",
  updated: ["Freebie-Limit: 10 (manuell)", "Empfehlungs-Slots: 5 (manuell)"],
  warning: "Diese Limits sind jetzt als 'manuell' markiert..."
}
```

**Wichtig:** Setzt automatisch `source='manual'`!

---

### POST /api/product-sync-limits.php
**Beschreibung:** Alle Kunden eines Tarifs synchronisieren

**Parameter:**
```javascript
{
  product_id: "STARTER2024",
  overwrite_manual: false  // Optional, default: false
}
```

**Response:**
```javascript
{
  success: true,
  message: "Tarif erfolgreich synchronisiert",
  stats: {
    total_customers: 42,
    freebies_updated: 38,
    referrals_updated: 40,
    manual_skipped: 4
  },
  product: {
    name: "Starter Abo",
    freebies: 6,
    referral_slots: 1
  }
}
```

---

## ⚠️ Wichtige Hinweise

### 1. Backup vor Schema-Änderungen!
```sql
-- Backup erstellen
CREATE TABLE customer_freebie_limits_backup AS 
SELECT * FROM customer_freebie_limits;

CREATE TABLE customer_referral_slots_backup AS 
SELECT * FROM customer_referral_slots;
```

### 2. Webhook-Update ist manuell
Die Webhook-Funktionen müssen manuell ersetzt werden! Siehe:
`/webhook/webhook-source-tracking-update.php`

### 3. Bestehende Daten werden als 'webhook' markiert
Alle existierenden Limits werden automatisch als `source='webhook'` markiert.

### 4. Logging
Alle Admin-Aktionen werden in `admin_logs` geloggt:
- `customer_limits_manual_update` - Manuelle Einzel-Updates
- `product_sync` - Globale Sync-Aktionen

---

## 🔍 Troubleshooting

### Problem: Sync aktualisiert nicht
**Prüfe:**
1. Ist das Produkt aktiv? (`is_active=1`)
2. Haben Kunden die richtige `product_id`?
3. Sind die Spalten vorhanden? (`SHOW COLUMNS FROM ...`)

### Problem: Webhook überschreibt trotzdem
**Prüfe:**
1. Wurde Webhook-Code aktualisiert?
2. Hat Limit `source='manual'`?
3. Check `/webhook/webhook-logs.txt`

### Problem: Duplikate in customer_referral_slots
**Lösung:**
```sql
-- Zeige Duplikate
SELECT customer_id, COUNT(*) as count 
FROM customer_referral_slots 
GROUP BY customer_id 
HAVING count > 1;

-- Automatisch bereinigt durch fix-limits-conflicts.php
```

---

## ✅ Checkliste

Nach Installation prüfen:

- [ ] `source` Spalte in `customer_freebie_limits` vorhanden
- [ ] `product_id`, `product_name`, `source` in `customer_referral_slots` vorhanden
- [ ] Webhook verwendet neue Funktionen mit Source-Check
- [ ] Admin-API setzt `source='manual'`
- [ ] Sync-Button in Digistore-Admin verfügbar
- [ ] Test: Manuelles Limit wird nicht überschrieben
- [ ] Test: Webhook-Upgrade funktioniert
- [ ] Test: Globaler Sync funktioniert
- [ ] Logs zeigen Details

---

**Stand:** November 2025  
**Version:** 2.0 - Mit Konflikt-Schutz  
**Kritikalität:** HOCH - Installation dringend empfohlen!
