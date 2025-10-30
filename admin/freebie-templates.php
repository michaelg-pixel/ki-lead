<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Fehler: Nicht eingeloggt oder keine Admin-Rechte. <a href='/login.php'>Zum Login</a>");
}

// Database
$db_path = __DIR__ . '/../config/database.php';
if (!file_exists($db_path)) {
    die("Fehler: config/database.php nicht gefunden! Pfad: $db_path<br>Aktuelles Verzeichnis: " . __DIR__);
}

require_once $db_path;

if (!isset($pdo)) {
    die("Fehler: Datenbankverbindung konnte nicht hergestellt werden!");
}

// Templates abrufen - WICHTIG: Nutze die richtigen Spaltennamen!
try {
    $stmt = $pdo->query("SELECT * FROM freebies ORDER BY created_at DESC");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Datenbankfehler: " . $e->getMessage() . "<br><br>
         <strong>Hinweis:</strong> Stelle sicher, dass du die neue Tabellenstruktur importiert hast!<br>
         <a href='fix-freebies-table.sql'>SQL-Datei hier</a>");
}

$current_datetime = date('d.m.Y H:i');
$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie-Templates - KI Leadsystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        }
        .template-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(139, 92, 246, 0.3);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <aside class="sidebar w-64 text-white flex flex-col shadow-2xl">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                KI Leadsystem
            </h1>
            <p class="text-sm text-gray-400 mt-1">Admin Panel</p>
        </div>
        
        <nav class="flex-1 p-4 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg mb-2 hover:bg-purple-900/30">
                <i class="fas fa-home mr-3"></i>Dashboard
            </a>
            <a href="users.php" class="flex items-center px-4 py-3 rounded-lg mb-2 hover:bg-purple-900/30">
                <i class="fas fa-users mr-3"></i>Benutzer
            </a>
            <a href="courses.php" class="flex items-center px-4 py-3 rounded-lg mb-2 hover:bg-purple-900/30">
                <i class="fas fa-graduation-cap mr-3"></i>Kurse
            </a>
            <a href="freebie-templates.php" class="flex items-center px-4 py-3 rounded-lg mb-2 bg-purple-900/50">
                <i class="fas fa-gift mr-3"></i>Freebie-Templates
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $username; ?></p>
                    <p class="text-xs text-gray-400">Administrator</p>
                </div>
            </div>
            <a href="/logout.php" class="flex items-center justify-center w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
        <div class="p-8">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">
                        <i class="fas fa-gift mr-2"></i>Freebie-Templates
                    </h2>
                    <p class="text-purple-200">Verwalte deine wiederverwendbaren Lead-Magnet Templates</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right text-white">
                        <p class="text-sm opacity-75">
                            <i class="far fa-calendar mr-2"></i><?php echo $current_datetime; ?>
                        </p>
                    </div>
                    <a href="freebie-create.php" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-medium hover:from-purple-700 hover:to-pink-700 transition flex items-center">
                        <i class="fas fa-plus mr-2"></i>Neues Template
                    </a>
                </div>
            </div>

            <!-- Success Messages -->
            <?php if (isset($_GET['created'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <p class="text-green-800"><i class="fas fa-check-circle mr-2"></i>Template erfolgreich erstellt!</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                    <p class="text-blue-800"><i class="fas fa-check-circle mr-2"></i>Template erfolgreich aktualisiert!</p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <p class="text-red-800"><i class="fas fa-check-circle mr-2"></i>Template erfolgreich gelöscht!</p>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Gesamt Templates</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($templates); ?></p>
                        </div>
                        <div class="bg-purple-100 p-4 rounded-full">
                            <i class="fas fa-gift text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Aktive Templates</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">
                                <?php echo count(array_filter($templates, function($t) { return $t['is_active']; })); ?>
                            </p>
                        </div>
                        <div class="bg-green-100 p-4 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Mit Kurs-Verknüpfung</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2">
                                <?php echo count(array_filter($templates, function($t) { return !empty($t['linked_course_id']); })); ?>
                            </p>
                        </div>
                        <div class="bg-blue-100 p-4 rounded-full">
                            <i class="fas fa-graduation-cap text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Template-Typen</p>
                            <p class="text-3xl font-bold text-orange-600 mt-2">
                                <?php echo count(array_unique(array_column($templates, 'template_type'))); ?>
                            </p>
                        </div>
                        <div class="bg-orange-100 p-4 rounded-full">
                            <i class="fas fa-layer-group text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                <?php if (empty($templates)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">Noch keine Templates vorhanden</h3>
                        <p class="text-gray-500 mb-6">Erstelle dein erstes Freebie-Template!</p>
                        <a href="freebie-create.php" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-medium hover:from-purple-700 hover:to-pink-700 transition">
                            <i class="fas fa-plus mr-2"></i>Erstes Template Erstellen
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($templates as $template): 
                            // Template type icons
                            $typeIcons = [
                                'checklist' => 'fa-check-square',
                                'ebook' => 'fa-book',
                                'worksheet' => 'fa-file-alt',
                                'infographic' => 'fa-chart-bar',
                                'social' => 'fa-share-alt',
                                'guide' => 'fa-map'
                            ];
                            $icon = $typeIcons[$template['template_type']] ?? 'fa-file';
                            $primaryColor = $template['primary_color'] ?? '#8B5CF6';
                        ?>
                            <div class="template-card bg-white rounded-lg overflow-hidden border-2 border-gray-200" 
                                 style="border-top: 4px solid <?= $primaryColor ?>">
                                
                                <!-- Preview Bild wenn vorhanden -->
                                <?php if (!empty($template['mockup_image_url'])): ?>
                                    <div class="h-40 bg-gray-100 flex items-center justify-center p-4">
                                        <img src="<?= htmlspecialchars($template['mockup_image_url']) ?>" 
                                             alt="Preview" 
                                             class="max-h-full object-contain"
                                             onerror="this.style.display='none'">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <i class="fas <?= $icon ?> text-2xl mr-3" style="color: <?= $primaryColor ?>"></i>
                                                <h3 class="text-lg font-bold text-gray-800">
                                                    <?= htmlspecialchars($template['name']) ?>
                                                </h3>
                                            </div>
                                            
                                            <?php if (!empty($template['description'])): ?>
                                                <p class="text-sm text-gray-600 mb-3">
                                                    <?= htmlspecialchars(substr($template['description'], 0, 100)) ?>
                                                    <?= strlen($template['description']) > 100 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <!-- Headline Preview -->
                                            <?php if (!empty($template['headline'])): ?>
                                                <p class="text-xs text-gray-500 italic mb-2">
                                                    "<?= htmlspecialchars(substr($template['headline'], 0, 60)) ?>..."
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Badges -->
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $template['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $template['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                                        </span>
                                        
                                        <?php if (!empty($template['linked_course_id'])): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-graduation-cap mr-1"></i>Kurs
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <?= ucfirst($template['template_type']) ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                                        <span>
                                            <i class="far fa-calendar mr-1"></i>
                                            <?= date('d.m.Y', strtotime($template['created_at'])) ?>
                                        </span>
                                        <?php if ($template['usage_count'] > 0): ?>
                                            <span>
                                                <i class="fas fa-users mr-1"></i>
                                                <?= $template['usage_count'] ?>× verwendet
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex space-x-2">
                                        <a href="freebie-edit.php?id=<?= $template['id'] ?>" 
                                           class="flex-1 bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                                            <i class="fas fa-edit mr-1"></i>Bearbeiten
                                        </a>
                                        <a href="freebie-preview.php?id=<?= $template['id'] ?>" 
                                           class="flex-1 bg-gray-200 text-gray-700 text-center py-2 px-4 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                                            <i class="fas fa-eye mr-1"></i>Vorschau
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>