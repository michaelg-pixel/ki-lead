# 🎯 Referral System - Integration Example

## Vollständige Integration in bestehendes KI-Lead-System

### 1. Customer-Dashboard Integration

**Datei: `customer/dashboard.php`**

```php
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Auth Check
if (!isset($_SESSION['customer_id'])) {
    header('Location: /public/login.php');
    exit;
}

// Section-Handling erweitern
$section = $_GET['section'] ?? 'overview';
$allowedSections = [
    'overview',
    'kurse',
    'freebies',
    'tutorials',
    'fortschritt',
    'einstellungen',
    'empfehlungsprogramm' // NEU!
];

if (!in_array($section, $allowedSections)) {
    $section = 'overview';
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KI Lead System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Referral Tracking für alle Seiten laden -->
    <script src="/assets/js/referral-tracking.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800">KI Lead System</h2>
            </div>
            <nav class="mt-6">
                <a href="?section=overview" class="nav-item <?php echo $section === 'overview' ? 'active' : ''; ?>">
                    📊 Übersicht
                </a>
                <a href="?section=kurse" class="nav-item <?php echo $section === 'kurse' ? 'active' : ''; ?>">
                    🎓 Kurse
                </a>
                <a href="?section=freebies" class="nav-item <?php echo $section === 'freebies' ? 'active' : ''; ?>">
                    🎁 Freebies
                </a>
                
                <!-- NEU: Empfehlungsprogramm -->
                <a href="?section=empfehlungsprogramm" class="nav-item <?php echo $section === 'empfehlungsprogramm' ? 'active' : ''; ?>">
                    🎯 Empfehlungsprogramm
                    <?php
                    // Badge wenn aktiviert
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT referral_enabled FROM customers WHERE id = ?");
                    $stmt->execute([$_SESSION['customer_id']]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($customer && $customer['referral_enabled']):
                    ?>
                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Aktiv</span>
                    <?php endif; ?>
                </a>
                
                <a href="?section=tutorials" class="nav-item <?php echo $section === 'tutorials' ? 'active' : ''; ?>">
                    📚 Tutorials
                </a>
                <a href="?section=einstellungen" class="nav-item <?php echo $section === 'einstellungen' ? 'active' : ''; ?>">
                    ⚙️ Einstellungen
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <?php
            // Section-Content laden
            $sectionFile = __DIR__ . "/sections/{$section}.php";
            if (file_exists($sectionFile)) {
                include $sectionFile;
            } else {
                echo "<p class='text-red-500'>Sektion nicht gefunden</p>";
            }
            ?>
        </main>
    </div>
</body>
</html>
```

---

### 2. Admin-Dashboard Integration

**Datei: `admin/dashboard.php`**

```php
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Admin Auth Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /public/login.php');
    exit;
}

$section = $_GET['section'] ?? 'dashboard';
$allowedSections = [
    'dashboard',
    'users',
    'courses',
    'freebies',
    'settings',
    'referral-overview' // NEU!
];

if (!in_array($section, $allowedSections)) {
    $section = 'dashboard';
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin - KI Lead System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white">
            <div class="p-6">
                <h2 class="text-2xl font-bold">Admin Panel</h2>
            </div>
            <nav class="mt-6">
                <a href="?section=dashboard" class="admin-nav-item">📊 Dashboard</a>
                <a href="?section=users" class="admin-nav-item">👥 Kunden</a>
                <a href="?section=courses" class="admin-nav-item">🎓 Kurse</a>
                <a href="?section=freebies" class="admin-nav-item">🎁 Freebies</a>
                
                <!-- NEU: Referral-Übersicht -->
                <a href="?section=referral-overview" class="admin-nav-item <?php echo $section === 'referral-overview' ? 'active' : ''; ?>">
                    🎯 Empfehlungsprogramm
                    <?php
                    // Badge mit Anzahl aktiver Programme
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->query("SELECT COUNT(*) as count FROM customers WHERE referral_enabled = 1");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    if ($count > 0):
                    ?>
                    <span class="ml-2 px-2 py-1 bg-indigo-600 text-white text-xs rounded-full"><?php echo $count; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?section=settings" class="admin-nav-item">⚙️ Einstellungen</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <?php
            $sectionFile = __DIR__ . "/sections/{$section}.php";
            if (file_exists($sectionFile)) {
                include $sectionFile;
            } else {
                echo "<p class='text-red-500'>Sektion nicht gefunden</p>";
            }
            ?>
        </main>
    </div>
</body>
</html>
```

