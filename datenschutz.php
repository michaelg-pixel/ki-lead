<?php
require_once 'config/database.php';

// Support both old 'customer' and new 'user' parameter for backward compatibility
// If no user specified, try to find the first admin/user
$user_id = isset($_GET['user']) ? (int)$_GET['user'] : (isset($_GET['customer']) ? (int)$_GET['customer'] : 0);

$conn = getDBConnection();

// If no user_id provided, get the first user with legal texts
if (!$user_id) {
    $stmt = $conn->prepare("SELECT user_id FROM legal_texts WHERE datenschutz IS NOT NULL AND datenschutz != '' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $user_id = $result['user_id'];
    } else {
        die('Datenschutzerklärung nicht verfügbar.');
    }
}

$stmt = $conn->prepare("SELECT datenschutz FROM legal_texts WHERE user_id = ?");
$stmt->execute([$user_id]);
$legal_text = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$legal_text || empty($legal_text['datenschutz'])) {
    $content = "Datenschutzerklärung nicht verfügbar.";
} else {
    $content = $legal_text['datenschutz'];
}

// Für Cookie-Banner
$customer_id = $user_id;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenschutzerklärung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-6">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">Datenschutzerklärung</h1>
            <div class="prose max-w-none text-gray-700 whitespace-pre-line">
                <?= nl2br(htmlspecialchars($content)) ?>
            </div>
            <div class="mt-8 pt-6 border-t border-gray-200">
                <a href="javascript:history.back()" class="text-purple-600 hover:text-purple-700">
                    <i class="fas fa-arrow-left mr-2"></i> Zurück
                </a>
            </div>
        </div>
    </div>
    
    <!-- 🍪 Cookie-Banner -->
    <?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>
</body>
</html>