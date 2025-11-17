<?php
/**
 * Video-Management für Admin
 * Direkter Zugriff für michael.gluska@gmail.com
 * Videos sind für ALLE Leads sichtbar
 */

require_once __DIR__ . '/config/database.php';
session_start();

$pdo = getDBConnection();

// Nur für eingeloggte Benutzer
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT email, company_name FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['email'] !== 'michael.gluska@gmail.com') {
        die('<div style="padding: 40px; text-align: center; font-family: Arial;"><h2 style="color: #ff6b6b;">⛔ Zugriff verweigert</h2><p>Diese Seite ist nur für den Administrator (michael.gluska@gmail.com) zugänglich.</p><a href="/dashboard.php" style="color: #667eea;">Zurück zum Dashboard</a></div>');
    }
    
    $company_name = $user['company_name'] ?? 'Dashboard';
} catch (PDOException $e) {
    die('Fehler: ' . $e->getMessage());
}

// Freebie-ID aus URL
$selected_freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : null;

if (!$selected_freebie_id) {
    echo '<div style="padding: 40px; font-family: Arial;">';
    echo '<h3>Bitte Freebie-ID angeben</h3>';
    echo '<p>Beispiel: <a href="?freebie=7" style="color: #667eea;">admin_video_manager.php?freebie=7</a></p>';
    echo '<a href="/dashboard.php" style="color: #667eea;">Zurück zum Dashboard</a>';
    echo '</div>';
    exit;
}

