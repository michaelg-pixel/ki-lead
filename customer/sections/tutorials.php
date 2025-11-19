<?php
// Kategorien laden
$stmt = $pdo->query("SELECT * FROM tutorial_categories ORDER BY sort_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nur aktive Tutorials laden mit Kategorie-Info
$stmt = $pdo->query("
    SELECT t.*, tc.name as category_name, tc.slug as category_slug, tc.icon as category_icon
    FROM tutorials t
    LEFT JOIN tutorial_categories tc ON t.category_id = tc.id
    WHERE t.is_active = 1
    ORDER BY tc.sort_order, t.sort_order
");
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nach Kategorie gruppieren
$grouped = [];
foreach ($tutorials as $tutorial) {
    $grouped[$tutorial['category_id']][] = $tutorial;
}
?>

<style>
    .tutorials-container {
        padding: 32px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .tutorials-header {
        margin-bottom: 32px;
    }
    
    .tutorials-header h1 {
        font-size: 32px;
        color: white;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .tutorials-header p {
        color: #888;
        font-size: 16px;
    }
    
    .category-section {
        margin-bottom: 48px;
    }
    
    .category-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 24px;
        border-radius: 16px 16px 0 0;
        margin-bottom: 0;
    }
    
    .category-header h2 {
        font-size: 24px;
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    
    .category-header p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
    }
    
    .videos-slider-wrapper {
        position: relative;
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border-radius: 0 0 16px 16px;
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-top: none;
    }
    
    .videos-grid {
        display: flex;
        gap: 24px;
        padding: 24px;
        overflow-x: auto;
        overflow-y: hidden;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Scrollbar Styling */
    .videos-grid::-webkit-scrollbar {
        height: 10px;
    }
    
    .videos-grid::-webkit-scrollbar-track {
        background: rgba(102, 126, 234, 0.1);
        border-radius: 5px;
    }
    
    .videos-grid::-webkit-scrollbar-thumb {
        background: rgba(102, 126, 234, 0.5);
        border-radius: 5px;
    }
    
    .videos-grid::-webkit-scrollbar-thumb:hover {
        background: rgba(102, 126, 234, 0.7);
    }
    
    /* Scroll Arrow Buttons */
    .scroll-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(102, 126, 234, 0.2);
        border: 2px solid rgba(102, 126, 234, 0.4);
        color: rgba(255, 255, 255, 0.6);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: all 0.3s ease;
        font-size: 28px;
        backdrop-filter: blur(10px);
        opacity: 0;
        pointer-events: none;
    }
    
    .videos-slider-wrapper:hover .scroll-arrow {
        opacity: 1;
        pointer-events: all;
    }
    
    .scroll-arrow:hover {
        background: rgba(102, 126, 234, 0.5);
        border-color: rgba(102, 126, 234, 0.8);
        color: white;
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }
    
    .scroll-arrow:active {
        transform: translateY(-50%) scale(0.95);
    }
    
    .scroll-arrow.left {
        left: 16px;
    }
    
    .scroll-arrow.right {
        right: 16px;
    }
    
    .scroll-arrow.hidden {
        display: none;
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: translateY(-50%) scale(1);
        }
        50% {
            transform: translateY(-50%) scale(1.05);
        }
    }
    
    .scroll-arrow.pulse {
        animation: pulse 2s infinite;
    }
    
    .video-card {
        background: #1a1a2e;
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
        cursor: pointer;
        min-width: 320px;
        max-width: 320px;
        flex-shrink: 0;
    }
    
    .video-card:hover {
        transform: translateY(-4px);
        border-color: #667eea;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .video-thumbnail {
        aspect-ratio: 16/9;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
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
    
    .video-thumbnail::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.3);
        transition: all 0.3s;
        z-index: 1;
    }
    
    .video-card:hover .video-thumbnail::before {
        background: rgba(0, 0, 0, 0.5);
    }
    
    .play-icon {
        font-size: 60px;
        color: white;
        position: relative;
        z-index: 2;
        transition: all 0.3s;
        filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.6));
    }
    
    .video-card:hover .play-icon {
        transform: scale(1.15);
    }
    
    .video-info {
        padding: 20px;
    }
    
    .video-info h3 {
        font-size: 18px;
        color: white;
        margin-bottom: 8px;
        line-height: 1.4;
    }
    
    .video-info p {
        font-size: 14px;
        color: #888;
        line-height: 1.5;
        margin-bottom: 12px;
    }
    
    .video-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #666;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 32px;
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border-radius: 16px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        font-size: 24px;
        color: white;
        margin-bottom: 8px;
    }
    
    .empty-state p {
        color: #888;
        font-size: 16px;
    }
    
    /* Video Modal */
    .video-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 10000;
        padding: 20px;
        overflow-y: auto;
    }
    
    .video-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .video-modal-content {
        width: 100%;
        max-width: 1200px;
        position: relative;
    }
    
    .video-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .video-modal-title {
        font-size: 24px;
        color: white;
        font-weight: 600;
    }
    
    .video-modal-close {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: white;
        font-size: 32px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .video-modal-close:hover {
        background: rgba(255, 107, 107, 0.3);
        color: #ff6b6b;
    }
    
    .video-container {
        aspect-ratio: 16/9;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .video-container iframe {
        width: 100%;
        height: 100%;
    }
    
    .help-section {
        margin-top: 48px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border-radius: 16px;
        padding: 32px;
        display: flex;
        align-items: center;
        gap: 24px;
    }
    
    .help-icon {
        font-size: 48px;
        flex-shrink: 0;
    }
    
    .help-content {
        flex: 1;
    }
    
    .help-content h3 {
        font-size: 24px;
        color: white;
        margin-bottom: 8px;
    }
    
    .help-content p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 16px;
    }
    
    .help-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: #2563eb;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .help-button:hover {
        background: #f0f0f0;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .tutorials-container {
            padding: 16px;
        }
        
        .tutorials-header h1 {
            font-size: 24px;
        }
        
        .videos-grid {
            gap: 16px;
            padding: 16px;
        }
        
        .video-card {
            min-width: 280px;
            max-width: 280px;
        }
        
        .scroll-arrow {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }
        
        .scroll-arrow.left {
            left: 8px;
        }
        
        .scroll-arrow.right {
            right: 8px;
        }
        
        .category-header {
            padding: 20px;
        }
        
        .category-header h2 {
            font-size: 20px;
        }
        
        .video-modal-content {
            max-width: 100%;
        }
        
        .video-modal-title {
            font-size: 18px;
        }
        
        .help-section {
            flex-direction: column;
            text-align: center;
            padding: 24px;
        }
        
        .help-icon {
            font-size: 40px;
        }
        
        .help-content h3 {
            font-size: 20px;
        }
    }
