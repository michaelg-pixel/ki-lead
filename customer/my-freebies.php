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

// Löschen-Funktion
if (isset($_POST['delete_freebie']) && isset($_POST['freebie_id'])) {
    $freebie_id = (int)$_POST['freebie_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM customer_freebies WHERE id = ? AND customer_id = ?");
        $stmt->execute([$freebie_id, $customer_id]);
        $success_message = "Freebie erfolgreich gelöscht!";
    } catch (PDOException $e) {
        $error_message = "Fehler beim Löschen: " . $e->getMessage();
    }
}

// Eigene Freebies des Kunden laden
try {
    $stmt = $conn->prepare("
        SELECT 
            cf.*,
            c.title as course_title,
            c.thumbnail as course_thumbnail
        FROM customer_freebies cf
        LEFT JOIN courses c ON cf.course_id = c.id
        WHERE cf.customer_id = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $my_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_freebies = [];
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meine Freebies - KI Lead-System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .freebie-card {
            transition: all 0.3s ease;
        }
        .freebie-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f3f4f6;
            border-radius: 20px;
            font-size: 13px;
        }
    </style>
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
                    <a href="dashboard.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-graduation-cap mr-2"></i> Kurse
                    </a>
                    <a href="dashboard.php?page=freebies" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-gift mr-2"></i> Templates
                    </a>
                    <a href="my-freebies.php" class="text-purple-600 font-semibold">
                        <i class="fas fa-folder mr-2"></i> Meine Freebies
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
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">
                    📁 Meine Freebies
                </h1>
                <p class="text-gray-600">
                    Verwalte deine personalisierten Lead-Magneten
                </p>
            </div>
            <a href="freebie-editor.php" 
               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2 transition">
                <i class="fas fa-plus"></i> Neues Freebie erstellen
            </a>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <p class="font-semibold"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                    <p class="font-semibold"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($my_freebies)): ?>
            <!-- Leerer Zustand -->
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="max-w-md mx-auto">
                    <div class="text-6xl mb-6">🎨</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">
                        Noch keine Freebies erstellt
                    </h3>
                    <p class="text-gray-600 mb-6">
                        Starte jetzt und erstelle dein erstes Freebie! Wähle ein Template aus oder erstelle ein komplett neues.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <a href="dashboard.php?page=freebies" 
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2">
                            <i class="fas fa-gift"></i> Templates durchsuchen
                        </a>
                        <a href="freebie-editor.php" 
                           class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2">
                            <i class="fas fa-plus"></i> Neu erstellen
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Statistik-Übersicht -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Gesamt Freebies</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo count($my_freebies); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-folder text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Gesamt Aufrufe</p>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php 
                                $total_views = array_sum(array_column($my_freebies, 'usage_count'));
                                echo number_format($total_views ?? 0);
                                ?>
                            </p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-eye text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Aktive Freebies</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo count($my_freebies); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Zuletzt erstellt</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php 
                                $latest = new DateTime($my_freebies[0]['created_at']);
                                echo $latest->format('d.m.Y');
                                ?>
                            </p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <i class="fas fa-calendar text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Freebies Liste -->
            <div class="space-y-4">
                <?php foreach ($my_freebies as $freebie): 
                    $freebie_url = $freebie['freebie_url'] ?: ('/freebie/' . $freebie['unique_id']);
                    $primary_color = $freebie['primary_color'] ?: '#7C3AED';
                    $bg_color = $freebie['background_color'] ?: '#FFFFFF';
                    
                    $date = new DateTime($freebie['created_at']);
                    $formatted_date = $date->format('d.m.Y H:i');
                    
                    $layout_names = [
                        'layout1' => 'Modern',
                        'layout2' => 'Klassisch',
                        'layout3' => 'Minimal'
                    ];
                    $layout_name = $layout_names[$freebie['layout']] ?? 'Standard';
                ?>
                    <div class="freebie-card bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="flex flex-col md:flex-row">
                            
                            <!-- Preview Thumbnail -->
                            <div class="w-full md:w-64 h-48 relative" style="background: <?php echo htmlspecialchars($bg_color); ?>;">
                                <?php if (!empty($freebie['mockup_image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($freebie['name']); ?>"
                                         class="w-full h-full object-cover">
                                <?php elseif (!empty($freebie['course_thumbnail'])): ?>
                                    <img src="../uploads/thumbnails/<?php echo htmlspecialchars($freebie['course_thumbnail']); ?>" 
                                         alt="<?php echo htmlspecialchars($freebie['name']); ?>"
                                         class="w-full h-full object-cover opacity-60">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span style="color: <?php echo htmlspecialchars($primary_color); ?>; font-size: 48px;">🎁</span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Layout Badge -->
                                <div class="absolute top-3 left-3 bg-white bg-opacity-90 px-2 py-1 rounded text-xs font-semibold">
                                    <?php echo $layout_name; ?>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-1">
                                            <?php echo htmlspecialchars($freebie['name'] ?: $freebie['headline']); ?>
                                        </h3>
                                        <p class="text-gray-600 text-sm">
                                            <?php echo htmlspecialchars($freebie['headline']); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        <div class="w-6 h-6 rounded-full" 
                                             style="background-color: <?php echo htmlspecialchars($primary_color); ?>;"
                                             title="Primärfarbe"></div>
                                    </div>
                                </div>
                                
                                <!-- Meta Infos -->
                                <div class="flex flex-wrap gap-3 mb-4">
                                    <?php if (!empty($freebie['course_title'])): ?>
                                        <span class="stats-badge">
                                            <i class="fas fa-graduation-cap text-purple-600"></i>
                                            <?php echo htmlspecialchars($freebie['course_title']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="stats-badge">
                                        <i class="fas fa-eye text-blue-600"></i>
                                        <?php echo number_format($freebie['usage_count'] ?? 0); ?> Aufrufe
                                    </span>
                                    
                                    <span class="stats-badge">
                                        <i class="fas fa-calendar text-gray-600"></i>
                                        <?php echo $formatted_date; ?>
                                    </span>
                                </div>
                                
                                <!-- URL -->
                                <div class="bg-gray-50 rounded-lg p-3 mb-4">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-link text-gray-400"></i>
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($freebie_url); ?>" 
                                               readonly 
                                               class="flex-1 bg-transparent text-sm text-gray-700 outline-none"
                                               onclick="this.select()">
                                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($freebie_url); ?>', this)" 
                                                class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                                            <i class="fas fa-copy"></i> Kopieren
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <a href="<?php echo htmlspecialchars($freebie_url); ?>" 
                                       target="_blank"
                                       class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold text-center text-sm transition">
                                        <i class="fas fa-eye mr-2"></i> Vorschau
                                    </a>
                                    
                                    <a href="freebie-editor.php?id=<?php echo $freebie['id']; ?>" 
                                       class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold text-center text-sm transition">
                                        <i class="fas fa-edit mr-2"></i> Bearbeiten
                                    </a>
                                    
                                    <button onclick="confirmDelete(<?php echo $freebie['id']; ?>, '<?php echo htmlspecialchars(addslashes($freebie['name'] ?: $freebie['headline'])); ?>')" 
                                            class="bg-red-100 hover:bg-red-200 text-red-600 px-4 py-2 rounded-lg font-semibold text-sm transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="text-center mb-6">
                <div class="text-5xl mb-4">🗑️</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Freebie löschen?</h3>
                <p class="text-gray-600" id="deleteMessage">
                    Möchtest du dieses Freebie wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.
                </p>
            </div>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="freebie_id" id="deleteFreebieId">
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeDeleteModal()" 
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold">
                        Abbrechen
                    </button>
                    <button type="submit" 
                            name="delete_freebie"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold">
                        Ja, löschen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Copy to Clipboard
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            button.classList.add('text-green-600');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('text-green-600');
            }, 2000);
        }).catch(err => {
            console.error('Fehler beim Kopieren:', err);
        });
    }
    
    // Delete Confirmation
    function confirmDelete(freebieId, freebieName) {
        document.getElementById('deleteFreebieId').value = freebieId;
        document.getElementById('deleteMessage').innerHTML = 
            `Möchtest du "<strong>${freebieName}</strong>" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // ESC key closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
    
    // Click outside closes modal
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    </script>

</body>
</html>