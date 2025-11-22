<?php
/**
 * Mailgun Reward Helper
 * Automatischer Belohnungs-Email-Versand bei Lead-Registrierung
 * 
 * VERWENDUNG in lead_register.php:
 * require_once __DIR__ . '/includes/MailgunRewardHelper.php';
 * 
 * // Nach erfolgreicher Lead-Erstellung:
 * MailgunRewardHelper::checkAndSendRewards($lead_id, $customer_id);
 */

class MailgunRewardHelper {
    
    /**
     * Prüft erreichte Belohnungsstufen und versendet Emails
     */
    public static function checkAndSendRewards($leadId, $customerId) {
        $pdo = getDBConnection();
        
        try {
            // Lead-Daten laden
            $stmt = $pdo->prepare("
                SELECT * FROM lead_users WHERE id = ?
            ");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                error_log("❌ MAILGUN: Lead #{$leadId} nicht gefunden");
                return false;
            }
            
            $successfulReferrals = intval($lead['successful_referrals'] ?? 0);
            
            // Prüfen ob reward_emails_sent Tabelle existiert
            self::createRewardEmailsTableIfNotExists($pdo);
            
            // Erreichte Belohnungsstufen laden (noch nicht versendet)
            $stmt = $pdo->prepare("
                SELECT rd.* 
                FROM reward_definitions rd
                LEFT JOIN reward_emails_sent res ON (res.reward_id = rd.id AND res.lead_id = ?)
                WHERE rd.user_id = ?
                  AND rd.required_referrals <= ?
                  AND res.id IS NULL
                ORDER BY rd.required_referrals ASC
            ");
            $stmt->execute([$leadId, $customerId, $successfulReferrals]);
            $unsentRewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($unsentRewards)) {
                error_log("ℹ️ MAILGUN: Keine neuen Belohnungen für Lead #{$leadId} (Empfehlungen: {$successfulReferrals})");
                return true;
            }
            
            error_log("🎁 MAILGUN: " . count($unsentRewards) . " neue Belohnung(en) für Lead #{$leadId}");
            
            // Jede Belohnung versenden
            foreach ($unsentRewards as $reward) {
                self::sendRewardEmail($lead, $reward, $customerId);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ MAILGUN ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Versendet Belohnungs-Email via Mailgun
     */
    private static function sendRewardEmail($lead, $reward, $customerId) {
        $pdo = getDBConnection();
        
        try {
            // Kunden-Daten laden (für Impressum)
            $stmt = $pdo->prepare("
                SELECT company_name, company_imprint_html 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                error_log("❌ MAILGUN: Customer #{$customerId} nicht gefunden");
                return false;
            }
            
            // MailgunService laden
            $configPath = __DIR__ . '/../config/mailgun.php';
            if (!file_exists($configPath)) {
                error_log("❌ MAILGUN: Config nicht gefunden - {$configPath}");
                return false;
            }
            
            require_once __DIR__ . '/../mailgun/includes/MailgunService.php';
            $mailgun = new MailgunService();
            
            // Email versenden
            $result = $mailgun->sendRewardEmail($lead, $reward, $customer);
            
            if ($result['success']) {
                // Tracking-Eintrag erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO reward_emails_sent 
                    (lead_id, reward_id, mailgun_id, email_type, sent_at)
                    VALUES (?, ?, ?, 'reward_unlocked', NOW())
                ");
                $stmt->execute([
                    $lead['id'],
                    $reward['id'],
                    $result['message_id'] ?? null
                ]);
                
                // rewards_earned Counter erhöhen
                $stmt = $pdo->prepare("
                    UPDATE lead_users 
                    SET rewards_earned = COALESCE(rewards_earned, 0) + 1
                    WHERE id = ?
                ");
                $stmt->execute([$lead['id']]);
                
                error_log("✅ MAILGUN: Belohnungs-Email versendet - Lead #{$lead['id']} - Belohnung: {$reward['title']}");
                return true;
                
            } else {
                // Fehler loggen
                $stmt = $pdo->prepare("
                    INSERT INTO reward_emails_sent 
                    (lead_id, reward_id, email_type, sent_at, failed_at, error_message)
                    VALUES (?, ?, 'reward_unlocked', NOW(), NOW(), ?)
                ");
                $stmt->execute([
                    $lead['id'],
                    $reward['id'],
                    $result['error'] ?? 'Unknown error'
                ]);
                
                error_log("❌ MAILGUN: Email-Versand fehlgeschlagen - " . ($result['error'] ?? 'Unknown error'));
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ MAILGUN EXCEPTION: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Erstellt reward_emails_sent Tabelle falls nicht vorhanden
     */
    private static function createRewardEmailsTableIfNotExists($pdo) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'reward_emails_sent'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("
                    CREATE TABLE reward_emails_sent (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        lead_id INT NOT NULL,
                        reward_id INT NOT NULL,
                        mailgun_id VARCHAR(255) NULL,
                        email_type VARCHAR(50) DEFAULT 'reward_unlocked',
                        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        opened_at DATETIME NULL,
                        clicked_at DATETIME NULL,
                        failed_at DATETIME NULL,
                        error_message TEXT NULL,
                        INDEX idx_lead (lead_id),
                        INDEX idx_reward (reward_id),
                        INDEX idx_mailgun_id (mailgun_id),
                        INDEX idx_email_type (email_type),
                        INDEX idx_sent_at (sent_at),
                        UNIQUE KEY unique_reward (lead_id, reward_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                error_log("✅ MAILGUN: reward_emails_sent Tabelle erstellt");
            }
        } catch (PDOException $e) {
            error_log("❌ MAILGUN: Fehler beim Erstellen der Tabelle - " . $e->getMessage());
        }
    }
}
