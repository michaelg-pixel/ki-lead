# ⚡ QUICK FIX - Limits-Konflikte beheben

## 🚨 Dringende Updates!

Es wurden **kritische Konflikte** im Limits-System gefunden und behoben.

---

## ⚡ 3-Minuten-Installation

### 1️⃣ Datenbank-Schema fixen
```
https://app.mehr-infos-jetzt.de/database/fix-limits-conflicts.php
```
✅ Fügt `source` Spalten hinzu  
✅ Erweitert `customer_referral_slots`  
✅ Markiert bestehende Daten

---

### 2️⃣ Webhook aktualisieren
⚠️ **Manuelle Änderung erforderlich!**

Öffne: `/webhook/digistore24.php`

Ersetze `setFreebieLimit()` und `setReferralSlots()` mit den Versionen aus:
```
/webhook/webhook-source-tracking-update.php
```

**Kritisch:** Ohne diesen Schritt werden manuelle Admin-Limits überschrieben!

---

### 3️⃣ Fertig! ✅
Admin-API ist bereits aktualisiert.  
Sync-Funktion ist verfügbar.

---

## 🎯 Was wurde behoben?

### Problem 1: Webhook überschreibt Admin-Limits ❌
**Vorher:**
```
1. Admin setzt Kunde auf 15 Freebies (manuell)
2. Webhook kommt mit 20 Freebies
3. Webhook überschreibt → 20 (falsch!)
```

**Jetzt:** ✅
```
1. Admin setzt Kunde auf 15 Freebies (source='manual')
2. Webhook kommt mit 20 Freebies
3. Webhook prüft: source='manual' → überschreibt NICHT
4. Kunde behält 15 Freebies ✅
```

---

### Problem 2: Keine globale Tarif-Verwaltung ❌
**Vorher:**
```
1. Admin ändert Starter: 4 → 6 Freebies
2. Bestehende Starter-Kunden haben weiterhin 4
3. Keine Möglichkeit zum Aktualisieren
```

**Jetzt:** ✅
```
1. Admin ändert Starter: 4 → 6 Freebies
2. Admin klickt "Alle Kunden aktualisieren"
3. System aktualisiert alle Starter-Kunden auf 6
4. Respektiert manuelle Limits (überspringbar)
```

---

### Problem 3: Fehlende Produkt-Referenz ❌
**Vorher:**
```
customer_referral_slots hatte KEINE product_id
→ Konnte nicht synchronisieren
→ Konnte nicht tracken
```

**Jetzt:** ✅
```
customer_referral_slots hat:
- product_id
- product_name  
- source (webhook/manual)
→ Vollständiges Tracking
→ Sync möglich
```

---

## 🔧 Neue Features

### Admin kann jetzt:
1. ✅ Individuelle Limits setzen (werden geschützt)
2. ✅ Globale Tarif-Limits ändern
3. ✅ Alle Kunden eines Tarifs synchronisieren
4. ✅ Manuelle Limits optional überschreiben

### Webhook jetzt:
1. ✅ Respektiert manuelle Admin-Limits
2. ✅ Speichert Produkt-Referenz
3. ✅ Nur Upgrades, nie Downgrades
4. ✅ Detailliertes Logging

---

## 📊 Neue Admin-Funktionen

### Individuelle Limits (pro Kunde)
```
Admin → Kunden → 📊 Limits verwalten
- Freebie-Limit anpassen
- Empfehlungs-Slots anpassen
→ Wird als 'manual' markiert
→ Webhook überschreibt NICHT
```

### Globale Tarif-Synchronisation
```
Admin → Digistore24 → Produkt bearbeiten
- Limits ändern
- "Alle Kunden aktualisieren" klicken
→ Alle Kunden mit diesem Tarif werden aktualisiert
→ Manuelle Limits werden respektiert
```

---

## ⚠️ WICHTIG

### Webhook MUSS manuell aktualisiert werden!
Ohne Webhook-Update werden manuelle Limits weiterhin überschrieben!

**Anleitung:** Siehe `/webhook/webhook-source-tracking-update.php`

---

## ✅ Test nach Installation

### Test 1: Manuelle Limits geschützt
```
1. Setze Kunde manuell auf 15 Freebies
2. Simuliere Webhook mit 10 Freebies (über Test-Tool)
3. Prüfe: Kunde hat noch 15 ✅
```

### Test 2: Webhook-Upgrade funktioniert
```
1. Kunde hat 4 Freebies (via Webhook)
2. Simuliere Webhook mit 10 Freebies
3. Prüfe: Kunde hat jetzt 10 ✅
```

### Test 3: Globaler Sync
```
1. Ändere Tarif-Limits in digistore_products
2. Klicke "Alle Kunden aktualisieren"
3. Prüfe: Kunden haben neue Limits ✅
```

---

## 📚 Vollständige Dokumentation

Siehe: **LIMITS_CONFLICTS_FIXED.md**

Für Installation: **LIMITS_MANAGEMENT_INSTALLATION.md**

---

**⏱️ Installation: 3 Minuten**  
**🎯 Kritikalität: HOCH**  
**✅ Empfehlung: Sofort installieren!**
