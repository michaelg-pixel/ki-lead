# 🚀 Webhook-System Schnellstart

## 1️⃣ Installation (2 Minuten)

### Browser-Migration ausführen

1. Öffne: `https://app.mehr-infos-jetzt.de/database/migrate-webhook-system.html`
2. Klicke auf **"✨ Migration starten"**
3. Warte bis "✅ Migration erfolgreich" erscheint

**Fertig!** Die Tabellen wurden erstellt.

## 2️⃣ Ersten Webhook erstellen (3 Minuten)

### Admin-Interface öffnen

Gehe zu: **Admin Dashboard > Digistore24**

Du siehst jetzt einen grünen Banner:
> 🔗 Neues flexibles Webhook-System verfügbar!

Klicke auf **"➕ Erste Webhooks erstellen"**

### Webhook konfigurieren

1. **Name**: z.B. "Premium Paket 2025"
2. **Produkt-IDs**: Trage eine oder mehrere Digistore24 IDs ein
   - Beispiel: `639493`, `PREMIUM_2025`
   - Klicke nach jeder ID auf "Hinzufügen"
3. **Ressourcen einstellen**:
   - Eigene Freebies: `10`
   - Fertige Freebies: `5`
   - Empfehlungs-Slots: `3`
4. **Kurse auswählen** (optional)
5. **Aktivieren** ✅
6. **Speichern** 💾

**Fertig!** Dein erster flexibler Webhook ist live.

## 3️⃣ Upsell erstellen (optional)

### Haupt-Paket

- Name: "Starter Paket"
- Produkt-IDs: `STARTER`
- Eigene Freebies: `5`
- Upsell: ❌ Nein

### Upsell-Paket

- Name: "Pro Upgrade"
- Produkt-IDs: `PRO_UPGRADE`
- Eigene Freebies: `15`
- **Upsell: ✅ Ja**
- **Upsell-Verhalten: ADD** (addiert die Werte)

**Resultat**: Kunde mit beiden Käufen hat `5 + 15 = 20 Freebies`

## 4️⃣ Testen

1. Gehe zu deinem Webhook
2. Klicke auf **"🧪 Testen"**
3. Prüfe die **Aktivitäten** nach einem echten Kauf

## ✅ Checkliste

- [ ] Migration ausgeführt
- [ ] Ersten Webhook erstellt
- [ ] Webhook-URL in Digistore24 eingetragen
- [ ] Test-Kauf durchgeführt
- [ ] Aktivitäten geprüft

## 🔗 Webhook-URL

```
https://app.mehr-infos-jetzt.de/webhook/digistore24.php
```

Diese URL funktioniert für **alte UND neue Webhooks**!

## 💡 Wichtige Hinweise

### Legacy-System bleibt funktional
Deine bestehenden `digistore_products` Webhooks funktionieren weiter. Du kannst beide Systeme parallel nutzen.

### Eine Produkt-ID = Ein System
Jede Digistore24 Produkt-ID sollte entweder im alten ODER neuen System sein - nicht in beiden.

### Upsell-Modi verstehen

- **ADD**: Addiert Werte (5 + 10 = 15)
- **UPGRADE**: Nimmt höheren Wert (max(5, 10) = 10)
- **REPLACE**: Ersetzt komplett (5 → 10)

## 📚 Vollständige Dokumentation

Siehe: [WEBHOOK_SYSTEM_README.md](WEBHOOK_SYSTEM_README.md)

## 🆘 Hilfe

### Webhook wird nicht gefunden
- Prüfe ob **aktiv** ✅
- Prüfe **Produkt-ID** korrekt
- Schaue in Logs: `/webhook/webhook-logs.txt`

### Fragen?
Schaue in die Aktivitäten-Ansicht oder Webhook-Logs für Details.

---

**Viel Erfolg mit deinem flexiblen Webhook-System! 🚀**
