<?php
/**
 * Kundenverwaltung - Admin Dashboard
 * Mit Digistore24 Integration - RESPONSIVE OPTIMIERT
 */

// Kunden aus Datenbank laden
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

$query = "SELECT u.*, 
          GROUP_CONCAT(DISTINCT f.name SEPARATOR ', ') as assigned_freebies
          FROM users u
          LEFT JOIN user_freebies uf ON u.id = uf.user_id
          LEFT JOIN freebies f ON uf.freebie_id = f.id
          WHERE u.role = 'customer'";

$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.raw_code LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

if ($status !== 'all') {
    $query .= " AND u.is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Alle Freebie Templates laden (aus der richtigen Tabelle!)
$freebieTemplates = $pdo->query("SELECT id, name, headline FROM freebies ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ANGEPASSTE FARBEN - Basierend auf Screenshot */
:root {
    /* Hintergrundfarben - Dunklere violett-blaue Töne */
    --bg-primary: #0a0a16;
    --bg-secondary: #1a1532;
    --bg-tertiary: #252041;
    --bg-card: #2a2550;
    
    /* Primärfarben - Violett/Lila Töne */
    --primary: #a855f7;
    --primary-dark: #8b40d1;
    --primary-light: #c084fc;
    
    /* Akzentfarben */
    --accent: #f59e0b;
    --success: #4ade80;
    --success-dark: #22c55e;
    --danger: #fb7185;
    --danger-dark: #f43f5e;
    --warning: #fbbf24;
    
    /* Text-Farben */
    --text-primary: #e5e7eb;
    --text-secondary: #9ca3af;
    --text-muted: #6b7280;
    
    /* Borders */
    --border: rgba(168, 85, 247, 0.2);
    --border-light: rgba(255, 255, 255, 0.05);
}

.customers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    gap: 16px;
}

.search-bar {
    display: flex;
    gap: 12px;
    flex: 1;
    max-width: 600px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    min-width: 0;
}

.search-input::placeholder {
    color: var(--text-muted);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.status-filter {
    padding: 12px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    cursor: pointer;
    font-size: 14px;
    white-space: nowrap;
}

.status-filter:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.btn-primary {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    font-size: 14px;
    white-space: nowrap;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
}

.customers-table {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}

.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.customers-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.customers-table th {
    background: rgba(168, 85, 247, 0.05);
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px;
    text-align: left;
}

.customers-table td {
    padding: 16px;
    border-top: 1px solid var(--border-light);
    color: var(--text-primary);
    font-size: 14px;
}

.customers-table tbody tr {
    transition: background 0.2s;
}

.customers-table tbody tr:hover {
    background: rgba(168, 85, 247, 0.05);
}

.raw-code {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(168, 85, 247, 0.15);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: var(--primary-light);
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.status-active {
    background: rgba(74, 222, 128, 0.15);
    color: var(--success);
}

.status-inactive {
    background: rgba(251, 113, 133, 0.15);
    color: var(--danger);
}

.assigned-content {
    font-size: 12px;
    color: var(--text-secondary);
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.action-icons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid var(--border);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-secondary);
    font-size: 14px;
}

.action-btn:hover {
    background: rgba(168, 85, 247, 0.2);
    transform: translateY(-2px);
    color: var(--primary-light);
}

.action-btn.delete {
    color: var(--danger);
    background: rgba(251, 113, 133, 0.1);
    border-color: rgba(251, 113, 133, 0.2);
}

.action-btn.delete:hover {
    background: rgba(251, 113, 133, 0.2);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
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
    z-index: 10000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    padding: 20px;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 32px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modal-title {
    font-size: 24px;
    font-weight: 700;
    color: white;
}

.modal-close {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(251, 113, 133, 0.1);
    border: 1px solid rgba(251, 113, 133, 0.2);
    border-radius: 6px;
    cursor: pointer;
    color: var(--danger);
    font-size: 20px;
    transition: all 0.2s;
    flex-shrink: 0;
}

.modal-close:hover {
    background: rgba(251, 113, 133, 0.2);
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 600;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(168, 85, 247, 0.05);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(168, 85, 247, 0.1);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-secondary {
    padding: 12px 24px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--primary-light);
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    flex: 1;
}

.btn-secondary:hover {
    background: rgba(168, 85, 247, 0.2);
}

.btn-danger {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    flex: 1;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(251, 113, 133, 0.4);
}

/* ============================================
   RESPONSIVE DESIGN - MOBILE OPTIMIERUNG
   ============================================ */

/* Tablets */
@media (max-width: 1024px) {
    .customers-header {
        flex-wrap: wrap;
    }
    
    .search-bar {
        max-width: 100%;
        order: 2;
        width: 100%;
    }
    
    .btn-primary {
        order: 1;
        width: 100%;
        justify-content: center;
    }
}

/* Mobile Geräte */
@media (max-width: 768px) {
    .customers-header {
        flex-direction: column;
        gap: 12px;
    }
    
    .search-bar {
        flex-direction: column;
        width: 100%;
    }
    
    .search-input,
    .status-filter {
        width: 100%;
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
    
    /* Tabelle scrollbar machen auf Mobile */
    .table-wrapper {
        margin: 0 -16px;
        padding: 0 16px;
    }
    
    .customers-table table {
        font-size: 12px;
    }
    
    .customers-table th,
    .customers-table td {
        padding: 12px 8px;
    }
    
    .action-icons {
        flex-direction: column;
        gap: 4px;
    }
    
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    
    /* Modal Anpassungen */
    .modal-content {
        padding: 24px 20px;
        margin: 0 10px;
    }
    
    .modal-title {
        font-size: 20px;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .btn-secondary,
    .btn-primary,
    .btn-danger {
        width: 100%;
    }
}

/* Sehr kleine Mobile Geräte */
@media (max-width: 480px) {
    .customers-table {
        border-radius: 8px;
        font-size: 11px;
    }
    
    .raw-code {
        font-size: 10px;
        padding: 4px 8px;
    }
    
    .status-badge {
        font-size: 10px;
        padding: 4px 8px;
    }
    
    .assigned-content {
        font-size: 11px;
        max-width: 120px;
    }
    
    .modal {
        padding: 10px;
    }
    
    .modal-content {
        padding: 20px 16px;
        border-radius: 12px;
    }
}

/* Landscape Modus auf kleinen Geräten */
@media (max-height: 600px) and (max-width: 768px) {
    .modal-content {
        max-height: 95vh;
        padding: 20px;
    }
}
</style>

<div class="customers-header">
    <div class="search-bar">
        <input type="text" 
               class="search-input" 
               placeholder="Suche nach Name, E-Mail oder RAW-Code..." 
               id="searchInput"
               value="<?php echo htmlspecialchars($search); ?>">
        <select class="status-filter" id="statusFilter">
            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Alle Status</option>
            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Aktiv</option>
            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inaktiv</option>
        </select>
    </div>
    <button class="btn-primary" onclick="openAddCustomerModal()">
        <span>+</span>
        <span>Neuen Kunden hinzufügen</span>
    </button>
</div>

<div class="customers-table">
    <?php if (count($customers) > 0): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>RAW-Code</th>
                    <th>Zugewiesene Inhalte</th>
                    <th>Registrierung</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td>
                        <span class="raw-code"><?php echo htmlspecialchars($customer['raw_code'] ?? 'N/A'); ?></span>
                    </td>
                    <td>
                        <div class="assigned-content" title="<?php echo htmlspecialchars($customer['assigned_freebies'] ?: 'Keine Zuweisungen'); ?>">
                            <?php echo $customer['assigned_freebies'] ? htmlspecialchars($customer['assigned_freebies']) : 'Keine Zuweisungen'; ?>
                        </div>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($customer['created_at'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $customer['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-icons">
                            <button class="action-btn" 
                                    onclick="viewCustomer(<?php echo $customer['id']; ?>)" 
                                    title="Ansehen">
                                👁️
                            </button>
                            <button class="action-btn" 
                                    onclick="assignFreebie(<?php echo $customer['id']; ?>)" 
                                    title="Freebie zuweisen">
                                ➕
                            </button>
                            <button class="action-btn" 
                                    onclick="editCustomer(<?php echo $customer['id']; ?>)" 
                                    title="Bearbeiten">
                                ✏️
                            </button>
                            <button class="action-btn" 
                                    onclick="toggleStatus(<?php echo $customer['id']; ?>, <?php echo $customer['is_active']; ?>)" 
                                    title="<?php echo $customer['is_active'] ? 'Sperren' : 'Aktivieren'; ?>">
                                <?php echo $customer['is_active'] ? '🔒' : '🔓'; ?>
                            </button>
                            <button class="action-btn delete" 
                                    onclick="deleteCustomer(<?php echo $customer['id']; ?>)" 
                                    title="Löschen">
                                🗑️
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">👥</div>
        <p>Keine Kunden gefunden</p>
        <?php if (!empty($search)): ?>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 8px;">Versuche einen anderen Suchbegriff</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: Kunde hinzufügen -->
<div id="addCustomerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Neuen Kunden hinzufügen</h3>
            <button class="modal-close" onclick="closeModal('addCustomerModal')">×</button>
        </div>
        <form id="addCustomerForm">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" class="form-input" name="name" required>
            </div>
            <div class="form-group">
                <label class="form-label">E-Mail</label>
                <input type="email" class="form-input" name="email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Passwort</label>
                <input type="password" class="form-input" name="password" required>
                <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 4px;">Mindestens 8 Zeichen</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('addCustomerModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Kunde hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Freebie zuweisen -->
<div id="assignFreebieModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Freebie Template zuweisen</h3>
            <button class="modal-close" onclick="closeModal('assignFreebieModal')">×</button>
        </div>
        <form id="assignFreebieForm">
            <input type="hidden" name="user_id" id="assignUserId">
            <div class="form-group">
                <label class="form-label">Freebie Template auswählen</label>
                <select class="form-select" name="freebie_id" required>
                    <option value="">Bitte wählen...</option>
                    <?php foreach ($freebieTemplates as $template): ?>
                    <option value="<?php echo $template['id']; ?>">
                        <?php echo htmlspecialchars($template['name']); ?>
                        <?php if (!empty($template['headline'])): ?>
                            - <?php echo htmlspecialchars($template['headline']); ?>
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($freebieTemplates) === 0): ?>
                    <small style="color: var(--danger); font-size: 12px; display: block; margin-top: 8px;">
                        ⚠️ Keine Freebie Templates vorhanden. Bitte erst Templates erstellen!
                    </small>
                <?php endif; ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('assignFreebieModal')">Abbrechen</button>
                <button type="submit" class="btn-primary" <?php echo count($freebieTemplates) === 0 ? 'disabled' : ''; ?>>Zuweisen</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search und Filter
document.getElementById('searchInput').addEventListener('input', function(e) {
    const search = e.target.value;
    const status = document.getElementById('statusFilter').value;
    updateURL(search, status);
});

document.getElementById('statusFilter').addEventListener('change', function(e) {
    const search = document.getElementById('searchInput').value;
    const status = e.target.value;
    updateURL(search, status);
});

function updateURL(search, status) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'users');
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    if (status !== 'all') url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    window.location = url.toString();
}

// Modal Functions
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

function openAddCustomerModal() {
    openModal('addCustomerModal');
}

// Kunde hinzufügen
document.getElementById('addCustomerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/api/customer-add.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Kunde erfolgreich hinzugefügt!');
            window.location.reload();
        } else {
            alert('❌ Fehler: ' + result.message);
        }
    } catch (error) {
        alert('❌ Fehler beim Hinzufügen des Kunden');
    }
});

// Freebie zuweisen
function assignFreebie(userId) {
    document.getElementById('assignUserId').value = userId;
    openModal('assignFreebieModal');
}

document.getElementById('assignFreebieForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/api/customer-assign-freebie.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Freebie erfolgreich zugewiesen!');
            window.location.reload();
        } else {
            alert('❌ Fehler: ' + result.message);
        }
    } catch (error) {
        alert('❌ Fehler beim Zuweisen des Freebies');
    }
});

