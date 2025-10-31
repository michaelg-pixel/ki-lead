<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

$template_id = $_GET['id'] ?? null;

if (!$template_id) {
    header('Location: /admin/freebie-templates.php');
    exit();
}

// Get template data
try {
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        header('Location: /admin/freebie-templates.php');
        exit();
    }
    
    $design_config = json_decode($template['design_config'], true) ?? [];
    $customizable_fields = json_decode($template['customizable_fields'], true) ?? [];
    
} catch (PDOException $e) {
    die("Fehler beim Laden des Templates: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    
    // Handle file upload for mockup
    $mockup_url = $template['mockup_image_url']; // Keep existing URL
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
                // Delete old mockup if exists
                if (!empty($template['mockup_image_url']) && file_exists(__DIR__ . '/..' . $template['mockup_image_url'])) {
                    @unlink(__DIR__ . '/..' . $template['mockup_image_url']);
                }
                $mockup_url = '/uploads/freebies/' . $new_filename;
            }
        }
    }
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $template_type = $_POST['template_type'] ?? 'checklist';
    $headline = trim($_POST['headline'] ?? '');
    $subheadline = trim($_POST['subheadline'] ?? '');
    $preheadline = trim($_POST['preheadline'] ?? '');
    
    $design_config_new = json_encode([
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
        'layout' => $_POST['layout'] ?? 'modern',
        'template_type' => $template_type
    ]);
    
    $customizable_fields_new = json_encode([
        'title' => ['type' => 'text', 'label' => 'Titel', 'required' => true],
        'subtitle' => ['type' => 'text', 'label' => 'Untertitel', 'required' => false],
        'content' => ['type' => 'textarea', 'label' => 'Inhalt', 'required' => true],
        'logo' => ['type' => 'image', 'label' => 'Logo', 'required' => false],
        'cta_text' => ['type' => 'text', 'label' => 'Call-to-Action Text', 'required' => false],
        'cta_url' => ['type' => 'url', 'label' => 'Call-to-Action URL', 'required' => false]
    ]);
    
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $primary_color = $_POST['color_primary'] ?? '#8B5CF6';
    $secondary_color = $_POST['color_secondary'] ?? '#6D28D9';
    
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE freebies 
                SET name = ?, description = ?, template_type = ?, design_config = ?, customizable_fields = ?, 
                    is_active = ?, headline = ?, subheadline = ?, preheadline = ?, mockup_image_url = ?,
                    primary_color = ?, secondary_color = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $name, 
                $description, 
                $template_type, 
                $design_config_new, 
                $customizable_fields_new, 
                $is_active,
                $headline,
                $subheadline,
                $preheadline,
                $mockup_url,
                $primary_color,
                $secondary_color,
                $template_id
            ])) {
                $success_message = "Template erfolgreich aktualisiert!";
                
                // Reload template data
                $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
                $stmt->execute([$template_id]);
                $template = $stmt->fetch(PDO::FETCH_ASSOC);
                $design_config = json_decode($template['design_config'], true) ?? [];
                $customizable_fields = json_decode($template['customizable_fields'], true) ?? [];
            } else {
                $error_message = "Fehler beim Aktualisieren des Templates.";
            }
        } catch (PDOException $e) {
            $error_message = "Datenbankfehler: " . $e->getMessage();
        }
    } else {
        $error_message = "Bitte gib einen Template-Namen ein.";
    }
}

