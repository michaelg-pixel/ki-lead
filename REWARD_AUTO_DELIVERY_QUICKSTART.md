# 🚀 QUICK START: Reward Auto-Delivery einrichten

## In 5 Minuten startklar!

### Schritt 1: Cronjob installieren

```bash
# SSH-Zugang zu deinem Server
ssh dein-user@mehr-infos-jetzt.de

# Navigiere zum Projektverzeichnis
cd /var/www/app.mehr-infos-jetzt.de

# Setup-Script ausführbar machen
chmod +x scripts/setup-reward-cronjob.sh

# Cronjob installieren
bash scripts/setup-reward-cronjob.sh
```

**Erwartete Ausgabe:**
```
✅ Cronjob erfolgreich installiert!
📊 Aktive Cronjobs:
*/10 * * * * /usr/bin/php /var/www/.../api/rewards/auto-deliver-cron.php >> .../logs/reward-delivery.log 2>&1
```

---

### Schritt 2: System testen

**Option A - Im Browser (empfohlen):**
```
https://app.mehr-infos-jetzt.de/api/rewards/test-auto-delivery.php
```

**Option B - Im Terminal:**
```bash
php api/rewards/test-auto-delivery.php
```

**Erwartete Ausgabe:**
```
✅ Datenbankverbindung OK
✅ Alle erforderlichen Tabellen vorhanden
✅ Test erfolgreich abgeschlossen
📊 Ausgeliefert: X | Fehlgeschlagen: 0 | Gesamt: X
```

---

### Schritt 3: Customer-Anleitung

**Als Customer musst du:**

#### 1. Empfehlungsprogramm aktivieren
```
🌐 https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm
```
- Toggle oben rechts auf "Aktiviert" stellen

#### 2. Email-Marketing-API einrichten
- Provider auswählen (z.B. Quentn, Brevo, GetResponse)
- API-Key eingeben
- "Speichern & Testen" klicken
- Warte auf ✅ grünen "Verifiziert" Status

#### 3. Custom Fields im Email-System anlegen

**In deinem Email-Marketing-System (z.B. Quentn):**

Lege diese Custom Fields an:

| Feldname | Typ | Beschreibung |
|----------|-----|--------------|
| `referral_code` | Text | Empfehlungscode des Leads |
| `total_referrals` | Zahl | Gesamte Empfehlungen |
| `successful_referrals` | Zahl | Erfolgreiche Empfehlungen |
| `rewards_earned` | Zahl | Erhaltene Belohnungen |
| `last_reward` | Text | Letzte Belohnung |

#### 4. Belohnungsstufen erstellen
```
🌐 https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=belohnungsstufen
```
- Freebie auswählen
- "Neue Stufe hinzufügen"
- Erforderliche Empfehlungen: z.B. 3, 5, 10
- Belohnungstitel, Beschreibung, Hinweise eingeben
- Speichern

#### 5. Email-Templates einrichten (nur bei Tag-Trigger-Providern)

**Wenn du Quentn, Klick-Tipp oder ActiveCampaign nutzt:**

In deinem Email-System:
1. Erstelle Kampagne für Tag: `reward_1_earned`
2. Email-Template mit Platzhaltern:
   ```
   Hallo!
   
   Du hast {successful_referrals} erfolgreiche Empfehlungen!
   
   Deine Belohnung: {last_reward}
   
   Dein Code: {referral_code}
   ```
3. Kampagne aktivieren
4. Wiederhole für `reward_2_earned`, `reward_3_earned`, etc.

**Wenn du Brevo oder GetResponse nutzt:**
- ✅ Keine Email-Templates nötig!
- System versendet automatisch

---

### Schritt 4: Logs überwachen

```bash
# Live-Logs ansehen
tail -f logs/reward-delivery.log

# Oder prüfe regelmäßig
tail -n 50 logs/reward-delivery.log
```

**Erfolgreiche Auslieferung sieht so aus:**
```
[2025-01-19 10:00:00] [REWARD-AUTO-DELIVERY] START
[2025-01-19 10:00:01] [REWARD-AUTO-DELIVERY] Gefunden: 2 ausstehende Belohnungen
[2025-01-19 10:00:02] [REWARD-AUTO-DELIVERY] ✅ Belohnung ausgeliefert: Lead #123 - Premium Kurs
[2025-01-19 10:00:03] [REWARD-AUTO-DELIVERY] ✅ Belohnung ausgeliefert: Lead #456 - E-Book
[2025-01-19 10:00:04] [REWARD-AUTO-DELIVERY] ENDE - Ausgeliefert: 2 | Fehlgeschlagen: 0
```

---

## ✅ Fertig!

Das System läuft jetzt automatisch alle 10 Minuten und liefert Belohnungen aus.

---

## 🐛 Probleme?

### "Keine Email-API konfiguriert"
→ Schritt 3.2 wiederholen und API-Verbindung testen

### "Keine Belohnungen werden ausgeliefert"
→ Prüfe:
1. Sind Belohnungsstufen erstellt? (Schritt 3.4)
2. Haben Leads genug Empfehlungen? (`successful_referrals >= required_referrals`)
3. Läuft der Cronjob? (`crontab -l | grep reward`)

### "Email kommt nicht an" (Tag-Trigger-Provider)
→ Prüfe:
1. Ist Kampagne für `reward_X_earned` Tag aktiv?
2. Sind Custom Fields korrekt angelegt?
3. Wurde Tag im Email-System hinzugefügt? (Kontakt prüfen)

### "Email kommt nicht an" (Direct Email)
→ Prüfe:
1. Spam-Ordner
2. API-Verbindung: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm

---

## 📚 Weitere Dokumentation

- **Vollständige Doku:** [REWARD_AUTO_DELIVERY_COMPLETE.md](./REWARD_AUTO_DELIVERY_COMPLETE.md)
- **Test-Script:** https://app.mehr-infos-jetzt.de/api/rewards/test-auto-delivery.php
- **Email-Vorlagen-Beispiele:** In Doku enthalten

---

**Viel Erfolg! 🚀**
