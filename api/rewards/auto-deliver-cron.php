<?php
/**
 * Reward Auto-Delivery Cronjob
 * 
 * Läuft automatisch alle 5-10 Minuten und:
 * 1. Prüft alle Leads auf erreichte Belohnungsstufen
 * 2. Liefert noch nicht ausgelieferte Belohnungen aus
 * 3. Tracked Delivery in reward_deliveries Tabelle
 * 
 * SETUP: Cronjab einrichten:
 * */5 * * * * /usr/bin/php /path/to/api/rewards/auto-deliver-cron.php >> /path/to/logs/reward-cron.log 2>&1
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/email-delivery-service.php';

class RewardAutoDelivery {
    
    private $pdo;
    private $deliveryService;
    private $logPrefix = '[REWARD-AUTO-DELIVERY]';
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->deliveryService = new RewardEmailDeliveryService();
    }
    
    /**
     * Hauptprozess: Alle ausstehenden Belohnungen ausliefern
     */
    public function run() {
        $this->log("START - Reward Auto-Delivery Cronjob");
        
        try {
            // 1. Prüfe ob reward_deliveries Tabelle existiert
            if (!$this->tableExists('reward_deliveries')) {
                $this->createRewardDeliveriesTable();
            }
            
            // 2. Finde alle Leads mit ausstehenden Belohnungen
            $pendingRewards = $this->findPendingRewards();
            $this->log("Gefunden: " . count($pendingRewards) . " ausstehende Belohnungen");
            
            // 3. Belohnungen ausliefern
            $delivered = 0;
            $failed = 0;
            
            foreach ($pendingRewards as $pending) {
                $result = $this->deliverReward($pending);
                
                if ($result['success']) {
                    $delivered++;
                    $this->log("✅ Belohnung ausgeliefert: Lead #{$pending['lead_id']} - {$pending['reward_title']}");
                } else {
                    $failed++;
                    $this->log("❌ Fehler bei Lead #{$pending['lead_id']}: {$result['message']}");
                }
                
                // Kleine Pause zwischen Requests (Rate-Limiting)
                usleep(500000); // 0.5 Sekunden
            }
            
            $this->log("ENDE - Ausgeliefert: $delivered | Fehlgeschlagen: $failed");
            
            return [
                'success' => true,
                'delivered' => $delivered,
                'failed' => $failed,
                'total' => count($pendingRewards)
            ];
            
        } catch (Exception $e) {
            $this->log("FEHLER: " . $e->getMessage());
            error_log("Reward Auto-Delivery Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Findet alle Leads mit erreichten aber noch nicht ausgelieferten Belohnungen
     */
    private function findPendingRewards() {
        $sql = "
            SELECT 
                lu.id as lead_id,
                lu.user_id as customer_id,
                lu.email as lead_email,
                lu.name as lead_name,
                lu.successful_referrals,
                lu.referral_code,
                rd.id as reward_id,
                rd.tier_level,
                rd.tier_name,
                rd.reward_title,
                rd.reward_type,
                rd.required_referrals,
                rd.freebie_id
            FROM lead_users lu
            INNER JOIN reward_definitions rd ON rd.user_id = lu.user_id
            WHERE 
                lu.successful_referrals >= rd.required_referrals
                AND rd.is_active = 1
                AND (rd.freebie_id IS NULL OR rd.freebie_id = lu.freebie_id)
                AND NOT EXISTS (
                    SELECT 1 FROM reward_deliveries rde
                    WHERE rde.lead_id = lu.id 
                    AND rde.reward_id = rd.id
                )
            ORDER BY lu.id, rd.tier_level
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Liefert eine einzelne Belohnung aus
     */
    private function deliverReward($pending) {
        try {
            // 1. Email über Customer's API versenden
            $emailResult = $this->deliveryService->sendRewardEmail(
                $pending['customer_id'],
                $pending['lead_id'],
                $pending['reward_id']
            );
            
            if (!$emailResult['success']) {
                return $emailResult;
            }
            
            // 2. Delivery tracken
            $this->trackDelivery(
                $pending['lead_id'],
                $pending['reward_id'],
                $emailResult['delivery_method'] ?? 'unknown',
                $emailResult['provider'] ?? null,
                $emailResult
            );
            
            // 3. rewards_earned Counter erhöhen
            $this->incrementRewardsEarned($pending['lead_id']);
            
            return [
                'success' => true,
                'message' => 'Belohnung erfolgreich ausgeliefert',
                'delivery_method' => $emailResult['delivery_method'],
                'lead_email' => $pending['lead_email']
            ];
            
        } catch (Exception $e) {
            error_log("Delivery Error for Lead #{$pending['lead_id']}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Tracked Belohnungsauslieferung in DB
     */
    private function trackDelivery($leadId, $rewardId, $deliveryMethod, $provider, $details) {
        $stmt = $this->pdo->prepare("
            INSERT INTO reward_deliveries 
            (lead_id, reward_id, delivery_method, delivered_at, delivery_details)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        
        $stmt->execute([
            $leadId,
            $rewardId,
            $deliveryMethod,
            json_encode([
                'provider' => $provider,
                'timestamp' => date('c'),
                'details' => $details
            ])
        ]);
    }
    
    /**
     * Erhöht rewards_earned Counter beim Lead
     */
    private function incrementRewardsEarned($leadId) {
        // Prüfe ob Spalte existiert
        $checkColumn = $this->pdo->query("
            SHOW COLUMNS FROM lead_users LIKE 'rewards_earned'
        ");
        
        if ($checkColumn->rowCount() == 0) {
            // Spalte erstellen
            $this->pdo->exec("
                ALTER TABLE lead_users 
                ADD COLUMN rewards_earned INT DEFAULT 0 AFTER successful_referrals
            ");
        }
        
        // Counter erhöhen
        $stmt = $this->pdo->prepare("
            UPDATE lead_users 
            SET rewards_earned = COALESCE(rewards_earned, 0) + 1
            WHERE id = ?
        ");
        $stmt->execute([$leadId]);
    }
    
    /**
     * Prüft ob Tabelle existiert
     */
    private function tableExists($tableName) {
        $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Erstellt reward_deliveries Tabelle falls nicht vorhanden
     */
    private function createRewardDeliveriesTable() {
        $this->log("Erstelle reward_deliveries Tabelle...");
        
        $sql = "
        CREATE TABLE IF NOT EXISTS reward_deliveries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            reward_id INT NOT NULL,
            delivery_method VARCHAR(50) DEFAULT 'email' COMMENT 'email, tag_trigger, direct_email',
            delivered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            delivery_details JSON NULL,
            INDEX idx_lead (lead_id),
            INDEX idx_reward (reward_id),
            UNIQUE KEY unique_delivery (lead_id, reward_id),
            FOREIGN KEY (lead_id) REFERENCES lead_users(id) ON DELETE CASCADE,
            FOREIGN KEY (reward_id) REFERENCES reward_definitions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->pdo->exec($sql);
        $this->log("✅ reward_deliveries Tabelle erstellt");
    }
    
    /**
     * Logging Helper
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] {$this->logPrefix} {$message}\n";
    }
}

// CLI oder Web ausführen
if (php_sapi_name() === 'cli' || basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    
    // Bei Web-Request: JSON Output
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
    }
    
    $autoDelivery = new RewardAutoDelivery();
    $result = $autoDelivery->run();
    
    if (php_sapi_name() !== 'cli') {
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
    
    exit($result['success'] ? 0 : 1);
}
