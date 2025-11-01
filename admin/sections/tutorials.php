<?php
// Admin Tutorials Management
$pdo = getDBConnection();

// Kategorien laden
$categories = $pdo->query("SELECT * FROM tutorial_categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Alle Videos laden mit Kategorie-Info
$videos = $pdo->query("
    SELECT t.*, tc.name as category_name, tc.slug as category_slug 
    FROM tutorials t 
    LEFT JOIN tutorial_categories tc ON t.category_id = tc.id 
    ORDER BY tc.sort_order, t.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// Nach Kategorien gruppieren
$grouped_videos = [];
foreach ($videos as $video) {
    $grouped_videos[$video['category_id']][] = $video;
}
?>

<div class="tutorials-admin">
    <!-- Header mit Tabs -->
    <div class="section-header">
        <h3 class="section-title">📖 Anleitungen & Tutorials verwalten</h3>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs-container">
        <button class="tab-btn active" data-tab="videos">
            <i class="fas fa-video"></i> Videos verwalten
        </button>
        <button class="tab-btn" data-tab="categories">
            <i class="fas fa-folder"></i> Kategorien verwalten
        </button>
    </div>

    <!-- Videos Tab -->
    <div class="tab-content active" id="videos-tab">
        <div class="section">
            <div class="section-header">
                <h4 class="section-subtitle">Video-Bibliothek</h4>
                <button class="btn btn-primary" onclick="openVideoModal()">
                    <i class="fas fa-plus"></i> Neues Video hinzufügen
                </button>
            </div>

            <?php if (count($videos) > 0): ?>
                <?php foreach ($categories as $category): ?>
                    <?php if (isset($grouped_videos[$category['id']])): ?>
                        <div class="category-section">
                            <div class="category-header">
                                <div class="category-info">
                                    <i class="fas fa-<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                    <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <span class="video-count"><?php echo count($grouped_videos[$category['id']]); ?> Videos</span>
                                </div>
                            </div>

                            <div class="videos-grid">
                                <?php foreach ($grouped_videos[$category['id']] as $video): ?>
                                    <div class="video-card">
                                        <div class="video-thumbnail">
                                            <i class="fas fa-play-circle"></i>
                                        </div>
                                        <div class="video-info">
                                            <h6><?php echo htmlspecialchars($video['title']); ?></h6>
                                            <p><?php echo htmlspecialchars($video['description'] ?? ''); ?></p>
                                            <div class="video-meta">
                                                <span class="status-badge <?php echo $video['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $video['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="video-actions">
                                            <button class="btn-icon" onclick="editVideo(<?php echo $video['id']; ?>)" title="Bearbeiten">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon" onclick="previewVideo('<?php echo htmlspecialchars($video['vimeo_url']); ?>', '<?php echo htmlspecialchars($video['title']); ?>')" title="Vorschau">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-danger" onclick="deleteVideo(<?php echo $video['id']; ?>)" title="Löschen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎥</div>
                    <p>Noch keine Videos vorhanden</p>
                    <button class="btn btn-primary" onclick="openVideoModal()">
                        Erstes Video hinzufügen
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Categories Tab -->
    <div class="tab-content" id="categories-tab">
        <div class="section">
            <div class="section-header">
                <h4 class="section-subtitle">Kategorien verwalten</h4>
                <button class="btn btn-primary" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Neue Kategorie
                </button>
            </div>

            <?php if (count($categories) > 0): ?>
                <div class="categories-list">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item">
                            <div class="category-icon">
                                <i class="fas fa-<?php echo htmlspecialchars($category['icon']); ?>"></i>
                            </div>
                            <div class="category-details">
                                <h6><?php echo htmlspecialchars($category['name']); ?></h6>
                                <p><?php echo htmlspecialchars($category['description'] ?? 'Keine Beschreibung'); ?></p>
                                <span class="category-slug">Slug: <?php echo htmlspecialchars($category['slug']); ?></span>
                            </div>
                            <div class="category-stats">
                                <span class="stat-badge">
                                    <?php echo isset($grouped_videos[$category['id']]) ? count($grouped_videos[$category['id']]) : 0; ?> Videos
                                </span>
                            </div>
                            <div class="category-actions">
                                <button class="btn-icon" onclick="editCategory(<?php echo $category['id']; ?>)" title="Bearbeiten">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)" title="Löschen">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📁</div>
                    <p>Noch keine Kategorien vorhanden</p>
                    <button class="btn btn-primary" onclick="openCategoryModal()">
                        Erste Kategorie erstellen
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div id="videoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="videoModalTitle">Video hinzufügen</h3>
            <button class="modal-close" onclick="closeVideoModal()">&times;</button>
        </div>
        <form id="videoForm" class="modal-body">
            <input type="hidden" id="videoId" name="id">
            
            <div class="form-group">
                <label>Titel *</label>
                <input type="text" id="videoTitle" name="title" required class="form-control">
            </div>

            <div class="form-group">
                <label>Beschreibung</label>
                <textarea id="videoDescription" name="description" rows="3" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label>Vimeo Video URL *</label>
                <input type="url" id="videoUrl" name="vimeo_url" required class="form-control" placeholder="https://player.vimeo.com/video/123456789">
                <small>Füge die Vimeo Player-URL ein (z.B. https://player.vimeo.com/video/123456789)</small>
            </div>

            <div class="form-group">
                <label>Kategorie *</label>
                <select id="videoCategory" name="category_id" required class="form-control">
                    <option value="">Kategorie wählen...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Sortierung</label>
                <input type="number" id="videoSortOrder" name="sort_order" value="0" class="form-control">
                <small>Niedrigere Zahlen werden zuerst angezeigt</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="videoActive" name="is_active" checked>
                    Video ist aktiv (für Kunden sichtbar)
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeVideoModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Video speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="categoryModalTitle">Kategorie erstellen</h3>
            <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <form id="categoryForm" class="modal-body">
            <input type="hidden" id="categoryId" name="id">
            
            <div class="form-group">
                <label>Name *</label>
                <input type="text" id="categoryName" name="name" required class="form-control">
            </div>

            <div class="form-group">
                <label>Slug *</label>
                <input type="text" id="categorySlug" name="slug" required class="form-control" pattern="[a-z0-9-]+" placeholder="z.b. erste-schritte">
                <small>Nur Kleinbuchstaben, Zahlen und Bindestriche (wird automatisch generiert)</small>
            </div>

            <div class="form-group">
                <label>Beschreibung</label>
                <textarea id="categoryDescription" name="description" rows="2" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label>Icon (Font Awesome)</label>
                <input type="text" id="categoryIcon" name="icon" value="video" class="form-control" placeholder="z.B. rocket, graduation-cap, star">
                <small>Siehe <a href="https://fontawesome.com/icons" target="_blank">Font Awesome Icons</a></small>
            </div>

            <div class="form-group">
                <label>Sortierung</label>
                <input type="number" id="categorySortOrder" name="sort_order" value="0" class="form-control">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Kategorie speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="previewTitle">Video Vorschau</h3>
            <button class="modal-close" onclick="closePreviewModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="previewContainer" class="video-preview-container"></div>
        </div>
    </div>
</div>

<style>
/* Tabs */
.tabs-container {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--border);
}

.tab-btn {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--text-secondary);
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn:hover {
    color: var(--primary);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Category Sections */
.category-section {
    margin-bottom: 32px;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--border);
}

.category-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-info i {
    font-size: 24px;
    color: var(--primary);
}

.category-info h5 {
    font-size: 18px;
    color: white;
    margin: 0;
}

.video-count {
    background: rgba(168, 85, 247, 0.2);
    color: var(--primary-light);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Videos Grid */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.video-card {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.video-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.video-thumbnail {
    aspect-ratio: 16/9;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
    cursor: pointer;
}

.video-info {
    padding: 16px;
}

.video-info h6 {
    font-size: 16px;
    color: white;
    margin-bottom: 8px;
}

.video-info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 12px;
    line-height: 1.4;
}

.video-meta {
    display: flex;
    gap: 8px;
    align-items: center;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.active {
    background: rgba(74, 222, 128, 0.2);
    color: var(--success);
}

.status-badge.inactive {
    background: rgba(251, 113, 133, 0.2);
    color: var(--danger);
}

.video-actions {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid var(--border);
}

/* Categories List */
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.category-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 12px;
    transition: all 0.2s;
}

.category-item:hover {
    border-color: var(--primary);
}

.category-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.category-details {
    flex: 1;
}

.category-details h6 {
    font-size: 16px;
    color: white;
    margin-bottom: 4px;
}

.category-details p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.category-slug {
    font-size: 11px;
    color: var(--text-muted);
    font-family: monospace;
}

.category-stats {
    display: flex;
    gap: 8px;
}

.stat-badge {
    background: rgba(168, 85, 247, 0.2);
    color: var(--primary-light);
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.category-actions {
    display: flex;
    gap: 8px;
}

/* Buttons */
.btn-icon {
    width: 36px;
    height: 36px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid var(--border);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-secondary);
}

.btn-icon:hover {
    background: rgba(168, 85, 247, 0.2);
    color: var(--primary-light);
}

.btn-icon.btn-danger:hover {
    background: rgba(251, 113, 133, 0.2);
    color: var(--danger);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content.modal-large {
    max-width: 900px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}

.modal-header h3 {
    font-size: 20px;
    color: white;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--text-secondary);
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(251, 113, 133, 0.2);
    color: var(--danger);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 20px;
    margin-top: 20px;
    border-top: 1px solid var(--border);
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-primary);
    font-weight: 500;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: var(--text-muted);
    font-size: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.video-preview-container {
    aspect-ratio: 16/9;
    background: black;
    border-radius: 8px;
    overflow: hidden;
}

.video-preview-container iframe {
    width: 100%;
    height: 100%;
}

/* Responsive */
@media (max-width: 768px) {
    .videos-grid {
        grid-template-columns: 1fr;
    }
    
    .category-item {
        flex-wrap: wrap;
    }
    
    .category-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
// Tab Switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tabName = btn.dataset.tab;
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(tabName + '-tab').classList.add('active');
    });
});

