<?php
/**
 * Migration Backend - Step by Step Execution
 * Führt einzelne Migrations-Schritte aus
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error Handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    // POST-Daten lesen
    $input = json_decode(file_get_contents('php://input'), true);
    $step = isset($input['step']) ? (int)$input['step'] : 0;
    
    if ($step < 1 || $step > 5) {
        throw new Exception('Ungültige Schrittnummer');
    }
    
    // Datenbank-Verbindung
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Schritt ausführen
    $result = executeStep($pdo, $step);
    
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'details' => $result['details'] ?? null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function executeStep($pdo, $step) {
    switch ($step) {
        case 1:
            return createLoginTokensTable($pdo);
        case 2:
            return createLeadUsersTable($pdo);
        case 3:
            return createLeadReferralsTable($pdo);
        case 4:
            return createRewardDefinitionsTable($pdo);
        case 5:
            return createClaimedRewardsTable($pdo);
        default:
            throw new Exception('Unbekannter Schritt');
    }
}

function createLoginTokensTable($pdo) {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'lead_login_tokens'");
    
    if ($stmt->rowCount() > 0) {
        return [
            'message' => 'Tabelle lead_login_tokens existiert bereits',
            'details' => 'Keine Änderungen nötig'
        ];
    }
    
    // Tabelle erstellen
    $pdo->exec("
        CREATE TABLE lead_login_tokens (
            id INT PRIMARY KEY AUTO_INCREMENT,
            token VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) NOT NULL,
            name VARCHAR(255),
            customer_id INT,
            freebie_id INT,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_token (token),
            INDEX idx_email (email),
            INDEX idx_expires (expires_at),
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    return [
        'message' => 'Tabelle lead_login_tokens erfolgreich erstellt',
        'details' => 'Spalten: id, token, email, name, customer_id, freebie_id, expires_at, used_at, created_at'
    ];
}

function createLeadUsersTable($pdo) {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'lead_users'");
    
    if ($stmt->rowCount() > 0) {
        // Tabelle existiert - prüfe Spalten
        $stmt = $pdo->query("SHOW COLUMNS FROM lead_users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = [
            'referral_code' => "ADD COLUMN referral_code VARCHAR(20) UNIQUE",
            'total_referrals' => "ADD COLUMN total_referrals INT DEFAULT 0",
            'successful_referrals' => "ADD COLUMN successful_referrals INT DEFAULT 0"
        ];
        
        $added = [];
        foreach ($required_columns as $col => $sql) {
            if (!in_array($col, $columns)) {
                $pdo->exec("ALTER TABLE lead_users " . $sql);
                $added[] = $col;
            }
        }
        
        if (empty($added)) {
            return [
                'message' => 'Tabelle lead_users ist bereits vollständig',
                'details' => 'Alle Spalten vorhanden'
            ];
        } else {
            return [
                'message' => 'Tabelle lead_users aktualisiert',
                'details' => 'Hinzugefügte Spalten: ' . implode(', ', $added)
            ];
        }
    }
    
    // Tabelle erstellen
    $pdo->exec("
        CREATE TABLE lead_users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            referral_code VARCHAR(20) UNIQUE,
            total_referrals INT DEFAULT 0,
            successful_referrals INT DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_email (email),
            INDEX idx_user (user_id),
            INDEX idx_referral_code (referral_code),
            UNIQUE KEY unique_email_user (email, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    return [
        'message' => 'Tabelle lead_users erfolgreich erstellt',
        'details' => 'Spalten: id, name, email, user_id, referral_code, total_referrals, successful_referrals, created_at'
    ];
}

function createLeadReferralsTable($pdo) {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'lead_referrals'");
    
    if ($stmt->rowCount() > 0) {
        return [
            'message' => 'Tabelle lead_referrals existiert bereits',
            'details' => 'Keine Änderungen nötig'
        ];
    }
    
    // Tabelle erstellen
    $pdo->exec("
        CREATE TABLE lead_referrals (
            id INT PRIMARY KEY AUTO_INCREMENT,
            referrer_id INT NOT NULL,
            referred_name VARCHAR(255),
            referred_email VARCHAR(255) NOT NULL,
            status ENUM('pending', 'active', 'converted', 'cancelled') DEFAULT 'pending',
            invited_at DATETIME NOT NULL,
            converted_at DATETIME NULL,
            INDEX idx_referrer (referrer_id),
            INDEX idx_email (referred_email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    return [
        'message' => 'Tabelle lead_referrals erfolgreich erstellt',
        'details' => 'Spalten: id, referrer_id, referred_name, referred_email, status, invited_at, converted_at'
    ];
}

function createRewardDefinitionsTable($pdo) {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_definitions'");
    
    if ($stmt->rowCount() > 0) {
        return [
            'message' => 'Tabelle reward_definitions existiert bereits',
            'details' => 'Keine Änderungen nötig'
        ];
    }
    
    // Tabelle erstellen
    $pdo->exec("
        CREATE TABLE reward_definitions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            tier_level INT NOT NULL,
            tier_name VARCHAR(255),
            tier_description TEXT,
            required_referrals INT NOT NULL,
            reward_type VARCHAR(50),
            reward_title VARCHAR(255) NOT NULL,
            reward_description TEXT,
            reward_icon VARCHAR(100),
            reward_color VARCHAR(20),
            reward_value VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL,
            INDEX idx_user (user_id),
            INDEX idx_tier (tier_level),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    return [
        'message' => 'Tabelle reward_definitions erfolgreich erstellt',
        'details' => 'Spalten: id, user_id, tier_level, tier_name, required_referrals, reward_title, etc.'
    ];
}

function createClaimedRewardsTable($pdo) {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'referral_claimed_rewards'");
    
    if ($stmt->rowCount() > 0) {
        return [
            'message' => 'Tabelle referral_claimed_rewards existiert bereits',
            'details' => 'Keine Änderungen nötig'
        ];
    }
    
    // Tabelle erstellen
    $pdo->exec("
        CREATE TABLE referral_claimed_rewards (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lead_id INT NOT NULL,
            reward_id INT NOT NULL,
            reward_name VARCHAR(255),
            claimed_at DATETIME NOT NULL,
            INDEX idx_lead (lead_id),
            INDEX idx_reward (reward_id),
            INDEX idx_claimed (claimed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    return [
        'message' => 'Tabelle referral_claimed_rewards erfolgreich erstellt',
        'details' => 'Spalten: id, lead_id, reward_id, reward_name, claimed_at'
    ];
}
?>