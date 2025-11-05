<?php
/**
 * Freebie Public Page mit POPUP-SUPPORT
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
            u.id as customer_id,
            f.video_url as template_video_url,
            f.video_format as template_video_format,
            f.optin_display_mode as template_optin_display_mode,
            f.popup_message as template_popup_message,
            f.cta_animation as template_cta_animation
        FROM customer_freebies cf 
        LEFT JOIN users u ON cf.customer_id = u.id 
        LEFT JOIN freebies f ON cf.template_id = f.id 
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
    die('Freebie nicht gefunden: ' . htmlspecialchars($identifier));
}

// Wenn customer-Parameter übergeben wurde, verwende diesen
if ($customer_param) {
    $customer_id = $customer_param;
}

// Popup-Einstellungen mit Fallback
$optinDisplayMode = $freebie['optin_display_mode'] ?? $freebie['template_optin_display_mode'] ?? 'direct';
$popupMessage = $freebie['popup_message'] ?? $freebie['template_popup_message'] ?? 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!';
$ctaAnimation = $freebie['cta_animation'] ?? $freebie['template_cta_animation'] ?? 'none';

// Video-Einstellungen mit Fallback
$videoUrl = $freebie['video_url'] ?? $freebie['template_video_url'] ?? '';
$videoFormat = $freebie['video_format'] ?? $freebie['template_video_format'] ?? 'widescreen';

// Video Embed URL Konvertierung
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube (watch, youtu.be, shorts)
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    return null;
}

$videoEmbedUrl = getVideoEmbedUrl($videoUrl);

// Layout-Entscheidung
$layout = $freebie['layout'] ?? 'hybrid';

// Footer-Links
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

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
            
            <!-- Video -->
            <?php if ($videoEmbedUrl): ?>
                <div class="mb-8 max-w-2xl mx-auto">
                    <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                        <iframe src="<?= htmlspecialchars($videoEmbedUrl) ?>" 
                                class="absolute top-0 left-0 w-full h-full rounded-xl"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Optin -->
            <?php if ($optinDisplayMode === 'popup'): ?>
                <div class="text-center">
                    <button onclick="openOptinPopup()" 
                            class="px-8 py-4 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition <?= $ctaAnimation !== 'none' ? 'animate-' . $ctaAnimation : '' ?>">
                        <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                    </button>
                </div>
            <?php else: ?>
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
            <?php endif; ?>
            
            <footer class="mt-16 text-center text-sm text-gray-600">
                <a href="<?= $impressum_link ?>" class="hover:underline">Impressum</a>
                <span class="mx-2">•</span>
                <a href="<?= $datenschutz_link ?>" class="hover:underline">Datenschutz</a>
            </footer>
        </div>
        
        <?php if ($optinDisplayMode === 'popup'): ?>
        <!-- Popup -->
        <div id="optinPopupOverlay" class="hidden fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="if(event.target === this) closeOptinPopup()">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <div class="p-8 text-center border-b">
                    <button onclick="closeOptinPopup()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl">×</button>
                    <div class="text-6xl mb-4">🎁</div>
                    <h2 class="text-3xl font-bold mb-3"><?= htmlspecialchars($freebie['headline'] ?? '') ?></h2>
                    <p class="text-gray-600"><?= htmlspecialchars($popupMessage) ?></p>
                </div>
                <div class="p-8">
                    <?php if (!empty($freebie['raw_code'])): ?>
                        <?= $freebie['raw_code'] ?>
                    <?php else: ?>
                        <form class="space-y-4">
                            <input type="email" name="email" placeholder="E-Mail-Adresse" required
                                   class="w-full p-3 border-2 border-gray-300 rounded-lg">
                            <button type="submit" 
                                    class="w-full p-4 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">
                                <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); } 20%, 40%, 60%, 80% { transform: translateX(5px); } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes glow { 0%, 100% { box-shadow: 0 0 10px currentColor; } 50% { box-shadow: 0 0 20px currentColor; } }
        .animate-pulse { animation: pulse 2s ease-in-out infinite; }
        .animate-shake { animation: shake 0.6s ease-in-out infinite; }
        .animate-bounce { animation: bounce 1.2s ease-in-out infinite; }
        .animate-glow { animation: glow 2s ease-in-out infinite; }
        body.popup-open { overflow: hidden; }
        </style>
        
        <script>
        function openOptinPopup() {
            document.getElementById('optinPopupOverlay').classList.remove('hidden');
            document.body.classList.add('popup-open');
        }
        function closeOptinPopup() {
            document.getElementById('optinPopupOverlay').classList.add('hidden');
            document.body.classList.remove('popup-open');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeOptinPopup();
        });
        </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>
