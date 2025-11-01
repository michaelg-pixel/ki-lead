<?php
/**
 * Debug-Version der Freebie index.php
 * Zeigt alle Fehler und den kompletten Ablauf an
 */

// ALLE Fehler anzeigen
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!-- DEBUG START -->\n";
echo "<!-- PHP Version: " . PHP_VERSION . " -->\n";
echo "<!-- Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . " -->\n";

// Get the identifier from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

echo "<!-- Path: " . htmlspecialchars($path) . " -->\n";
echo "<!-- Segments: " . implode(', ', array_map('htmlspecialchars', $segments)) . " -->\n";

// Find the identifier after 'freebie'
$identifier = null;
foreach ($segments as $index => $segment) {
    if ($segment === 'freebie' && isset($segments[$index + 1])) {
        $identifier = $segments[$index + 1];
        break;
    }
}

echo "<!-- Identifier from URL: " . htmlspecialchars($identifier ?? 'NONE') . " -->\n";

// Fallback: check for query parameter
if (!$identifier && isset($_GET['id'])) {
    $identifier = $_GET['id'];
    echo "<!-- Identifier from GET: " . htmlspecialchars($identifier) . " -->\n";
}

// Error if no identifier found
if (!$identifier) {
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Fehler</title></head><body>';
    echo '<div style="font-family: Arial; padding: 50px; background: #1a1a2e; color: white;">';
    echo '<h1>❌ Keine Freebie-ID gefunden</h1>';
    echo '<p><strong>Request URI:</strong> ' . htmlspecialchars($requestUri) . '</p>';
    echo '<p><strong>Erwartet:</strong> /freebie/{unique_id}</p>';
    echo '<hr><a href="/customer/dashboard.php?page=freebies" style="color: #667eea;">← Zurück zu Freebies</a>';
    echo '</div></body></html>';
    exit;
}

echo "<!-- Loading database... -->\n";

// Database connection
require_once __DIR__ . '/../config/database.php';

echo "<!-- Database loaded -->\n";

// Check database connection
if (!isset($pdo) || !$pdo) {
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Datenbankfehler</title></head><body>';
    echo '<div style="font-family: Arial; padding: 50px; background: #1a1a2e; color: white;">';
    echo '<h1>❌ Datenbankverbindung fehlgeschlagen</h1>';
    echo '<p>Bitte überprüfen Sie die Konfiguration in <code>/config/database.php</code></p>';
    echo '</div></body></html>';
    exit;
}

echo "<!-- Database connected -->\n";

// Find the freebie
$customer_id = null;
try {
    echo "<!-- Searching for freebie with ID: " . htmlspecialchars($identifier) . " -->\n";
    
    // First check if this is a customer-specific freebie
    $stmt = $pdo->prepare("SELECT cf.*, u.id as customer_id FROM customer_freebies cf LEFT JOIN users u ON cf.customer_id = u.id WHERE cf.unique_id = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        echo "<!-- Found in customer_freebies! ID: " . $freebie['id'] . " -->\n";
        $customer_id = $freebie['customer_id'] ?? null;
        echo "<!-- Customer ID: " . ($customer_id ?? 'NULL') . " -->\n";
    } else {
        echo "<!-- Not found in customer_freebies, checking templates... -->\n";
        
        // Check if this is a template freebie
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($freebie) {
            echo "<!-- Found in freebies (template)! ID: " . $freebie['id'] . " -->\n";
        } else {
            echo "<!-- NOT FOUND ANYWHERE! -->\n";
        }
    }
} catch (PDOException $e) {
    echo "<!-- DATABASE ERROR: " . htmlspecialchars($e->getMessage()) . " -->\n";
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Datenbankfehler</title></head><body>';
    echo '<div style="font-family: Arial; padding: 50px; background: #1a1a2e; color: white;">';
    echo '<h1>❌ Datenbankfehler</h1>';
    echo '<p><strong>Fehler:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div></body></html>';
    exit;
}

// Check if freebie exists
if (!$freebie) {
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Nicht gefunden</title></head><body>';
    echo '<div style="font-family: Arial; padding: 50px; background: #1a1a2e; color: white;">';
    echo '<h1>❌ Freebie nicht gefunden</h1>';
    echo '<p><strong>Gesucht nach:</strong> ' . htmlspecialchars($identifier) . '</p>';
    echo '<p>Dieses Freebie existiert nicht in der Datenbank.</p>';
    echo '<hr><a href="/customer/dashboard.php?page=freebies" style="color: #667eea;">← Zurück zu Freebies</a>';
    echo '</div></body></html>';
    exit;
}

echo "<!-- Freebie found! Building page... -->\n";

// Footer-Links mit customer_id falls vorhanden
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

echo "<!-- Footer links: Impressum=" . htmlspecialchars($impressum_link) . ", Datenschutz=" . htmlspecialchars($datenschutz_link) . " -->\n";

// Get values with defaults
$primaryColor = $freebie['primary_color'] ?? '#7C3AED';
$backgroundColor = $freebie['background_color'] ?? '#FFFFFF';

echo "<!-- Colors: Primary=" . htmlspecialchars($primaryColor) . ", Background=" . htmlspecialchars($backgroundColor) . " -->\n";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['headline'] ?? 'Freebie'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: <?php echo htmlspecialchars($backgroundColor); ?>;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .debug {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="debug">
            <strong>🔍 DEBUG INFO:</strong><br>
            Freebie ID: <?php echo htmlspecialchars($freebie['id']); ?><br>
            Unique ID: <?php echo htmlspecialchars($identifier); ?><br>
            Customer ID: <?php echo htmlspecialchars($customer_id ?? 'N/A'); ?><br>
            Template ID: <?php echo htmlspecialchars($freebie['template_id'] ?? 'N/A'); ?><br>
            Layout: <?php echo htmlspecialchars($freebie['layout'] ?? 'N/A'); ?><br>
        </div>
        
        <h1><?php echo htmlspecialchars($freebie['headline'] ?? 'Willkommen'); ?></h1>
        
        <?php if (!empty($freebie['subheadline'])): ?>
            <p><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($freebie['raw_code'])): ?>
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <strong>E-Mail Optin:</strong>
                <?php echo $freebie['raw_code']; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
            <span>•</span>
            <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
        </div>
    </div>
</body>
</html>
<?php
echo "\n<!-- DEBUG END - Page rendered successfully -->\n";
?>