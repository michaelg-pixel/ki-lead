<?php
/**
 * Kundenverwaltung - Admin Dashboard
 * Mit Digistore24 Integration & Limits-Verwaltung - RESPONSIVE OPTIMIERT
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
/* VERBESSERTE FARBEN - Konsistent mit neuem Dashboard-Theme */
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
    background: rgba(26, 26, 46, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    font-size: 14px;
    min-width: 0;
}

.search-input::placeholder {
    color: #888;
}

.search-input:focus {
    outline: none;
    border-color: #a855f7;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
}

.status-filter {
    padding: 12px 16px;
    background: rgba(26, 26, 46, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    cursor: pointer;
    font-size: 14px;
    white-space: nowrap;
}

.status-filter:focus {
    outline: none;
    border-color: #a855f7;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
}

.customers-table {
    background: rgba(26, 26, 46, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.3);
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
    background: rgba(168, 85, 247, 0.1);
    color: #c084fc;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px;
    text-align: left;
}

.customers-table td {
    padding: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    color: #e0e0e0;
    font-size: 14px;
}

.customers-table tbody tr {
    transition: background 0.2s;
}

.customers-table tbody tr:hover {
    background: rgba(168, 85, 247, 0.08);
}

.raw-code {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(168, 85, 247, 0.2);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #c084fc;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    border: 1px solid;
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border-color: rgba(34, 197, 94, 0.4);
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.4);
}

.assigned-content {
    font-size: 12px;
    color: #a0a0a0;
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
    background: rgba(168, 85, 247, 0.15);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    color: #c084fc;
    font-size: 14px;
}

.action-btn:hover {
    background: rgba(168, 85, 247, 0.25);
    transform: translateY(-2px);
    border-color: rgba(168, 85, 247, 0.5);
}

.action-btn.delete {
    color: #f87171;
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
}

.action-btn.delete:hover {
    background: rgba(239, 68, 68, 0.25);
    border-color: rgba(239, 68, 68, 0.5);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #888;
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
    background: rgba(26, 26, 46, 0.95);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 16px;
    padding: 32px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content.large {
    max-width: 700px;
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
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 6px;
    cursor: pointer;
    color: #f87171;
    font-size: 20px;
    transition: all 0.2s;
    flex-shrink: 0;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.25);
    border-color: rgba(239, 68, 68, 0.5);
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: #b0b0b0;
    font-size: 14px;
    font-weight: 600;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: #e0e0e0;
    font-size: 14px;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #a855f7;
    background: rgba(0, 0, 0, 0.4);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
}

.form-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

/* Limits Management Styles */
.limits-info-box {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.limits-info-box .info-title {
    color: #60a5fa;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.limits-info-box .info-text {
    color: #a0a0a0;
    font-size: 13px;
    line-height: 1.5;
}

.limit-section {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.limit-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.limit-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #c084fc;
    display: flex;
    align-items: center;
    gap: 8px;
}

.current-value {
    background: rgba(168, 85, 247, 0.2);
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    color: #c084fc;
    font-size: 14px;
}

.limit-description {
    color: #888;
    font-size: 13px;
    margin-bottom: 12px;
    line-height: 1.5;
}

/* Detail View Styles */
.detail-section {
    margin-bottom: 24px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 12px;
}

.detail-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #c084fc;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #888;
    font-size: 14px;
    font-weight: 500;
}

.detail-value {
    color: #e0e0e0;
    font-size: 14px;
    font-weight: 600;
    text-align: right;
}

.freebie-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.freebie-item {
    padding: 8px 12px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 6px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #e0e0e0;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 16px;
}

