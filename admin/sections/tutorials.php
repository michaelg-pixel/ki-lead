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
                                            <?php if (!empty($video['mockup_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($video['mockup_image']); ?>" alt="Mockup" class="mockup-image">
                                                <div class="mockup-badge">📱 Mit Mockup</div>
                                            <?php endif; ?>
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
                                            <button class="action-btn action-btn-view" onclick="previewVideo('<?php echo htmlspecialchars($video['vimeo_url']); ?>', '<?php echo htmlspecialchars($video['title']); ?>')" title="Video-Vorschau">
                                                👁️
                                            </button>
                                            <button class="action-btn action-btn-edit" onclick="editVideo(<?php echo $video['id']; ?>)" title="Video bearbeiten">
                                                ✏️
                                            </button>
                                            <button class="action-btn action-btn-delete" onclick="deleteVideo(<?php echo $video['id']; ?>)" title="Video löschen">
                                                🗑️
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
                                <button class="action-btn action-btn-edit" onclick="editCategory(<?php echo $category['id']; ?>)" title="Kategorie bearbeiten">
                                    ✏️
                                </button>
                                <button class="action-btn action-btn-delete" onclick="deleteCategory(<?php echo $category['id']; ?>)" title="Kategorie löschen">
                                    🗑️
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
    <div class="modal-overlay" onclick="closeVideoModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="videoModalTitle">Video hinzufügen</h3>
            <button class="modal-close" onclick="closeVideoModal()">&times;</button>
        </div>
        <form id="videoForm" class="modal-body" enctype="multipart/form-data">
            <input type="hidden" id="videoId" name="id">
            <input type="hidden" id="currentMockup" name="current_mockup">
            <input type="hidden" id="deleteMockup" name="delete_mockup" value="0">
            
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
                <label>Mockup-Bild (optional) 📱</label>
                <div id="mockupPreviewContainer" class="mockup-preview-container" style="display: none;">
                    <img id="mockupPreview" src="" alt="Mockup Vorschau" class="mockup-preview-image">
                    <button type="button" class="btn-remove-mockup" onclick="removeMockup()">
                        <i class="fas fa-times"></i> Mockup entfernen
                    </button>
                </div>
                <input type="file" id="mockupImage" name="mockup_image" accept="image/*" class="form-control" onchange="previewMockupImage(this)">
                <small>Optional: Lade ein Mockup-Bild hoch (JPG, PNG, GIF, WebP). Wird im Customer Dashboard angezeigt.</small>
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
                    <span>Video ist aktiv (für Kunden sichtbar)</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeVideoModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Video speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-overlay" onclick="closeCategoryModal()"></div>
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
                <small>Siehe <a href="https://fontawesome.com/icons" target="_blank" style="color: #a855f7;">Font Awesome Icons</a></small>
            </div>

            <div class="form-group">
                <label>Sortierung</label>
                <input type="number" id="categorySortOrder" name="sort_order" value="0" class="form-control">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeCategoryModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Kategorie speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-overlay" onclick="closePreviewModal()"></div>
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
/* VERBESSERTE FARBEN & KONTRASTE */

/* Tabs */
.tabs-container {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid rgba(168, 85, 247, 0.3);
}

.tab-btn {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: #a0a0a0;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn:hover {
    color: #c084fc;
}

.tab-btn.active {
    color: #a855f7;
    border-bottom-color: #a855f7;
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
    border-bottom: 2px solid rgba(168, 85, 247, 0.3);
}

.category-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-info i {
    font-size: 24px;
    color: #a855f7;
}

.category-info h5 {
    font-size: 18px;
    color: white;
    margin: 0;
}

.video-count {
    background: rgba(168, 85, 247, 0.2);
    color: #c084fc;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid rgba(168, 85, 247, 0.3);
}

/* Videos Grid */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.video-card {
    background: rgba(26, 26, 46, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.video-card:hover {
    border-color: #a855f7;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(168, 85, 247, 0.3);
}

.video-thumbnail {
    aspect-ratio: 16/9;
    background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
    position: relative;
    overflow: hidden;
}

.video-thumbnail .mockup-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
}

.video-thumbnail .fa-play-circle {
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
}

.mockup-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(168, 85, 247, 0.9);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    z-index: 3;
    backdrop-filter: blur(8px);
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
    color: #b0b0b0;
    margin-bottom: 12px;
    line-height: 1.4;
}

.video-meta {
    display: flex;
    gap: 8px;
    align-items: center;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid;
}

.status-badge.active {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border-color: rgba(34, 197, 94, 0.4);
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.4);
}

/* Action Buttons - konsistent mit globalem Theme */
.video-actions,
.category-actions {
    display: flex;
    gap: 8px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.3);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
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
    background: rgba(26, 26, 46, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 12px;
    transition: all 0.2s;
}

.category-item:hover {
    border-color: #a855f7;
    box-shadow: 0 4px 12px rgba(168, 85, 247, 0.2);
}

.category-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
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
    color: #b0b0b0;
    margin-bottom: 4px;
}

.category-slug {
    font-size: 11px;
    color: #888;
    font-family: monospace;
}

.category-stats {
    display: flex;
    gap: 8px;
}

.stat-badge {
    background: rgba(168, 85, 247, 0.2);
    color: #c084fc;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid rgba(168, 85, 247, 0.3);
}

/* Mockup Preview Styles */
.mockup-preview-container {
    margin-bottom: 12px;
    position: relative;
    border: 2px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.3);
}

