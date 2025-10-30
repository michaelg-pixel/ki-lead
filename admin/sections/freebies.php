<?php
// Freebie Templates aus Datenbank holen
$freebies = $pdo->query("SELECT * FROM freebies ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section">
    <div class="section-header">
        <h3 class="section-title">Freebie Templates</h3>
        <a href="?page=freebie-create" class="btn">+ Neues Template</a>
    </div>
    
    <?php if (count($freebies) > 0): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-top: 24px;">
        <?php foreach ($freebies as $freebie): ?>
        <div class="freebie-card">
            <div class="freebie-mockup">
                <?php if (!empty($freebie['mockup_image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" alt="Mockup">
                <?php else: ?>
                    <div class="no-mockup">📱</div>
                <?php endif; ?>
            </div>
            <div class="freebie-content">
                <h4><?php echo htmlspecialchars($freebie['name']); ?></h4>
                <p><?php echo htmlspecialchars($freebie['headline'] ?? 'Keine Headline'); ?></p>
                <div class="freebie-meta">
                    <span class="badge badge-admin">Master Template</span>
                    <span style="color: #888; font-size: 12px;">
                        <?php echo date('d.m.Y', strtotime($freebie['created_at'])); ?>
                    </span>
                </div>
                <div class="freebie-actions">
                    <a href="/freebie/<?php echo htmlspecialchars($freebie['unique_id']); ?>" 
                       class="btn-preview" 
                       target="_blank" 
                       title="Vorschau öffnen">
                        👁️ Vorschau
                    </a>
                    <a href="?page=freebie-edit&id=<?php echo $freebie['id']; ?>" class="btn-secondary">Bearbeiten</a>
                    <button onclick="deleteTemplate(<?php echo $freebie['id']; ?>, '<?php echo htmlspecialchars($freebie['name'], ENT_QUOTES); ?>')" class="btn-danger">Löschen</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎁</div>
        <p>Noch keine Freebie Templates erstellt</p>
        <a href="?page=freebie-create" class="btn" style="margin-top: 16px;">Erstes Template erstellen</a>
    </div>
    <?php endif; ?>
</div>

<style>
    .freebie-card {
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s;
    }
    
    .freebie-card:hover {
        transform: translateY(-4px);
    }
    
    .freebie-mockup {
        width: 100%;
        height: 200px;
        background: #0f0f1e;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .freebie-mockup img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .no-mockup {
        font-size: 48px;
        opacity: 0.3;
    }
    
    .freebie-content {
        padding: 20px;
    }
    
    .freebie-content h4 {
        color: white;
        font-size: 18px;
        margin-bottom: 8px;
    }
    
    .freebie-content p {
        color: #888;
        font-size: 14px;
        margin-bottom: 16px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .freebie-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .freebie-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    
    .btn-preview {
        grid-column: 1 / -1;
        padding: 10px 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-preview:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        padding: 8px 16px;
        background: rgba(102, 126, 234, 0.2);
        color: #667eea;
        border: 1px solid #667eea;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-secondary:hover {
        background: rgba(102, 126, 234, 0.3);
    }
    
    .btn-danger {
        padding: 8px 16px;
        background: rgba(255, 107, 107, 0.1);
        color: #ff6b6b;
        border: 1px solid #ff6b6b;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .btn-danger:hover {
        background: rgba(255, 107, 107, 0.2);
    }
</style>

<script>
// EINZIGE LÖSCHEN-FUNKTION - MIT RICHTIGEM PFAD
async function deleteTemplate(templateId, templateName) {
    // Bestätigung
    if (!confirm(`Möchten Sie das Template "${templateName}" wirklich löschen?\n\n⚠️ Diese Aktion kann nicht rückgängig gemacht werden!`)) {
        return;
    }
    
    try {
        // RICHTIGER PFAD: /api/delete-freebie.php
        const response = await fetch('/api/delete-freebie.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: templateId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Template erfolgreich gelöscht!');
            window.location.href = '?page=freebies&deleted=1';
        } else {
            alert('❌ Fehler beim Löschen:\n\n' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('❌ Netzwerkfehler:\n\n' + error.message);
        console.error('Delete error:', error);
    }
}
</script>