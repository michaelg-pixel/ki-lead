<?php
/**
 * Datenbank-Migration für vereintes Lead-Dashboard
 * Erstellt benötigte Tabellen für One-Click-Login und Tracking
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

echo "<h2>🚀 Migration: Vereintes Lead-Dashboard</h2>";
echo "<hr>";

// ===== 1. Lead Login Tokens Tabelle =====
echo "<h3>1. Lead Login Tokens erstellen...</h3>";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lead_login_tokens (
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
    echo "<p style='color: green;'>✅ Lead Login Tokens Tabelle erstellt/überprüft</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

// ===== 2. Lead Users Tabelle prüfen =====
echo "<h3>2. Lead Users Tabelle prüfen...</h3>";
try {
    // Prüfe ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'lead_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Lead Users Tabelle existiert bereits</p>";
        
        // Prüfe wichtige Spalten
        $stmt = $pdo->query("SHOW COLUMNS FROM lead_users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = ['referral_code', 'total_referrals', 'successful_referrals'];
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                echo "<p style='color: orange;'>⚠️ Spalte '$col' fehlt - wird hinzugefügt...</p>";
                
                if ($col === 'referral_code') {
                    $pdo->exec("ALTER TABLE lead_users ADD COLUMN referral_code VARCHAR(20) UNIQUE");
                } elseif ($col === 'total_referrals') {
                    $pdo->exec("ALTER TABLE lead_users ADD COLUMN total_referrals INT DEFAULT 0");
                } elseif ($col === 'successful_referrals') {
                    $pdo->exec("ALTER TABLE lead_users ADD COLUMN successful_referrals INT DEFAULT 0");
                }
                
                echo "<p style='color: green;'>✅ Spalte '$col' hinzugefügt</p>";
            }
        }
    } else {
        // Erstelle Tabelle
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
        echo "<p style='color: green;'>✅ Lead Users Tabelle erstellt</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

// ===== 3. Lead Referrals Tabelle prüfen =====
echo "<h3>3. Lead Referrals Tabelle prüfen...</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'lead_referrals'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Lead Referrals Tabelle existiert bereits</p>";
    } else {
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
        echo "<p style='color: green;'>✅ Lead Referrals Tabelle erstellt</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

// ===== 4. Reward Definitions Tabelle prüfen =====
echo "<h3>4. Reward Definitions Tabelle prüfen...</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_definitions'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Reward Definitions Tabelle existiert bereits</p>";
    } else {
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
                INDEX idx_tier (tier_level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Reward Definitions Tabelle erstellt</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

// ===== 5. Referral Claimed Rewards Tabelle prüfen =====
echo "<h3>5. Referral Claimed Rewards Tabelle prüfen...</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'referral_claimed_rewards'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Referral Claimed Rewards Tabelle existiert bereits</p>";
    } else {
        $pdo->exec("
            CREATE TABLE referral_claimed_rewards (
                id INT PRIMARY KEY AUTO_INCREMENT,
                lead_id INT NOT NULL,
                reward_id INT NOT NULL,
                reward_name VARCHAR(255),
                claimed_at DATETIME NOT NULL,
                INDEX idx_lead (lead_id),
                INDEX idx_reward (reward_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Referral Claimed Rewards Tabelle erstellt</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

// ===== 6. Freebie Course Tables prüfen =====
echo "<h3>6. Freebie Course Tabellen prüfen...</h3>";
try {
    $tables = ['freebie_courses', 'freebie_course_modules', 'freebie_course_lessons', 'freebie_course_progress', 'freebie_course_lead_access'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ $table Tabelle existiert</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $table Tabelle fehlt - sollte bereits vorhanden sein</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>✅ Migration abgeschlossen!</h2>";
echo "<p><a href='/lead-dashboard-unified.php'>→ Zum Lead Dashboard</a></p>";
?>