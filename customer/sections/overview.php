<?php
/**
 * Customer Dashboard - Overview Section
 * Modernes SaaS-Dashboard mit Statistiken, Checkliste und Motivationssprüchen
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// ===== STATISTIKEN ABRUFEN =====
try {
    // Freigeschaltete Freebies
    $stmt_freebies = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?");
    $stmt_freebies->execute([$customer_id]);
    $freebies_unlocked = $stmt_freebies->fetchColumn();
    
    // Videokurse (Zugewiesene Kurse für diesen Kunden)
    $stmt_courses = $pdo->prepare("
        SELECT COUNT(*) FROM course_access 
        WHERE customer_id = ? AND has_access = 1
    ");
    $stmt_courses->execute([$customer_id]);
    $courses_count = $stmt_courses->fetchColumn();
    
    // Gesamte Klicks (aus customer_freebies Tabelle)
    $stmt_clicks = $pdo->prepare("
        SELECT COALESCE(SUM(clicks), 0) FROM customer_freebies 
        WHERE customer_id = ?
    ");
    $stmt_clicks->execute([$customer_id]);
    $total_clicks = $stmt_clicks->fetchColumn();
    
    // Neue Kurse prüfen (Kurse, die in den letzten 30 Tagen erstellt wurden)
    $stmt_new_courses = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.thumbnail, c.is_premium 
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.customer_id = ?
        WHERE c.is_active = 1 
        AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND (ca.customer_id IS NULL OR ca.has_access = 0)
        ORDER BY c.created_at DESC
        LIMIT 3
    ");
    $stmt_new_courses->execute([$customer_id]);
    $new_courses = $stmt_new_courses->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard Stats Error: " . $e->getMessage());
    $freebies_unlocked = 0;
    $courses_count = 0;
    $total_clicks = 0;
    $new_courses = [];
}

// ===== MOTIVATIONSSPRÜCHE =====
$motivational_quotes = [
    "Erfolg entsteht durch Tun – starte jetzt deinen nächsten Schritt! 🚀",
    "Jeder Klick bringt dich deinem Ziel näher. 🎯",
    "Kleine Schritte – große Ergebnisse. 💪",
    "Dein Erfolg beginnt genau hier, genau jetzt. ⭐",
    "Konstanz schlägt Perfektion – bleib dran! 🔥",
    "Die besten Leads entstehen aus echtem Mehrwert. 💎",
    "Heute ist der perfekte Tag, um durchzustarten! 🌟",
    "Deine Vision ist größer als jede Herausforderung. 🏆",
    "Jedes Freebie ist eine Chance für neue Kunden. 🎁",
    "Glaube an dich – deine Leads warten schon! 💫"
];

// Täglicher Spruch basierend auf dem aktuellen Tag
$day_of_year = date('z');
$daily_quote = $motivational_quotes[$day_of_year % count($motivational_quotes)];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .count-animation {
            animation: countUp 0.5s ease-out;
        }
        
        .progress-bar-fill {
            transition: width 1s ease-out;
        }
        
        .checkbox-custom {
            appearance: none;
            width: 24px;
            height: 24px;
            border: 2px solid #667eea;
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }
        
        .checkbox-custom:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .checkbox-custom:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- ===== WILLKOMMENSBEREICH ===== -->
        <div class="mb-8 animate-fade-in-up">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl p-8 shadow-2xl">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                    Willkommen zurück, <?php echo htmlspecialchars($customer_name); ?>! 👋
                </h1>
                <p class="text-purple-100 text-lg mb-6">
                    Hier siehst du deine Erfolge und nächsten Schritte.
                </p>
                <a href="?page=tutorials" 
                   class="inline-flex items-center gap-2 bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-rocket"></i>
                    Jetzt starten
                </a>
            </div>
        </div>
        
        <!-- ===== STATISTIKÜBERSICHT ===== -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Freigeschaltete Freebies -->
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-gift text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($freebies_unlocked); ?>
                    </div>
                    <div class="text-blue-100 text-sm font-medium">
                        Freigeschaltete Freebies
                    </div>
                </div>
            </div>
            
            <!-- Deine Videokurse -->
            <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-play-circle text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($courses_count); ?>
                    </div>
                    <div class="text-purple-100 text-sm font-medium">
                        Deine Videokurse
                    </div>
                </div>
            </div>
            
            <!-- Gesamte Klicks -->
            <div class="stat-card bg-gradient-to-br from-pink-500 to-pink-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-mouse-pointer text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($total_clicks); ?>
                    </div>
                    <div class="text-pink-100 text-sm font-medium">
                        Gesamte Klicks
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===== NEUE KURSE (OPTIONAL) ===== -->
        <?php if (!empty($new_courses)): ?>
        <div class="mb-8 animate-fade-in-up opacity-0" style="animation-delay: 0.4s;">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white mb-1">
                            <i class="fas fa-sparkles text-yellow-400 mr-2"></i>
                            Neue Kurse verfügbar!
                        </h2>
                        <p class="text-gray-400">Entdecke die neuesten Lerninhalte</p>
                    </div>
                    <a href="?page=kurse" class="text-purple-400 hover:text-purple-300 font-semibold">
                        Alle ansehen →
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($new_courses as $course): ?>
                    <div class="bg-gray-800/50 rounded-xl overflow-hidden border border-gray-700 hover:border-purple-500 transition-all">
                        <?php if (!empty($course['thumbnail'])): ?>
                        <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                             class="w-full h-32 object-cover">
                        <?php else: ?>
                        <div class="w-full h-32 bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-white text-4xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-white font-semibold text-sm line-clamp-1">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </h3>
                                <?php if ($course['is_premium']): ?>
                                <span class="bg-yellow-500/20 text-yellow-300 text-xs px-2 py-0.5 rounded-full">
                                    Premium
                                </span>
                                <?php else: ?>
                                <span class="bg-green-500/20 text-green-300 text-xs px-2 py-0.5 rounded-full">
                                    Kostenlos
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-gray-400 text-xs line-clamp-2 mb-3">
                                <?php echo htmlspecialchars($course['description'] ?? 'Neuer Kurs verfügbar'); ?>
                            </p>
                            <a href="?page=kurse" 
                               class="block text-center bg-purple-600 hover:bg-purple-700 text-white text-sm py-2 rounded-lg transition-colors">
                                Jetzt ansehen
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ===== CHECKLISTE & MOTIVATIONSSPRUCH ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Checkliste -->
            <div class="lg:col-span-2 animate-fade-in-up opacity-0" style="animation-delay: 0.5s;">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-blue-500/20">
                    <h2 class="text-2xl font-bold text-white mb-2">
                        <i class="fas fa-list-check text-blue-400 mr-2"></i>
                        Dein Start-Plan
                    </h2>
                    <p class="text-gray-400 mb-6">Folge diesen Schritten für deinen erfolgreichen Start</p>
                    
                    <!-- Fortschrittsbalken -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-300">Fortschritt</span>
                            <span class="text-sm font-bold text-blue-400" id="progress-percentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div id="progress-bar" 
                                 class="progress-bar-fill bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full" 
                                 style="width: 0%">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Checkliste -->
                    <div class="space-y-4" id="checklist">
                        <label class="flex items-start gap-4 p-4 rounded-xl bg-gray-800/50 hover:bg-gray-800 cursor-pointer transition-all border border-transparent hover:border-blue-500/30">
                            <input type="checkbox" 
                                   class="checkbox-custom mt-1 flex-shrink-0" 
                                   data-task="videos"
                                   onchange="updateProgress()">
                            <div class="flex-1">
                                <div class="text-white font-medium mb-1">Anleitungsvideos ansehen</div>
                                <div class="text-gray-400 text-sm">Lerne die Grundlagen unseres Systems kennen</div>
                            </div>
                        </label>
                        
                        <label class="flex items-start gap-4 p-4 rounded-xl bg-gray-800/50 hover:bg-gray-800 cursor-pointer transition-all border border-transparent hover:border-blue-500/30">
                            <input type="checkbox" 
                                   class="checkbox-custom mt-1 flex-shrink-0" 
                                   data-task="rechtstexte"
                                   onchange="updateProgress()">
                            <div class="flex-1">
                                <div class="text-white font-medium mb-1">Rechtstexte erstellen</div>
                                <div class="text-gray-400 text-sm">Erstelle deine Datenschutzerklärung und Impressum</div>
                            </div>
                        </label>
                        
                        <label class="flex items-start gap-4 p-4 rounded-xl bg-gray-800/50 hover:bg-gray-800 cursor-pointer transition-all border border-transparent hover:border-blue-500/30">
                            <input type="checkbox" 
                                   class="checkbox-custom mt-1 flex-shrink-0" 
                                   data-task="freebie"
                                   onchange="updateProgress()">
                            <div class="flex-1">
                                <div class="text-white font-medium mb-1">Erstes Freebie erstellen</div>
                                <div class="text-gray-400 text-sm">Nutze unsere Templates für dein erstes Freebie</div>
                            </div>
                        </label>
                        
                        <label class="flex items-start gap-4 p-4 rounded-xl bg-gray-800/50 hover:bg-gray-800 cursor-pointer transition-all border border-transparent hover:border-blue-500/30">
                            <input type="checkbox" 
                                   class="checkbox-custom mt-1 flex-shrink-0" 
                                   data-task="template"
                                   onchange="updateProgress()">
                            <div class="flex-1">
                                <div class="text-white font-medium mb-1">Template veröffentlichen</div>
                                <div class="text-gray-400 text-sm">Stelle dein Freebie online und teile den Link</div>
                            </div>
                        </label>
                        
                        <label class="flex items-start gap-4 p-4 rounded-xl bg-gray-800/50 hover:bg-gray-800 cursor-pointer transition-all border border-transparent hover:border-blue-500/30">
                            <input type="checkbox" 
                                   class="checkbox-custom mt-1 flex-shrink-0" 
                                   data-task="lead"
                                   onchange="updateProgress()">
                            <div class="flex-1">
                                <div class="text-white font-medium mb-1">Ersten Lead generieren</div>
                                <div class="text-gray-400 text-sm">Gewinne deinen ersten Interessenten</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Motivationsspruch -->
            <div class="animate-fade-in-up opacity-0" style="animation-delay: 0.6s;">
                <div class="bg-gradient-to-br from-yellow-500/10 to-orange-500/10 rounded-2xl p-6 shadow-xl border border-yellow-500/30 h-full flex flex-col justify-center">
                    <div class="text-center">
                        <div class="text-6xl mb-4">💡</div>
                        <h3 class="text-xl font-bold text-white mb-4">Deine tägliche Motivation</h3>
                        <blockquote class="text-lg text-gray-200 font-medium leading-relaxed mb-4">
                            "<?php echo htmlspecialchars($daily_quote); ?>"
                        </blockquote>
                        <div class="text-sm text-gray-400">
                            <?php echo date('d.m.Y'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // LocalStorage für Checklisten-Fortschritt
        const STORAGE_KEY = 'customer_checklist_progress';
        
        // Fortschritt beim Laden wiederherstellen
        function loadProgress() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const progress = JSON.parse(saved);
                Object.keys(progress).forEach(task => {
                    const checkbox = document.querySelector(`[data-task="${task}"]`);
                    if (checkbox) {
                        checkbox.checked = progress[task];
                    }
                });
            }
            updateProgress();
        }
        
        // Fortschritt aktualisieren
        function updateProgress() {
            const checkboxes = document.querySelectorAll('#checklist input[type="checkbox"]');
            const total = checkboxes.length;
            let checked = 0;
            
            // Fortschritt speichern
            const progress = {};
            checkboxes.forEach(checkbox => {
                const task = checkbox.dataset.task;
                progress[task] = checkbox.checked;
                if (checkbox.checked) checked++;
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(progress));
            
            // Fortschrittsbalken aktualisieren
            const percentage = Math.round((checked / total) * 100);
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-percentage');
            
            progressBar.style.width = percentage + '%';
            progressText.textContent = percentage + '%';
        }
        
        // Beim Laden ausführen
        document.addEventListener('DOMContentLoaded', function() {
            loadProgress();
        });
    </script>
</body>
</html>