---

### 3. Freebie-Seiten Integration

**Datei: `freebie/index.php` oder `freebie/view.php`**

```php
<?php
// ... bestehender Code ...

// Customer-Daten laden
$customer_id = $_GET['customer'] ?? null;
$ref = $_GET['ref'] ?? null; // NEU: Referral-Code

// ... Freebie-Daten laden ...
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($freebie['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Referral Tracking -->
    <script src="/assets/js/referral-tracking.js"></script>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Freebie-Content -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($freebie['title']); ?></h1>
            
            <!-- Freebie-Formular -->
            <form id="freebieForm" method="POST" action="process.php">
                <input type="email" name="email" required placeholder="Ihre E-Mail-Adresse" class="w-full px-4 py-2 border rounded-lg">
                
                <!-- Versteckte Felder für Tracking -->
                <?php if ($ref): ?>
                <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref); ?>">
                <?php endif; ?>
                
                <button type="submit" class="mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg">
                    Jetzt downloaden
                </button>
            </form>
        </div>
    </div>
    
    <!-- Referral Tracking initialisieren -->
    <?php if ($ref && $customer_id): ?>
    <script>
        // Auto-Tracking ist bereits aktiv durch referral-tracking.js
        // Optional: Manuelles Tracking
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Referral-Tracking aktiv für:', '<?php echo $ref; ?>');
        });
    </script>
    <?php endif; ?>
</body>
</html>
```

---

### 4. Danke-Seiten Integration

**Datei: `freebie/thankyou.php` oder `public/thankyou.php`**

```php
<?php
// ... bestehender Code ...

$customer_id = $_GET['customer'] ?? null;
$ref = $_GET['ref'] ?? null; // NEU

// Prüfe ob Empfehlungsprogramm aktiv
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT referral_enabled, company_name FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
$referralEnabled = $customer && $customer['referral_enabled'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Vielen Dank!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Referral Tracking -->
    <script src="/assets/js/referral-tracking.js"></script>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Erfolgs-Nachricht -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Vielen Dank!</h1>
            <p class="text-gray-600 mb-6">Ihr Freebie wurde an Ihre E-Mail-Adresse gesendet.</p>
            
            <!-- Download-Link -->
            <a href="<?php echo $download_link; ?>" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Jetzt herunterladen
            </a>
        </div>
        
        <!-- Empfehlungsformular (nur wenn aktiviert und ref vorhanden) -->
        <?php if ($referralEnabled && $ref): ?>
        <div id="referral-form-container" class="mt-8"></div>
        <?php endif; ?>
    </div>
    
    <!-- Referral Tracking & Form -->
    <?php if ($ref && $customer_id): ?>
    <script>
        // Auto-Conversion-Tracking ist bereits aktiv
        
        // Zeige Empfehlungsformular wenn aktiviert
        <?php if ($referralEnabled): ?>
        ReferralTracker.showReferralForm({
            customer_id: <?php echo $customer_id; ?>,
            ref: '<?php echo htmlspecialchars($ref); ?>',
            container_id: 'referral-form-container'
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>
</body>
</html>
```

---

### 5. Externe Tracking-Pixel Integration

**Für Customers die eigene externe Danke-Seiten haben:**

```html
<!-- Beispiel: Externe Webseite des Customers -->
<!DOCTYPE html>
<html>
<head>
    <title>Danke für Ihre Anmeldung</title>
</head>
<body>
    <h1>Vielen Dank!</h1>
    <p>Ihr Download startet gleich...</p>
    
    <!-- Tracking-Pixel (Customer kopiert dies aus Dashboard) -->
    <img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=REF000123ABC" 
         width="1" height="1" style="display:none;" alt="">
</body>
</html>
```

---

### 6. E-Mail-Template Integration

