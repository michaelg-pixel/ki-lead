# 🎯 Erweiterte Limits-Verwaltung - Installation

## 📋 Übersicht

Dieses Update erweitert das System um zwei wichtige Features:

1. **Admin kann Limits manuell anpassen** (Freebie-Limits & Empfehlungs-Slots)
2. **Customer sieht seine verfügbaren Slots** im Empfehlungsprogramm-Dashboard

---

## 🚀 Installation in 4 Schritten

### Schritt 1: Datenbank-Setup

```
https://app.mehr-infos-jetzt.de/database/setup-digistore-products.php
```

Falls bereits ausgeführt, überspringe diesen Schritt.

---

### Schritt 2: Referral-Slots Tabellen erstellen

```
https://app.mehr-infos-jetzt.de/database/migrate-referral-slots.php
```

Erstellt die notwendigen Tabellen:
- `customer_referral_slots`
- `customer_freebies` 
- Fügt `is_template` Spalte hinzu

---

### Schritt 3: Admin-Oberfläche erweitern

```
https://app.mehr-infos-jetzt.de/database/update-users-limits-management.php
```

Fügt zur Admin-Kundenverwaltung hinzu:
- 📊 Button "Limits verwalten"
- Modal zum Anpassen der Limits

---

### Schritt 4: Customer-Dashboard erweitern

```
https://app.mehr-infos-jetzt.de/database/update-empfehlungsprogramm-limits.php
```

Fügt zum Empfehlungsprogramm hinzu:
- Limits-Banner mit Fortschrittsbalken
- Anzeige verfügbarer Slots
- Warnungen bei erreichtem Limit

---

## ✅ Fertig!

Nach erfolgreicher Installation kannst du:

### Als Admin:
1. Gehe zu **Admin-Dashboard → Kunden**
2. Klicke bei einem Kunden auf **📊**
3. Passe die Limits an:
   - 🎁 Freebie-Limit (Anzahl eigener Freebies)
   - 🚀 Empfehlungs-Slots (Anzahl Empfehlungsprogramme)
4. Speichern!

### Als Customer:
1. Gehe zu **Dashboard → Empfehlungsprogramm**
2. Sieh deine Limits:
   - Wie viele Freebies du noch erstellen kannst
   - Wie viele Empfehlungsprogramme du noch nutzen kannst
3. Fortschrittsbalken zeigen die Nutzung visuell an

---

## 🎯 Wie funktioniert es?

### Automatische Limits (via Webhook):
Wenn ein Kunde ein Produkt über Digistore24 kauft:
- **Launch (497€):** 4 eigene + 4 fertige Freebies, 1 Empfehlungs-Slot
- **Starter (49€/M):** 4 eigene Freebies, 1 Empfehlungs-Slot
- **Pro (99€/M):** 8 eigene Freebies, 3 Empfehlungs-Slots
- **Business (199€/M):** 20 eigene Freebies, 10 Empfehlungs-Slots

### Manuelle Limits (via Admin):
Der Admin kann die Limits jederzeit überschreiben:
- Für spezielle Kunden höhere Limits setzen
- Für Tests temporär mehr Freebies erlauben
- Individuelle Anpassungen nach Bedarf

---

## 📊 Die neuen APIs

### Für Admin:
- `POST /api/customer-update-limits.php` - Limits aktualisieren
- `GET /api/customer-get-limits.php` - Aktuelle Limits abrufen

### Parameter:
```javascript
{
  user_id: 123,
  freebie_limit: 10,      // Optional
  referral_slots: 5        // Optional
}
```

---

## 🔧 Troubleshooting

### Limits werden nicht angezeigt?

1. Prüfe ob die Tabellen existieren:
   ```sql
   SHOW TABLES LIKE 'customer_referral_slots';
   SHOW TABLES LIKE 'customer_freebie_limits';
   ```

2. Prüfe die Webhook-Logs:
   ```
   /webhook/webhook-logs.txt
   ```

### Admin-Button erscheint nicht?

1. Gehe zu:
   ```
   https://app.mehr-infos-jetzt.de/database/update-users-limits-management.php
   ```
2. Führe das Update erneut aus

### Customer sieht keine Limits?

1. Gehe zu:
   ```
   https://app.mehr-infos-jetzt.de/database/update-empfehlungsprogramm-limits.php
   ```
2. Führe das Update erneut aus

---

## 📝 Datenbank-Struktur

### `customer_freebie_limits`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| customer_id | INT | User-ID |
| freebie_limit | INT | Max. Anzahl Freebies |
| product_id | VARCHAR | Digistore Produkt-ID |
| product_name | VARCHAR | Produktname |

### `customer_referral_slots`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| customer_id | INT | User-ID |
| total_slots | INT | Gesamt Slots |
| used_slots | INT | Genutzte Slots |

---

## 🎉 Das war's!

Das System ist jetzt vollständig eingerichtet und:
- ✅ Webhook setzt automatisch Limits basierend auf Produkt
- ✅ Admin kann Limits manuell anpassen
- ✅ Customer sieht seine verfügbaren Limits
- ✅ Warnungen bei erreichtem Limit

Bei Fragen oder Problemen siehe die Logs:
- `/webhook/webhook-logs.txt`
- Browser-Konsole (F12)

---

**Stand:** November 2025  
**Version:** 1.0
