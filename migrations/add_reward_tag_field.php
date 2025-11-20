<?php
/**
 * Migration: reward_tag Feld zu reward_definitions und customer_email_api_settings hinzufügen
 * 
 * Ermöglicht die Konfiguration eines benutzerdefinierten Tags für Belohnungs-Kampagnen.
 * Besonders wichtig für Tag-basierte Provider wie Quentn, Klick-Tipp, ActiveCampaign.
 */

require_once __DIR__ . '/../config/database.php';

class AddRewardTagField {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function up() {
        echo "🔄 Migration START: reward_tag Felder hinzufügen\n";
        
        try {
            // 1. Prüfe aktuelle Struktur von reward_definitions
            echo "  ➜ Prüfe reward_definitions Struktur...\n";
            $columns = $this->pdo->query("SHOW COLUMNS FROM reward_definitions")->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            echo "  ℹ️  Gefundene Spalten: " . implode(', ', $columnNames) . "\n";
            
            // 2. reward_tag zu reward_definitions hinzufügen
            echo "  ➜ Füge reward_tag zu reward_definitions hinzu...\n";
            
            $checkColumn = $this->pdo->query("
                SHOW COLUMNS FROM reward_definitions LIKE 'reward_tag'
            ");
            
            if ($checkColumn->rowCount() == 0) {
                // Finde die letzte Spalte für AFTER clause
                $lastColumn = end($columnNames);
                
                $this->pdo->exec("
                    ALTER TABLE reward_definitions
                    ADD COLUMN reward_tag VARCHAR(100) NULL 
                    COMMENT 'Optional: Benutzerdefinierter Tag für Kampagnen-Trigger (z.B. Optinpilot-Belohnung)'
                    AFTER {$lastColumn}
                ");
                echo "  ✅ reward_tag Feld zu reward_definitions hinzugefügt (nach {$lastColumn})\n";
            } else {
                echo "  ℹ️  reward_tag Feld existiert bereits in reward_definitions\n";
            }
            
            // 3. reward_tag zu customer_email_api_settings hinzufügen (global für alle Rewards)
            echo "  ➜ Füge reward_tag zu customer_email_api_settings hinzu...\n";
            
            // Prüfe zuerst ob Tabelle existiert
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'customer_email_api_settings'");
            
            if ($checkTable->rowCount() == 0) {
                echo "  ℹ️  Tabelle customer_email_api_settings existiert nicht - überspringe\n";
            } else {
                $checkColumn = $this->pdo->query("
                    SHOW COLUMNS FROM customer_email_api_settings LIKE 'reward_tag'
                ");
                
                if ($checkColumn->rowCount() == 0) {
                    // Finde die Spalte api_url oder eine andere gute Position
                    $apiColumns = $this->pdo->query("SHOW COLUMNS FROM customer_email_api_settings")->fetchAll(PDO::FETCH_ASSOC);
                    $apiColumnNames = array_column($apiColumns, 'Field');
                    
                    // Versuche nach api_url, sonst nach letzter Spalte
                    $afterColumn = in_array('api_url', $apiColumnNames) ? 'api_url' : end($apiColumnNames);
                    
                    $this->pdo->exec("
                        ALTER TABLE customer_email_api_settings
                        ADD COLUMN reward_tag VARCHAR(100) NULL 
                        COMMENT 'Optional: Globaler Tag für alle Belohnungen (falls nicht in reward_definitions definiert)'
                        AFTER {$afterColumn}
                    ");
                    echo "  ✅ reward_tag Feld zu customer_email_api_settings hinzugefügt (nach {$afterColumn})\n";
                } else {
                    echo "  ℹ️  reward_tag Feld existiert bereits in customer_email_api_settings\n";
                }
            }
            
            echo "✅ Migration ERFOLGREICH abgeschlossen\n";
            
            // Hinweise ausgeben
            $this->printUsageInstructions();
            
            return true;
            
        } catch (Exception $e) {
            echo "❌ Migration FEHLER: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function down() {
        echo "🔄 Migration ROLLBACK: reward_tag Felder entfernen\n";
        
        try {
            // reward_tag von reward_definitions entfernen
            $checkColumn = $this->pdo->query("
                SHOW COLUMNS FROM reward_definitions LIKE 'reward_tag'
            ");
            
            if ($checkColumn->rowCount() > 0) {
                $this->pdo->exec("
                    ALTER TABLE reward_definitions
                    DROP COLUMN reward_tag
                ");
                echo "  ✅ reward_tag Feld von reward_definitions entfernt\n";
            }
            
            // reward_tag von customer_email_api_settings entfernen
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'customer_email_api_settings'");
            
            if ($checkTable->rowCount() > 0) {
                $checkColumn = $this->pdo->query("
                    SHOW COLUMNS FROM customer_email_api_settings LIKE 'reward_tag'
                ");
                
                if ($checkColumn->rowCount() > 0) {
                    $this->pdo->exec("
                        ALTER TABLE customer_email_api_settings
                        DROP COLUMN reward_tag
                    ");
                    echo "  ✅ reward_tag Feld von customer_email_api_settings entfernt\n";
                }
            }
            
            echo "✅ Rollback ERFOLGREICH abgeschlossen\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Rollback FEHLER: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function printUsageInstructions() {
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 ANLEITUNG: reward_tag verwenden\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "\n";
        echo "Das reward_tag Feld ermöglicht die Verwendung eigener Tag-Namen für\n";
        echo "Belohnungs-Kampagnen in deinem Email-Marketing-System.\n";
        echo "\n";
        echo "🎯 VERWENDUNG:\n";
        echo "\n";
        echo "OPTION 1 - Pro Belohnung (reward_definitions):\n";
        echo "  UPDATE reward_definitions \n";
        echo "  SET reward_tag = 'Optinpilot-Belohnung'\n";
        echo "  WHERE user_id = DEINE_USER_ID AND tier_level = 1;\n";
        echo "\n";
        echo "OPTION 2 - Global für alle Belohnungen (customer_email_api_settings):\n";
        echo "  UPDATE customer_email_api_settings \n";
        echo "  SET reward_tag = 'Optinpilot-Belohnung'\n";
        echo "  WHERE customer_id = DEINE_USER_ID;\n";
        echo "\n";
        echo "🔍 TAG-PRIORITÄT:\n";
        echo "  1. reward_tag aus reward_definitions (spezifisch pro Belohnung)\n";
        echo "  2. reward_tag aus customer_email_api_settings (global)\n";
        echo "  3. Fallback: 'reward_X_earned' (dynamisch mit tier_level)\n";
        echo "\n";
        echo "⚡ BEISPIEL für Quentn:\n";
        echo "  - In Quentn eine Kampagne mit Start-Tag 'Optinpilot-Belohnung' erstellen\n";
        echo "  - reward_tag auf 'Optinpilot-Belohnung' setzen (siehe oben)\n";
        echo "  - Wenn Lead Belohnung erreicht, wird dieser Tag automatisch gesetzt\n";
        echo "  - Quentn-Kampagne wird getriggert und Email wird versendet\n";
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "\n";
    }
}

// Migration ausführen
if (php_sapi_name() === 'cli' || basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    
    $migration = new AddRewardTagField();
    
    // Prüfe Parameter
    $command = $argv[1] ?? 'up';
    
    if ($command === 'down') {
        $result = $migration->down();
    } else {
        $result = $migration->up();
    }
    
    exit($result ? 0 : 1);
}