// Handle delete request
if (isset($_POST['delete']) && $_POST['delete'] === 'confirm') {
    try {
        // Delete mockup image if exists
        if (!empty($template['mockup_image_url']) && file_exists(__DIR__ . '/..' . $template['mockup_image_url'])) {
            @unlink(__DIR__ . '/..' . $template['mockup_image_url']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM freebies WHERE id = ?");
        if ($stmt->execute([$template_id])) {
            header('Location: /admin/freebie-templates.php?deleted=1');
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Fehler beim Löschen: " . $e->getMessage();
    }
}

$current_datetime = date('d.m.Y H:i');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Bearbeiten - KI Leadsystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts für Vorschau -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700;900&family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800&family=Open+Sans:wght@300;400;500;600;700;800&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        }
        .nav-item {
            transition: all 0.3s ease;
        }
        .nav-item:hover {
            background: rgba(139, 92, 246, 0.1);
            border-left: 4px solid #8B5CF6;
        }
        .nav-item.active {
            background: rgba(139, 92, 246, 0.2);
            border-left: 4px solid #8B5CF6;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .preview-box {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            min-height: 400px;
        }
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
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
        .delete-zone {
            border: 2px dashed #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }
        .upload-area {
            border: 2px dashed #cbd5e0;
            background: #f9fafb;
            transition: all 0.3s;
            cursor: pointer;
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
            <a href="/admin/dashboard.php" class="nav-item flex items-center px-4 py-3 rounded-lg mb-2">
                <i class="fas fa-home mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="/admin/users.php" class="nav-item flex items-center px-4 py-3 rounded-lg mb-2">
                <i class="fas fa-users mr-3"></i>
                <span>Benutzer</span>
            </a>
            <a href="/admin/courses.php" class="nav-item flex items-center px-4 py-3 rounded-lg mb-2">
                <i class="fas fa-graduation-cap mr-3"></i>
                <span>Kurse</span>
            </a>
            <a href="/admin/freebie-templates.php" class="nav-item active flex items-center px-4 py-3 rounded-lg mb-2">
                <i class="fas fa-gift mr-3"></i>
                <span>Freebie-Templates</span>
            </a>
            <a href="/admin/tutorials.php" class="nav-item flex items-center px-4 py-3 rounded-lg mb-2">
                <i class="fas fa-book mr-3"></i>
                <span>Tutorials</span>
            </a>
            <a href="/admin/settings.php" class="nav-item flex items-center px-4 py-3 rounded-lg mb-2">
                <i class="fas fa-cog mr-3"></i>
                <span>Einstellungen</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-400">Administrator</p>
                </div>
            </div>
            <a href="/logout.php" class="flex items-center justify-center w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span>Logout</span>
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
                        <i class="fas fa-edit mr-2"></i>Template Bearbeiten
                    </h2>
                    <p class="text-purple-200">Template: <?php echo htmlspecialchars($template['name']); ?></p>
                </div>
                <div class="text-right text-white">
                    <p class="text-sm opacity-75">
                        <i class="far fa-calendar mr-2"></i><?php echo $current_datetime; ?>
                    </p>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="card p-4 mb-6 bg-green-50 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <p class="text-green-800 font-medium"><?php echo $success_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="card p-4 mb-6 bg-red-50 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                        <p class="text-red-800"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="templateForm" enctype="multipart/form-data">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Column - Form -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Basic Information -->
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle text-purple-600 mr-2"></i>
                                Grundinformationen
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Template-Name *
                                    </label>
                                    <input type="text" name="name" required
                                           value="<?php echo htmlspecialchars($template['name']); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                           placeholder="z.B. Lead-Magnet Checkliste">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Beschreibung
                                    </label>
                                    <textarea name="description" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                              placeholder="Kurze Beschreibung des Templates..."><?php echo htmlspecialchars($template['description']); ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Vorüberschrift (Preheadline)
                                    </label>
                                    <input type="text" 
                                           id="preheadline"
                                           name="preheadline"
                                           value="<?php echo htmlspecialchars($template['preheadline'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="NUR FÜR KURZE ZEIT"
                                           oninput="updatePreview()">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Hauptüberschrift (Headline)
                                    </label>
                                    <input type="text" 
                                           id="headline"
                                           name="headline"
                                           value="<?php echo htmlspecialchars($template['headline'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="Wie du eigene KI Kurse verkaufst..."
                                           oninput="updatePreview()">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Unterüberschrift (Subheadline)
                                    </label>
                                    <input type="text" 
                                           id="subheadline"
                                           name="subheadline"
                                           value="<?php echo htmlspecialchars($template['subheadline'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                           placeholder="ohne diese selbst erstellen zu müssen"
                                           oninput="updatePreview()">
                                </div>
                            </div>
                        </div>

                        <!-- Mockup Upload -->
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-image text-purple-600 mr-2"></i>
                                Mockup-Bild
                            </h3>
                            
                            <?php if (!empty($template['mockup_image_url'])): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600 mb-2">Aktuelles Mockup:</p>
                                    <img src="<?php echo htmlspecialchars($template['mockup_image_url']); ?>" 
                                         alt="Current Mockup" 
                                         class="max-w-sm rounded-lg shadow-md"
                                         id="current-mockup">
                                </div>
                            <?php endif; ?>
                            
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
                                        <p class="text-gray-600 font-medium mb-1">
                                            <?php echo !empty($template['mockup_image_url']) ? 'Neues Bild hochladen' : 'Klicke zum Hochladen'; ?>
                                        </p>
                                        <p class="text-sm text-gray-500">PNG, JPG, GIF oder WEBP (Max. 5MB)</p>
                                    </div>
                                    <img id="image-preview" class="hidden max-w-full h-auto rounded-lg mx-auto" alt="Preview">
                                </label>
                            </div>
                        </div>

                        <!-- Template Type Selection -->
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-palette text-purple-600 mr-2"></i>
                                Template-Typ
                            </h3>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="template-option <?php echo $template['template_type'] === 'checklist' ? 'selected' : ''; ?> p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'checklist')">
                                    <input type="radio" name="template_type" value="checklist" <?php echo $template['template_type'] === 'checklist' ? 'checked' : ''; ?> class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-check-square text-4xl text-purple-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Checkliste</p>
                                        <p class="text-xs text-gray-500 mt-1">Aufgaben-Liste</p>
                                    </div>
                                </div>
                                
                                <div class="template-option <?php echo $template['template_type'] === 'ebook' ? 'selected' : ''; ?> p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'ebook')">
                                    <input type="radio" name="template_type" value="ebook" <?php echo $template['template_type'] === 'ebook' ? 'checked' : ''; ?> class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-book text-4xl text-blue-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">E-Book Cover</p>
                                        <p class="text-xs text-gray-500 mt-1">Buch-Design</p>
                                    </div>
                                </div>
                                
                                <div class="template-option <?php echo $template['template_type'] === 'worksheet' ? 'selected' : ''; ?> p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'worksheet')">
                                    <input type="radio" name="template_type" value="worksheet" <?php echo $template['template_type'] === 'worksheet' ? 'checked' : ''; ?> class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-file-alt text-4xl text-green-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Arbeitsblatt</p>
                                        <p class="text-xs text-gray-500 mt-1">Worksheet</p>
                                    </div>
                                </div>
                                
                                <div class="template-option <?php echo $template['template_type'] === 'infographic' ? 'selected' : ''; ?> p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'infographic')">
                                    <input type="radio" name="template_type" value="infographic" <?php echo $template['template_type'] === 'infographic' ? 'checked' : ''; ?> class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-chart-bar text-4xl text-red-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Infografik</p>
                                        <p class="text-xs text-gray-500 mt-1">Daten-Grafik</p>
                                    </div>
                                </div>
                                
                                <div class="template-option <?php echo $template['template_type'] === 'social' ? 'selected' : ''; ?> p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'social')">
                                    <input type="radio" name="template_type" value="social" <?php echo $template['template_type'] === 'social' ? 'checked' : ''; ?> class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-share-alt text-4xl text-pink-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Social Media</p>
                                        <p class="text-xs text-gray-500 mt-1">Post-Design</p>
                                    </div>
                                </div>
                                
                                <div class="template-option <?php echo $template['template_type'] === 'guide' ? 'selected' : ''; ?> p-4 bg-white rounded-lg border-2" onclick="selectTemplate(this, 'guide')">
                                    <input type="radio" name="template_type" value="guide" <?php echo $template['template_type'] === 'guide' ? 'checked' : ''; ?> class="hidden">
                                    <div class="text-center">
                                        <i class="fas fa-map text-4xl text-yellow-600 mb-2"></i>
                                        <p class="font-medium text-gray-800">Guide</p>
                                        <p class="text-xs text-gray-500 mt-1">Anleitung</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Design Configuration -->
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-paint-brush text-purple-600 mr-2"></i>
                                Design-Konfiguration
                            </h3>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Primärfarbe
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="color_primary" 
                                               value="<?php echo $design_config['colors']['primary'] ?? '#8B5CF6'; ?>"
                                               class="w-full h-10 rounded cursor-pointer" 
                                               onchange="updatePreview()">
                                        <div class="color-preview" style="background-color: <?php echo $design_config['colors']['primary'] ?? '#8B5CF6'; ?>;"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Sekundärfarbe
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="color_secondary" 
                                               value="<?php echo $design_config['colors']['secondary'] ?? '#6D28D9'; ?>"
                                               class="w-full h-10 rounded cursor-pointer"
                                               onchange="updatePreview()">
                                        <div class="color-preview" style="background-color: <?php echo $design_config['colors']['secondary'] ?? '#6D28D9'; ?>;"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Textfarbe
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="color_text" 
                                               value="<?php echo $design_config['colors']['text'] ?? '#1F2937'; ?>"
                                               class="w-full h-10 rounded cursor-pointer"
                                               onchange="updatePreview()">
                                        <div class="color-preview" style="background-color: <?php echo $design_config['colors']['text'] ?? '#1F2937'; ?>;"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Hintergrund
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="color_background" 
                                               value="<?php echo $design_config['colors']['background'] ?? '#FFFFFF'; ?>"
                                               class="w-full h-10 rounded cursor-pointer"
                                               onchange="updatePreview()">
                                        <div class="color-preview" style="background-color: <?php echo $design_config['colors']['background'] ?? '#FFFFFF'; ?>;"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Überschrift-Schriftart
                                    </label>
                                    <select name="font_heading" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                            onchange="updatePreview()">
                                        <option value="Inter" <?php echo ($design_config['fonts']['heading'] ?? 'Inter') === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                        <option value="Poppins" <?php echo ($design_config['fonts']['heading'] ?? '') === 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                                        <option value="Roboto" <?php echo ($design_config['fonts']['heading'] ?? '') === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                        <option value="Montserrat" <?php echo ($design_config['fonts']['heading'] ?? '') === 'Montserrat' ? 'selected' : ''; ?>>Montserrat</option>
                                        <option value="Playfair Display" <?php echo ($design_config['fonts']['heading'] ?? '') === 'Playfair Display' ? 'selected' : ''; ?>>Playfair Display</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Text-Schriftart
                                    </label>
                                    <select name="font_body" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                            onchange="updatePreview()">
                                        <option value="Inter" <?php echo ($design_config['fonts']['body'] ?? 'Inter') === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                        <option value="Poppins" <?php echo ($design_config['fonts']['body'] ?? '') === 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                                        <option value="Roboto" <?php echo ($design_config['fonts']['body'] ?? '') === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                        <option value="Open Sans" <?php echo ($design_config['fonts']['body'] ?? '') === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                                        <option value="Lato" <?php echo ($design_config['fonts']['body'] ?? '') === 'Lato' ? 'selected' : ''; ?>>Lato</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Layout-Stil
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="flex items-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 transition">
                                        <input type="radio" name="layout" value="modern" <?php echo ($design_config['layout'] ?? 'modern') === 'modern' ? 'checked' : ''; ?> class="mr-2">
                                        <span class="text-sm font-medium">Modern</span>
                                    </label>
                                    <label class="flex items-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 transition">
                                        <input type="radio" name="layout" value="minimal" <?php echo ($design_config['layout'] ?? '') === 'minimal' ? 'checked' : ''; ?> class="mr-2">
                                        <span class="text-sm font-medium">Minimal</span>
                                    </label>
                                    <label class="flex items-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 transition">
                                        <input type="radio" name="layout" value="bold" <?php echo ($design_config['layout'] ?? '') === 'bold' ? 'checked' : ''; ?> class="mr-2">
                                        <span class="text-sm font-medium">Bold</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column - Preview & Actions -->
                    <div class="lg:col-span-1 space-y-6">
                        
                        <!-- Preview -->
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-eye text-purple-600 mr-2"></i>
                                Live-Vorschau
                            </h3>
                            <div id="previewBox" class="preview-box p-6">
                                <div class="text-center">
                                    <i class="fas fa-file-image text-6xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">Vorschau wird geladen...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-toggle-on text-purple-600 mr-2"></i>
                                Status
                            </h3>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" <?php echo $template['is_active'] ? 'checked' : ''; ?> class="mr-3 w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                <span class="text-sm font-medium text-gray-700">Template aktivieren</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-2">Aktivierte Templates können von Kunden verwendet werden.</p>
                        </div>

                        <!-- Actions -->
                        <div class="card p-6">
                            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-medium hover:from-purple-700 hover:to-pink-700 transition flex items-center justify-center mb-3">
                                <i class="fas fa-save mr-2"></i>
                                Änderungen Speichern
                            </button>
                            <a href="/admin/freebie-templates.php" class="block w-full text-center bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium hover:bg-gray-300 transition">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Zurück zur Übersicht
                            </a>
                        </div>

                        <!-- Delete Zone -->
                        <div class="card p-6 delete-zone">
                            <h3 class="text-xl font-bold text-red-600 mb-4 flex items-center">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Gefahrenzone
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">Wenn du dieses Template löschst, kann es nicht wiederhergestellt werden!</p>
                            <button type="button" onclick="confirmDelete()" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 transition">
                                <i class="fas fa-trash mr-2"></i>
                                Template Löschen
                            </button>
                        </div>

                    </div>

                </div>
            </form>

            <!-- Delete Confirmation Modal (Hidden Form) -->
            <form method="POST" id="deleteForm" style="display: none;">
                <input type="hidden" name="delete" value="confirm">
            </form>

        </div>
    </main>

    <script>
        function selectTemplate(element, type) {
            document.querySelectorAll('.template-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            updatePreview();
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('upload-placeholder').classList.add('hidden');
                    const preview = document.getElementById('image-preview');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    
                    // Hide current mockup if exists
                    const currentMockup = document.getElementById('current-mockup');
                    if (currentMockup) {
                        currentMockup.style.display = 'none';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function updatePreview() {
            const preheadline = document.getElementById('preheadline')?.value || '';
            const headline = document.getElementById('headline')?.value || '';
            const subheadline = document.getElementById('subheadline')?.value || '';
            const primaryColor = document.querySelector('input[name="color_primary"]')?.value || '#8B5CF6';
            const secondaryColor = document.querySelector('input[name="color_secondary"]')?.value || '#6D28D9';
            const textColor = document.querySelector('input[name="color_text"]')?.value || '#1F2937';
            const bgColor = document.querySelector('input[name="color_background"]')?.value || '#FFFFFF';
            const headingFont = document.querySelector('select[name="font_heading"]')?.value || 'Inter';
            const bodyFont = document.querySelector('select[name="font_body"]')?.value || 'Inter';
            
            const previewBox = document.getElementById('previewBox');
            
            // VERBESSERTE VORSCHAU mit korrekten Font-Familien und Größen
            const previewContent = `
                <div style="background: ${bgColor}; color: ${textColor}; padding: 30px; border-radius: 12px; width: 100%; text-align: center; font-family: '${bodyFont}', sans-serif;">
                    ${preheadline ? `<p style="font-family: '${bodyFont}', sans-serif; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: ${secondaryColor}; margin-bottom: 12px; font-weight: 700;">${preheadline}</p>` : ''}
                    ${headline ? `<h2 style="font-family: '${headingFont}', sans-serif; color: ${primaryColor}; font-size: 22px; font-weight: 700; margin-bottom: 10px; line-height: 1.3;">${headline}</h2>` : `<h2 style="font-family: '${headingFont}', sans-serif; color: ${primaryColor}; font-size: 22px; font-weight: 700; margin-bottom: 10px;">Deine Hauptüberschrift</h2>`}
                    ${subheadline ? `<p style="font-family: '${bodyFont}', sans-serif; font-size: 14px; color: ${textColor}; opacity: 0.8; line-height: 1.6;">${subheadline}</p>` : `<p style="font-family: '${bodyFont}', sans-serif; font-size: 14px; color: ${textColor}; opacity: 0.8;">Deine Unterüberschrift</p>`}
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid ${primaryColor}; opacity: 0.3;"></div>
                    <p style="font-family: '${bodyFont}', sans-serif; font-size: 10px; color: ${textColor}; opacity: 0.5; margin-top: 10px;">✓ Live-Vorschau mit Schriftarten & Größen</p>
                </div>
            `;
            
            previewBox.innerHTML = previewContent;
        }
        
        function confirmDelete() {
            if (confirm('Bist du sicher, dass du dieses Template löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden!')) {
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Initial preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>

</body>
</html>
