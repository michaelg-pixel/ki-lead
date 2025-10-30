<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

$conn = getDBConnection();

// Alle Tutorials laden
$stmt = $conn->query("SELECT * FROM tutorials ORDER BY category, sort_order");
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nach Kategorie gruppieren
$grouped = [];
foreach ($tutorials as $tutorial) {
    $grouped[$tutorial['category']][] = $tutorial;
}

$categories = [
    'getting_started' => ['name' => 'Erste Schritte', 'icon' => 'rocket'],
    'freebie_editor' => ['name' => 'Freebie-Editor', 'icon' => 'edit'],
    'courses' => ['name' => 'Kurse verwenden', 'icon' => 'graduation-cap'],
    'legal' => ['name' => 'Rechtstexte', 'icon' => 'file-contract'],
    'advanced' => ['name' => 'Fortgeschritten', 'icon' => 'star']
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anleitungen - KI Lead-System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="text-2xl font-bold text-purple-600">
                    🚀 KI Lead-System
                </div>
                <div class="flex gap-6">
                    <a href="index.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-graduation-cap mr-2"></i> Kurse
                    </a>
                    <a href="freebie-editor.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-edit mr-2"></i> Freebie-Editor
                    </a>
                    <a href="legal-texts.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-file-contract mr-2"></i> Rechtstexte
                    </a>
                    <a href="tutorials.php" class="text-purple-600 font-semibold">
                        <i class="fas fa-question-circle mr-2"></i> Anleitungen
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-6 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Anleitungen & Tutorials</h1>
            <p class="text-gray-600">Lerne, wie du das KI Lead-System optimal nutzt</p>
        </div>

        <!-- Tutorials -->
        <?php if (empty($tutorials)): ?>
            <div class="bg-white rounded-lg shadow-lg p-12 text-center">
                <i class="fas fa-video text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-600">Noch keine Anleitungen verfügbar</p>
                <p class="text-gray-500 mt-2">Schau bald wieder vorbei!</p>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($categories as $cat_key => $cat_data): ?>
                    <?php if (isset($grouped[$cat_key])): ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-6">
                                <h2 class="text-2xl font-bold">
                                    <i class="fas fa-<?= $cat_data['icon'] ?> mr-3"></i>
                                    <?= $cat_data['name'] ?>
                                </h2>
                            </div>
                            
                            <div class="p-6">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <?php foreach ($grouped[$cat_key] as $tutorial): ?>
                                        <div class="border-2 border-gray-200 rounded-lg overflow-hidden hover:border-purple-400 hover:shadow-lg transition cursor-pointer"
                                             onclick="playVideo('<?= htmlspecialchars($tutorial['vimeo_url']) ?>', '<?= htmlspecialchars($tutorial['title']) ?>')">
                                            <div class="aspect-video bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center relative group">
                                                <div class="absolute inset-0 bg-black bg-opacity-30 group-hover:bg-opacity-40 transition"></div>
                                                <i class="fas fa-play-circle text-6xl text-white relative z-10 group-hover:scale-110 transition"></i>
                                            </div>
                                            <div class="p-4">
                                                <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars($tutorial['title']) ?></h3>
                                                <?php if ($tutorial['description']): ?>
                                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($tutorial['description']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="mt-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-8 text-white">
            <div class="flex items-center gap-6">
                <div class="text-6xl">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-2xl font-bold mb-2">Brauchst du weitere Hilfe?</h3>
                    <p class="mb-4">Unser Support-Team steht dir jederzeit zur Verfügung.</p>
                    <a href="mailto:support@ki-leadsystem.com" 
                       class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold inline-block hover:bg-gray-100">
                        <i class="fas fa-envelope mr-2"></i> Support kontaktieren
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Player Modal -->
    <div id="video-modal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-6">
        <div class="w-full max-w-5xl relative">
            <div class="flex justify-between items-center mb-4">
                <h3 id="video-title" class="text-white text-2xl font-bold"></h3>
                <button onclick="closeVideo()" 
                        class="text-white text-4xl hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="video-container" class="bg-black rounded-lg overflow-hidden aspect-video">
                <!-- Vimeo-Player wird hier geladen -->
            </div>
        </div>
    </div>

    <script src="https://player.vimeo.com/api/player.js"></script>
    <script>
        function playVideo(vimeoUrl, title) {
            const modal = document.getElementById('video-modal');
            const container = document.getElementById('video-container');
            const titleElement = document.getElementById('video-title');
            
            titleElement.textContent = title;
            container.innerHTML = `<iframe src="${vimeoUrl}" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
            
            modal.classList.remove('hidden');
        }
        
        function closeVideo() {
            const modal = document.getElementById('video-modal');
            const container = document.getElementById('video-container');
            
            container.innerHTML = '';
            modal.classList.add('hidden');
        }
        
        // ESC schließt Video
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideo();
            }
        });
    </script>

</body>
</html>
