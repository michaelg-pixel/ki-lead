<?php
require_once 'config/database.php';

$customer_id = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;

if (!$customer_id) {
    die('Ungültige Kunden-ID');
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT impressum FROM legal_texts WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$legal_text = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$legal_text || empty($legal_text['impressum'])) {
    $content = "Impressum nicht verfügbar.";
} else {
    $content = $legal_text['impressum'];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-6">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">Impressum</h1>
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
</body>
</html>
