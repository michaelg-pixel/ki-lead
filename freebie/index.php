<?php
/**
 * Freebie Public Page - EMERGENCY FIX
 * Vollständige Version mit HTML-Output
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

// Font-Konfiguration laden
$fontConfig = require __DIR__ . '/../config/fonts.php';

$identifier = $_GET['id'] ?? null;
if (!$identifier) { 
    die('Keine Freebie-ID angegeben');
}

// REFERRAL TRACKING
$ref_code = isset($_GET['ref']) ? trim($_GET['ref']) : null;
$customer_param = isset($_GET['customer']) ? intval($_GET['customer']) : null;

$customer_id = null;
$freebie_db_id = null;
$template = null;

try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.id as customer_id
        FROM customer_freebies cf 
        LEFT JOIN users u ON cf.customer_id = u.id 
        WHERE cf.unique_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'] ?? null;
        $freebie_db_id = $freebie['id'] ?? null;
    }
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

if (!$freebie) { 
    die('Freebie nicht gefunden');
}

// Wenn customer-Parameter übergeben wurde, verwende diesen
if ($customer_param) {
    $customer_id = $customer_param;
}

// Layout-Entscheidung
$layout = $freebie['layout'] ?? 'hybrid';

// Lade entsprechendes Template
$templateFile = __DIR__ . '/templates/layout1.php';

if (file_exists($templateFile)) {
    require $templateFile;
} else {
    // Fallback - Direkte Ausgabe
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($freebie['headline'] ?? 'Freebie') ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50">
        <div class="max-w-4xl mx-auto p-8">
            <h1 class="text-4xl font-bold text-center mb-6">
                <?= htmlspecialchars($freebie['headline'] ?? 'Willkommen') ?>
            </h1>
            
            <?php if (!empty($freebie['subheadline'])): ?>
                <p class="text-xl text-center text-gray-600 mb-8">
                    <?= htmlspecialchars($freebie['subheadline']) ?>
                </p>
            <?php endif; ?>
            
            <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md mx-auto">
                <?php if (!empty($freebie['raw_code'])): ?>
                    <?= $freebie['raw_code'] ?>
                <?php else: ?>
                    <form class="space-y-4">
                        <input type="email" name="email" placeholder="E-Mail-Adresse" required
                               class="w-full p-3 border-2 border-gray-300 rounded-lg">
                        <button type="submit" 
                                class="w-full p-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">
                            <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <footer class="mt-16 text-center text-sm text-gray-600">
                <?php
                $impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
                $datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";
                ?>
                <a href="<?= $impressum_link ?>" class="hover:underline">Impressum</a>
                <span class="mx-2">•</span>
                <a href="<?= $datenschutz_link ?>" class="hover:underline">Datenschutz</a>
            </footer>
        </div>
    </body>
    </html>
    <?php
}
?>
