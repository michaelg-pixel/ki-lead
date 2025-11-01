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
</head>
<body class="bg-gray-900 text-white">

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 min-h-screen p-6">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-purple-500">🚀 KI Leadsystem Admin</h1>
            </div>
            <nav class="space-y-2">
                <a href="index.php" class="block px-4 py-2 rounded hover:bg-gray-700">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="customers.php" class="block px-4 py-2 rounded hover:bg-gray-700">
                    <i class="fas fa-users mr-2"></i> Kunden
                </a>
                <a href="courses.php" class="block px-4 py-2 rounded bg-purple-600">
                    <i class="fas fa-graduation-cap mr-2"></i> Kurse
                </a>
                <a href="tutorials.php" class="block px-4 py-2 rounded hover:bg-gray-700">
                    <i class="fas fa-video mr-2"></i> Anleitungen
                </a>
                <a href="logout.php" class="block px-4 py-2 rounded hover:bg-red-600 mt-8">
                    <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-600 text-white px-4 py-3 rounded mb-6">
                    Kurs erfolgreich gespeichert!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['module_added'])): ?>
                <div class="bg-green-600 text-white px-4 py-3 rounded mb-6">
                    Modul erfolgreich hinzugefügt!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['module_updated'])): ?>
                <div class="bg-green-600 text-white px-4 py-3 rounded mb-6">
                    Modul erfolgreich aktualisiert!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['lesson_added'])): ?>
                <div class="bg-green-600 text-white px-4 py-3 rounded mb-6">
                    Lektion erfolgreich hinzugefügt!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['lesson_updated'])): ?>
                <div class="bg-green-600 text-white px-4 py-3 rounded mb-6">
                    Lektion erfolgreich aktualisiert!
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold">
                        <?= $is_edit ? 'Kurs bearbeiten' : 'Neuen Kurs erstellen' ?>
                    </h2>
                    <p class="text-gray-400 mt-2">
                        <?= $is_edit ? 'Bearbeite deinen Videokurs' : 'Erstelle einen neuen Videokurs' ?>
                    </p>
                </div>
                <a href="courses.php" class="bg-gray-700 hover:bg-gray-600 px-6 py-3 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i> Zurück
                </a>
            </div>

            <!-- Kurs-Grunddaten -->
            <div class="bg-gray-800 rounded-lg p-8 mb-8">
                <h3 class="text-2xl font-bold mb-6">
                    <i class="fas fa-info-circle mr-2 text-purple-500"></i> Grunddaten
                </h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block mb-2 font-semibold">Kurstitel *</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($course['title'] ?? '') ?>" 
                                   class="w-full bg-gray-700 px-4 py-3 rounded-lg" required>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block mb-2 font-semibold">Beschreibung *</label>
                            <textarea name="description" rows="4" 
                                      class="w-full bg-gray-700 px-4 py-3 rounded-lg" required><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block mb-2 font-semibold">Nische *</label>
                            <select name="niche" class="w-full bg-gray-700 px-4 py-3 rounded-lg" required>
                                <option value="">-- Nische wählen --</option>
                                <?php foreach ($niches as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($course['niche'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block mb-2 font-semibold">Thumbnail (Mockup-Bild) *</label>
                            <input type="file" name="thumbnail" accept="image/*" 
                                   class="w-full bg-gray-700 px-4 py-3 rounded-lg">
                            <?php if (isset($course['thumbnail']) && $course['thumbnail']): ?>
                                <div class="mt-2">
                                    <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                                         class="w-48 h-32 object-cover rounded" alt="Current Thumbnail">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-span-2 flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_premium" value="1" 
                                       <?= ($course['is_premium'] ?? 0) ? 'checked' : '' ?>
                                       class="w-5 h-5">
                                <span><i class="fas fa-crown text-yellow-500 mr-1"></i> Premium Kurs (Kostenpflichtig)</span>
                            </label>
                            
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?= ($course['is_active'] ?? 1) ? 'checked' : '' ?>
                                       class="w-5 h-5">
                                <span><i class="fas fa-check-circle text-green-500 mr-1"></i> Kurs aktiv</span>
                            </label>
                        </div>
                        
                        <div class="col-span-2" id="digistore-field" style="display: <?= ($course['is_premium'] ?? 0) ? 'block' : 'none' ?>">
                            <label class="block mb-2 font-semibold">
                                <i class="fas fa-shopping-cart mr-1"></i> Digistore24 Link
                            </label>
                            <input type="url" name="digistore_link" value="<?= htmlspecialchars($course['digistore_link'] ?? '') ?>" 
                                   class="w-full bg-gray-700 px-4 py-3 rounded-lg" 
                                   placeholder="https://www.digistore24.com/...">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" name="save_course" 
                                class="bg-purple-600 hover:bg-purple-700 px-8 py-3 rounded-lg font-semibold">
                            <i class="fas fa-save mr-2"></i> Kurs speichern
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($is_edit): ?>
                <!-- Module & Lektionen -->
                <div class="bg-gray-800 rounded-lg p-8">
                    <h3 class="text-2xl font-bold mb-6">
                        <i class="fas fa-list mr-2 text-purple-500"></i> Module & Lektionen
                    </h3>
                    
                    <!-- Module anzeigen -->
                    <?php if (!empty($modules)): ?>
                        <div class="space-y-6 mb-8">
                            <?php foreach ($modules as $module): ?>
                                <div class="bg-gray-700 rounded-lg p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex-1">
                                            <h4 class="text-xl font-bold"><?= htmlspecialchars($module['title']) ?></h4>
                                            <p class="text-gray-400 mt-1"><?= htmlspecialchars($module['description']) ?></p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="toggleEditModule(<?= $module['id'] ?>)" 
                                                    class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded text-sm">
                                                <i class="fas fa-edit"></i> Bearbeiten
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Modul wirklich löschen?')" class="inline">
                                                <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                                <button type="submit" name="delete_module" 
                                                        class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded text-sm">
                                                    <i class="fas fa-trash"></i> Löschen
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Bearbeitungsformular für Modul (versteckt) -->
                                    <div id="edit-module-<?= $module['id'] ?>" class="hidden bg-gray-600 p-4 rounded mb-4">
                                        <form method="POST">
                                            <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block mb-1 text-sm">Modultitel</label>
                                                    <input type="text" name="module_title" 
                                                           value="<?= htmlspecialchars($module['title']) ?>" 
                                                           class="w-full bg-gray-700 px-4 py-2 rounded" required>
                                                </div>
                                                <div>
                                                    <label class="block mb-1 text-sm">Beschreibung</label>
                                                    <textarea name="module_description" rows="2" 
                                                              class="w-full bg-gray-700 px-4 py-2 rounded"><?= htmlspecialchars($module['description']) ?></textarea>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="submit" name="edit_module" 
                                                            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">
                                                        <i class="fas fa-save mr-1"></i> Speichern
                                                    </button>
                                                    <button type="button" onclick="toggleEditModule(<?= $module['id'] ?>)" 
                                                            class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">
                                                        Abbrechen
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Lektionen -->
                                    <?php if (!empty($lessons_by_module[$module['id']])): ?>
                                        <div class="space-y-3">
                                            <?php foreach ($lessons_by_module[$module['id']] as $lesson): ?>
                                                <div class="bg-gray-800 p-4 rounded">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <div class="font-semibold"><?= htmlspecialchars($lesson['title']) ?></div>
                                                            <div class="text-sm text-gray-400"><?= htmlspecialchars($lesson['description']) ?></div>
                                                            <div class="text-xs text-purple-400 mt-1">
                                                                <i class="fas fa-video mr-1"></i> Vimeo: <?= htmlspecialchars($lesson['vimeo_url']) ?>
                                                                <?php if ($lesson['pdf_file']): ?>
                                                                    <span class="ml-3">
                                                                        <i class="fas fa-file-pdf mr-1"></i> 
                                                                        <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf_file']) ?>" 
                                                                           target="_blank" class="hover:underline">PDF vorhanden</a>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="flex gap-2 ml-4">
                                                            <button onclick="toggleEditLesson(<?= $lesson['id'] ?>)" 
                                                                    class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded text-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="POST" onsubmit="return confirm('Lektion löschen?')" class="inline">
                                                                <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                                                <button type="submit" name="delete_lesson" 
                                                                        class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded text-sm">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Bearbeitungsformular für Lektion (versteckt) -->
                                                    <div id="edit-lesson-<?= $lesson['id'] ?>" class="hidden mt-4 bg-gray-700 p-4 rounded">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                                            <div class="space-y-3">
                                                                <div>
                                                                    <label class="block mb-1 text-sm">Lektionstitel</label>
                                                                    <input type="text" name="lesson_title" 
                                                                           value="<?= htmlspecialchars($lesson['title']) ?>" 
                                                                           class="w-full bg-gray-600 px-4 py-2 rounded" required>
                                                                </div>
                                                                <div>
                                                                    <label class="block mb-1 text-sm">Beschreibung</label>
                                                                    <textarea name="lesson_description" rows="2" 
                                                                              class="w-full bg-gray-600 px-4 py-2 rounded"><?= htmlspecialchars($lesson['description']) ?></textarea>
                                                                </div>
                                                                <div>
                                                                    <label class="block mb-1 text-sm">Vimeo URL</label>
                                                                    <input type="url" name="vimeo_url" 
                                                                           value="<?= htmlspecialchars($lesson['vimeo_url']) ?>" 
                                                                           class="w-full bg-gray-600 px-4 py-2 rounded" required>
                                                                </div>
                                                                <div>
                                                                    <label class="block mb-1 text-sm">
                                                                        PDF hochladen (optional - leer lassen um beizubehalten)
                                                                    </label>
                                                                    <input type="file" name="pdf_file" accept=".pdf" 
                                                                           class="w-full bg-gray-600 px-4 py-2 rounded">
                                                                    <?php if ($lesson['pdf_file']): ?>
                                                                        <div class="text-xs text-gray-400 mt-1">
                                                                            Aktuell: <?= htmlspecialchars($lesson['pdf_file']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="flex gap-2">
                                                                    <button type="submit" name="edit_lesson" 
                                                                            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">
                                                                        <i class="fas fa-save mr-1"></i> Speichern
                                                                    </button>
                                                                    <button type="button" onclick="toggleEditLesson(<?= $lesson['id'] ?>)" 
                                                                            class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">
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
                                        <summary class="cursor-pointer text-purple-400 hover:text-purple-300">
                                            <i class="fas fa-plus mr-2"></i> Neue Lektion hinzufügen
                                        </summary>
                                        <form method="POST" enctype="multipart/form-data" class="mt-4 bg-gray-600 p-4 rounded">
                                            <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                            <div class="space-y-3">
                                                <input type="text" name="lesson_title" placeholder="Lektionstitel" 
                                                       class="w-full bg-gray-700 px-4 py-2 rounded" required>
                                                <textarea name="lesson_description" placeholder="Beschreibung" rows="2" 
                                                          class="w-full bg-gray-700 px-4 py-2 rounded"></textarea>
                                                <input type="url" name="vimeo_url" placeholder="Vimeo URL" 
                                                       class="w-full bg-gray-700 px-4 py-2 rounded" required>
                                                <div>
                                                    <label class="block mb-1 text-sm">PDF hochladen (optional)</label>
                                                    <input type="file" name="pdf_file" accept=".pdf" 
                                                           class="w-full bg-gray-700 px-4 py-2 rounded">
                                                </div>
                                                <button type="submit" name="add_lesson" 
                                                        class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded">
                                                    Lektion hinzufügen
                                                </button>
                                            </div>
                                        </form>
                                    </details>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Neues Modul hinzufügen -->
                    <div class="bg-gray-700 rounded-lg p-6">
                        <h4 class="text-xl font-bold mb-4">
                            <i class="fas fa-plus-circle mr-2 text-green-500"></i> Neues Modul hinzufügen
                        </h4>
                        <form method="POST">
                            <div class="space-y-4">
                                <input type="text" name="module_title" placeholder="Modultitel" 
                                       class="w-full bg-gray-600 px-4 py-3 rounded-lg" required>
                                <textarea name="module_description" placeholder="Beschreibung" rows="3" 
                                          class="w-full bg-gray-600 px-4 py-3 rounded-lg"></textarea>
                                <button type="submit" name="add_module" 
                                        class="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-semibold">
                                    <i class="fas fa-plus mr-2"></i> Modul hinzufügen
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