</style>

<div class="tutorials-container">
    <!-- Header -->
    <div class="tutorials-header">
        <h1>
            <span>📖</span>
            Anleitungen & Tutorials
        </h1>
        <p>Lerne, wie du das KI Lead-System optimal nutzt</p>
    </div>

    <!-- Tutorials Content -->
    <?php if (empty($tutorials)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🎥</div>
            <h3>Noch keine Anleitungen verfügbar</h3>
            <p>Schau bald wieder vorbei! Neue Tutorials werden regelmäßig hinzugefügt.</p>
        </div>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <?php if (isset($grouped[$category['id']])): ?>
                <div class="category-section">
                    <div class="category-header">
                        <h2>
                            <i class="fas fa-<?= htmlspecialchars($category['icon']) ?>"></i>
                            <?= htmlspecialchars($category['name']) ?>
                        </h2>
                        <?php if ($category['description']): ?>
                            <p><?= htmlspecialchars($category['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="videos-slider-wrapper">
                        <div class="videos-grid" data-category-id="<?= $category['id'] ?>">
                            <?php foreach ($grouped[$category['id']] as $tutorial): ?>
                                <div class="video-card" onclick="playVideo('<?= htmlspecialchars($tutorial['vimeo_url']) ?>', '<?= htmlspecialchars($tutorial['title']) ?>')">
                                    <div class="video-thumbnail">
                                        <?php if (!empty($tutorial['mockup_image'])): ?>
                                            <img src="<?= htmlspecialchars($tutorial['mockup_image']) ?>" alt="<?= htmlspecialchars($tutorial['title']) ?>" class="mockup-image">
                                        <?php endif; ?>
                                        <div class="play-icon">▶</div>
                                    </div>
                                    <div class="video-info">
                                        <h3><?= htmlspecialchars($tutorial['title']) ?></h3>
                                        <?php if ($tutorial['description']): ?>
                                            <p><?= htmlspecialchars($tutorial['description']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($tutorial['duration']): ?>
                                            <div class="video-meta">
                                                <i class="fas fa-clock"></i>
                                                <?= htmlspecialchars($tutorial['duration']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Scroll Arrows -->
                        <div class="scroll-arrow left" onclick="scrollSlider(<?= $category['id'] ?>, 'left')">
                            ‹
                        </div>
                        <div class="scroll-arrow right pulse" onclick="scrollSlider(<?= $category['id'] ?>, 'right')">
                            ›
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="help-section">
        <div class="help-icon">📞</div>
        <div class="help-content">
            <h3>Brauchst du weitere Hilfe?</h3>
            <p>Unser Support-Team steht dir jederzeit zur Verfügung.</p>
            <a href="mailto:support@ki-leadsystem.com" class="help-button">
                <i class="fas fa-envelope"></i>
                Support kontaktieren
            </a>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div id="videoModal" class="video-modal" onclick="closeVideoIfClickOutside(event)">
    <div class="video-modal-content">
        <div class="video-modal-header">
            <h3 id="videoTitle" class="video-modal-title"></h3>
            <button class="video-modal-close" onclick="closeVideo(event)">×</button>
        </div>
        <div id="videoContainer" class="video-container">
            <!-- Vimeo iframe wird hier eingefügt -->
        </div>
    </div>
</div>

<script src="https://player.vimeo.com/api/player.js"></script>
<script>
    function playVideo(vimeoUrl, title) {
        const modal = document.getElementById('videoModal');
        const container = document.getElementById('videoContainer');
        const titleElement = document.getElementById('videoTitle');
        
        titleElement.textContent = title;
        container.innerHTML = `<iframe src="${vimeoUrl}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeVideo(event) {
        if (event) {
            event.stopPropagation();
        }
        
        const modal = document.getElementById('videoModal');
        const container = document.getElementById('videoContainer');
        
        container.innerHTML = '';
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function closeVideoIfClickOutside(event) {
        if (event.target.id === 'videoModal') {
            closeVideo();
        }
    }
    
    // Scroll Slider Function
    function scrollSlider(categoryId, direction) {
        const slider = document.querySelector(`[data-category-id="${categoryId}"]`);
        if (!slider) return;
        
        const scrollAmount = 350; // Scroll-Distanz in Pixeln
        const currentScroll = slider.scrollLeft;
        
        if (direction === 'left') {
            slider.scrollTo({
                left: currentScroll - scrollAmount,
                behavior: 'smooth'
            });
        } else {
            slider.scrollTo({
                left: currentScroll + scrollAmount,
                behavior: 'smooth'
            });
        }
        
        // Update arrow visibility after scroll
        setTimeout(() => updateArrowVisibility(slider), 300);
    }
    
    // Update Arrow Visibility based on scroll position
    function updateArrowVisibility(slider) {
        const wrapper = slider.parentElement;
        const leftArrow = wrapper.querySelector('.scroll-arrow.left');
        const rightArrow = wrapper.querySelector('.scroll-arrow.right');
        
        if (!leftArrow || !rightArrow) return;
        
        const isAtStart = slider.scrollLeft <= 10;
        const isAtEnd = slider.scrollLeft >= slider.scrollWidth - slider.clientWidth - 10;
        
        // Hide/show arrows based on position
        if (isAtStart) {
            leftArrow.classList.add('hidden');
        } else {
            leftArrow.classList.remove('hidden');
        }
        
        if (isAtEnd) {
            rightArrow.classList.add('hidden');
            rightArrow.classList.remove('pulse');
        } else {
            rightArrow.classList.remove('hidden');
        }
        
        // Remove pulse animation after first interaction
        if (slider.scrollLeft > 0) {
            rightArrow.classList.remove('pulse');
        }
    }
    
    // Initialize arrow visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        const sliders = document.querySelectorAll('.videos-grid');
        sliders.forEach(slider => {
            updateArrowVisibility(slider);
            
            // Update on scroll
            slider.addEventListener('scroll', () => {
                updateArrowVisibility(slider);
            });
        });
    });
    
    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVideo();
        }
    });
</script>
