<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

// Handle file upload for mockup
$mockup_url = '';
if (isset($_FILES['mockup_image']) && $_FILES['mockup_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../uploads/freebies/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['mockup_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($file_extension, $allowed_extensions)) {
        $new_filename = 'mockup_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['mockup_image']['tmp_name'], $target_path)) {
            $mockup_url = '/uploads/freebies/' . $new_filename;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_template'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $template_type = $_POST['template_type'] ?? 'checklist';
    $headline = trim($_POST['headline'] ?? '');
    $subheadline = trim($_POST['subheadline'] ?? '');
    $preheadline = trim($_POST['preheadline'] ?? '');
    
    $design_config = json_encode([
        'colors' => [
            'primary' => $_POST['color_primary'] ?? '#8B5CF6',
            'secondary' => $_POST['color_secondary'] ?? '#6D28D9',
            'text' => $_POST['color_text'] ?? '#1F2937',
            'background' => $_POST['color_background'] ?? '#FFFFFF'
        ],
        'fonts' => [
            'heading' => $_POST['font_heading'] ?? 'Inter',
            'body' => $_POST['font_body'] ?? 'Inter'
        ],
        'layout' => $_POST['layout'] ?? 'modern'
    ]);
    
    $customizable_fields = json_encode([
        'title' => ['type' => 'text', 'label' => 'Titel', 'required' => true],
        'subtitle' => ['type' => 'text', 'label' => 'Untertitel', 'required' => false],
        'content' => ['type' => 'textarea', 'label' => 'Inhalt', 'required' => true]
    ]);
    
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate that at least name is provided
    if (empty($name)) {
        $error_message = "Bitte gib einen Template-Namen ein.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO freebies 
                (name, description, template_type, design_config, customizable_fields, is_active, 
                 headline, subheadline, preheadline, mockup_image_url, primary_color, secondary_color, 
                 created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $primary_color = $_POST['color_primary'] ?? '#8B5CF6';
            $secondary_color = $_POST['color_secondary'] ?? '#6D28D9';
            
            if ($stmt->execute([
                $name, 
                $description, 
                $template_type, 
                $design_config, 
                $customizable_fields, 
                $is_active,
                $headline,
                $subheadline,
                $preheadline,
                $mockup_url,
                $primary_color,
                $secondary_color
            ])) {
                header('Location: /admin/freebie-templates.php?created=1');
                exit();
            } else {
                $error_message = "Fehler beim Erstellen des Templates.";
            }
        } catch (PDOException $e) {
            $error_message = "Datenbankfehler: " . $e->getMessage();
        }
    }
}

$current_datetime = date('d.m.Y H:i');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neues Template - KI Leadsystem</title>
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
        .template-option {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .template-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }
        .template-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }
        .upload-area {
            border: 2px dashed #cbd5e0;
            background: #f9fafb;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #8B5CF6;
            background: #f3f4f6;
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
            <a href="/admin/dashboard.php" class="flex items-center px-4 py-3 rounded-lg mb-2 hover:bg-purple-900/30">
                <i class="fas fa-home mr-3"></i>Dashboard
            </a>
            <a href="/admin/freebie-templates.php" class="flex items-center px-4 py-3 rounded-lg mb-2 bg-purple-900/50">
                <i class="fas fa-gift mr-3"></i>Freebie-Templates
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-700">
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
                        <i class="fas fa-plus mr-2"></i>Neues Template
                    </h2>
                    <p class="text-purple-200">Erstelle ein neues Freebie-Template</p>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <p class="text-red-800"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Column -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Basic Info -->
                        <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">
                                <i class="fas fa-info-circle text-purple-600 mr-2"></i>
                                Grundinformationen
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Template-Name * <span class="text-red-500">Pflichtfeld</span>
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="z.B. KI-Kurs Lead Magnet">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Beschreibung
                                    </label>
                                    <textarea name="description" 
                                              rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                              placeholder="Kurze Beschreibung..."></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Hauptüberschrift (Headline)
                                    </label>
                                    <input type="text" 
                                           name="headline"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="Wie du eigene KI Kurse in nur 7 Tagen verkaufst...">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Unterüberschrift (Subheadline)
                                    </label>
                                    <input type="text" 
                                           name="subheadline"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="ohne diese selbst erstellen zu müssen">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Vorüberschrift (Preheadline)
                                    </label>
                                    <input type="text" 
                                           name="preheadline"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="NUR FÜR KURZE ZEIT">
                                </div>
                            </div>
                        </div>

                        <!-- Mockup Upload -->
                        <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">
                                <i class="fas fa-image text-purple-600 mr-2"></i>
                                Mockup-Bild
                            </h3>
                            
                            <div class="upload-area rounded-lg p-8 text-center">
                                <input type="file" 
                                       name="mockup_image" 
                                       id="mockup_image"
                                       accept="image/*"
                                       class="hidden"
                                       onchange="previewImage(this)">
                                <label for="mockup_image" class="cursor-pointer">
                                    <div id="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                                        <p class="text-gray-600 font-medium mb-1">Klicke zum Hochladen</p>
                                        <p class="text-sm text-gray-500">PNG, JPG, GIF oder WEBP (Max. 5MB)</p>
                                    </div>
                                    <img id="image-preview" class="hidden max-w-full h-auto rounded-lg mx-auto" alt="Preview">
                                </label>
                            </div>
                        </div>

                        <!-- Template Type -->
                        <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">
                                <i class="fas fa-palette text-purple-600 mr-2"></i>
                                Template-Typ
                            </h3>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="template-option selected p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'checklist')">
                                    <input type="radio" name="template_type" value="checklist" checked class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-check-square text-4xl text-purple-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Checkliste</p>
                                    </div>
                                </div>
                                
                                <div class="template-option p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'ebook')">
                                    <input type="radio" name="template_type" value="ebook" class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-book text-4xl text-blue-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">E-Book</p>
                                    </div>
                                </div>
                                
                                <div class="template-option p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'guide')">
                                    <input type="radio" name="template_type" value="guide" class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-map text-4xl text-yellow-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Guide</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Design Config -->
                        <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">
                                <i class="fas fa-paint-brush text-purple-600 mr-2"></i>
                                Design-Konfiguration
                            </h3>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Primärfarbe</label>
                                    <input type="color" name="color_primary" value="#8B5CF6" class="w-full h-10 rounded cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sekundärfarbe</label>
                                    <input type="color" name="color_secondary" value="#6D28D9" class="w-full h-10 rounded cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Textfarbe</label>
                                    <input type="color" name="color_text" value="#1F2937" class="w-full h-10 rounded cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Hintergrund</label>
                                    <input type="color" name="color_background" value="#FFFFFF" class="w-full h-10 rounded cursor-pointer">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Überschrift-Schriftart</label>
                                    <select name="font_heading" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="Inter" selected>Inter</option>
                                        <option value="Poppins">Poppins</option>
                                        <option value="Montserrat">Montserrat</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Text-Schriftart</label>
                                    <select name="font_body" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="Inter" selected>Inter</option>
                                        <option value="Poppins">Poppins</option>
                                        <option value="Open Sans">Open Sans</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column -->
                    <div class="lg:col-span-1 space-y-6">
                        
                        <!-- Status -->
                        <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">
                                <i class="fas fa-toggle-on text-purple-600 mr-2"></i>
                                Status
                            </h3>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" checked class="mr-3 w-5 h-5 text-purple-600 rounded">
                                <span class="text-sm font-medium text-gray-700">Template aktivieren</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white/95 backdrop-blur rounded-lg shadow p-6">
                            <button type="submit" name="create_template" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-medium hover:from-purple-700 hover:to-pink-700 transition mb-3">
                                <i class="fas fa-save mr-2"></i>Template Erstellen
                            </button>
                            <a href="/admin/freebie-templates.php" class="block w-full text-center bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium hover:bg-gray-300 transition">
                                <i class="fas fa-arrow-left mr-2"></i>Zurück
                            </a>
                        </div>

                    </div>

                </div>
            </form>

        </div>
    </main>

    <script>
        function selectTemplate(element, type) {
            document.querySelectorAll('.template-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('upload-placeholder').classList.add('hidden');
                    const preview = document.getElementById('image-preview');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html>