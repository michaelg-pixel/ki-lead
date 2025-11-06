<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Admin-Check
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../public/admin-login.php');
    exit;
}

$conn = getDBConnection();
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $course_id > 0;

// Kurs laden (bei Bearbeitung)
if ($is_edit) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        header('Location: courses.php');
        exit;
    }
    
    // Module laden
    $stmt = $conn->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order");
    $stmt->execute([$course_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lektionen laden
    $lessons_by_module = [];
    foreach ($modules as $module) {
        $stmt = $conn->prepare("SELECT * FROM lessons WHERE module_id = ? ORDER BY sort_order");
        $stmt->execute([$module['id']]);
        $lessons_by_module[$module['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Kurs speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $niche = $_POST['niche'];
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $digistore_link = $_POST['digistore_link'] ?? '';
    
    // Thumbnail hochladen
    $thumbnail = $course['thumbnail'] ?? '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $upload_dir = '../uploads/thumbnails/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
            $thumbnail = $new_filename;
        }
    }
    
    if ($is_edit) {
        $stmt = $conn->prepare("
            UPDATE courses SET 
                title = ?, 
                description = ?, 
                niche = ?, 
                thumbnail = ?,
                is_premium = ?, 
                is_active = ?,
                digistore_link = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $niche, $thumbnail, $is_premium, $is_active, $digistore_link, $course_id]);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO courses (title, description, niche, thumbnail, is_premium, is_active, digistore_link) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $niche, $thumbnail, $is_premium, $is_active, $digistore_link]);
        $course_id = $conn->lastInsertId();
    }
    
    header('Location: course-edit.php?id=' . $course_id . '&success=1');
    exit;
}

// Modul hinzufügen
if (isset($_POST['add_module'])) {
    $module_title = $_POST['module_title'];
    $module_description = $_POST['module_description'];
    
    $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM modules WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
    
    $stmt = $conn->prepare("INSERT INTO modules (course_id, title, description, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$course_id, $module_title, $module_description, $next_order]);
    
    header('Location: course-edit.php?id=' . $course_id . '&module_added=1');
    exit;
}

// Modul bearbeiten
if (isset($_POST['edit_module'])) {
    $module_id = (int)$_POST['module_id'];
    $module_title = $_POST['module_title'];
    $module_description = $_POST['module_description'];
    
    $stmt = $conn->prepare("UPDATE modules SET title = ?, description = ? WHERE id = ?");
    $stmt->execute([$module_title, $module_description, $module_id]);
    
    header('Location: course-edit.php?id=' . $course_id . '&module_updated=1');
    exit;
}

// Lektion hinzufügen
if (isset($_POST['add_lesson'])) {
    $module_id = (int)$_POST['module_id'];
    $lesson_title = $_POST['lesson_title'];
    $lesson_description = $_POST['lesson_description'];
    $vimeo_url = $_POST['vimeo_url'];
    
    // PDF hochladen
    $pdf_file = '';
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $upload_dir = '../uploads/pdfs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $new_filename = uniqid() . '_' . time() . '.pdf';
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                $pdf_file = $new_filename;
            }
        }
    }
    
    $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM lessons WHERE module_id = ?");
    $stmt->execute([$module_id]);
    $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
    
    $stmt = $conn->prepare("
        INSERT INTO lessons (module_id, title, description, vimeo_url, pdf_file, sort_order) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$module_id, $lesson_title, $lesson_description, $vimeo_url, $pdf_file, $next_order]);
    
    header('Location: course-edit.php?id=' . $course_id . '&lesson_added=1');
    exit;
}

