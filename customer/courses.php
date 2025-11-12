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

// Kurse laden mit course_access (Webhook-System)
// Zeigt ALLE Kurse an, mit Status ob Zugriff besteht oder nicht
$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM modules WHERE course_id = c.id) as module_count,
           (SELECT COUNT(*) FROM lessons l 
            JOIN modules m ON l.module_id = m.id 
            WHERE m.course_id = c.id) as lesson_count,
           ca.id as has_access,
           ca.access_source
    FROM courses c
    LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
    WHERE c.is_active = 1
";

$params = [$customer_id];

if ($selected_niche !== 'all') {
    $sql .= " AND c.niche = ?";
    $params[] = $selected_niche;
}

$sql .= " ORDER BY 
    CASE WHEN ca.id IS NOT NULL THEN 0 ELSE 1 END,
    c.is_premium ASC,
    c.created_at DESC
";

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
    <style>
        /* Mobile Optimierungen */
        @media (max-width: 768px) {
            /* Navigation kompakter */
            .nav-mobile {
                flex-direction: column;
                gap: 12px;
            }
            
            .nav-mobile a {
                font-size: 14px;
                padding: 8px 12px;
            }
            
            /* Kurs-Karten volle Breite */
            .course-card {
                width: 100%;
            }
            
            /* Buttons größer und besser sichtbar */
            .course-actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-top: 16px;
            }
            
            .course-actions button,
            .course-actions a {
                width: 100% !important;
                padding: 14px 20px !important;
                font-size: 15px !important;
                font-weight: 600 !important;
                text-align: center;
                display: flex !important;
                align-items: center;
                justify-content: center;
                min-height: 48px;
            }
            
            /* Nischen-Filter scrollbar */
            .niche-filter-scroll {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            
            .niche-filter-scroll::-webkit-scrollbar {
                height: 6px;
            }
            
            .niche-filter-scroll::-webkit-scrollbar-thumb {
                background: rgba(168, 85, 247, 0.5);
                border-radius: 3px;
            }
            
            /* Badges besser sichtbar */
            .course-badge {
                font-size: 13px;
                padding: 6px 12px;
            }
            
            /* Titel und Beschreibung besser lesbar */
            .course-title {
                font-size: 18px;
                line-height: 1.3;
            }
            
            .course-description {
                font-size: 14px;
                line-height: 1.5;
            }
            
            /* Meta-Informationen kompakter */
            .course-meta {
                font-size: 13px;
            }
            
            /* Padding anpassen */
            .page-container {
                padding: 16px;
            }
            
            .course-card-inner {
                padding: 16px;
            }
            
            /* Header kompakter */
            .page-header {
                margin-bottom: 20px;
            }
            
            .page-header h1 {
                font-size: 28px;
            }
            
            .page-header p {
                font-size: 14px;
            }
        }
        
        /* Smooth transitions */
        button, a {
            transition: all 0.2s ease;
        }
        
        /* Touch-friendly buttons */
        @media (hover: none) {
            button:active,
            a:active {
                transform: scale(0.95);
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="text-xl md:text-2xl font-bold text-purple-600">
                    🚀 KI Lead-System
                </div>
                <div class="nav-mobile flex flex-wrap gap-3 md:gap-6 w-full md:w-auto">
                    <a href="index.php" class="text-gray-600 hover:text-purple-600 text-sm md:text-base">
                        <i class="fas fa-home mr-1 md:mr-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="text-purple-600 font-semibold text-sm md:text-base">
                        <i class="fas fa-graduation-cap mr-1 md:mr-2"></i> Kurse
                    </a>
                    <a href="freebie-editor.php" class="text-gray-600 hover:text-purple-600 text-sm md:text-base">
                        <i class="fas fa-edit mr-1 md:mr-2"></i> Freebie
                    </a>
                    <a href="legal-texts.php" class="text-gray-600 hover:text-purple-600 text-sm md:text-base">
                        <i class="fas fa-file-contract mr-1 md:mr-2"></i> Rechtstexte
                    </a>
                    <a href="tutorials.php" class="text-gray-600 hover:text-purple-600 text-sm md:text-base">
                        <i class="fas fa-question-circle mr-1 md:mr-2"></i> Hilfe
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 text-sm md:text-base">
                        <i class="fas fa-sign-out-alt mr-1 md:mr-2"></i> Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-container max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
        <!-- Header -->
        <div class="page-header mb-6 md:mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">Videokurse</h1>
            <p class="text-gray-600 text-sm md:text-base">Wähle einen Kurs aus, um ihn in deinem Freebie zu verwenden</p>
        </div>

        <!-- Nischen-Filter -->
        <div class="bg-white rounded-lg shadow-lg p-4 md:p-6 mb-6 md:mb-8">
            <h3 class="font-bold text-base md:text-lg mb-3 md:mb-4">
                <i class="fas fa-filter mr-2 text-purple-600"></i> Nach Nische filtern
            </h3>
            <div class="niche-filter-scroll">
                <div class="flex flex-nowrap md:flex-wrap gap-2 md:gap-3 pb-2">
                    <?php foreach ($niches as $key => $label): ?>
                        <a href="?niche=<?= $key ?>" 
                           class="flex-shrink-0 px-3 md:px-4 py-2 rounded-lg whitespace-nowrap text-sm md:text-base
                                  <?= $selected_niche === $key ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Kurse -->
        <?php if (empty($courses)): ?>
            <div class="bg-white rounded-lg shadow-lg p-8 md:p-12 text-center">
                <i class="fas fa-graduation-cap text-5xl md:text-6xl text-gray-300 mb-4"></i>
                <p class="text-lg md:text-xl text-gray-600">Keine Kurse in dieser Kategorie verfügbar</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                <?php foreach ($courses as $course): ?>
                    <?php
                    // Zugriffsstatus prüfen
                    $hasAccess = !empty($course['has_access']) || !$course['is_premium'];
                    $isPremiumLocked = $course['is_premium'] && empty($course['has_access']);
                    ?>
                    
                    <div class="course-card bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <!-- Thumbnail -->
                        <div class="relative">
                            <?php if ($course['thumbnail']): ?>
                                <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                                     alt="<?= htmlspecialchars($course['title']) ?>" 
                                     class="w-full h-40 md:h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-40 md:h-48 bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                                    <i class="fas fa-graduation-cap text-5xl md:text-6xl text-white opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Lock Overlay für gesperrte Premium-Kurse -->
                            <?php if ($isPremiumLocked): ?>
                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                    <i class="fas fa-lock text-white text-4xl md:text-5xl"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Access Badge -->
                            <?php if ($hasAccess && $course['is_premium']): ?>
                                <div class="absolute top-2 md:top-3 right-2 md:right-3 bg-green-500 text-white px-2 md:px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                                    <i class="fas fa-check-circle"></i> Freigeschaltet
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="course-card-inner p-4 md:p-6">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="course-title text-lg md:text-xl font-bold text-gray-800 flex-1 pr-2">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h3>
                                <?php if ($course['is_premium']): ?>
                                    <span class="course-badge flex-shrink-0 px-2 md:px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                        <i class="fas fa-crown"></i> Premium
                                    </span>
                                <?php else: ?>
                                    <span class="course-badge flex-shrink-0 px-2 md:px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                        <i class="fas fa-gift"></i> Kostenlos
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="course-description text-gray-600 text-sm mb-3 md:mb-4 line-clamp-3">
                                <?= substr(htmlspecialchars($course['description']), 0, 120) ?>...
                            </p>
                            
                            <div class="course-meta flex gap-3 md:gap-4 text-xs md:text-sm text-gray-500 mb-3 md:mb-4">
                                <div>
                                    <i class="fas fa-list mr-1"></i> <?= $course['module_count'] ?> Module
                                </div>
                                <div>
                                    <i class="fas fa-play-circle mr-1"></i> <?= $course['lesson_count'] ?> Videos
                                </div>
                            </div>
                            
                            <!-- Zugriffsquelle anzeigen -->
                            <?php if ($hasAccess && !empty($course['access_source'])): ?>
                                <div class="mb-3 md:mb-4 text-xs text-gray-500 flex items-center gap-1">
                                    <i class="fas fa-info-circle"></i>
                                    Zugriff über: 
                                    <?php 
                                    switch($course['access_source']) {
                                        case 'webhook_v4':
                                            echo 'Dein Paket';
                                            break;
                                        case 'webhook_v4_upsell':
                                            echo 'Dein Upgrade';
                                            break;
                                        case 'manual':
                                            echo 'Manuell gewährt';
                                            break;
                                        default:
                                            echo 'Digistore24-Kauf';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="course-actions">
                                <?php if ($hasAccess): ?>
                                    <!-- Kunde hat Zugriff -->
                                    <button onclick="previewCourse(<?= $course['id'] ?>)" 
                                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-semibold">
                                        <i class="fas fa-eye mr-2"></i> Vorschau
                                    </button>
                                    <a href="freebie-editor.php?course_id=<?= $course['id'] ?>" 
                                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-semibold text-center">
                                        <i class="fas fa-magic mr-2"></i> Im Editor verwenden
                                    </a>
                                <?php elseif ($isPremiumLocked): ?>
                                    <!-- Premium-Kurs ohne Zugriff -->
                                    <?php if (!empty($course['digistore_product_id'])): ?>
                                        <button onclick="previewCourse(<?= $course['id'] ?>)" 
                                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-semibold">
                                            <i class="fas fa-eye mr-2"></i> Vorschau ansehen
                                        </button>
                                        <a href="https://www.digistore24.com/product/<?= htmlspecialchars($course['digistore_product_id']) ?>" 
                                           target="_blank"
                                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-lg font-semibold text-center">
                                            <i class="fas fa-shopping-cart mr-2"></i> Jetzt freischalten
                                        </a>
                                    <?php else: ?>
                                        <div class="bg-gray-200 text-gray-500 px-4 py-3 rounded-lg font-semibold text-center cursor-not-allowed">
                                            <i class="fas fa-lock mr-2"></i> Gesperrt
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vorschau Modal -->
    <div id="preview-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4 md:p-6">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-4 md:p-6">
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h2 class="text-xl md:text-2xl font-bold" id="preview-title">Kursvorschau</h2>
                    <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl w-10 h-10 flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="preview-content">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl md:text-4xl text-purple-600"></i>
                        <p class="text-gray-600 mt-4 text-sm md:text-base">Lade Vorschau...</p>
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
        
        // Verhindere Zoom bei Doppeltap auf Buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('touchend', function(e) {
                e.preventDefault();
                this.click();
            }, { passive: false });
        });
    </script>

</body>
</html>