.mockup-preview-image {
    width: 100%;
    max-height: 200px;
    object-fit: contain;
    border-radius: 4px;
    margin-bottom: 8px;
}

.btn-remove-mockup {
    width: 100%;
    padding: 8px;
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.btn-remove-mockup:hover {
    background: rgba(239, 68, 68, 0.3);
    border-color: rgba(239, 68, 68, 0.5);
}

/* VERBESSERTE MODAL STYLES */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    padding: 20px;
    overflow-y: auto;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(4px);
    z-index: -1;
}

.modal-content {
    background: rgba(26, 26, 46, 0.98);
    border: 1px solid rgba(168, 85, 247, 0.4);
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    position: relative;
    z-index: 1;
}

.modal-content.modal-large {
    max-width: 900px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid rgba(168, 85, 247, 0.2);
}

.modal-header h3 {
    font-size: 20px;
    color: white;
    margin: 0;
}

.modal-close {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    font-size: 24px;
    color: #f87171;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
    flex-shrink: 0;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.25);
    border-color: rgba(239, 68, 68, 0.5);
    transform: scale(1.1);
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
    border-top: 1px solid rgba(168, 85, 247, 0.2);
}

/* VERBESSERTE FORM STYLES */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #e0e0e0;
    font-weight: 600;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control::placeholder {
    color: #666;
}

.form-control:focus {
    outline: none;
    border-color: #a855f7;
    background: rgba(0, 0, 0, 0.5);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #888;
    font-size: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    color: #e0e0e0;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #a855f7;
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
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
    
    .modal-content {
        max-width: 100%;
        margin: 0;
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
        document.getElementById('mockupPreviewContainer').style.display = 'none';
        document.getElementById('deleteMockup').value = '0';
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeVideoModal() {
    document.getElementById('videoModal').classList.remove('active');
    document.body.style.overflow = '';
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
            
            // Mockup-Vorschau anzeigen falls vorhanden
            if (data.video.mockup_image) {
                document.getElementById('currentMockup').value = data.video.mockup_image;
                document.getElementById('mockupPreview').src = data.video.mockup_image;
                document.getElementById('mockupPreviewContainer').style.display = 'block';
            } else {
                document.getElementById('mockupPreviewContainer').style.display = 'none';
            }
            document.getElementById('deleteMockup').value = '0';
        }
    } catch (error) {
        console.error('Fehler beim Laden:', error);
        alert('Fehler beim Laden des Videos');
    }
}

// Mockup Vorschau
function previewMockupImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('mockupPreview').src = e.target.result;
            document.getElementById('mockupPreviewContainer').style.display = 'block';
            document.getElementById('deleteMockup').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeMockup() {
    if (confirm('Möchtest du das Mockup wirklich entfernen?')) {
        document.getElementById('mockupImage').value = '';
        document.getElementById('mockupPreviewContainer').style.display = 'none';
        document.getElementById('deleteMockup').value = '1';
        document.getElementById('currentMockup').value = '';
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
    document.body.style.overflow = 'hidden';
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';
    modal.classList.remove('active');
    document.body.style.overflow = '';
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
    document.body.style.overflow = 'hidden';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
    document.body.style.overflow = '';
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
