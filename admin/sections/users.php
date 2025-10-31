<?php
/**
 * Kundenverwaltung - Admin Dashboard
 * Mit Digistore24 Integration
 */

// Kunden aus Datenbank laden
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

$query = "SELECT u.*, 
          GROUP_CONCAT(DISTINCT ft.title SEPARATOR ', ') as assigned_freebies
          FROM users u
          LEFT JOIN user_freebies uf ON u.id = uf.user_id
          LEFT JOIN freebie_templates ft ON uf.freebie_id = ft.id
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

// Alle Freebie Templates laden
$freebieTemplates = $pdo->query("SELECT id, title FROM freebie_templates ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Modern Dark Purple Theme - wie im Screenshot */
.customers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
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
    background: #2a2a4f;
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    font-size: 14px;
}

.search-input::placeholder {
    color: #888;
}

.search-input:focus {
    outline: none;
    border-color: #a855f7;
}

.status-filter {
    padding: 12px 16px;
    background: #2a2a4f;
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    cursor: pointer;
}

.btn-primary {
    padding: 12px 24px;
    background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
}

.customers-table {
    background: #1e1e3f;
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 12px;
    overflow: hidden;
}

.customers-table table {
    width: 100%;
    border-collapse: collapse;
}

.customers-table th {
    background: #2a2a4f;
    color: #a0a0a0;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px;
    text-align: left;
}

.customers-table td {
    padding: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    color: #e0e0e0;
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
    background: rgba(168, 85, 247, 0.2);
    border: 1px solid rgba(168, 85, 247, 0.4);
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #a855f7;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: rgba(74, 222, 128, 0.2);
    color: #4ade80;
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.assigned-content {
    font-size: 12px;
    color: #888;
}

.action-icons {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    color: #a855f7;
}

.action-btn:hover {
    background: rgba(168, 85, 247, 0.2);
    transform: translateY(-2px);
}

.action-btn.delete {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.2);
}

.action-btn.delete:hover {
    background: rgba(239, 68, 68, 0.2);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
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
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: #1e1e3f;
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 16px;
    padding: 32px;
    max-width: 500px;
    width: 90%;
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
    background: rgba(239, 68, 68, 0.1);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: #ef4444;
    font-size: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: #a0a0a0;
    font-size: 14px;
    font-weight: 600;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 16px;
    background: #2a2a4f;
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    font-size: 14px;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #a855f7;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-secondary {
    padding: 12px 24px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #a855f7;
    cursor: pointer;
    font-weight: 600;
}

.btn-danger {
    padding: 12px 24px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
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
                    <span class="raw-code"><?php echo htmlspecialchars($customer['raw_code'] ?? 'Keine Zuweisungen'); ?></span>
                </td>
                <td>
                    <div class="assigned-content">
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
    <?php else: ?>
    <div class="empty-state">
        <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
        <p>Keine Kunden gefunden</p>
        <?php if (!empty($search)): ?>
        <p style="color: #666; font-size: 14px;">Versuche einen anderen Suchbegriff</p>
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
                <small style="color: #888; font-size: 12px;">Mindestens 8 Zeichen</small>
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
                        <?php echo htmlspecialchars($template['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('assignFreebieModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Zuweisen</button>
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
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
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
</script>
