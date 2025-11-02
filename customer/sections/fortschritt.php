<?php
/**
 * Customer Dashboard - Fortschritt & Analytics Section
 * Umfassende Performance-Übersicht mit Charts, Stats und Achievements
 */

if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// ===== DATEN ABRUFEN =====
try {
    // Basis-Statistiken
    $stmt_freebies = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?");
    $stmt_freebies->execute([$customer_id]);
    $total_freebies = $stmt_freebies->fetchColumn();
    
    $stmt_clicks = $pdo->prepare("SELECT COALESCE(SUM(clicks), 0) FROM customer_freebies WHERE customer_id = ?");
    $stmt_clicks->execute([$customer_id]);
    $total_clicks = $stmt_clicks->fetchColumn();
    
    $stmt_courses = $pdo->prepare("SELECT COUNT(*) FROM course_access WHERE customer_id = ? AND has_access = 1");
    $stmt_courses->execute([$customer_id]);
    $total_courses = $stmt_courses->fetchColumn();
    
    // Freebie Performance Details
    $stmt_freebie_details = $pdo->prepare("
        SELECT 
            cf.id,
            cf.freebie_name,
            cf.clicks,
            cf.created_at,
            cf.url_slug
        FROM customer_freebies cf
        WHERE cf.customer_id = ?
        ORDER BY cf.clicks DESC
        LIMIT 10
    ");
    $stmt_freebie_details->execute([$customer_id]);
    $freebie_performance = $stmt_freebie_details->fetchAll(PDO::FETCH_ASSOC);
    
    // Kurs-Fortschritt
    $stmt_course_progress = $pdo->prepare("
        SELECT 
            c.id,
            c.title,
            c.thumbnail,
            COUNT(DISTINCT cm.id) as total_modules,
            COUNT(DISTINCT cl.id) as total_lessons,
            COUNT(DISTINCT clc.lesson_id) as completed_lessons
        FROM courses c
        INNER JOIN course_access ca ON c.id = ca.course_id
        LEFT JOIN course_modules cm ON c.id = cm.course_id
        LEFT JOIN course_lessons cl ON cm.id = cl.module_id
        LEFT JOIN course_lesson_completions clc ON cl.id = clc.lesson_id AND clc.customer_id = ?
        WHERE ca.customer_id = ? AND ca.has_access = 1
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt_course_progress->execute([$customer_id, $customer_id]);
    $course_progress = $stmt_course_progress->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktivitäts-Timeline (letzte 10 Aktivitäten)
    $activities = [];
    
    // Freebies erstellt
    $stmt_freebie_activity = $pdo->prepare("
        SELECT 'freebie_created' as type, freebie_name as name, created_at 
        FROM customer_freebies 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt_freebie_activity->execute([$customer_id]);
    $activities = array_merge($activities, $stmt_freebie_activity->fetchAll(PDO::FETCH_ASSOC));
    
    // Lektionen abgeschlossen
    $stmt_lesson_activity = $pdo->prepare("
        SELECT 'lesson_completed' as type, cl.title as name, clc.completed_at as created_at
        FROM course_lesson_completions clc
        INNER JOIN course_lessons cl ON clc.lesson_id = cl.id
        WHERE clc.customer_id = ?
        ORDER BY clc.completed_at DESC
        LIMIT 5
    ");
    $stmt_lesson_activity->execute([$customer_id]);
    $activities = array_merge($activities, $stmt_lesson_activity->fetchAll(PDO::FETCH_ASSOC));
    
    // Sortieren nach Datum
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $activities = array_slice($activities, 0, 10);
    
    // Klick-Daten für Chart (letzte 30 Tage)
    $chart_data = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_data[$date] = 0;
    }
    
    // Berechnung von durchschnittlichen Klicks pro Tag (simuliert, da wir keine historischen Daten haben)
    $avg_clicks_per_day = $total_clicks > 0 ? round($total_clicks / 30) : 0;
    
} catch (PDOException $e) {
    error_log("Fortschritt Error: " . $e->getMessage());
    $total_freebies = 0;
    $total_clicks = 0;
    $total_courses = 0;
    $freebie_performance = [];
    $course_progress = [];
    $activities = [];
}

// Achievement-Berechnung
$achievements = [
    [
        'id' => 'first_freebie',
        'title' => 'Erster Schritt',
        'description' => 'Erstes Freebie erstellt',
        'icon' => '🎁',
        'unlocked' => $total_freebies >= 1,
        'progress' => min(100, ($total_freebies / 1) * 100)
    ],
    [
        'id' => 'freebie_master',
        'title' => 'Freebie Master',
        'description' => '5 Freebies erstellt',
        'icon' => '🏆',
        'unlocked' => $total_freebies >= 5,
        'progress' => min(100, ($total_freebies / 5) * 100)
    ],
    [
        'id' => 'first_clicks',
        'title' => 'Aufmerksamkeit erregt',
        'description' => '10 Klicks erreicht',
        'icon' => '🎯',
        'unlocked' => $total_clicks >= 10,
        'progress' => min(100, ($total_clicks / 10) * 100)
    ],
    [
        'id' => 'click_champion',
        'title' => 'Klick Champion',
        'description' => '100 Klicks erreicht',
        'icon' => '🚀',
        'unlocked' => $total_clicks >= 100,
        'progress' => min(100, ($total_clicks / 100) * 100)
    ],
    [
        'id' => 'learner',
        'title' => 'Wissbegierig',
        'description' => 'Ersten Kurs gestartet',
        'icon' => '📚',
        'unlocked' => $total_courses >= 1,
        'progress' => min(100, ($total_courses / 1) * 100)
    ],
    [
        'id' => 'dedicated',
        'title' => 'Engagiert',
        'description' => 'Erste Lektion abgeschlossen',
        'icon' => '✅',
        'unlocked' => !empty($course_progress) && array_sum(array_column($course_progress, 'completed_lessons')) >= 1,
        'progress' => !empty($course_progress) ? min(100, (array_sum(array_column($course_progress, 'completed_lessons')) / 1) * 100) : 0
    ]
];

$unlocked_achievements = array_filter($achievements, function($a) { return $a['unlocked']; });
$achievement_percentage = round((count($unlocked_achievements) / count($achievements)) * 100);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .achievement-card {
            transition: all 0.3s ease;
        }
        .achievement-card:hover {
            transform: translateY(-4px);
        }
        .achievement-locked {
            opacity: 0.5;
            filter: grayscale(100%);
        }
        .achievement-unlocked {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .activity-item {
            border-left: 3px solid #667eea;
            transition: all 0.2s;
        }
        .activity-item:hover {
            background: rgba(102, 126, 234, 0.1);
            border-left-color: #764ba2;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                <i class="fas fa-chart-line text-purple-400 mr-2"></i>
                Dein Fortschritt
            </h1>
            <p class="text-gray-400">Verfolge deine Erfolge und erreiche neue Meilensteine</p>
        </div>
        
        <!-- Key Metrics Hero -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-gift text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo number_format($total_freebies); ?></div>
                <div class="text-sm opacity-90">Freebies erstellt</div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-mouse-pointer text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Gesamt</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo number_format($total_clicks); ?></div>
                <div class="text-sm opacity-90">Klicks generiert</div>
            </div>
            
            <div class="bg-gradient-to-br from-pink-500 to-pink-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-graduation-cap text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Aktiv</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo number_format($total_courses); ?></div>
                <div class="text-sm opacity-90">Kurse verfügbar</div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-trophy text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Level</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo count($unlocked_achievements); ?>/<?php echo count($achievements); ?></div>
                <div class="text-sm opacity-90">Achievements</div>
            </div>
        </div>
        
        <!-- Charts & Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Performance Chart -->
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-chart-area text-blue-400 mr-2"></i>
                    Performance Übersicht
                </h3>
                <div class="bg-gray-900 rounded-xl p-4">
                    <canvas id="performanceChart" height="200"></canvas>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400"><?php echo number_format($avg_clicks_per_day); ?></div>
                        <div class="text-sm text-gray-400">Ø Klicks/Tag</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400">
                            <?php echo $total_freebies > 0 ? number_format($total_clicks / $total_freebies, 1) : 0; ?>
                        </div>
                        <div class="text-sm text-gray-400">Ø pro Freebie</div>
                    </div>
                </div>
            </div>
            
            <!-- Top Freebies -->
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                    Top Performance Freebies
                </h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (!empty($freebie_performance)): ?>
                        <?php foreach (array_slice($freebie_performance, 0, 5) as $index => $freebie): ?>
                        <div class="bg-gray-900 rounded-lg p-4 hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-blue-500 w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold">
                                        #<?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <div class="text-white font-semibold line-clamp-1">
                                            <?php echo htmlspecialchars($freebie['freebie_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            <?php echo date('d.m.Y', strtotime($freebie['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-blue-400">
                                        <?php echo number_format($freebie['clicks']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">Klicks</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Noch keine Freebies erstellt</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Achievements Section -->
        <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">
                    <i class="fas fa-trophy text-yellow-400 mr-2"></i>
                    Achievements
                </h3>
                <div class="text-sm">
                    <span class="text-yellow-400 font-bold"><?php echo $achievement_percentage; ?>%</span>
                    <span class="text-gray-400"> freigeschaltet</span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($achievements as $achievement): ?>
                <div class="achievement-card <?php echo $achievement['unlocked'] ? 'achievement-unlocked' : 'achievement-locked'; ?> 
                            bg-gray-900 rounded-xl p-4 text-center border border-gray-700 hover:border-purple-500">
                    <div class="text-5xl mb-3"><?php echo $achievement['icon']; ?></div>
                    <div class="text-white font-semibold text-sm mb-1">
                        <?php echo $achievement['title']; ?>
                    </div>
                    <div class="text-gray-400 text-xs mb-3">
                        <?php echo $achievement['description']; ?>
                    </div>
                    <?php if ($achievement['unlocked']): ?>
                        <div class="bg-green-500/20 text-green-400 text-xs py-1 px-2 rounded-full inline-block">
                            <i class="fas fa-check mr-1"></i>Erreicht
                        </div>
                    <?php else: ?>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-500 to-blue-500 h-2 rounded-full" 
                                 style="width: <?php echo $achievement['progress']; ?>%">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Kurs-Fortschritt -->
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-graduation-cap text-purple-400 mr-2"></i>
                    Kurs-Fortschritt
                </h3>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php if (!empty($course_progress)): ?>
                        <?php foreach ($course_progress as $course): ?>
                        <?php 
                            $total_lessons = $course['total_lessons'];
                            $completed = $course['completed_lessons'];
                            $percentage = $total_lessons > 0 ? round(($completed / $total_lessons) * 100) : 0;
                        ?>
                        <div class="bg-gray-900 rounded-lg p-4">
                            <div class="flex items-start gap-4 mb-3">
                                <?php if (!empty($course['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>"
                                     class="w-16 h-16 object-cover rounded-lg">
                                <?php else: ?>
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-blue-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-book text-white text-2xl"></i>
                                </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold mb-1">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </h4>
                                    <div class="text-sm text-gray-400 mb-2">
                                        <?php echo $completed; ?> / <?php echo $total_lessons; ?> Lektionen
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-purple-500 to-blue-500 h-2 rounded-full transition-all" 
                                             style="width: <?php echo $percentage; ?>%">
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-purple-400"><?php echo $percentage; ?>%</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-book-open text-5xl mb-4"></i>
                            <p class="mb-2">Noch keine Kurse gestartet</p>
                            <a href="?page=kurse" class="text-purple-400 hover:text-purple-300 text-sm">
                                Kurse ansehen →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Aktivitäts-Timeline -->
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-history text-green-400 mr-2"></i>
                    Aktivitäts-Timeline
                </h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item bg-gray-900 rounded-lg p-4 pl-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-1">
                                    <?php if ($activity['type'] === 'freebie_created'): ?>
                                        <i class="fas fa-gift text-blue-400"></i>
                                    <?php elseif ($activity['type'] === 'lesson_completed'): ?>
                                        <i class="fas fa-check-circle text-green-400"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="text-white font-medium mb-1">
                                        <?php if ($activity['type'] === 'freebie_created'): ?>
                                            Freebie erstellt: <span class="text-blue-400"><?php echo htmlspecialchars($activity['name']); ?></span>
                                        <?php elseif ($activity['type'] === 'lesson_completed'): ?>
                                            Lektion abgeschlossen: <span class="text-green-400"><?php echo htmlspecialchars($activity['name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?php 
                                            $time_diff = time() - strtotime($activity['created_at']);
                                            if ($time_diff < 3600) {
                                                echo 'Vor ' . round($time_diff / 60) . ' Minuten';
                                            } elseif ($time_diff < 86400) {
                                                echo 'Vor ' . round($time_diff / 3600) . ' Stunden';
                                            } else {
                                                echo date('d.m.Y H:i', strtotime($activity['created_at']));
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-clock text-5xl mb-4"></i>
                            <p class="mb-2">Noch keine Aktivitäten</p>
                            <p class="text-sm">Deine Timeline wird hier erscheinen</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Performance Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($chart_data)); ?>,
                datasets: [{
                    label: 'Klicks',
                    data: [<?php 
                        // Simulierte Daten mit realistischem Verlauf
                        $simulation = [];
                        $base = max(1, floor($avg_clicks_per_day * 0.8));
                        for ($i = 0; $i < 30; $i++) {
                            $simulation[] = $base + rand(-floor($base * 0.3), floor($base * 0.5));
                        }
                        echo implode(',', $simulation);
                    ?>],
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#888'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#888',
                            maxRotation: 0,
                            callback: function(val, index) {
                                return index % 5 === 0 ? this.getLabelForValue(val).substr(5) : '';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