// Status togglen (Sperren/Entsperren)
async function toggleStatus(userId, currentStatus) {
    const action = currentStatus ? 'sperren' : 'aktivieren';
    if (!confirm(`Möchtest du diesen Kunden wirklich ${action}?`)) return;
    
    try {
        const response = await fetch('/api/customer-toggle-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Kunde ${action === 'sperren' ? 'gesperrt' : 'aktiviert'}!`);
            window.location.reload();
        } else {
            alert('❌ Fehler: ' + result.message);
        }
    } catch (error) {
        alert('❌ Fehler beim Ändern des Status');
    }
}

// Kunde löschen
async function deleteCustomer(userId) {
    if (!confirm('⚠️ ACHTUNG: Möchtest du diesen Kunden wirklich permanent löschen?')) return;
    
    try {
        const response = await fetch('/api/customer-delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Kunde erfolgreich gelöscht!');
            window.location.reload();
        } else {
            alert('❌ Fehler: ' + result.message);
        }
    } catch (error) {
        alert('❌ Fehler beim Löschen des Kunden');
    }
}

// Kunde ansehen
function viewCustomer(userId) {
    // TODO: Detail-Ansicht implementieren
    alert('Detail-Ansicht für Kunde ID: ' + userId);
}

// Kunde bearbeiten
function editCustomer(userId) {
    // TODO: Bearbeiten-Modal implementieren
    alert('Bearbeiten für Kunde ID: ' + userId);
}

// ESC-Taste zum Schließen von Modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.active');
        modals.forEach(modal => {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
});
</script>