// Lektion bearbeiten
if (isset($_POST['edit_lesson'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    $lesson_title = $_POST['lesson_title'];
    $lesson_description = $_POST['lesson_description'];
    $vimeo_url = $_POST['vimeo_url'];
    
    // Aktuelle PDF-Datei laden
    $stmt = $conn->prepare("SELECT pdf_file FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $current_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    $pdf_file = $current_lesson['pdf_file'];
    
    // Neue PDF hochladen (falls vorhanden)
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $upload_dir = '../uploads/pdfs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $new_filename = uniqid() . '_' . time() . '.pdf';
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                // Alte PDF löschen
                if ($pdf_file && file_exists($upload_dir . $pdf_file)) {
                    unlink($upload_dir . $pdf_file);
                }
                $pdf_file = $new_filename;
            }
        }
    }
    
    $stmt = $conn->prepare("
        UPDATE lessons 
        SET title = ?, description = ?, vimeo_url = ?, pdf_file = ? 
        WHERE id = ?
    ");
    $stmt->execute([$lesson_title, $lesson_description, $vimeo_url, $pdf_file, $lesson_id]);
    
    header('Location: course-edit.php?id=' . $course_id . '&lesson_updated=1');
    exit;
}

// Modul löschen
if (isset($_POST['delete_module'])) {
    $module_id = (int)$_POST['module_id'];
    $stmt = $conn->prepare("DELETE FROM lessons WHERE module_id = ?");
    $stmt->execute([$module_id]);
    $stmt = $conn->prepare("DELETE FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    
    header('Location: course-edit.php?id=' . $course_id);
    exit;
}

// Lektion löschen
if (isset($_POST['delete_lesson'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    
    // PDF-Datei löschen falls vorhanden
    $stmt = $conn->prepare("SELECT pdf_file FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lesson && $lesson['pdf_file']) {
        $pdf_path = '../uploads/pdfs/' . $lesson['pdf_file'];
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    
    header('Location: course-edit.php?id=' . $course_id);
    exit;
}

$niches = [
    'business_coaching' => 'Business Coaching',
    'fitness' => 'Fitness & Gesundheit',
    'real_estate' => 'Immobilien',
    'consulting' => 'Unternehmensberatung',
    'online_marketing' => 'Online Marketing',
    'finance' => 'Finanzen & Versicherungen',
    'wellness' => 'Wellness & Beauty',
    'handwerk' => 'Handwerk',
    'law' => 'Recht',
    'tech' => 'IT & Software',
    'education' => 'Bildung',
    'hospitality' => 'Gastronomie',
    'automotive' => 'Automotive',
    'photography' => 'Fotografie',
    'personal_development' => 'Persönlichkeitsentwicklung',
    'other' => 'Sonstiges'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Kurs bearbeiten' : 'Neuer Kurs' ?> - KI Leadsystem Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Styles mit verbessertem Kontrast */
        body {
            background: #0f0f1e;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Verbesserte Input-Felder */
        .custom-input {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            color: #e0e0e0;
            transition: all 0.2s ease;
        }
        
        .custom-input:focus {
            background: rgba(26, 26, 46, 0.95);
            border-color: rgba(168, 85, 247, 0.6);
            outline: none;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
        }
        
        .custom-input::placeholder {
            color: #a0a0a0;
        }
        
        /* Card Styles */
        .card {
            background: rgba(26, 26, 46, 0.7);
            border: 1px solid rgba(168, 85, 247, 0.2);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            border-color: rgba(168, 85, 247, 0.4);
        }
        
        .card-nested {
            background: rgba(22, 33, 62, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.25);
        }
        
        .card-deep {
            background: rgba(15, 15, 30, 0.9);
            border: 1px solid rgba(168, 85, 247, 0.2);
        }
        
        /* Label Styles */
        .custom-label {
            color: #c084fc;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }
        
        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            border: 1px solid rgba(168, 85, 247, 0.5);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #9333ea 0%, #db2777 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(168, 85, 247, 0.5);
        }
        
        .btn-secondary {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #60a5fa;
        }
        
        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
        }
        
        .btn-success:hover {
            background: rgba(34, 197, 94, 0.3);
            border-color: rgba(34, 197, 94, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
        }
        
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #e0e0e0;
        }
        
        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(168, 85, 247, 0.2);
        }
        
        .sidebar a {
            transition: all 0.2s ease;
        }
        
        .sidebar a:hover {
            background: rgba(168, 85, 247, 0.15);
            transform: translateX(4px);
        }
        
        .sidebar a.active {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
        }
        
        /* Alert Styles */
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
        }
        
        /* Details/Summary */
        details summary {
            color: #c084fc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        details summary:hover {
            color: #e9d5ff;
        }
        
        /* Checkbox Styling */
        input[type="checkbox"] {
            accent-color: #a855f7;
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
    </style>
</head>
<body class="text-white">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 min-h-screen p-6 flex-shrink-0">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <span style="font-size: 1.5rem;">⭐</span>
                    <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                        Admin Panel
                    </span>
                </h1>
                <p class="text-sm" style="color: #a0a0a0; margin-top: 4px;">KI Leadsystem</p>
            </div>
            <nav class="space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg" style="color: #b0b0b0;">
                    <i class="fas fa-home" style="width: 20px;"></i> 
                    <span>Dashboard</span>
                </a>
                <a href="customers.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg" style="color: #b0b0b0;">
                    <i class="fas fa-users" style="width: 20px;"></i> 
                    <span>Kunden</span>
                </a>
                <a href="courses.php" class="active flex items-center gap-3 px-4 py-2.5 rounded-lg text-white">
                    <i class="fas fa-graduation-cap" style="width: 20px;"></i> 
                    <span>Kurse</span>
                </a>
                <a href="tutorials.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg" style="color: #b0b0b0;">
                    <i class="fas fa-video" style="width: 20px;"></i> 
                    <span>Anleitungen</span>
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg mt-8" style="color: #ff6b6b;">
                    <i class="fas fa-sign-out-alt" style="width: 20px;"></i> 
                    <span>Abmelden</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success px-6 py-4 rounded-lg mb-6 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i> Kurs erfolgreich gespeichert!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['module_added'])): ?>
                <div class="alert-success px-6 py-4 rounded-lg mb-6 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i> Modul erfolgreich hinzugefügt!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['module_updated'])): ?>
                <div class="alert-success px-6 py-4 rounded-lg mb-6 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i> Modul erfolgreich aktualisiert!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['lesson_added'])): ?>
                <div class="alert-success px-6 py-4 rounded-lg mb-6 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i> Lektion erfolgreich hinzugefügt!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['lesson_updated'])): ?>
                <div class="alert-success px-6 py-4 rounded-lg mb-6 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i> Lektion erfolgreich aktualisiert!
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-4xl font-bold text-white mb-2">
                        <?= $is_edit ? 'Kurs bearbeiten' : 'Neuen Kurs erstellen' ?>
                    </h2>
                    <p class="text-base" style="color: #a0a0a0;">
                        <?= $is_edit ? 'Bearbeite deinen Videokurs' : 'Erstelle einen neuen Videokurs' ?>
                    </p>
                </div>
                <a href="courses.php" class="btn-ghost px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Zurück
                </a>
            </div>

            <!-- Kurs-Grunddaten -->
            <div class="card rounded-xl p-8 mb-8">
                <h3 class="text-2xl font-bold mb-6 text-white flex items-center gap-3">
                    <i class="fas fa-info-circle" style="color: #a855f7;"></i> Grunddaten
                </h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="custom-label block mb-2">Kurstitel *</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($course['title'] ?? '') ?>" 
                                   class="custom-input w-full px-4 py-3 rounded-lg" required>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="custom-label block mb-2">Beschreibung *</label>
                            <textarea name="description" rows="4" 
                                      class="custom-input w-full px-4 py-3 rounded-lg" required><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="custom-label block mb-2">Nische *</label>
                            <select name="niche" class="custom-input w-full px-4 py-3 rounded-lg" required>
                                <option value="">-- Nische wählen --</option>
                                <?php foreach ($niches as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($course['niche'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="custom-label block mb-2">Thumbnail (Mockup-Bild)</label>
                            <input type="file" name="thumbnail" accept="image/*" 
                                   class="custom-input w-full px-4 py-3 rounded-lg">
                            <?php if (isset($course['thumbnail']) && $course['thumbnail']): ?>
                                <div class="mt-3">
                                    <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                                         class="w-48 h-32 object-cover rounded-lg border border-purple-500/30" alt="Current Thumbnail">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-span-2 flex gap-6">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_premium" value="1" 
                                       <?= ($course['is_premium'] ?? 0) ? 'checked' : '' ?>>
                                <span style="color: #e0e0e0;">
                                    <i class="fas fa-crown" style="color: #fbbf24;"></i> Premium Kurs (Kostenpflichtig)
                                </span>
                            </label>
                            
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?= ($course['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <span style="color: #e0e0e0;">
                                    <i class="fas fa-check-circle" style="color: #22c55e;"></i> Kurs aktiv
                                </span>
                            </label>
                        </div>
                        
                        <div class="col-span-2" id="digistore-field" style="display: <?= ($course['is_premium'] ?? 0) ? 'block' : 'none' ?>">
                            <label class="custom-label block mb-2">
                                <i class="fas fa-shopping-cart"></i> Digistore24 Link
                            </label>
                            <input type="url" name="digistore_link" value="<?= htmlspecialchars($course['digistore_link'] ?? '') ?>" 
                                   class="custom-input w-full px-4 py-3 rounded-lg" 
                                   placeholder="https://www.digistore24.com/...">
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <button type="submit" name="save_course" 
                                class="btn-primary px-8 py-3 rounded-lg font-semibold inline-flex items-center gap-2">
                            <i class="fas fa-save"></i> Kurs speichern
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($is_edit): ?>
                <!-- Module & Lektionen -->
                <div class="card rounded-xl p-8">
                    <h3 class="text-2xl font-bold mb-6 text-white flex items-center gap-3">
                        <i class="fas fa-list" style="color: #a855f7;"></i> Module & Lektionen
                    </h3>
                    
                    <!-- Module anzeigen -->
                    <?php if (!empty($modules)): ?>
                        <div class="space-y-6 mb-8">
                            <?php foreach ($modules as $module): ?>
                                <div class="card-nested rounded-lg p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex-1">
                                            <h4 class="text-xl font-bold text-white"><?= htmlspecialchars($module['title']) ?></h4>
                                            <p class="mt-1" style="color: #b0b0b0;"><?= htmlspecialchars($module['description']) ?></p>
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <button onclick="toggleEditModule(<?= $module['id'] ?>)" 
                                                    class="btn-secondary px-4 py-2 rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                                                <i class="fas fa-edit"></i> Bearbeiten
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Modul wirklich löschen?')" class="inline">
                                                <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                                <button type="submit" name="delete_module" 
                                                        class="btn-danger px-4 py-2 rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                                                    <i class="fas fa-trash"></i> Löschen
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Bearbeitungsformular für Modul (versteckt) -->
                                    <div id="edit-module-<?= $module['id'] ?>" class="hidden card-deep p-5 rounded-lg mb-4">
                                        <form method="POST">
                                            <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="custom-label block mb-2">Modultitel</label>
                                                    <input type="text" name="module_title" 
                                                           value="<?= htmlspecialchars($module['title']) ?>" 
                                                           class="custom-input w-full px-4 py-2.5 rounded-lg" required>
                                                </div>
                                                <div>
                                                    <label class="custom-label block mb-2">Beschreibung</label>
                                                    <textarea name="module_description" rows="2" 
                                                              class="custom-input w-full px-4 py-2.5 rounded-lg"><?= htmlspecialchars($module['description']) ?></textarea>
                                                </div>
                                                <div class="flex gap-3">
                                                    <button type="submit" name="edit_module" 
                                                            class="btn-success px-5 py-2.5 rounded-lg font-semibold inline-flex items-center gap-2">
                                                        <i class="fas fa-save"></i> Speichern
                                                    </button>
                                                    <button type="button" onclick="toggleEditModule(<?= $module['id'] ?>)" 
                                                            class="btn-ghost px-5 py-2.5 rounded-lg font-semibold">
                                                        Abbrechen
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Lektionen -->
                                    <?php if (!empty($lessons_by_module[$module['id']])): ?>
                                        <div class="space-y-3 mt-4">
                                            <?php foreach ($lessons_by_module[$module['id']] as $lesson): ?>
                                                <div class="card-deep p-4 rounded-lg">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <div class="font-semibold text-white"><?= htmlspecialchars($lesson['title']) ?></div>
                                                            <div class="text-sm mt-1" style="color: #a0a0a0;"><?= htmlspecialchars($lesson['description']) ?></div>
                                                            <div class="text-xs mt-2 flex items-center gap-4" style="color: #c084fc;">
                                                                <span><i class="fas fa-video mr-1"></i> <?= htmlspecialchars($lesson['vimeo_url']) ?></span>
                                                                <?php if ($lesson['pdf_file']): ?>
                                                                    <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf_file']) ?>" 
                                                                       target="_blank" class="hover:underline">
                                                                        <i class="fas fa-file-pdf mr-1"></i> PDF vorhanden
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="flex gap-2 ml-4">
                                                            <button onclick="toggleEditLesson(<?= $lesson['id'] ?>)" 
                                                                    class="btn-secondary px-3 py-2 rounded-lg text-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="POST" onsubmit="return confirm('Lektion löschen?')" class="inline">
                                                                <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                                                <button type="submit" name="delete_lesson" 
                                                                        class="btn-danger px-3 py-2 rounded-lg text-sm">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Bearbeitungsformular für Lektion (versteckt) -->
                                                    <div id="edit-lesson-<?= $lesson['id'] ?>" class="hidden mt-4" 
                                                         style="background: rgba(10, 10, 20, 0.8); border: 1px solid rgba(168, 85, 247, 0.2); padding: 1rem; border-radius: 0.5rem;">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                                            <div class="space-y-3">
                                                                <div>
                                                                    <label class="custom-label block mb-2">Lektionstitel</label>
                                                                    <input type="text" name="lesson_title" 
                                                                           value="<?= htmlspecialchars($lesson['title']) ?>" 
                                                                           class="custom-input w-full px-4 py-2.5 rounded-lg" required>
                                                                </div>
                                                                <div>
                                                                    <label class="custom-label block mb-2">Beschreibung</label>
                                                                    <textarea name="lesson_description" rows="2" 
                                                                              class="custom-input w-full px-4 py-2.5 rounded-lg"><?= htmlspecialchars($lesson['description']) ?></textarea>
                                                                </div>
                                                                <div>
                                                                    <label class="custom-label block mb-2">Vimeo URL</label>
                                                                    <input type="url" name="vimeo_url" 
                                                                           value="<?= htmlspecialchars($lesson['vimeo_url']) ?>" 
                                                                           class="custom-input w-full px-4 py-2.5 rounded-lg" required>
                                                                </div>
                                                                <div>
                                                                    <label class="custom-label block mb-2">
                                                                        PDF hochladen (optional - leer lassen um beizubehalten)
                                                                    </label>
                                                                    <input type="file" name="pdf_file" accept=".pdf" 
                                                                           class="custom-input w-full px-4 py-2.5 rounded-lg">
                                                                    <?php if ($lesson['pdf_file']): ?>
                                                                        <div class="text-xs mt-2" style="color: #a0a0a0;">
                                                                            Aktuell: <?= htmlspecialchars($lesson['pdf_file']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="flex gap-3">
                                                                    <button type="submit" name="edit_lesson" 
                                                                            class="btn-success px-5 py-2.5 rounded-lg font-semibold inline-flex items-center gap-2">
                                                                        <i class="fas fa-save"></i> Speichern
                                                                    </button>
                                                                    <button type="button" onclick="toggleEditLesson(<?= $lesson['id'] ?>)" 
                                                                            class="btn-ghost px-5 py-2.5 rounded-lg font-semibold">
                                                                        Abbrechen
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Neue Lektion hinzufügen -->
                                    <details class="mt-4">
                                        <summary class="cursor-pointer font-semibold">
                                            <i class="fas fa-plus mr-2"></i> Neue Lektion hinzufügen
                                        </summary>
                                        <form method="POST" enctype="multipart/form-data" class="mt-4 card-deep p-4 rounded-lg">
                                            <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                            <div class="space-y-3">
                                                <input type="text" name="lesson_title" placeholder="Lektionstitel" 
                                                       class="custom-input w-full px-4 py-2.5 rounded-lg" required>
                                                <textarea name="lesson_description" placeholder="Beschreibung" rows="2" 
                                                          class="custom-input w-full px-4 py-2.5 rounded-lg"></textarea>
                                                <input type="url" name="vimeo_url" placeholder="Vimeo URL" 
                                                       class="custom-input w-full px-4 py-2.5 rounded-lg" required>
                                                <div>
                                                    <label class="custom-label block mb-2">PDF hochladen (optional)</label>
                                                    <input type="file" name="pdf_file" accept=".pdf" 
                                                           class="custom-input w-full px-4 py-2.5 rounded-lg">
                                                </div>
                                                <button type="submit" name="add_lesson" 
                                                        class="btn-primary px-5 py-2.5 rounded-lg font-semibold inline-flex items-center gap-2">
                                                    <i class="fas fa-plus"></i> Lektion hinzufügen
                                                </button>
                                            </div>
                                        </form>
                                    </details>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Neues Modul hinzufügen -->
                    <div class="card-nested rounded-lg p-6">
                        <h4 class="text-xl font-bold mb-5 text-white flex items-center gap-2">
                            <i class="fas fa-plus-circle" style="color: #22c55e;"></i> Neues Modul hinzufügen
                        </h4>
                        <form method="POST">
                            <div class="space-y-4">
                                <input type="text" name="module_title" placeholder="Modultitel" 
                                       class="custom-input w-full px-4 py-3 rounded-lg" required>
                                <textarea name="module_description" placeholder="Beschreibung" rows="3" 
                                          class="custom-input w-full px-4 py-3 rounded-lg"></textarea>
                                <button type="submit" name="add_module" 
                                        class="btn-success px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2">
                                    <i class="fas fa-plus"></i> Modul hinzufügen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Toggle Digistore24-Feld bei Premium-Checkbox
        document.querySelector('input[name="is_premium"]').addEventListener('change', function() {
            document.getElementById('digistore-field').style.display = this.checked ? 'block' : 'none';
        });
        
        // Toggle Modul-Bearbeitung
        function toggleEditModule(moduleId) {
            const editForm = document.getElementById('edit-module-' + moduleId);
            if (editForm.classList.contains('hidden')) {
                editForm.classList.remove('hidden');
            } else {
                editForm.classList.add('hidden');
            }
        }
        
        // Toggle Lektions-Bearbeitung
        function toggleEditLesson(lessonId) {
            const editForm = document.getElementById('edit-lesson-' + lessonId);
            if (editForm.classList.contains('hidden')) {
                editForm.classList.remove('hidden');
            } else {
                editForm.classList.add('hidden');
            }
        }
    </script>

</body>
</html>