**Beispiel für Newsletter mit Referral-Link:**

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Neues E-Book verfügbar!</h2>
        <p>Hallo {{name}},</p>
        <p>wir haben ein brandneues E-Book für Sie:</p>
        
        <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>10 Marketing-Strategien für 2025</h3>
            <p>Lernen Sie die effektivsten Strategien kennen...</p>
        </div>
        
        <!-- Referral-Link -->
        <a href="https://app.mehr-infos-jetzt.de/freebie.php?customer={{customer_id}}&ref={{ref_code}}" 
           style="display: inline-block; padding: 12px 24px; background: #3B82F6; color: white; text-decoration: none; border-radius: 8px;">
            Jetzt kostenlos herunterladen
        </a>
        
        <p style="margin-top: 20px; font-size: 12px; color: #666;">
            Sie erhalten diese E-Mail, weil Sie sich für unseren Newsletter angemeldet haben.
        </p>
    </div>
</body>
</html>
```

---

## 🔧 CSS-Styling für Navigation

**Datei: `assets/css/dashboard.css`** (falls nicht vorhanden)

```css
/* Navigation Items */
.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 24px;
    color: #4B5563;
    text-decoration: none;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background: #F3F4F6;
    color: #1F2937;
}

.nav-item.active {
    background: #EEF2FF;
    color: #4F46E5;
    border-left-color: #4F46E5;
    font-weight: 600;
}

/* Admin Navigation */
.admin-nav-item {
    display: flex;
    align-items: center;
    padding: 12px 24px;
    color: #D1D5DB;
    text-decoration: none;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.admin-nav-item:hover {
    background: #1F2937;
    color: #FFFFFF;
}

.admin-nav-item.active {
    background: #1F2937;
    color: #FFFFFF;
    border-left-color: #6366F1;
    font-weight: 600;
}
```

---

## 📊 Datenbank-Abfragen für Widgets

**Beispiel: Referral-Stats im Dashboard-Overview**

```php
<?php
// In customer/sections/overview.php

$db = Database::getInstance()->getConnection();
$customerId = $_SESSION['customer_id'];

// Hole Referral-Stats
$stmt = $db->prepare("
    SELECT 
        referral_enabled,
        referral_code,
        rs.total_clicks,
        rs.total_conversions,
        rs.total_leads,
        rs.conversion_rate
    FROM customers c
    LEFT JOIN referral_stats rs ON c.id = rs.customer_id
    WHERE c.id = ?
");
$stmt->execute([$customerId]);
$referralStats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- Referral Widget im Dashboard -->
<?php if ($referralStats['referral_enabled']): ?>
<div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
    <h3 class="text-lg font-semibold mb-4">🎯 Empfehlungsprogramm</h3>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <div class="text-2xl font-bold"><?php echo $referralStats['total_clicks']; ?></div>
            <div class="text-sm opacity-90">Klicks</div>
        </div>
        <div>
            <div class="text-2xl font-bold"><?php echo $referralStats['total_conversions']; ?></div>
            <div class="text-sm opacity-90">Conversions</div>
        </div>
        <div>
            <div class="text-2xl font-bold"><?php echo $referralStats['total_leads']; ?></div>
            <div class="text-sm opacity-90">Leads</div>
        </div>
    </div>
    <a href="?section=empfehlungsprogramm" class="mt-4 inline-block text-sm underline hover:no-underline">
        Details ansehen →
    </a>
</div>
<?php else: ?>
<div class="bg-gray-100 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-2">🎁 Empfehlungsprogramm aktivieren</h3>
    <p class="text-gray-600 mb-4">Starten Sie Ihr eigenes Empfehlungsprogramm und verfolgen Sie Ihre Erfolge.</p>
    <a href="?section=empfehlungsprogramm" class="px-4 py-2 bg-indigo-600 text-white rounded-lg inline-block hover:bg-indigo-700 transition">
        Jetzt aktivieren
    </a>
</div>
<?php endif; ?>
```

---

## ✅ Integrations-Checkliste

- [ ] Setup-Script ausgeführt
- [ ] Navigation in Customer-Dashboard hinzugefügt
- [ ] Navigation in Admin-Dashboard hinzugefügt
- [ ] Tracking-Script in Freebie-Seiten eingebunden
- [ ] Tracking-Script in Danke-Seiten eingebunden
- [ ] Empfehlungsformular auf Danke-Seiten integriert
- [ ] CSS-Styling angepasst
- [ ] Dashboard-Widgets hinzugefügt (optional)
- [ ] Test-Customer angelegt
- [ ] Empfehlungsprogramm getestet
- [ ] Cron-Jobs eingerichtet (optional)

---

**Integration abgeschlossen! 🎉**
