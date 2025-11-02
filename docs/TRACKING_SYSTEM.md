# 📊 Customer Tracking System

## Übersicht

Das Customer Tracking System erfasst in Echtzeit alle Benutzeraktivitäten im Kunden-Dashboard und stellt aussagekräftige Statistiken bereit.

## Features

### ✨ Automatisches Tracking

- **Seitenaufrufe**: Jeder Besuch einer Seite wird automatisch erfasst
- **Klicks**: Alle Buttons und Links mit `data-track` Attribut werden getrackt
- **Verweildauer**: Misst, wie lange Nutzer auf einer Seite bleiben
- **Custom Events**: Beliebige Events (z.B. Checklisten-Updates) können getrackt werden

### 📈 Live-Statistiken

Das Dashboard zeigt in Echtzeit:
- Seitenaufrufe (letzte 30 Tage)
- Gesamte Klicks (letzte 30 Tage)
- Durchschnittliche Verweildauer
- Heute's Aktivitäten
- Top 5 meistbesuchte Seiten
- 7-Tage-Aktivitätschart

## Technische Implementierung

### Datenbank

```sql
CREATE TABLE customer_tracking (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  type ENUM('page_view', 'click', 'event', 'time_spent'),
  page VARCHAR(255),
  element VARCHAR(255),
  target VARCHAR(500),
  event_name VARCHAR(100),
  event_data TEXT,
  duration INT,
  referrer VARCHAR(500),
  user_agent VARCHAR(500),
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### API Endpoint

**URL**: `/customer/api/tracking.php`

**Methode**: `POST`

**Request Body**:
```json
{
  "type": "page_view|click|event|time_spent",
  "data": {
    "page": "overview",
    "element": "button-tutorials",
    "target": "?page=tutorials",
    ...
  }
}
```

### JavaScript Integration

#### Automatisches Tracking aktivieren

Jede Seite trackt automatisch:
1. Seitenaufruf beim Laden
2. Klicks auf Elemente mit `data-track` Attribut
3. Verweildauer alle 30 Sekunden und beim Verlassen

#### Beispiel: Element trackbar machen

```html
<a href="?page=tutorials" data-track="button-tutorials">
  Jetzt starten
</a>
```

#### Beispiel: Custom Event tracken

```javascript
TrackingSystem.trackEvent('freebie_created', {
  freebie_id: 123,
  template: 'standard'
});
```

#### Beispiel: Klick manuell tracken

```javascript
TrackingSystem.trackClick('download-button', '/downloads/guide.pdf');
```

## Verwendung in anderen Seiten

### 1. JavaScript-Tracking einbinden

Kopiere das TrackingSystem-Objekt in deine Seite:

```javascript
const TrackingSystem = {
    apiUrl: '/customer/api/tracking.php',
    pageStartTime: Date.now(),
    
    trackPageView: function() {
        this.sendTrackingData({
            type: 'page_view',
            data: {
                page: 'DEINE_SEITE',
                referrer: document.referrer
            }
        });
    },
    
    trackClick: function(element, target = '') {
        this.sendTrackingData({
            type: 'click',
            data: {
                page: 'DEINE_SEITE',
                element: element,
                target: target
            }
        });
    },
    
    sendTrackingData: function(data) {
        fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).catch(err => console.error('Tracking error:', err));
    }
};

// Auto-Track Setup
document.addEventListener('DOMContentLoaded', function() {
    TrackingSystem.trackPageView();
    
    // Alle trackbaren Elemente
    document.querySelectorAll('[data-track]').forEach(element => {
        element.addEventListener('click', function() {
            const trackId = this.getAttribute('data-track');
            const href = this.getAttribute('href') || '';
            TrackingSystem.trackClick(trackId, href);
        });
    });
    
    // Verweildauer tracken
    window.addEventListener('beforeunload', function() {
        const duration = Math.floor((Date.now() - TrackingSystem.pageStartTime) / 1000);
        navigator.sendBeacon(TrackingSystem.apiUrl, JSON.stringify({
            type: 'time_spent',
            data: { page: 'DEINE_SEITE', duration: duration }
        }));
    });
});
```

### 2. Statistiken in PHP abrufen

```php
// Seitenaufrufe der letzten 30 Tage
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM customer_tracking 
    WHERE customer_id = ? 
    AND type = 'page_view'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$customer_id]);
$page_views = $stmt->fetchColumn();

// Top Seiten
$stmt = $pdo->prepare("
    SELECT page, COUNT(*) as visits 
    FROM customer_tracking 
    WHERE customer_id = ? 
    AND type = 'page_view'
    GROUP BY page 
    ORDER BY visits DESC 
    LIMIT 5
");
$stmt->execute([$customer_id]);
$top_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

## Performance-Tipps

1. **Batch-Tracking**: Bei vielen Events, sammle sie und sende sie gebündelt
2. **Indexierung**: Die Tabelle hat bereits optimierte Indices
3. **Daten-Archivierung**: Alte Daten (>90 Tage) können archiviert werden
4. **Caching**: Statistiken können für 5-10 Minuten gecacht werden

## Datenschutz

- IP-Adressen werden erfasst, sollten aber nach DSGVO-Richtlinien anonymisiert werden
- User-Agent wird für Device-Detection gespeichert
- Tracking ist nur für eingeloggte Kunden aktiv
- Keine Cross-Site Tracking Cookies

## Migration

Führe die SQL-Migration aus:

```bash
mysql -u USERNAME -p DATENBANK < database/migrations/002_customer_tracking.sql
```

## Beispiel-Queries

### Aktivität pro Tag
```sql
SELECT 
    DATE(created_at) as date, 
    COUNT(*) as activities
FROM customer_tracking
WHERE customer_id = ?
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
```

### Durchschnittliche Verweildauer
```sql
SELECT 
    page, 
    AVG(duration) as avg_seconds
FROM customer_tracking
WHERE type = 'time_spent'
AND customer_id = ?
GROUP BY page;
```

### Conversion-Funnel
```sql
SELECT 
    event_name,
    COUNT(*) as count
FROM customer_tracking
WHERE type = 'event'
AND customer_id = ?
GROUP BY event_name;
```

## Support

Bei Fragen oder Problemen:
- Prüfe die Browser-Konsole auf Fehler
- Überprüfe, ob die Tracking-Tabelle existiert
- Stelle sicher, dass die Session aktiv ist

---

**Version**: 1.0  
**Letzte Aktualisierung**: <?php echo date('d.m.Y'); ?>