.stat-card {
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 8px;
    padding: 16px;
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #c084fc;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(168, 85, 247, 0.3);
    border-radius: 50%;
    border-top-color: #a855f7;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
    
    .btn {
        order: 1;
        width: 100%;
        justify-content: center;
    }
    
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
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
    
    .btn {
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
    
    .modal-actions .btn {
        width: 100%;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .detail-value {
        text-align: left;
    }
    
    .stat-grid {
        grid-template-columns: 1fr;
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
    
    .detail-section {
        padding: 16px;
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
    <button class="btn btn-primary" onclick="openAddCustomerModal()">
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
                                    onclick="manageLimits(<?php echo $customer['id']; ?>)" 
                                    title="Limits verwalten">
                                📊
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
        <p style="color: #888; font-size: 14px; margin-top: 8px;">Versuche einen anderen Suchbegriff</p>
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
                <small style="color: #888; font-size: 12px; display: block; margin-top: 4px;">Mindestens 8 Zeichen</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCustomerModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Kunde hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Limits verwalten -->
<div id="limitsModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">📊 Limits verwalten</h3>
            <button class="modal-close" onclick="closeModal('limitsModal')">×</button>
        </div>
        
        <div id="limitsContent">
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner"></div>
                <p style="color: #888; margin-top: 16px;">Lade Limit-Daten...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kunde ansehen (Detail-Ansicht) -->
<div id="viewCustomerModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">👤 Kundendetails</h3>
            <button class="modal-close" onclick="closeModal('viewCustomerModal')">×</button>
        </div>
        <div id="customerDetailContent">
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner"></div>
                <p style="color: #888; margin-top: 16px;">Lade Kundendaten...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kunde bearbeiten -->
<div id="editCustomerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">✏️ Kunde bearbeiten</h3>
            <button class="modal-close" onclick="closeModal('editCustomerModal')">×</button>
        </div>
        <form id="editCustomerForm">
            <input type="hidden" name="user_id" id="editUserId">
            
            <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" class="form-input" name="name" id="editName" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">E-Mail *</label>
                <input type="email" class="form-input" name="email" id="editEmail" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">RAW-Code</label>
                <input type="text" class="form-input" name="raw_code" id="editRawCode" placeholder="Optional">
            </div>
            
            <div class="form-group">
                <label class="form-label">Firmenname</label>
                <input type="text" class="form-input" name="company_name" id="editCompanyName" placeholder="Optional">
            </div>
            
            <div class="form-group">
                <label class="form-label">Firmen-E-Mail</label>
                <input type="email" class="form-input" name="company_email" id="editCompanyEmail" placeholder="Optional">
            </div>
            
            <div class="form-group">
                <label class="form-label">Neues Passwort</label>
                <input type="password" class="form-input" name="new_password" id="editPassword" placeholder="Leer lassen, um beizubehalten">
                <small style="color: #888; font-size: 12px; display: block; margin-top: 4px;">
                    Mindestens 8 Zeichen, nur ausfüllen wenn Änderung gewünscht
                </small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCustomerModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Änderungen speichern</button>
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
                    <small style="color: #f87171; font-size: 12px; display: block; margin-top: 8px;">
                        ⚠️ Keine Freebie Templates vorhanden. Bitte erst Templates erstellen!
                    </small>
                <?php endif; ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('assignFreebieModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary" <?php echo count($freebieTemplates) === 0 ? 'disabled' : ''; ?>>Zuweisen</button>
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

// Limits verwalten
let currentLimitsUserId = null;

async function manageLimits(userId) {
    currentLimitsUserId = userId;
    openModal('limitsModal');
    
    try {
        const response = await fetch(`/api/customer-get-limits.php?user_id=${userId}`);
        const result = await response.json();
        
        if (result.success) {
            displayLimitsForm(result.data);
        } else {
            document.getElementById('limitsContent').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #f87171;">
                    <p>❌ ${result.error}</p>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('limitsContent').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #f87171;">
                <p>❌ Fehler beim Laden der Limit-Daten</p>
            </div>
        `;
    }
}

function displayLimitsForm(data) {
    document.getElementById('limitsContent').innerHTML = `
        <div class="limits-info-box">
            <div class="info-title">ℹ️ Hinweis zu manuellen Änderungen</div>
            <div class="info-text">
                Wenn du hier Limits manuell anpasst, werden diese als "manuell gesetzt" markiert 
                und NICHT vom Digistore24 Webhook überschrieben. Dies gibt dir volle Kontrolle für Sonderfälle.
            </div>
        </div>
        
        <form id="updateLimitsForm">
            <input type="hidden" name="user_id" value="${data.user.id}">
            
            <!-- Freebie Limits -->
            <div class="limit-section">
                <div class="limit-section-header">
                    <div class="limit-section-title">
                        🎁 Freebie-Limits
                    </div>
                    <div class="current-value">${data.freebies_created} / ${data.freebie_limit}</div>
                </div>
                <div class="limit-description">
                    Der Kunde hat aktuell <strong>${data.freebies_created}</strong> von 
                    <strong>${data.freebie_limit}</strong> möglichen Freebies erstellt.
                </div>
                <div class="form-group">
                    <label class="form-label">Neues Freebie-Limit</label>
                    <input type="number" 
                           class="form-input" 
                           name="freebie_limit" 
                           value="${data.freebie_limit}"
                           min="0"
                           step="1"
                           required>
                    <small style="color: #888; font-size: 12px; display: block; margin-top: 4px;">
                        Anzahl der Freebies, die der Kunde erstellen kann
                    </small>
                </div>
            </div>
            
            <!-- Empfehlungs-Slots -->
            <div class="limit-section">
                <div class="limit-section-header">
                    <div class="limit-section-title">
                        🚀 Empfehlungsprogramm-Slots
                    </div>
                    <div class="current-value">${data.referral_slots_used} / ${data.referral_slots}</div>
                </div>
                <div class="limit-description">
                    Der Kunde hat aktuell <strong>${data.referral_slots_used}</strong> von 
                    <strong>${data.referral_slots}</strong> Empfehlungs-Slots genutzt. 
                    <strong>${data.referral_slots_available}</strong> Slots verfügbar.
                </div>
                <div class="form-group">
                    <label class="form-label">Neue Anzahl Slots</label>
                    <input type="number" 
                           class="form-input" 
                           name="referral_slots" 
                           value="${data.referral_slots}"
                           min="0"
                           step="1"
                           required>
                    <small style="color: #888; font-size: 12px; display: block; margin-top: 4px;">
                        Anzahl der Empfehlungen, die der Kunde registrieren kann
                    </small>
                </div>
            </div>
            
            <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; padding: 16px; margin-top: 20px;">
                <div style="color: #fbbf24; font-weight: 600; margin-bottom: 8px;">
                    ⚠️ Wichtiger Hinweis
                </div>
                <div style="color: #a0a0a0; font-size: 13px; line-height: 1.5;">
                    Diese Limits werden als "manuell vom Admin gesetzt" markiert. 
                    Sie werden <strong>NICHT</strong> durch Digistore24 Webhook-Aktualisierungen überschrieben.
                    Tarif: <strong>${data.product_name}</strong>
                </div>
            </div>
            
            <div class="modal-actions" style="margin-top: 24px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('limitsModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Limits aktualisieren</button>
            </div>
        </form>
    `;
    
    // Form Submit Handler
    document.getElementById('updateLimitsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('/api/customer-update-limits.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('✅ Limits erfolgreich aktualisiert!\n\n' + result.warning);
                closeModal('limitsModal');
                window.location.reload();
            } else {
                alert('❌ Fehler: ' + result.error);
            }
        } catch (error) {
            alert('❌ Fehler beim Aktualisieren der Limits');
        }
    });
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

// Kunde ansehen (Detail-Ansicht)
async function viewCustomer(userId) {
    openModal('viewCustomerModal');
    
    try {
        const response = await fetch(`/api/customer-get.php?user_id=${userId}`);
        const result = await response.json();
        
        if (result.success) {
            displayCustomerDetails(result.customer);
        } else {
            document.getElementById('customerDetailContent').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #f87171;">
                    <p>❌ ${result.message}</p>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('customerDetailContent').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #f87171;">
                <p>❌ Fehler beim Laden der Kundendaten</p>
            </div>
        `;
    }
}

function displayCustomerDetails(customer) {
    const freebiesList = customer.freebies && customer.freebies.length > 0
        ? customer.freebies.map(f => `<li class="freebie-item">${f.name}</li>`).join('')
        : '<li class="freebie-item" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2);">Keine Freebies zugewiesen</li>';
    
    const lastLogin = customer.stats.last_login 
        ? new Date(customer.stats.last_login).toLocaleString('de-DE')
        : 'Noch nie';
    
    document.getElementById('customerDetailContent').innerHTML = `
        <div class="detail-section">
            <div class="detail-section-title">
                📋 Grundinformationen
            </div>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value">${customer.name || '-'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">E-Mail</span>
                <span class="detail-value">${customer.email || '-'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">RAW-Code</span>
                <span class="detail-value"><span class="raw-code">${customer.raw_code || 'N/A'}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Registriert seit</span>
                <span class="detail-value">${new Date(customer.created_at).toLocaleDateString('de-DE')}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="status-badge status-${customer.is_active ? 'active' : 'inactive'}">
                        ${customer.is_active ? 'Aktiv' : 'Inaktiv'}
                    </span>
                </span>
            </div>
        </div>
        
        ${customer.company_name || customer.company_email ? `
        <div class="detail-section">
            <div class="detail-section-title">
                🏢 Firmendaten
            </div>
            ${customer.company_name ? `
            <div class="detail-row">
                <span class="detail-label">Firmenname</span>
                <span class="detail-value">${customer.company_name}</span>
            </div>
            ` : ''}
            ${customer.company_email ? `
            <div class="detail-row">
                <span class="detail-label">Firmen-E-Mail</span>
                <span class="detail-value">${customer.company_email}</span>
            </div>
            ` : ''}
        </div>
        ` : ''}
        
        <div class="detail-section">
            <div class="detail-section-title">
                🎁 Zugewiesene Freebies (${customer.stats.total_freebies})
            </div>
            <ul class="freebie-list">
                ${freebiesList}
            </ul>
        </div>
        
        <div class="detail-section">
            <div class="detail-section-title">
                📊 Statistiken
            </div>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-value">${customer.stats.total_freebies}</div>
                    <div class="stat-label">Freebies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${customer.stats.total_downloads}</div>
                    <div class="stat-label">Downloads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="font-size: 14px;">${lastLogin}</div>
                    <div class="stat-label">Letzter Login</div>
                </div>
            </div>
        </div>
    `;
}

// Kunde bearbeiten
async function editCustomer(userId) {
    openModal('editCustomerModal');
    
    // Kundendaten laden
    try {
        const response = await fetch(`/api/customer-get.php?user_id=${userId}`);
        const result = await response.json();
        
        if (result.success) {
            const customer = result.customer;
            document.getElementById('editUserId').value = customer.id;
            document.getElementById('editName').value = customer.name || '';
            document.getElementById('editEmail').value = customer.email || '';
            document.getElementById('editRawCode').value = customer.raw_code || '';
            document.getElementById('editCompanyName').value = customer.company_name || '';
            document.getElementById('editCompanyEmail').value = customer.company_email || '';
            document.getElementById('editPassword').value = '';
        } else {
            alert('❌ Fehler beim Laden der Kundendaten: ' + result.message);
            closeModal('editCustomerModal');
        }
    } catch (error) {
        alert('❌ Fehler beim Laden der Kundendaten');
        closeModal('editCustomerModal');
    }
}

document.getElementById('editCustomerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/api/customer-update.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Kundendaten erfolgreich aktualisiert!');
            window.location.reload();
        } else {
            alert('❌ Fehler: ' + result.message);
        }
    } catch (error) {
        alert('❌ Fehler beim Aktualisieren der Kundendaten');
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