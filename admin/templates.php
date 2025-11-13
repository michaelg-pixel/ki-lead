<?php
session_start();
require_once '../config/database.php';

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Template löschen
if (isset($_POST['delete_template'])) {
    $template_id = $_POST['template_id'];
    
    $query = "DELETE FROM freebie_templates WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $template_id);
    
    if ($stmt->execute()) {
        $success_message = "Template erfolgreich gelöscht!";
    } else {
        $error_message = "Fehler beim Löschen des Templates.";
    }
}

// Template erstellen/bearbeiten
if (isset($_POST['save_template'])) {
    $id = $_POST['template_id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $niche = $_POST['niche'];
    $preview_url = $_POST['preview_url'];
    $download_url = $_POST['download_url'];
    $video_tutorial_url = $_POST['video_tutorial_url'] ?? null;
    $product_mockup_url = $_POST['product_mockup_url'] ?? null;
    $course_duration = $_POST['course_duration'] ?? null;
    $original_product_link = $_POST['original_product_link'] ?? null;
    $status = $_POST['status'];
    
    if ($id) {
        // Update
        $query = "UPDATE freebie_templates SET 
                  name = :name, 
                  description = :description, 
                  category = :category,
                  niche = :niche,
                  preview_url = :preview_url, 
                  download_url = :download_url, 
                  video_tutorial_url = :video_tutorial_url,
                  product_mockup_url = :product_mockup_url,
                  course_duration = :course_duration,
                  original_product_link = :original_product_link,
                  status = :status,
                  updated_at = NOW()
                  WHERE id = :id";
    } else {
        // Insert
        $query = "INSERT INTO freebie_templates 
                  (name, description, category, niche, preview_url, download_url, video_tutorial_url, product_mockup_url, course_duration, original_product_link, status, created_at, updated_at) 
                  VALUES 
                  (:name, :description, :category, :niche, :preview_url, :download_url, :video_tutorial_url, :product_mockup_url, :course_duration, :original_product_link, :status, NOW(), NOW())";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':niche', $niche);
    $stmt->bindParam(':preview_url', $preview_url);
    $stmt->bindParam(':download_url', $download_url);
    $stmt->bindParam(':video_tutorial_url', $video_tutorial_url);
    $stmt->bindParam(':product_mockup_url', $product_mockup_url);
    $stmt->bindParam(':course_duration', $course_duration);
    $stmt->bindParam(':original_product_link', $original_product_link);
    $stmt->bindParam(':status', $status);
    
    if ($id) {
        $stmt->bindParam(':id', $id);
    }
    
    if ($stmt->execute()) {
        $success_message = $id ? "Template erfolgreich aktualisiert!" : "Template erfolgreich erstellt!";
    } else {
        $error_message = "Fehler beim Speichern des Templates.";
    }
}

// Alle Templates laden
$query = "SELECT * FROM freebie_templates ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategorien für Dropdown
$categories = [
    'sales_page' => 'Sales Page',
    'optin_page' => 'Optin Page',
    'thank_you_page' => 'Thank You Page',
    'webinar_page' => 'Webinar Page',
    'course_page' => 'Videokurs Seite',
    'download_page' => 'Download Page',
    'coming_soon' => 'Coming Soon Page',
    'other' => 'Sonstiges'
];

// Nischen für Dropdown
$niches = [
    'online_marketing' => 'Online Marketing',
    'affiliate_marketing' => 'Affiliate Marketing',
    'ecommerce' => 'E-Commerce',
    'coaching' => 'Coaching',
    'consulting' => 'Consulting',
    'saas' => 'SaaS',
    'info_products' => 'Info-Produkte',
    'real_estate' => 'Immobilien',
    'fitness' => 'Fitness',
    'health' => 'Gesundheit',
    'finance' => 'Finanzen',
    'personal_development' => 'Persönlichkeitsentwicklung',
    'business' => 'Business',
    'technology' => 'Technologie',
    'other' => 'Sonstiges'
];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template-Verwaltung - KI Leadsystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1400px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .template-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .template-mockup {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .template-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .template-actions {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--light-color);
        }

        .btn-custom {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            border: none;
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }

        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .alert-custom {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .niche-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .duration-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .template-info {
            padding: 1rem;
        }

        .template-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .template-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .template-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }

        .search-box input {
            padding-left: 45px;
        }
    </style>
</head>
<body>
    <div class="container-fluid admin-container">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-grid-3x3-gap-fill me-3"></i>Template-Verwaltung</h1>
                    <p class="mb-0 opacity-75">Verwalte alle Freebie-Templates für deine Kunden</p>
                </div>
                <div>
                    <button class="btn btn-light btn-custom" onclick="window.location.href='dashboard.php'">
                        <i class="bi bi-arrow-left me-2"></i>Zurück zum Dashboard
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-custom" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-custom" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistiken -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%); color: white;">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <h3 class="mb-0"><?php echo count($templates); ?></h3>
                    <p class="text-muted mb-0">Gesamt Templates</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, var(--success-color) 0%, #16a34a 100%); color: white;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="mb-0"><?php echo count(array_filter($templates, fn($t) => $t['status'] === 'active')); ?></h3>
                    <p class="text-muted mb-0">Aktive Templates</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%); color: white;">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <h3 class="mb-0"><?php echo count(array_filter($templates, fn($t) => $t['status'] === 'draft')); ?></h3>
                    <p class="text-muted mb-0">Entwürfe</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, var(--info-color) 0%, #2563eb 100%); color: white;">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <h3 class="mb-0"><?php echo count(array_filter($templates, fn($t) => !empty($t['video_tutorial_url']))); ?></h3>
                    <p class="text-muted mb-0">Mit Video</p>
                </div>
            </div>
        </div>

        <!-- Filter und Suche -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label fw-bold">Suche</label>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Template suchen...">
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label fw-bold">Kategorie</label>
                    <select class="form-select" id="filterCategory">
                        <option value="">Alle Kategorien</option>
                        <?php foreach ($categories as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label fw-bold">Nische</label>
                    <select class="form-select" id="filterNiche">
                        <option value="">Alle Nischen</option>
                        <?php foreach ($niches as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary-custom btn-custom w-100" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle me-2"></i>Neues Template
                    </button>
                </div>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="row" id="templatesGrid">
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6 col-lg-4 mb-4 template-item" 
                     data-category="<?php echo htmlspecialchars($template['category']); ?>"
                     data-niche="<?php echo htmlspecialchars($template['niche']); ?>"
                     data-name="<?php echo htmlspecialchars($template['name']); ?>">
                    <div class="card template-card">
                        <div class="position-relative">
                            <?php if (!empty($template['product_mockup_url'])): ?>
                                <img src="<?php echo htmlspecialchars($template['product_mockup_url']); ?>" 
                                     class="template-mockup" 
                                     alt="<?php echo htmlspecialchars($template['name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/400x300?text=Kein+Mockup'">
                            <?php else: ?>
                                <div class="template-mockup d-flex align-items-center justify-content-center">
                                    <i class="bi bi-image" style="font-size: 3rem; color: #cbd5e1;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="badge template-badge <?php echo $template['status'] === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $template['status'] === 'active' ? 'Aktiv' : 'Entwurf'; ?>
                            </span>
                        </div>
                        
                        <div class="template-info">
                            <h5 class="template-title"><?php echo htmlspecialchars($template['name']); ?></h5>
                            <p class="template-description"><?php echo htmlspecialchars(substr($template['description'], 0, 100)) . '...'; ?></p>
                            
                            <div class="template-meta">
                                <span class="badge bg-primary"><?php echo $categories[$template['category']] ?? $template['category']; ?></span>
                                <span class="badge niche-badge bg-secondary"><?php echo $niches[$template['niche']] ?? $template['niche']; ?></span>
                                <?php if (!empty($template['video_tutorial_url'])): ?>
                                    <span class="badge bg-info"><i class="bi bi-play-circle me-1"></i>Video</span>
                                <?php endif; ?>
                                <?php if (!empty($template['course_duration'])): ?>
                                    <span class="duration-badge"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($template['course_duration']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($template['original_product_link'])): ?>
                                <div class="mb-2">
                                    <a href="<?php echo htmlspecialchars($template['original_product_link']); ?>" 
                                       target="_blank" 
                                       class="text-decoration-none small">
                                        <i class="bi bi-link-45deg me-1"></i>Original-Produkt
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="template-actions">
                            <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editTemplate(<?php echo htmlspecialchars(json_encode($template)); ?>)">
                                <i class="bi bi-pencil me-1"></i>Bearbeiten
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="window.open('<?php echo htmlspecialchars($template['preview_url']); ?>', '_blank')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($templates)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e1;"></i>
                <h3 class="mt-3 text-muted">Keine Templates vorhanden</h3>
                <p class="text-muted">Erstelle dein erstes Template mit dem Button oben.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Neues Template erstellen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="templateForm">
                    <div class="modal-body">
                        <input type="hidden" name="template_id" id="template_id">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-bold">Template-Name *</label>
                                <input type="text" class="form-control" name="name" id="name" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Status *</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="active">Aktiv</option>
                                    <option value="draft">Entwurf</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Beschreibung *</label>
                            <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kategorie *</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Kategorie wählen...</option>
                                    <?php foreach ($categories as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nische *</label>
                                <select class="form-select" name="niche" id="niche" required>
                                    <option value="">Nische wählen...</option>
                                    <?php foreach ($niches as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preview URL *</label>
                            <input type="url" class="form-control" name="preview_url" id="preview_url" required placeholder="https://...">
                            <small class="text-muted">URL zur Live-Vorschau des Templates</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Download URL *</label>
                            <input type="url" class="form-control" name="download_url" id="download_url" required placeholder="https://...">
                            <small class="text-muted">URL zum Download des Template-Pakets</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Video Tutorial URL</label>
                            <input type="url" class="form-control" name="video_tutorial_url" id="video_tutorial_url" placeholder="https://...">
                            <small class="text-muted">Optional: URL zum Erklärvideo</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Product Mockup URL</label>
                            <input type="url" class="form-control" name="product_mockup_url" id="product_mockup_url" placeholder="https://...">
                            <small class="text-muted">Optional: URL zum Produkt-Mockup Bild</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Videokurs-Dauer</label>
                            <input type="text" class="form-control" name="course_duration" id="course_duration" placeholder="z.B. 2 Stunden, 45 Minuten">
                            <small class="text-muted">Optional: Dauer des Videokurses</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Original-Produkt Link</label>
                            <input type="url" class="form-control" name="original_product_link" id="original_product_link" placeholder="https://...">
                            <small class="text-muted">Optional: Link zum Original-Produkt (falls Template von eigenem Produkt)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" name="save_template" class="btn btn-primary-custom btn-custom">
                            <i class="bi bi-save me-2"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Template löschen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="template_id" id="delete_template_id">
                        <p>Möchtest du das Template "<strong id="delete_template_name"></strong>" wirklich löschen?</p>
                        <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Diese Aktion kann nicht rückgängig gemacht werden!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" name="delete_template" class="btn btn-danger btn-custom">
                            <i class="bi bi-trash me-2"></i>Löschen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        function openCreateModal() {
            document.getElementById('templateForm').reset();
            document.getElementById('template_id').value = '';
            document.getElementById('modalTitle').textContent = 'Neues Template erstellen';
            templateModal.show();
        }

        function editTemplate(template) {
            document.getElementById('template_id').value = template.id;
            document.getElementById('name').value = template.name;
            document.getElementById('description').value = template.description;
            document.getElementById('category').value = template.category;
            document.getElementById('niche').value = template.niche;
            document.getElementById('preview_url').value = template.preview_url;
            document.getElementById('download_url').value = template.download_url;
            document.getElementById('video_tutorial_url').value = template.video_tutorial_url || '';
            document.getElementById('product_mockup_url').value = template.product_mockup_url || '';
            document.getElementById('course_duration').value = template.course_duration || '';
            document.getElementById('original_product_link').value = template.original_product_link || '';
            document.getElementById('status').value = template.status;
            document.getElementById('modalTitle').textContent = 'Template bearbeiten';
            templateModal.show();
        }

        function deleteTemplate(id, name) {
            document.getElementById('delete_template_id').value = id;
            document.getElementById('delete_template_name').textContent = name;
            deleteModal.show();
        }

        // Filter und Suche
        const searchInput = document.getElementById('searchInput');
        const filterCategory = document.getElementById('filterCategory');
        const filterNiche = document.getElementById('filterNiche');

        function filterTemplates() {
            const searchTerm = searchInput.value.toLowerCase();
            const categoryFilter = filterCategory.value;
            const nicheFilter = filterNiche.value;
            const items = document.querySelectorAll('.template-item');

            items.forEach(item => {
                const name = item.dataset.name.toLowerCase();
                const category = item.dataset.category;
                const niche = item.dataset.niche;

                const matchesSearch = name.includes(searchTerm);
                const matchesCategory = !categoryFilter || category === categoryFilter;
                const matchesNiche = !nicheFilter || niche === nicheFilter;

                if (matchesSearch && matchesCategory && matchesNiche) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterTemplates);
        filterCategory.addEventListener('change', filterTemplates);
        filterNiche.addEventListener('change', filterTemplates);
    </script>
</body>
</html>