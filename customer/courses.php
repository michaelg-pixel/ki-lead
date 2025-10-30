<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

$conn = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Nischen-Filter
$selected_niche = $_GET['niche'] ?? 'all';

// Kurse laden (nur aktive + kostenlose ODER gekaufte Premium-Kurse)
$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM modules WHERE course_id = c.id) as module_count,
           (SELECT COUNT(*) FROM lessons l 
            JOIN modules m ON l.module_id = m.id 
            WHERE m.course_id = c.id) as lesson_count,
           cp.id as has_access
    FROM courses c
    LEFT JOIN customer_purchases cp ON c.id = cp.course_id AND cp.customer_id = ?
    WHERE c.is_active = 1 
    AND (c.is_premium = 0 OR cp.id IS NOT NULL)
";

$params = [$customer_id];

if ($selected_niche !== 'all') {
    $sql .= " AND c.niche = ?";
    $params[] = $selected_niche;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$niches = [
    'all' => 'Alle Nischen',
    'business_coaching' => 'Business Coaching',
    'fitness' => 'Fitness & Gesundheit',
    'real_estate' => 'Immobilien',
    'consulting' => 'Unternehmensberatung',
    'online_marketing' => 'Online Marketing',
    'finance' => 'Finanzen & Versicherungen',
    'wellness' => 'Wellness & Beauty',
    'handwerk' => 'Handwerk',
    'law' => 'Recht',
    'tech' => 'IT & Software',
    'education' => 'Bildung',
    'hospitality' => 'Gastronomie',
    'automotive' => 'Automotive',
    'photography' => 'Fotografie',
    'personal_development' => 'Persönlichkeitsentwicklung',
    'other' => 'Sonstiges'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videokurse - KI Lead-System</title>
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
                    <a href="courses.php" class="text-purple-600 font-semibold">
                        <i class="fas fa-graduation-cap mr-2"></i> Kurse
                    </a>
                    <a href="freebie-editor.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-edit mr-2"></i> Freebie-Editor
                    </a>
                    <a href="legal-texts.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-file-contract mr-2"></i> Rechtstexte
                    </a>
                    <a href="tutorials.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-question-circle mr-2"></i> Anleitungen
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Videokurse</h1>
            <p class="text-gray-600">Wähle einen Kurs aus, um ihn in deinem Freebie zu verwenden</p>
        </div>

        <!-- Nischen-Filter -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-filter mr-2 text-purple-600"></i> Nach Nische filtern
            </h3>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($niches as $key => $label): ?>
                    <a href="?niche=<?= $key ?>" 
                       class="px-4 py-2 rounded-lg <?= $selected_niche === $key ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Kurse -->
        <?php if (empty($courses)): ?>
            <div class="bg-white rounded-lg shadow-lg p-12 text-center">
                <i class="fas fa-graduation-cap text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-600">Keine Kurse in dieser Kategorie verfügbar</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $course): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <!-- Thumbnail -->
                        <?php if ($course['thumbnail']): ?>
                            <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                                 alt="<?= htmlspecialchars($course['title']) ?>" 
                                 class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-graduation-cap text-6xl text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-xl font-bold text-gray-800 flex-1">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h3>
                                <?php if ($course['is_premium']): ?>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                        <i class="fas fa-crown"></i> Premium
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                <?= substr(htmlspecialchars($course['description']), 0, 120) ?>...
                            </p>
                            
                            <div class="flex gap-4 text-sm text-gray-500 mb-4">
                                <div>
                                    <i class="fas fa-list mr-1"></i> <?= $course['module_count'] ?> Module
                                </div>
                                <div>
                                    <i class="fas fa-play-circle mr-1"></i> <?= $course['lesson_count'] ?> Videos
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="previewCourse(<?= $course['id'] ?>)" 
                                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-semibold">
                                    <i class="fas fa-eye mr-2"></i> Vorschau
                                </button>
                                <a href="freebie-editor.php?course_id=<?= $course['id'] ?>" 
                                   class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-semibold text-center">
                                    <i class="fas fa-magic mr-2"></i> Im Editor
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vorschau Modal -->
    <div id="preview-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold" id="preview-title">Kursvorschau</h2>
                    <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="preview-content">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i>
                        <p class="text-gray-600 mt-4">Lade Vorschau...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewCourse(courseId) {
            document.getElementById('preview-modal').classList.remove('hidden');
            
            fetch('freebie-preview.php?course_id=' + courseId + '&preview_only=1')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('preview-content').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('preview-content').innerHTML = 
                        '<div class="text-center text-red-600 py-8">Fehler beim Laden der Vorschau</div>';
                });
        }
        
        function closePreview() {
            document.getElementById('preview-modal').classList.add('hidden');
        }
        
        // ESC-Taste schließt Modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreview();
            }
        });
    </script>

</body>
</html>
