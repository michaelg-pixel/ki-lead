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
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Kurs laden
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Freebie speichern
if (isset($_POST['save_freebie'])) {
    $layout = $_POST['layout'];
    $headline = $_POST['headline'];
    $subheadline = $_POST['subheadline'];
    $bullet_points = $_POST['bullet_points'] ?? [];
    $cta_text = $_POST['cta_text'];
    $primary_color = $_POST['primary_color'];
    $secondary_color = $_POST['secondary_color'];
    $raw_code = $_POST['raw_code'];
    
    // Unique ID für Freebie-Seite generieren
    $unique_id = bin2hex(random_bytes(16));
    
    // In Datenbank speichern
    $stmt = $conn->prepare("
        INSERT INTO freebies (
            customer_id, course_id, layout, headline, subheadline, 
            bullet_points, cta_text, primary_color, secondary_color, 
            raw_code, unique_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $bullet_points_json = json_encode($bullet_points);
    $stmt->execute([
        $customer_id, $course_id, $layout, $headline, $subheadline,
        $bullet_points_json, $cta_text, $primary_color, $secondary_color,
        $raw_code, $unique_id
    ]);
    
    $freebie_id = $conn->lastInsertId();
    
    // Freebie-Seite erstellen
    $freebie_link = BASE_URL . '/freebie/' . $unique_id . '.php';
    
    // Browser-Link in Datenbank speichern
    $stmt = $conn->prepare("UPDATE freebies SET freebie_url = ? WHERE id = ?");
    $stmt->execute([$freebie_link, $freebie_id]);
    
    // Redirect zur Vorschau mit Success
    header('Location: freebie-preview.php?id=' . $freebie_id . '&success=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie-Editor - KI Lead-System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
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

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="courses.php" class="text-purple-600 hover:text-purple-700 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i> Zurück zu Kursen
            </a>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Freebie-Editor</h1>
            <p class="text-gray-600">Erstelle deine Freebie-Seite für: <strong><?= htmlspecialchars($course['title']) ?></strong></p>
        </div>

        <form method="POST" id="freebie-form">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- LINKE SEITE: EINGABEFELDER -->
                <div class="space-y-6">
                    
                    <!-- Layout wählen -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-palette mr-2 text-purple-600"></i> Layout wählen
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="layout" value="layout1" checked class="hidden layout-radio">
                                <div class="layout-option border-2 border-gray-200 rounded-lg p-4 hover:border-purple-600 transition">
                                    <div class="aspect-video bg-gradient-to-br from-blue-400 to-blue-600 rounded mb-2"></div>
                                    <div class="text-center font-semibold">Modern</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="layout" value="layout2" class="hidden layout-radio">
                                <div class="layout-option border-2 border-gray-200 rounded-lg p-4 hover:border-purple-600 transition">
                                    <div class="aspect-video bg-gradient-to-br from-purple-400 to-purple-600 rounded mb-2"></div>
                                    <div class="text-center font-semibold">Klassisch</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="layout" value="layout3" class="hidden layout-radio">
                                <div class="layout-option border-2 border-gray-200 rounded-lg p-4 hover:border-purple-600 transition">
                                    <div class="aspect-video bg-gradient-to-br from-green-400 to-green-600 rounded mb-2"></div>
                                    <div class="text-center font-semibold">Minimalistisch</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Texte -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-heading mr-2 text-purple-600"></i> Texte
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block font-semibold mb-2">Hauptüberschrift *</label>
                                <input type="text" name="headline" 
                                       value="Sichere dir jetzt deinen kostenlosen Videokurs!" 
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-600" 
                                       required>
                            </div>
                            
                            <div>
                                <label class="block font-semibold mb-2">Subheadline</label>
                                <input type="text" name="subheadline" 
                                       value="Starte noch heute und lerne die besten Strategien" 
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-600">
                            </div>
                        </div>
                    </div>

                    <!-- Bullet Points -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-list-ul mr-2 text-purple-600"></i> Was bekommst du?
                        </h3>
                        <div id="bullet-points-container" class="space-y-3">
                            <input type="text" name="bullet_points[]" 
                                   value="✓ Sofortiger Zugang zum Videokurs" 
                                   class="w-full px-4 py-3 border rounded-lg">
                            <input type="text" name="bullet_points[]" 
                                   value="✓ Praxiserprobte Strategien" 
                                   class="w-full px-4 py-3 border rounded-lg">
                            <input type="text" name="bullet_points[]" 
                                   value="✓ Schritt-für-Schritt Anleitungen" 
                                   class="w-full px-4 py-3 border rounded-lg">
                        </div>
                        <button type="button" onclick="addBulletPoint()" 
                                class="mt-3 text-purple-600 hover:text-purple-700 font-semibold">
                            <i class="fas fa-plus mr-2"></i> Weiteren Punkt hinzufügen
                        </button>
                    </div>

                    <!-- CTA Button -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-mouse-pointer mr-2 text-purple-600"></i> Call-to-Action
                        </h3>
                        <label class="block font-semibold mb-2">Button-Text</label>
                        <input type="text" name="cta_text" 
                               value="JETZT KOSTENLOS SICHERN!" 
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-600" 
                               required>
                    </div>

                    <!-- Farben -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-fill-drip mr-2 text-purple-600"></i> Farben anpassen
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold mb-2">Hauptfarbe</label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="primary_color" value="#7C3AED" 
                                           class="color-preview cursor-pointer">
                                    <input type="text" name="primary_color_text" value="#7C3AED" 
                                           class="flex-1 px-4 py-2 border rounded-lg" readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold mb-2">Sekundärfarbe</label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="secondary_color" value="#EC4899" 
                                           class="color-preview cursor-pointer">
                                    <input type="text" name="secondary_color_text" value="#EC4899" 
                                           class="flex-1 px-4 py-2 border rounded-lg" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RAW Code (Autoresponder) -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-code mr-2 text-purple-600"></i> Autoresponder-Code einfügen
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Füge hier den Einbettungs-Code (RAW Code) deines E-Mail-Anbieters ein 
                            (z.B. CleverReach, Mailchimp, ActiveCampaign)
                        </p>
                        <textarea name="raw_code" rows="8" 
                                  placeholder="<form>...</form>" 
                                  class="w-full px-4 py-3 border rounded-lg font-mono text-sm"
                                  required></textarea>
                    </div>

                    <!-- Speichern Button -->
                    <div class="bg-purple-600 rounded-lg p-6 text-center">
                        <button type="submit" name="save_freebie" 
                                class="bg-white text-purple-600 hover:bg-gray-100 px-8 py-4 rounded-lg font-bold text-lg w-full">
                            <i class="fas fa-magic mr-2"></i> Freebie erstellen & Link generieren
                        </button>
                    </div>
                </div>

                <!-- RECHTE SEITE: LIVE-VORSCHAU -->
                <div class="sticky top-8">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold mb-4">
                            <i class="fas fa-eye mr-2 text-purple-600"></i> Live-Vorschau
                        </h3>
                        <div id="live-preview" class="border-2 border-gray-200 rounded-lg overflow-hidden">
                            <div class="aspect-video bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                                <!-- Mockup-Bild wird hier angezeigt -->
                                <?php if ($course['thumbnail']): ?>
                                    <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                                         alt="Course Mockup" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-image text-6xl text-white opacity-50"></i>
                                <?php endif; ?>
                            </div>
                            <div class="p-6 text-center">
                                <h2 id="preview-headline" class="text-3xl font-bold mb-3">
                                    Sichere dir jetzt deinen kostenlosen Videokurs!
                                </h2>
                                <p id="preview-subheadline" class="text-lg text-gray-600 mb-6">
                                    Starte noch heute und lerne die besten Strategien
                                </p>
                                <ul id="preview-bullets" class="space-y-2 mb-6 text-left max-w-md mx-auto">
                                    <li>✓ Sofortiger Zugang zum Videokurs</li>
                                    <li>✓ Praxiserprobte Strategien</li>
                                    <li>✓ Schritt-für-Schritt Anleitungen</li>
                                </ul>
                                <button id="preview-cta" class="w-full bg-purple-600 text-white py-4 rounded-lg font-bold text-lg">
                                    JETZT KOSTENLOS SICHERN!
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4 text-center">
                            Die Vorschau aktualisiert sich automatisch bei Änderungen
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Live-Vorschau Updates
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('input', updatePreview);
        });
        
        document.querySelectorAll('.layout-radio').forEach(radio => {
            radio.addEventListener('change', updatePreview);
        });
        
        function updatePreview() {
            // Headline
            const headline = document.querySelector('input[name="headline"]').value;
            document.getElementById('preview-headline').textContent = headline;
            
            // Subheadline
            const subheadline = document.querySelector('input[name="subheadline"]').value;
            document.getElementById('preview-subheadline').textContent = subheadline;
            
            // Bullet Points
            const bullets = Array.from(document.querySelectorAll('input[name="bullet_points[]"]'))
                .map(input => input.value)
                .filter(val => val.trim() !== '');
            
            const bulletsList = document.getElementById('preview-bullets');
            bulletsList.innerHTML = bullets.map(bullet => `<li>${bullet}</li>`).join('');
            
            // CTA Button
            const ctaText = document.querySelector('input[name="cta_text"]').value;
            document.getElementById('preview-cta').textContent = ctaText;
            
            // Farben
            const primaryColor = document.querySelector('input[name="primary_color"]').value;
            const secondaryColor = document.querySelector('input[name="secondary_color"]').value;
            
            document.getElementById('preview-cta').style.backgroundColor = primaryColor;
            document.getElementById('preview-headline').style.color = primaryColor;
            
            // Text-Inputs für Farben aktualisieren
            document.querySelector('input[name="primary_color_text"]').value = primaryColor;
            document.querySelector('input[name="secondary_color_text"]').value = secondaryColor;
        }
        
        // Layout-Auswahl visuell hervorheben
        document.querySelectorAll('.layout-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.layout-option').forEach(opt => {
                    opt.classList.remove('border-purple-600', 'bg-purple-50');
                });
                this.parentElement.querySelector('.layout-option').classList.add('border-purple-600', 'bg-purple-50');
            });
        });
        
        // Erster Radio-Button aktivieren
        document.querySelector('.layout-radio').dispatchEvent(new Event('change'));
        
        // Bullet Point hinzufügen
        function addBulletPoint() {
            const container = document.getElementById('bullet-points-container');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'bullet_points[]';
            input.className = 'w-full px-4 py-3 border rounded-lg';
            input.addEventListener('input', updatePreview);
            container.appendChild(input);
        }
        
        // Farb-Synchronisation
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            colorInput.addEventListener('input', function() {
                const textInput = this.nextElementSibling;
                textInput.value = this.value;
            });
        });
    </script>

</body>
</html>