// Video Modal Functions
function openVideoModal(videoId = null) {
    const modal = document.getElementById('videoModal');
    const form = document.getElementById('videoForm');
    const title = document.getElementById('videoModalTitle');
    
    if (videoId) {
        title.textContent = 'Video bearbeiten';
        loadVideoData(videoId);
    } else {
        title.textContent = 'Video hinzufügen';
        form.reset();
        document.getElementById('videoActive').checked = true;
    }
    
    modal.classList.add('active');
}

function closeVideoModal() {
    document.getElementById('videoModal').classList.remove('active');
}

function editVideo(videoId) {
    openVideoModal(videoId);
}

async function loadVideoData(videoId) {
    try {
        const response = await fetch(`/admin/api/tutorials/get-video.php?id=${videoId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('videoId').value = data.video.id;
            document.getElementById('videoTitle').value = data.video.title;
            document.getElementById('videoDescription').value = data.video.description || '';
            document.getElementById('videoUrl').value = data.video.vimeo_url;
            document.getElementById('videoCategory').value = data.video.category_id;
            document.getElementById('videoSortOrder').value = data.video.sort_order;
            document.getElementById('videoActive').checked = data.video.is_active == 1;
        }
    } catch (error) {
        console.error('Fehler beim Laden:', error);
        alert('Fehler beim Laden des Videos');
    }
}

// Video Form Submit
document.getElementById('videoForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const videoId = document.getElementById('videoId').value;
    const url = videoId ? '/admin/api/tutorials/update-video.php' : '/admin/api/tutorials/create-video.php';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Video erfolgreich gespeichert!');
            closeVideoModal();
            location.reload();
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern des Videos');
    }
});

async function deleteVideo(videoId) {
    if (!confirm('Video wirklich löschen?')) return;
    
    try {
        const response = await fetch('/admin/api/tutorials/delete-video.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: videoId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Video gelöscht!');
            location.reload();
        } else {
            alert('Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Löschen');
    }
}

function previewVideo(vimeoUrl, title) {
    const modal = document.getElementById('previewModal');
    const container = document.getElementById('previewContainer');
    const titleEl = document.getElementById('previewTitle');
    
    titleEl.textContent = title;
    container.innerHTML = `<iframe src="${vimeoUrl}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
    
    modal.classList.add('active');
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';
    modal.classList.remove('active');
}

// Category Modal Functions
function openCategoryModal(categoryId = null) {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('categoryModalTitle');
    
    if (categoryId) {
        title.textContent = 'Kategorie bearbeiten';
        loadCategoryData(categoryId);
    } else {
        title.textContent = 'Kategorie erstellen';
        form.reset();
    }
    
    modal.classList.add('active');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

function editCategory(categoryId) {
    openCategoryModal(categoryId);
}

async function loadCategoryData(categoryId) {
    try {
        const response = await fetch(`/admin/api/tutorials/get-category.php?id=${categoryId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('categoryId').value = data.category.id;
            document.getElementById('categoryName').value = data.category.name;
            document.getElementById('categorySlug').value = data.category.slug;
            document.getElementById('categoryDescription').value = data.category.description || '';
            document.getElementById('categoryIcon').value = data.category.icon;
            document.getElementById('categorySortOrder').value = data.category.sort_order;
        }
    } catch (error) {
        console.error('Fehler beim Laden:', error);
        alert('Fehler beim Laden der Kategorie');
    }
}

// Auto-generate slug from name
document.getElementById('categoryName').addEventListener('input', (e) => {
    if (!document.getElementById('categoryId').value) {
        const slug = e.target.value
            .toLowerCase()
            .replace(/ä/g, 'ae')
            .replace(/ö/g, 'oe')
            .replace(/ü/g, 'ue')
            .replace(/ß/g, 'ss')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        document.getElementById('categorySlug').value = slug;
    }
});

// Category Form Submit
document.getElementById('categoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const categoryId = document.getElementById('categoryId').value;
    const url = categoryId ? '/admin/api/tutorials/update-category.php' : '/admin/api/tutorials/create-category.php';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Kategorie erfolgreich gespeichert!');
            closeCategoryModal();
            location.reload();
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern der Kategorie');
    }
});

async function deleteCategory(categoryId) {
    if (!confirm('Kategorie wirklich löschen? Alle zugehörigen Videos werden ebenfalls gelöscht!')) return;
    
    try {
        const response = await fetch('/admin/api/tutorials/delete-category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: categoryId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Kategorie gelöscht!');
            location.reload();
        } else {
            alert('Fehler beim Löschen: ' + (data.message || ''));
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Löschen');
    }
}

// Close modals on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeVideoModal();
        closeCategoryModal();
        closePreviewModal();
    }
});
</script>
