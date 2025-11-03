# 🏆 Reward Definitions System - Belohnungsstufen

## Übersicht

Das Reward Definitions System ermöglicht es Usern, **konfigurierbare Belohnungsstufen** für ihr Empfehlungsprogramm zu erstellen und zu verwalten.

---

## ✨ Features

### Für User (Customer Dashboard)
- ✅ **Belohnungsstufen erstellen** mit individuellen Einstellungen
- ✅ **Flexible Konfiguration**: Anzahl benötigter Empfehlungen, Belohnungstyp, Wert, etc.
- ✅ **Visuelle Anpassung**: Icons, Farben, Badge-Images
- ✅ **Auto-Delivery**: Belohnungen automatisch zusenden
- ✅ **Email-Benachrichtigungen**: Custom Subject & Body
- ✅ **Statistiken**: Wie oft wurde eine Stufe erreicht/eingelöst?
- ✅ **Bearbeiten & Löschen** von Belohnungsstufen

### Belohnungstypen
- 📚 E-Book
- 📄 PDF-Download
- 💬 Beratung/Consultation
- 🎓 Kurs-Zugang
- 🎟️ Gutschein
- 💰 Rabatt
- 🎁 Freebie
- ⚙️ Sonstiges

---

## 📦 Installation

### Schritt 1: Datenbank-Migration ausführen

Rufe das Setup-Skript im Browser auf:
```
https://app.mehr-infos-jetzt.de/setup-reward-definitions.php
```

Das Skript:
- ✅ Erstellt die `reward_definitions` Tabelle
- ✅ Prüft die Tabellen-Struktur
- ✅ Optional: Erstellt Beispieldaten (Bronze, Silber, Gold)

**Nach erfolgreichem Setup sollte die Datei gelöscht werden:**
```bash
rm setup-reward-definitions.php
```

---

## 🗂️ Datenbank-Struktur

### Tabelle: `reward_definitions`

```sql
CREATE TABLE reward_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                    -- Welcher User
    
    -- Stufen-Info
    tier_level INT NOT NULL,                 -- Stufe: 1, 2, 3...
    tier_name VARCHAR(100) NOT NULL,         -- Bronze, Silber, Gold
    tier_description TEXT,
    
    -- Empfehlungen
    required_referrals INT NOT NULL,         -- Anzahl benötigter Refs
    
    -- Belohnung
    reward_type VARCHAR(50) NOT NULL,        -- ebook, consultation, etc.
    reward_title VARCHAR(255) NOT NULL,
    reward_description TEXT,
    reward_value VARCHAR(100),               -- z.B. 50€, 1h Beratung
    
    -- Zugriff
    reward_download_url TEXT,
    reward_access_code VARCHAR(100),
    reward_instructions TEXT,
    
    -- Visuals
    reward_icon VARCHAR(100) DEFAULT 'fa-gift',
    reward_color VARCHAR(20) DEFAULT '#667eea',
    reward_badge_image VARCHAR(255),
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    auto_deliver BOOLEAN DEFAULT FALSE,
    
    -- Email
    notification_subject VARCHAR(255),
    notification_body TEXT,
    
    sort_order INT DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_tier (user_id, tier_level)
);
```

### Verbindung zu anderen Tabellen

- **`lead_users`**: Leads/Werber die Empfehlungen sammeln
- **`referral_reward_tiers`**: Erreichte Belohnungsstufen pro Lead
- **`referral_claimed_rewards`**: Eingelöste Belohnungen

---

## 🎯 API-Endpunkte

### 1. Liste aller Belohnungsstufen
```http
GET /api/rewards/list.php
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tier_level": 1,
      "tier_name": "Bronze",
      "required_referrals": 3,
      "reward_title": "Starter E-Book",
      "leads_achieved": 5,
      "times_claimed": 3
    }
  ],
  "count": 1
}
```

### 2. Einzelne Belohnungsstufe abrufen
```http
GET /api/rewards/get.php?id=1
```

### 3. Belohnungsstufe erstellen/aktualisieren
```http
POST /api/rewards/save.php
Content-Type: application/json

{
  "tier_level": 1,
  "tier_name": "Bronze",
  "tier_description": "Erste Stufe",
  "required_referrals": 3,
  "reward_type": "ebook",
  "reward_title": "Starter E-Book",
  "reward_value": "Wert: 29€",
  "reward_icon": "fa-book",
  "reward_color": "#cd7f32",
  "is_active": true
}
```

### 4. Belohnungsstufe löschen
```http
POST /api/rewards/delete.php
Content-Type: application/json

{
  "id": 1
}
```

**Hinweis:** Falls die Belohnung bereits vergeben wurde, wird sie nur deaktiviert, nicht gelöscht.

---

## 🖥️ Customer Dashboard Integration

### Navigation
Neuer Menüpunkt in `customer/dashboard.php`:
```php
<a href="?page=belohnungsstufen" class="nav-item">
    <span class="nav-icon">🏆</span>
    <span>Belohnungsstufen</span>
</a>
```

### Sektion laden
```php
<?php elseif ($page === 'belohnungsstufen'): ?>
    <?php include __DIR__ . '/sections/belohnungsstufen.php'; ?>
<?php endif; ?>
```