// Videos laden - diese sind für ALLE Leads sichtbar
$videos = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM video_tutorials 
        WHERE customer_id = ? AND freebie_id = ? 
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([$customer_id, $selected_freebie_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Videos: " . $e->getMessage());
    $videos = [];
}

function getVimeoEmbedUrl($url) {
    preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
    if (isset($matches[1])) {
        return 'https://player.vimeo.com/video/' . $matches[1] . '?title=0&byline=0&portrait=0';
    }
    return $url;
}

$color_classes = [
    'purple' => 'from-purple-600 to-blue-600',
    'blue' => 'from-blue-600 to-cyan-600',
    'green' => 'from-green-600 to-emerald-600',
    'yellow' => 'from-yellow-600 to-orange-600',
    'red' => 'from-red-600 to-pink-600',
    'pink' => 'from-pink-600 to-purple-600',
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video-Management - <?php echo htmlspecialchars($company_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1e;
            color: #e0e0e0;
            padding: 32px;
            min-height: 100vh;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        @media (max-width: 768px) {
            body { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="mb-8">
            <a href="/dashboard.php" class="inline-flex items-center text-purple-400 hover:text-purple-300 mb-4 text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Zurück zum Dashboard
            </a>
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-video text-purple-400 mr-3"></i>
                        Video-Management
                    </h1>
                    <p class="text-gray-400">Freebie ID: <?php echo $selected_freebie_id; ?> | Diese Videos sind für alle Leads sichtbar</p>
                </div>
                <button onclick="openVideoModal()" 
                        class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg">
                    <i class="fas fa-plus-circle mr-2"></i>Video hinzufügen
                </button>
            </div>
        </div>

        <?php if (empty($videos)): ?>
        <!-- Keine Videos -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-12 text-center shadow-xl border border-purple-500/20">
            <div class="text-6xl mb-4">🎥</div>
            <h3 class="text-white text-2xl font-bold mb-2">Noch keine Videos vorhanden</h3>
            <p class="text-gray-400 mb-6">Füge dein erstes Video hinzu - es wird für alle Leads dieses Freebies sichtbar sein</p>
            <button onclick="openVideoModal()" 
                    class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition-all">
                <i class="fas fa-plus-circle mr-2"></i>Erstes Video hinzufügen
            </button>
        </div>
        <?php else: ?>
        <!-- Video Player -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 shadow-xl border border-purple-500/20 mb-8">
            <div id="currentVideoTitle" class="mb-6">
                <h3 class="text-white text-2xl font-bold mb-2">
                    <i id="currentVideoIcon" class="fas <?php echo htmlspecialchars($videos[0]['category_icon']); ?> text-purple-400 mr-2"></i>
                    <span id="currentVideoName"><?php echo htmlspecialchars($videos[0]['category_name']); ?></span>
                </h3>
                <p id="currentVideoDescription" class="text-gray-400">
                    <?php echo htmlspecialchars($videos[0]['description'] ?: 'Video-Vorschau'); ?>
                </p>
            </div>
            
            <div id="videoPlayerContainer" class="aspect-video bg-gray-900 rounded-xl overflow-hidden border border-purple-500/20">
                <iframe 
                    id="vimeoPlayer"
                    src="<?php echo getVimeoEmbedUrl($videos[0]['vimeo_url']); ?>" 
                    class="w-full h-full"
                    frameborder="0" 
                    allow="autoplay; fullscreen; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>
        
        <!-- Video-Kategorien -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($videos as $video): ?>
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden border border-purple-500/20 hover:border-purple-500 transition-all group shadow-xl cursor-pointer"
                 onclick="loadVideo(this)"
                 data-video-url="<?php echo htmlspecialchars(getVimeoEmbedUrl($video['vimeo_url'])); ?>"
                 data-video-name="<?php echo htmlspecialchars($video['category_name']); ?>"
                 data-video-icon="<?php echo htmlspecialchars($video['category_icon']); ?>"
                 data-video-description="<?php echo htmlspecialchars($video['description'] ?: ''); ?>">
                
                <div class="h-48 bg-gradient-to-br <?php echo $color_classes[$video['category_color']] ?? $color_classes['purple']; ?> flex items-center justify-center relative overflow-hidden">
                    <i class="fas <?php echo htmlspecialchars($video['category_icon']); ?> text-white text-6xl group-hover:scale-110 transition-transform duration-300"></i>
                    
                    <div class="absolute top-3 right-3 flex gap-2">
                        <button onclick="event.stopPropagation(); editVideo(<?php echo htmlspecialchars(json_encode($video)); ?>)" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm transition-all">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="event.stopPropagation(); deleteVideo(<?php echo $video['id']; ?>, '<?php echo addslashes($video['category_name']); ?>')" 
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm transition-all">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <h3 class="text-white font-bold text-xl mb-2"><?php echo htmlspecialchars($video['category_name']); ?></h3>
                    <p class="text-gray-400 text-sm mb-4 line-clamp-2">
                        <?php echo htmlspecialchars($video['description'] ?: 'Klicke um Video anzusehen'); ?>
                    </p>
                    <div class="bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white px-4 py-2 rounded-xl text-center font-semibold transition-all">
                        <i class="fas fa-play-circle mr-2"></i>Video ansehen
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-purple-500/20">
            <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-blue-600 p-6 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-white text-2xl font-bold">
                        <i class="fas fa-video mr-2"></i>
                        <span id="modalTitle">Video hinzufügen</span>
                    </h3>
                    <button onclick="closeVideoModal()" class="text-white hover:text-gray-300 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="videoForm" class="p-6 space-y-6">
                <input type="hidden" id="videoId" name="videoId" value="">
                
                <div>
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-tag mr-2"></i>Kategorie-Name *
                    </label>
                    <input type="text" id="categoryName" required placeholder="z.B. Social Media Sharing"
                           class="w-full bg-gray-700 text-white px-4 py-3 rounded-xl border border-purple-500/20 focus:border-purple-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-link mr-2"></i>Vimeo Video-URL *
                    </label>
                    <input type="url" id="vimeoUrl" required placeholder="https://vimeo.com/123456789"
                           class="w-full bg-gray-700 text-white px-4 py-3 rounded-xl border border-purple-500/20 focus:border-purple-500 focus:outline-none">
                    <p class="text-gray-400 text-sm mt-2">
                        <i class="fas fa-info-circle mr-1"></i>Füge die komplette Vimeo-URL ein
                    </p>
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-align-left mr-2"></i>Beschreibung
                    </label>
                    <textarea id="description" rows="3" placeholder="Kurze Beschreibung des Videos"
                              class="w-full bg-gray-700 text-white px-4 py-3 rounded-xl border border-purple-500/20 focus:border-purple-500 focus:outline-none"></textarea>
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-icons mr-2"></i>Icon
                    </label>
                    <div class="grid grid-cols-5 md:grid-cols-8 gap-2">
                        <?php
                        $icons = ['fa-video', 'fa-share-alt', 'fa-users', 'fa-robot', 'fa-trophy', 'fa-gift', 'fa-chart-line', 'fa-hashtag', 'fa-lightbulb', 'fa-rocket', 'fa-star', 'fa-heart', 'fa-thumbs-up', 'fa-play-circle', 'fa-comment', 'fa-envelope'];
                        foreach ($icons as $icon): ?>
                        <button type="button" class="icon-selector bg-gray-700 hover:bg-purple-600 text-white p-4 rounded-xl transition-all text-xl"
                                data-icon="<?php echo $icon; ?>" onclick="selectIcon('<?php echo $icon; ?>')">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="categoryIcon" name="categoryIcon" value="fa-video">
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-palette mr-2"></i>Farbe
                    </label>
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                        <?php foreach ($color_classes as $color => $gradient): ?>
                        <button type="button" class="color-selector h-12 rounded-xl bg-gradient-to-r <?php echo $gradient; ?> border-2 border-transparent hover:border-white transition-all"
                                data-color="<?php echo $color; ?>" onclick="selectColor('<?php echo $color; ?>')"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="categoryColor" name="categoryColor" value="purple">
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-sort mr-2"></i>Sortierung
                    </label>
                    <input type="number" id="sortOrder" value="0" min="0"
                           class="w-full bg-gray-700 text-white px-4 py-3 rounded-xl border border-purple-500/20 focus:border-purple-500 focus:outline-none">
                    <p class="text-gray-400 text-sm mt-2">
                        <i class="fas fa-info-circle mr-1"></i>Niedrigere Zahlen erscheinen zuerst
                    </p>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        <i class="fas fa-save mr-2"></i>Speichern
                    </button>
                    <button type="button" onclick="closeVideoModal()" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Abbrechen
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
function loadVideo(element) {
    document.getElementById('vimeoPlayer').src = element.getAttribute('data-video-url');
    document.getElementById('currentVideoName').textContent = element.getAttribute('data-video-name');
    document.getElementById('currentVideoIcon').className = 'fas ' + element.getAttribute('data-video-icon') + ' text-purple-400 mr-2';
    document.getElementById('currentVideoDescription').textContent = element.getAttribute('data-video-description') || 'Video-Vorschau';
    document.getElementById('videoPlayerContainer').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function openVideoModal() {
    document.getElementById('videoModal').classList.remove('hidden');
    document.getElementById('videoForm').reset();
    document.getElementById('videoId').value = '';
    document.getElementById('modalTitle').textContent = 'Video hinzufügen';
    document.querySelectorAll('.icon-selector').forEach(btn => btn.classList.remove('bg-purple-600'));
    document.querySelectorAll('.icon-selector')[0].classList.add('bg-purple-600');
    document.querySelectorAll('.color-selector').forEach(btn => btn.classList.remove('border-white'));
    document.querySelectorAll('.color-selector')[0].classList.add('border-white');
}

function closeVideoModal() {
    document.getElementById('videoModal').classList.add('hidden');
}

function selectIcon(icon) {
    document.getElementById('categoryIcon').value = icon;
    document.querySelectorAll('.icon-selector').forEach(btn => {
        btn.classList.remove('bg-purple-600');
        btn.classList.add('bg-gray-700');
    });
    event.target.closest('button').classList.remove('bg-gray-700');
    event.target.closest('button').classList.add('bg-purple-600');
}

function selectColor(color) {
    document.getElementById('categoryColor').value = color;
    document.querySelectorAll('.color-selector').forEach(btn => btn.classList.remove('border-white'));
    event.target.classList.add('border-white');
}

function editVideo(video) {
    openVideoModal();
    document.getElementById('modalTitle').textContent = 'Video bearbeiten';
    document.getElementById('videoId').value = video.id;
    document.getElementById('categoryName').value = video.category_name;
    document.getElementById('vimeoUrl').value = video.vimeo_url;
    document.getElementById('description').value = video.description || '';
    document.getElementById('categoryIcon').value = video.category_icon;
    document.getElementById('categoryColor').value = video.category_color;
    document.getElementById('sortOrder').value = video.sort_order;
    
    document.querySelectorAll('.icon-selector').forEach(btn => {
        btn.classList.remove('bg-purple-600');
        btn.classList.add('bg-gray-700');
        if (btn.getAttribute('data-icon') === video.category_icon) {
            btn.classList.remove('bg-gray-700');
            btn.classList.add('bg-purple-600');
        }
    });
    
    document.querySelectorAll('.color-selector').forEach(btn => {
        btn.classList.remove('border-white');
        if (btn.getAttribute('data-color') === video.category_color) {
            btn.classList.add('border-white');
        }
    });
}

async function deleteVideo(videoId, videoName) {
    if (!confirm(`Video "${videoName}" wirklich löschen?`)) return;
    
    try {
        const response = await fetch('/api/video-tutorials.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: videoId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('Fehler beim Löschen: ' + error.message);
    }
}

document.getElementById('videoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const videoId = document.getElementById('videoId').value;
    const action = videoId ? 'update' : 'create';
    
    const data = {
        freebie_id: <?php echo $selected_freebie_id; ?>,
        category_name: document.getElementById('categoryName').value,
        vimeo_url: document.getElementById('vimeoUrl').value,
        description: document.getElementById('description').value,
        category_icon: document.getElementById('categoryIcon').value,
        category_color: document.getElementById('categoryColor').value,
        sort_order: parseInt(document.getElementById('sortOrder').value)
    };
    
    if (videoId) {
        data.id = parseInt(videoId);
    }
    
    try {
        const response = await fetch('/api/video-tutorials.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('Fehler beim Speichern: ' + error.message);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeVideoModal();
    }
});
</script>
</body>
</html>
