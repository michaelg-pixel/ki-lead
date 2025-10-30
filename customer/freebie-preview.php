<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Wenn es nur eine Vorschau für Modal ist
$preview_only = isset($_GET['preview_only']) && $_GET['preview_only'] == 1;

if (!$preview_only && !isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

$conn = getDBConnection();

// Vorschau für Kurs (aus courses.php Modal)
if (isset($_GET['course_id']) && $preview_only) {
    $course_id = (int)$_GET['course_id'];
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT title FROM modules WHERE course_id = c.id ORDER BY sort_order LIMIT 1) as first_module
        FROM courses c
        WHERE c.id = ?
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo "<div class='text-center text-red-600 py-8'>Kurs nicht gefunden</div>";
        exit;
    }
    
    // Module & Lektionen laden
    $stmt = $conn->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order");
    $stmt->execute([$course_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $lessons_by_module = [];
    foreach ($modules as $module) {
        $stmt = $conn->prepare("SELECT * FROM lessons WHERE module_id = ? ORDER BY sort_order");
        $stmt->execute([$module['id']]);
        $lessons_by_module[$module['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>
    
    <div class="space-y-6">
        <!-- Course Info -->
        <div class="mb-6">
            <?php if ($course['thumbnail']): ?>
                <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                     alt="<?= htmlspecialchars($course['title']) ?>" 
                     class="w-full h-64 object-cover rounded-lg mb-4">
            <?php endif; ?>
            <h3 class="text-2xl font-bold mb-2"><?= htmlspecialchars($course['title']) ?></h3>
            <p class="text-gray-600"><?= htmlspecialchars($course['description']) ?></p>
        </div>
        
        <!-- Module & Lektionen -->
        <?php foreach ($modules as $module): ?>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-bold text-lg mb-3">
                    <i class="fas fa-folder mr-2 text-purple-600"></i>
                    <?= htmlspecialchars($module['title']) ?>
                </h4>
                <?php if (!empty($lessons_by_module[$module['id']])): ?>
                    <div class="space-y-2 ml-6">
                        <?php foreach ($lessons_by_module[$module['id']] as $lesson): ?>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-play-circle text-purple-600"></i>
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars($lesson['title']) ?></div>
                                    <?php if ($lesson['description']): ?>
                                        <div class="text-sm text-gray-600"><?= htmlspecialchars($lesson['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="text-center mt-6">
            <a href="freebie-editor.php?course_id=<?= $course['id'] ?>" 
               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold inline-block">
                <i class="fas fa-magic mr-2"></i> Diesen Kurs im Freebie verwenden
            </a>
        </div>
    </div>
    
    <?php
    exit;
}

// Normale Freebie-Vorschau nach Erstellung
if (!isset($_GET['id'])) {
    header('Location: courses.php');
    exit;
}

$freebie_id = (int)$_GET['id'];
$customer_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT f.*, c.title as course_title, c.thumbnail
    FROM freebies f
    JOIN courses c ON f.course_id = c.id
    WHERE f.id = ? AND f.customer_id = ?
");
$stmt->execute([$freebie_id, $customer_id]);
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freebie) {
    header('Location: courses.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie erfolgreich erstellt - KI Lead-System</title>
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
                    <a href="freebie-editor.php" class="text-purple-600 font-semibold">
                        <i class="fas fa-edit mr-2"></i> Freebie-Editor
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-6 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-500 text-white px-6 py-4 rounded-lg mb-8 flex items-center gap-4">
                <i class="fas fa-check-circle text-3xl"></i>
                <div>
                    <div class="font-bold text-lg">Freebie erfolgreich erstellt! 🎉</div>
                    <div class="text-sm">Dein Freebie ist jetzt live und bereit, Leads zu sammeln!</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Freebie-Link -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6">
                <i class="fas fa-link mr-2 text-purple-600"></i> Dein Freebie-Link
            </h2>
            
            <div class="bg-purple-50 border-2 border-purple-300 rounded-lg p-6 mb-6">
                <label class="block font-semibold mb-2 text-gray-700">Kopiere diesen Link und teile ihn:</label>
                <div class="flex gap-2">
                    <input type="text" id="freebie-link" 
                           value="<?= htmlspecialchars($freebie['freebie_url']) ?>" 
                           readonly 
                           class="flex-1 px-4 py-3 bg-white border-2 border-gray-300 rounded-lg font-mono text-sm">
                    <button onclick="copyLink()" 
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold whitespace-nowrap">
                        <i class="fas fa-copy mr-2"></i> Kopieren
                    </button>
                </div>
                <div id="copy-success" class="hidden mt-2 text-green-600 font-semibold">
                    <i class="fas fa-check mr-2"></i> Link kopiert!
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <a href="<?= htmlspecialchars($freebie['freebie_url']) ?>" target="_blank" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-center font-semibold">
                    <i class="fas fa-external-link-alt mr-2"></i> Freebie öffnen
                </a>
                <a href="courses.php" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg text-center font-semibold">
                    <i class="fas fa-plus mr-2"></i> Weiteres Freebie erstellen
                </a>
            </div>
        </div>

        <!-- Freebie-Details -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6">
                <i class="fas fa-info-circle mr-2 text-purple-600"></i> Freebie-Details
            </h2>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Kurs</div>
                    <div class="font-semibold"><?= htmlspecialchars($freebie['course_title']) ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">Layout</div>
                    <div class="font-semibold">
                        <?php
                        $layouts = [
                            'layout1' => 'Modern',
                            'layout2' => 'Klassisch',
                            'layout3' => 'Minimalistisch'
                        ];
                        echo $layouts[$freebie['layout']] ?? 'Unbekannt';
                        ?>
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">Hauptfarbe</div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded" style="background-color: <?= htmlspecialchars($freebie['primary_color']) ?>"></div>
                        <span class="font-mono text-sm"><?= htmlspecialchars($freebie['primary_color']) ?></span>
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">Erstellt am</div>
                    <div class="font-semibold"><?= date('d.m.Y H:i', strtotime($freebie['created_at'])) ?> Uhr</div>
                </div>
            </div>
            
            <?php if ($freebie['thumbnail']): ?>
                <div class="mt-6">
                    <div class="text-sm text-gray-600 mb-2">Mockup-Bild</div>
                    <img src="../uploads/thumbnails/<?= htmlspecialchars($freebie['thumbnail']) ?>" 
                         alt="Mockup" class="w-64 h-40 object-cover rounded-lg border-2 border-gray-200">
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById('freebie-link');
            input.select();
            document.execCommand('copy');
            
            const successMsg = document.getElementById('copy-success');
            successMsg.classList.remove('hidden');
            
            setTimeout(() => {
                successMsg.classList.add('hidden');
            }, 3000);
        }
    </script>

</body>
</html>