### UI Features
- ✅ **Card-Grid Layout** mit responsivem Design
- ✅ **Modal-Formular** zum Erstellen/Bearbeiten
- ✅ **Live-Statistiken** (Anzahl erreicht/eingelöst)
- ✅ **Farbcodierte Badges** für visuelle Unterscheidung
- ✅ **Empty State** wenn noch keine Belohnungen vorhanden

---

## 🎨 Beispiel-Belohnungsstufen

### Bronze (Stufe 1)
```php
[
    'tier_level' => 1,
    'tier_name' => 'Bronze',
    'required_referrals' => 3,
    'reward_type' => 'ebook',
    'reward_title' => 'Starter E-Book',
    'reward_value' => 'Wert: 29€',
    'reward_icon' => 'fa-book',
    'reward_color' => '#cd7f32'
]
```

### Silber (Stufe 2)
```php
[
    'tier_level' => 2,
    'tier_name' => 'Silber',
    'required_referrals' => 5,
    'reward_type' => 'consultation',
    'reward_title' => '30 Min. Gratis-Beratung',
    'reward_value' => 'Wert: 99€',
    'reward_icon' => 'fa-comments',
    'reward_color' => '#c0c0c0'
]
```

### Gold (Stufe 3)
```php
[
    'tier_level' => 3,
    'tier_name' => 'Gold',
    'required_referrals' => 10,
    'reward_type' => 'course',
    'reward_title' => 'Premium-Kurs Zugang',
    'reward_value' => 'Wert: 299€',
    'reward_icon' => 'fa-crown',
    'reward_color' => '#ffd700'
]
```

---

## 🔄 Workflow

### 1. User erstellt Belohnungsstufen
```
Dashboard → Belohnungsstufen → Neue Belohnungsstufe → Formular ausfüllen → Speichern
```

### 2. Lead sammelt Empfehlungen
```
Lead empfiehlt 3 Personen → Bronze-Stufe erreicht → Eintrag in referral_reward_tiers
```

### 3. Lead löst Belohnung ein
```
Lead Dashboard → Belohnung anfordern → Eintrag in referral_claimed_rewards
```

### 4. Optional: Auto-Delivery
```
Stufe erreicht → Auto-Delivery aktiviert → Email mit Belohnung automatisch versandt
```

---

## 🔐 Sicherheit

- ✅ **Session-basierte Authentifizierung**
- ✅ **User-ID-Prüfung**: Nur eigene Belohnungen sichtbar/bearbeitbar
- ✅ **Input-Validierung**: Alle Pflichtfelder geprüft
- ✅ **SQL-Injection-Schutz**: Prepared Statements
- ✅ **Unique Constraint**: Keine doppelten Tier-Levels pro User

---

## 📱 Responsive Design

- ✅ Desktop: 3-4 Spalten Grid
- ✅ Tablet: 2 Spalten Grid
- ✅ Mobile: 1 Spalte, optimierte Navigation

---

## 🐛 Troubleshooting

### Fehler: "Tabelle reward_definitions existiert nicht"
**Lösung:** Setup-Skript ausführen
```
https://app.mehr-infos-jetzt.de/setup-reward-definitions.php
```

### Fehler: "Unique constraint violation"
**Ursache:** User hat bereits eine Belohnung für dieses Tier-Level
**Lösung:** Anderes Tier-Level wählen oder bestehende bearbeiten

### Belohnungen werden nicht angezeigt
**Prüfen:**
1. Ist `is_active = TRUE`?
2. Stimmt die `user_id`?
3. Browser-Konsole auf JavaScript-Fehler prüfen

---

## 🚀 Nächste Schritte

1. **Setup ausführen:**
   ```
   https://app.mehr-infos-jetzt.de/setup-reward-definitions.php
   ```

2. **Dashboard öffnen:**
   ```
   https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=belohnungsstufen
   ```

3. **Erste Belohnungsstufen erstellen**

4. **Empfehlungsprogramm testen:**
   ```
   https://app.mehr-infos-jetzt.de/lead_login.php
   ```

---

## 📚 Weitere Dokumentation

- [REFERRAL_SYSTEM_README.md](REFERRAL_SYSTEM_README.md) - Komplettes Empfehlungsprogramm
- [REFERRAL_QUICKSTART.md](REFERRAL_QUICKSTART.md) - Schnellstart-Guide
- [REFERRAL_ARCHITECTURE.md](REFERRAL_ARCHITECTURE.md) - Technische Architektur

---

## 💡 Best Practices

1. **Belohnungsstufen sinnvoll staffeln:**
   - Stufe 1: 3 Empfehlungen
   - Stufe 2: 5 Empfehlungen
   - Stufe 3: 10 Empfehlungen
   - Stufe 4: 20 Empfehlungen

2. **Wert steigern:** Höhere Stufen = wertvollere Belohnungen

3. **Auto-Delivery nutzen:** Für digitale Produkte (E-Books, PDFs)

4. **Email-Benachrichtigungen personalisieren:**
   - Gratulation zur erreichten Stufe
   - Anleitung zur Einlösung
   - Call-to-Action

5. **Featured Rewards:** Besondere Belohnungen hervorheben

---

## ✅ Fertig!

Das Reward Definitions System ist jetzt einsatzbereit. 🎉

Bei Fragen oder Problemen: Dokumentation prüfen oder Support kontaktieren.
