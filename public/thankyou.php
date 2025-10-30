<?php
// Danke-Seite nach Freebie-Eintragung
// Zeigt den Link zum Videokurs an

// Parameter: freebie_id oder unique_id
$freebie_id = $_GET['freebie_id'] ?? '';
$unique_id = $_GET['unique_id'] ?? '';

require_once '../config/database.php';
$conn = getDBConnection();

// Freebie laden
if ($unique_id) {
    $stmt = $conn->prepare("
        SELECT f.*, c.title as course_title, c.thumbnail, c.id as course_id
        FROM freebies f
        JOIN courses c ON f.course_id = c.id
        WHERE f.unique_id = ?
    ");
    $stmt->execute([$unique_id]);
} elseif ($freebie_id) {
    $stmt = $conn->prepare("
        SELECT f.*, c.title as course_title, c.thumbnail, c.id as course_id
        FROM freebies f
        JOIN courses c ON f.course_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$freebie_id]);
} else {
    die('Keine gültige Freebie-ID');
}

$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freebie) {
    die('Freebie nicht gefunden');
}

// Module & Lektionen laden
$stmt = $conn->prepare("
    SELECT * FROM modules 
    WHERE course_id = ? 
    ORDER BY sort_order
");
$stmt->execute([$freebie['course_id']]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lessons_by_module = [];
foreach ($modules as $module) {
    $stmt = $conn->prepare("
        SELECT * FROM lessons 
        WHERE module_id = ? 
        ORDER BY sort_order
    ");
    $stmt->execute([$module['id']]);
    $lessons_by_module[$module['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vielen Dank! Dein Kurs wartet auf dich 🎉</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear;
        }
        
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 to-pink-50 min-h-screen">

    <!-- Success Header -->
    <div class="text-center py-16 fade-in-up">
        <div class="mb-6">
            <div class="inline-block bg-green-500 text-white rounded-full p-6">
                <i class="fas fa-check text-6xl"></i>
            </div>
        </div>
        <h1 class="text-5xl font-bold text-gray-800 mb-4">
            Geschafft! 🎉
        </h1>
        <p class="text-2xl text-gray-600 mb-2">
            Vielen Dank für deine Anmeldung!
        </p>
        <p class="text-lg text-gray-500">
            Du hast sofortigen Zugriff auf deinen Kurs.
        </p>
    </div>

    <!-- Course Access Section -->
    <div class="max-w-4xl mx-auto px-6 pb-16">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden fade-in-up" style="animation-delay: 0.2s">
            <!-- Course Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-8 text-center">
                <h2 class="text-3xl font-bold mb-2">Dein Videokurs</h2>
                <p class="text-lg opacity-90"><?= htmlspecialchars($freebie['course_title']) ?></p>
            </div>
            
            <!-- Course Thumbnail -->
            <?php if ($freebie['thumbnail']): ?>
                <div class="relative">
                    <img src="../uploads/thumbnails/<?= htmlspecialchars($freebie['thumbnail']) ?>" 
                         alt="Course" class="w-full h-80 object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                        <button onclick="scrollToCourse()" 
                                class="bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg shadow-2xl hover:scale-110 transform transition">
                            <i class="fas fa-play mr-3"></i> Kurs starten
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Course Benefits -->
            <div class="p-8 bg-purple-50">
                <h3 class="text-2xl font-bold mb-6 text-center">Was du lernen wirst:</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-purple-600 text-white w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-video text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold mb-1">HD-Videokurse</h4>
                            <p class="text-sm text-gray-600">Professionell produzierte Videos</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="bg-purple-600 text-white w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-pdf text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold mb-1">PDF-Downloads</h4>
                            <p class="text-sm text-gray-600">Begleitmaterial zum Herunterladen</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="bg-purple-600 text-white w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-infinity text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold mb-1">Lebenslanger Zugang</h4>
                            <p class="text-sm text-gray-600">Lerne in deinem Tempo</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="bg-purple-600 text-white w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-mobile-alt text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold mb-1">Mobile-optimiert</h4>
                            <p class="text-sm text-gray-600">Lerne überall & jederzeit</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Content -->
        <div id="course-content" class="mt-12 bg-white rounded-2xl shadow-2xl p-8 fade-in-up" style="animation-delay: 0.4s">
            <h3 class="text-3xl font-bold mb-8 text-center">
                <i class="fas fa-list mr-3 text-purple-600"></i> Kurs-Inhalte
            </h3>
            
            <?php if (!empty($modules)): ?>
                <div class="space-y-6">
                    <?php foreach ($modules as $index => $module): ?>
                        <div class="border-2 border-gray-200 rounded-xl overflow-hidden hover:border-purple-400 transition">
                            <div class="bg-gray-50 p-6">
                                <h4 class="text-xl font-bold text-gray-800">
                                    <span class="bg-purple-600 text-white px-3 py-1 rounded-lg mr-3">
                                        Modul <?= $index + 1 ?>
                                    </span>
                                    <?= htmlspecialchars($module['title']) ?>
                                </h4>
                                <?php if ($module['description']): ?>
                                    <p class="text-gray-600 mt-2 ml-20"><?= htmlspecialchars($module['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($lessons_by_module[$module['id']])): ?>
                                <div class="p-6 space-y-4">
                                    <?php foreach ($lessons_by_module[$module['id']] as $lesson_index => $lesson): ?>
                                        <div class="flex items-center gap-4 p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition cursor-pointer"
                                             onclick="playVideo('<?= htmlspecialchars($lesson['vimeo_url']) ?>')">
                                            <div class="bg-purple-600 text-white w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-play"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="font-semibold">
                                                    Lektion <?= $lesson_index + 1 ?>: <?= htmlspecialchars($lesson['title']) ?>
                                                </div>
                                                <?php if ($lesson['description']): ?>
                                                    <div class="text-sm text-gray-600"><?= htmlspecialchars($lesson['description']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($lesson['pdf_file']): ?>
                                                <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf_file']) ?>" 
                                                   target="_blank" 
                                                   onclick="event.stopPropagation()"
                                                   class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                                                    <i class="fas fa-file-pdf mr-2"></i> PDF
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500">Keine Module vorhanden.</p>
            <?php endif; ?>
            
            <!-- CTA -->
            <div class="text-center mt-12 pt-8 border-t border-gray-200">
                <p class="text-lg text-gray-600 mb-6">
                    Bereit, loszulegen? Klicke auf eine Lektion oben!
                </p>
                <button onclick="scrollToCourse()" 
                        class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-12 py-4 rounded-full font-bold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition">
                    <i class="fas fa-rocket mr-2"></i> Jetzt starten!
                </button>
            </div>
        </div>

        <!-- Email Check Reminder -->
        <div class="mt-12 bg-yellow-50 border-2 border-yellow-300 rounded-xl p-6 text-center fade-in-up" style="animation-delay: 0.6s">
            <i class="fas fa-envelope text-4xl text-yellow-600 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Vergiss nicht, deine E-Mail zu bestätigen!</h3>
            <p class="text-gray-600">
                Wir haben dir eine Bestätigungs-E-Mail geschickt. 
                Bitte überprüfe deinen Posteingang (auch den Spam-Ordner).
            </p>
        </div>
    </div>

    <!-- Video Player Modal -->
    <div id="video-modal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-6">
        <div class="w-full max-w-5xl relative">
            <button onclick="closeVideo()" 
                    class="absolute -top-12 right-0 text-white text-4xl hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
            <div id="video-container" class="bg-black rounded-lg overflow-hidden aspect-video">
                <!-- Vimeo-Player wird hier geladen -->
            </div>
        </div>
    </div>

    <script src="https://player.vimeo.com/api/player.js"></script>
    <script>
        let currentPlayer = null;
        
        function playVideo(vimeoUrl) {
            const modal = document.getElementById('video-modal');
            const container = document.getElementById('video-container');
            
            // Vimeo-Player erstellen
            container.innerHTML = `<iframe src="${vimeoUrl}" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
            
            modal.classList.remove('hidden');
        }
        
        function closeVideo() {
            const modal = document.getElementById('video-modal');
            const container = document.getElementById('video-container');
            
            container.innerHTML = '';
            modal.classList.add('hidden');
        }
        
        function scrollToCourse() {
            document.getElementById('course-content').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }
        
        // ESC schließt Video
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideo();
            }
        });
        
        // Konfetti-Effekt (optional)
        function createConfetti() {
            const colors = ['#ff0', '#f0f', '#0ff', '#f00', '#0f0'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * window.innerWidth + 'px';
                    confetti.style.top = '-10px';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 3000);
                }, i * 50);
            }
        }
        
        // Konfetti beim Laden
        window.addEventListener('load', createConfetti);
    </script>

</body>
</html>
