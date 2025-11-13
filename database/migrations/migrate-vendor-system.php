<?php
/**
 * Browser-Migration: Vendor Reward Templates System
 * 
 * Fügt alle benötigten Tabellen und Spalten für das Vendor-System hinzu
 */

session_start();

// Konfiguration
$show_errors = true;
if ($show_errors) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Datenbank-Verbindung (nutzt bestehende config)
require_once __DIR__ . '/../../config/database.php';

// Auth-Check (optional - kann auskommentiert werden wenn Admin-Zugriff)
/*
if (!isset($_SESSION['user_id'])) {
    die('Nicht authentifiziert. Bitte zuerst einloggen.');
}
*/

$pdo = getDBConnection();
$results = [];
$errors = [];

// AJAX Handler
if (isset($_GET['action']) && $_GET['action'] === 'migrate') {
    header('Content-Type: application/json');
    
    try {
        // Migration Step 1: vendor_reward_templates
        $sql1 = "CREATE TABLE IF NOT EXISTS vendor_reward_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            
            -- Template Info
            template_name VARCHAR(255) NOT NULL,
            template_description TEXT,
            category VARCHAR(100),
            niche VARCHAR(100),
            
            -- Belohnungs-Details
            reward_type VARCHAR(50) NOT NULL,
            reward_title VARCHAR(255) NOT NULL,
            reward_description TEXT,
            reward_value VARCHAR(100),
            
            -- Zugriff & Lieferung
            reward_delivery_type ENUM('automatic', 'manual', 'code', 'url') DEFAULT 'manual',
            reward_instructions TEXT,
            reward_access_code_template VARCHAR(100),
            reward_download_url TEXT,
            
            -- Visuals
            reward_icon VARCHAR(100) DEFAULT 'fa-gift',
            reward_color VARCHAR(20) DEFAULT '#667eea',
            reward_badge_image VARCHAR(255),
            preview_image VARCHAR(255),
            
            -- Empfehlungs-Vorschlag
            suggested_tier_level INT DEFAULT 1,
            suggested_referrals_required INT DEFAULT 3,
            
            -- Marktplatz
            is_published BOOLEAN DEFAULT FALSE,
            is_featured BOOLEAN DEFAULT FALSE,
            marketplace_price DECIMAL(10,2) DEFAULT 0.00,
            digistore_product_id VARCHAR(255),
            
            -- Provisionen
            commission_per_import DECIMAL(10,2) DEFAULT 0.00,
            commission_per_claim DECIMAL(10,2) DEFAULT 0.00,
            
            -- Statistiken
            times_imported INT DEFAULT 0,
            times_claimed INT DEFAULT 0,
            total_revenue DECIMAL(10,2) DEFAULT 0.00,
            
            -- Meta
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_vendor (vendor_id),
            INDEX idx_published (is_published),
            INDEX idx_category (category),
            INDEX idx_niche (niche),
            INDEX idx_featured (is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql1);
        $results[] = "✅ Tabelle vendor_reward_templates erstellt";
        
        // Migration Step 2: reward_template_imports
        $sql2 = "CREATE TABLE IF NOT EXISTS reward_template_imports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_id INT NOT NULL,
            customer_id INT NOT NULL,
            reward_definition_id INT,
            
            -- Import Details
            import_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            import_source VARCHAR(50) DEFAULT 'marketplace',
            
            -- Optional: Falls gekauft
            purchase_price DECIMAL(10,2) DEFAULT 0.00,
            digistore_transaction_id VARCHAR(255),
            
            UNIQUE KEY unique_import (template_id, customer_id),
            INDEX idx_customer (customer_id),
            INDEX idx_template (template_id),
            INDEX idx_import_date (import_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql2);
        $results[] = "✅ Tabelle reward_template_imports erstellt";
        
        // Migration Step 3: reward_template_claims
        $sql3 = "CREATE TABLE IF NOT EXISTS reward_template_claims (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_id INT NOT NULL,
            reward_definition_id INT NOT NULL,
            customer_id INT NOT NULL,
            lead_id INT,
            
            -- Claim Details
            claimed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            claim_status ENUM('pending', 'delivered', 'failed') DEFAULT 'pending',
            
            -- Provisionen
            commission_amount DECIMAL(10,2) DEFAULT 0.00,
            commission_paid BOOLEAN DEFAULT FALSE,
            
            INDEX idx_template (template_id),
            INDEX idx_customer (customer_id),
            INDEX idx_claimed_at (claimed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql3);
        $results[] = "✅ Tabelle reward_template_claims erstellt";
        
        // Migration Step 4: ALTER TABLE users
        $userColumns = [
            'is_vendor' => "ADD COLUMN is_vendor BOOLEAN DEFAULT FALSE",
            'vendor_company_name' => "ADD COLUMN vendor_company_name VARCHAR(255) DEFAULT NULL",
            'vendor_website' => "ADD COLUMN vendor_website VARCHAR(255) DEFAULT NULL",
            'vendor_description' => "ADD COLUMN vendor_description TEXT DEFAULT NULL",
            'vendor_paypal_email' => "ADD COLUMN vendor_paypal_email VARCHAR(255) DEFAULT NULL",
            'vendor_bank_account' => "ADD COLUMN vendor_bank_account VARCHAR(255) DEFAULT NULL",
            'vendor_activated_at' => "ADD COLUMN vendor_activated_at DATETIME DEFAULT NULL"
        ];
        
        foreach ($userColumns as $column => $sql) {
            try {
                // Prüfe ob Spalte existiert
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'");
                if ($stmt->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE users $sql");
                    $results[] = "✅ Spalte users.$column hinzugefügt";
                } else {
                    $results[] = "ℹ️ Spalte users.$column existiert bereits";
                }
            } catch (PDOException $e) {
                $results[] = "⚠️ Spalte users.$column: " . $e->getMessage();
            }
        }
        
        // Index für is_vendor
        try {
            $pdo->exec("ALTER TABLE users ADD INDEX idx_vendor (is_vendor)");
            $results[] = "✅ Index idx_vendor auf users hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false) {
                $results[] = "ℹ️ Index idx_vendor existiert bereits";
            }
        }
        
        // Migration Step 5: ALTER TABLE reward_definitions
        $rewardColumns = [
            'imported_from_template_id' => "ADD COLUMN imported_from_template_id INT DEFAULT NULL",
            'is_imported' => "ADD COLUMN is_imported BOOLEAN DEFAULT FALSE"
        ];
        
        foreach ($rewardColumns as $column => $sql) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE '$column'");
                if ($stmt->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE reward_definitions $sql");
                    $results[] = "✅ Spalte reward_definitions.$column hinzugefügt";
                } else {
                    $results[] = "ℹ️ Spalte reward_definitions.$column existiert bereits";
                }
            } catch (PDOException $e) {
                $results[] = "⚠️ Spalte reward_definitions.$column: " . $e->getMessage();
            }
        }
        
        // Index für is_imported
        try {
            $pdo->exec("ALTER TABLE reward_definitions ADD INDEX idx_imported (is_imported)");
            $results[] = "✅ Index idx_imported auf reward_definitions hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false) {
                $results[] = "ℹ️ Index idx_imported existiert bereits";
            }
        }
        
        // Migration Step 6: Foreign Keys (separat, da sie fehlschlagen können)
        try {
            // FK für vendor_reward_templates
            $pdo->exec("ALTER TABLE vendor_reward_templates 
                ADD CONSTRAINT fk_vrt_vendor 
                FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE");
            $results[] = "✅ Foreign Key fk_vrt_vendor hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_vrt_vendor existiert bereits oder Fehler: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            // FK für reward_template_imports
            $pdo->exec("ALTER TABLE reward_template_imports 
                ADD CONSTRAINT fk_rti_template 
                FOREIGN KEY (template_id) REFERENCES vendor_reward_templates(id) ON DELETE CASCADE");
            $results[] = "✅ Foreign Key fk_rti_template hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rti_template: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            $pdo->exec("ALTER TABLE reward_template_imports 
                ADD CONSTRAINT fk_rti_customer 
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE");
            $results[] = "✅ Foreign Key fk_rti_customer hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rti_customer: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            $pdo->exec("ALTER TABLE reward_template_imports 
                ADD CONSTRAINT fk_rti_reward 
                FOREIGN KEY (reward_definition_id) REFERENCES reward_definitions(id) ON DELETE SET NULL");
            $results[] = "✅ Foreign Key fk_rti_reward hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rti_reward: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            // FK für reward_template_claims
            $pdo->exec("ALTER TABLE reward_template_claims 
                ADD CONSTRAINT fk_rtc_template 
                FOREIGN KEY (template_id) REFERENCES vendor_reward_templates(id) ON DELETE CASCADE");
            $results[] = "✅ Foreign Key fk_rtc_template hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rtc_template: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            $pdo->exec("ALTER TABLE reward_template_claims 
                ADD CONSTRAINT fk_rtc_reward 
                FOREIGN KEY (reward_definition_id) REFERENCES reward_definitions(id) ON DELETE CASCADE");
            $results[] = "✅ Foreign Key fk_rtc_reward hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rtc_reward: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            $pdo->exec("ALTER TABLE reward_template_claims 
                ADD CONSTRAINT fk_rtc_customer 
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE");
            $results[] = "✅ Foreign Key fk_rtc_customer hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rtc_customer: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        try {
            // FK für reward_definitions
            $pdo->exec("ALTER TABLE reward_definitions 
                ADD CONSTRAINT fk_rd_template 
                FOREIGN KEY (imported_from_template_id) REFERENCES vendor_reward_templates(id) ON DELETE SET NULL");
            $results[] = "✅ Foreign Key fk_rd_template hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                $results[] = "ℹ️ Foreign Key fk_rd_template: " . substr($e->getMessage(), 0, 100);
            }
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results,
            'total' => count($results)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'results' => $results
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor System - Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        .info-box {
            background: #f3f4f6;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
        }
        
        .info-box h3 {
            color: #1f2937;
            margin-bottom: 0.75rem;
            font-size: 1.125rem;
        }
        
        .info-box p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        
        .info-box ul {
            margin-top: 1rem;
            padding-left: 1.5rem;
        }
        
        .info-box li {
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .progress-section {
            display: none;
            margin-top: 2rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 1rem;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.5s;
        }
        
        .progress-text {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        
        .results {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .result-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: start;
            gap: 0.75rem;
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        
        .result-item.success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
        }
        
        .result-item.info {
            background: rgba(59, 130, 246, 0.1);
            color: #1e40af;
        }
        
        .result-item.warning {
            background: rgba(251, 191, 36, 0.1);
            color: #92400e;
        }
        
        .result-item.error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
        }
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .summary {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            border: 2px solid #10b981;
            border-radius: 0.75rem;
            display: none;
        }
        
        .summary h3 {
            color: #065f46;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 2rem;
            display: none;
        }
        
        .error-box h3 {
            color: #991b1b;
            margin-bottom: 1rem;
        }
        
        .error-box p {
            color: #7f1d1d;
            line-height: 1.6;
        }
        
        @media (max-width: 640px) {
            .header h1 {
                font-size: 1.5rem;
            }
            
            .summary-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💎 Vendor System Migration</h1>
            <p>Datenbank-Update für Vendor Reward Templates</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <h3>📋 Was wird erstellt?</h3>
                <p>Dieses Script erstellt alle benötigten Datenbank-Strukturen für das Vendor-System:</p>
                <ul>
                    <li><strong>3 neue Tabellen:</strong> vendor_reward_templates, reward_template_imports, reward_template_claims</li>
                    <li><strong>7 neue Spalten</strong> in der users-Tabelle (Vendor-Informationen)</li>
                    <li><strong>2 neue Spalten</strong> in reward_definitions (Import-Tracking)</li>
                    <li><strong>Alle Indizes und Foreign Keys</strong> für Performance und Datenintegrität</li>
                </ul>
            </div>
            
            <button onclick="startMigration()" id="migrateBtn" class="btn">
                <span id="btnText">🚀 Migration starten</span>
            </button>
            
            <div class="progress-section" id="progressSection">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Initialisiere Migration...</div>
                
                <div class="results" id="results"></div>
            </div>
            
            <div class="summary" id="summary">
                <h3>✅ Migration erfolgreich abgeschlossen!</h3>
                <div class="summary-stats">
                    <div class="stat">
                        <div class="stat-value" id="totalSteps">0</div>
                        <div class="stat-label">Schritte</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" id="successCount">0</div>
                        <div class="stat-label">Erfolgreich</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" id="infoCount">0</div>
                        <div class="stat-label">Info</div>
                    </div>
                </div>
            </div>
            
            <div class="error-box" id="errorBox">
                <h3>❌ Fehler bei der Migration</h3>
                <p id="errorMessage"></p>
            </div>
        </div>
    </div>
    
    <script>
        async function startMigration() {
            const btn = document.getElementById('migrateBtn');
            const btnText = document.getElementById('btnText');
            const progressSection = document.getElementById('progressSection');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const resultsDiv = document.getElementById('results');
            const summary = document.getElementById('summary');
            const errorBox = document.getElementById('errorBox');
            
            // Disable button
            btn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span> Migration läuft...';
            
            // Show progress
            progressSection.style.display = 'block';
            progressFill.style.width = '10%';
            progressText.textContent = 'Verbinde zur Datenbank...';
            
            try {
                // Start migration
                const response = await fetch(window.location.href + '?action=migrate');
                const data = await response.json();
                
                // Update progress
                progressFill.style.width = '50%';
                progressText.textContent = 'Führe Migrations-Schritte aus...';
                
                // Show results
                if (data.success) {
                    // Animate through results
                    for (let i = 0; i < data.results.length; i++) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        
                        const result = data.results[i];
                        const item = document.createElement('div');
                        item.className = 'result-item';
                        
                        // Determine type
                        if (result.startsWith('✅')) {
                            item.classList.add('success');
                        } else if (result.startsWith('ℹ️')) {
                            item.classList.add('info');
                        } else if (result.startsWith('⚠️')) {
                            item.classList.add('warning');
                        } else {
                            item.classList.add('error');
                        }
                        
                        item.textContent = result;
                        resultsDiv.appendChild(item);
                        
                        // Update progress
                        const progress = 50 + ((i + 1) / data.results.length * 50);
                        progressFill.style.width = progress + '%';
                        progressText.textContent = `Schritt ${i + 1} von ${data.results.length}...`;
                        
                        // Scroll to bottom
                        resultsDiv.scrollTop = resultsDiv.scrollHeight;
                    }
                    
                    // Complete
                    progressFill.style.width = '100%';
                    progressText.textContent = '✅ Migration abgeschlossen!';
                    btnText.innerHTML = '✅ Migration erfolgreich';
                    
                    // Show summary
                    summary.style.display = 'block';
                    document.getElementById('totalSteps').textContent = data.total;
                    document.getElementById('successCount').textContent = data.results.filter(r => r.startsWith('✅')).length;
                    document.getElementById('infoCount').textContent = data.results.filter(r => r.startsWith('ℹ️')).length;
                    
                } else {
                    throw new Error(data.error);
                }
                
            } catch (error) {
                console.error('Migration error:', error);
                
                // Show error
                progressFill.style.width = '100%';
                progressFill.style.background = '#ef4444';
                progressText.textContent = '❌ Fehler bei der Migration';
                btnText.innerHTML = '❌ Migration fehlgeschlagen';
                
                errorBox.style.display = 'block';
                document.getElementById('errorMessage').textContent = error.message;
            }
        }
    </script>
</body>
</html>
