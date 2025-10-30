<?php
// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}
?>

<style>
    .progress-container {
        padding: 32px;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin-bottom: 8px;
    }
    
    .page-subtitle {
        font-size: 14px;
        color: #888;
    }
    
    .progress-card {
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 24px;
    }
    
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .stat-item {
        text-align: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 12px;
    }
    
    .stat-value {
        font-size: 36px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 8px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #888;
    }
    
    .progress-section {
        margin-bottom: 32px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: white;
        margin-bottom: 16px;
    }
    
    .progress-item {
        background: rgba(0, 0, 0, 0.3);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 16px;
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .progress-name {
        font-size: 16px;
        font-weight: 500;
        color: white;
    }
    
    .progress-percent {
        font-size: 14px;
        font-weight: 600;
        color: #667eea;
    }
    
    .progress-bar-container {
        height: 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
        transition: width 0.3s;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-icon {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .empty-title {
        font-size: 20px;
        font-weight: 600;
        color: white;
        margin-bottom: 8px;
    }
    
    .empty-text {
        font-size: 14px;
        color: #888;
    }
    
    /* Responsive Styles */
    @media (max-width: 1024px) {
        .stat-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }
    }
    
    @media (max-width: 768px) {
        .progress-container {
            padding: 24px 16px;
        }
        
        .page-title {
            font-size: 24px;
        }
        
        .progress-card {
            padding: 24px 20px;
        }
        
        .stat-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .stat-item {
            padding: 16px;
        }
        
        .stat-value {
            font-size: 28px;
        }
        
        .progress-section {
            margin-bottom: 24px;
        }
        
        .empty-state {
            padding: 40px 16px;
        }
        
        .empty-icon {
            font-size: 48px;
        }
    }
    
    @media (max-width: 480px) {
        .progress-container {
            padding: 16px 12px;
        }
        
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-title {
            font-size: 20px;
        }
        
        .page-subtitle {
            font-size: 13px;
        }
        
        .progress-card {
            padding: 20px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        
        .stat-item {
            padding: 12px;
        }
        
        .stat-value {
            font-size: 24px;
        }
        
        .stat-label {
            font-size: 12px;
        }
        
        .section-title {
            font-size: 16px;
        }
        
        .progress-item {
            padding: 16px;
        }
        
        .progress-name {
            font-size: 14px;
        }
        
        .progress-percent {
            font-size: 13px;
        }
        
        .empty-title {
            font-size: 18px;
        }
        
        .empty-text {
            font-size: 13px;
        }
    }
</style>

<div class="progress-container">
    <div class="page-header">
        <h1 class="page-title">📈 Mein Fortschritt</h1>
        <p class="page-subtitle">Verfolgen Sie Ihren Lernfortschritt</p>
    </div>
    
    <div class="progress-card">
        <div class="stat-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['courses'] ?? 0; ?></div>
                <div class="stat-label">Verfügbare Kurse</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['my_freebies'] ?? 0; ?></div>
                <div class="stat-label">Meine Freebies</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">0</div>
                <div class="stat-label">Abgeschlossen</div>
            </div>
        </div>
        
        <div class="progress-section">
            <h3 class="section-title">Kursfortschritt</h3>
            
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <div class="empty-title">Noch kein Fortschritt vorhanden</div>
                <div class="empty-text">Beginnen Sie mit einem Kurs, um Ihren Fortschritt zu verfolgen.</div>
            </div>
        </div>
    </div>
</div>