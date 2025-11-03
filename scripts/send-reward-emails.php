<?php
/**
 * Cron Job: Send Reward Emails
 * Sendet automatisch Belohnungs-E-Mails wenn Goal erreicht
 * 
 * Empfohlene Cron-Konfiguration:
 * 0 10 * * * php /path/to/scripts/send-reward-emails.php
 */

require_once __DIR__ . '/../config/database.php';

$logFile = __DIR__ . '/../logs/reward-emails-' . date('Y-m-d') . '.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

writeLog("=== REWARD EMAIL CRON STARTED ===");

try {
    $db = Database::getInstance()->getConnection();
    
    // Finde Customers mit Auto-Send aktiviert
    $stmt = $db->query("
        SELECT 
            c.id,
            c.email,
            c.company_name,
            c.company_email,
            c.company_imprint_html,
            rr.goal_referrals,
            rr.reward_email_subject,
            rr.reward_email_template,
            rs.confirmed_leads
        FROM customers c
        JOIN referral_rewards rr ON c.id = rr.customer_id
        JOIN referral_stats rs ON c.id = rs.customer_id
        WHERE c.referral_enabled = 1
            AND rr.auto_send_reward = 1
            AND rs.confirmed_leads >= rr.goal_referrals
    ");
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Found " . count($customers) . " customers with goals reached");
    
    $sent = 0;
    $errors = 0;
    
    foreach ($customers as $customer) {
        // Finde unbenachrichtigte Leads
        $stmt = $db->prepare("
            SELECT id, email
            FROM referral_leads
            WHERE customer_id = ?
                AND confirmed = 1
                AND reward_notified = 0
            LIMIT " . $customer['goal_referrals']
        );
        $stmt->execute([$customer['id']]);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($leads)) {
            writeLog("Customer {$customer['id']}: No leads to notify");
            continue;
        }
        
        writeLog("Customer {$customer['id']}: Processing " . count($leads) . " leads");
        
        foreach ($leads as $lead) {
            try {
                // Sende E-Mail
                $success = sendRewardEmail(
                    $lead['email'],
                    $customer['company_name'],
                    $customer['company_email'],
                    $customer['company_imprint_html'],
                    $customer['reward_email_subject'] ?: 'Ihre Belohnung wartet auf Sie!',
                    $customer['reward_email_template'] ?: getDefaultRewardTemplate()
                );
                
                if ($success) {
                    // Markiere als benachrichtigt
                    $updateStmt = $db->prepare("
                        UPDATE referral_leads 
                        SET reward_notified = 1, notified_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$lead['id']]);
                    
                    $sent++;
                    writeLog("✓ Sent reward email to {$lead['email']}");
                } else {
                    $errors++;
                    writeLog("✗ Failed to send reward email to {$lead['email']}");
                }
                
                // Rate-Limiting: 1 E-Mail pro Sekunde
                sleep(1);
                
            } catch (Exception $e) {
                $errors++;
                writeLog("✗ Error sending to {$lead['email']}: " . $e->getMessage());
            }
        }
    }
    
    writeLog("=== REWARD EMAIL CRON FINISHED ===");
    writeLog("Sent: $sent | Errors: $errors");
    
} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

/**
 * Sende Belohnungs-E-Mail
 */
function sendRewardEmail($toEmail, $companyName, $companyEmail, $imprint, $subject, $template) {
    $companyName = $companyName ?: 'Mehr-Infos-Jetzt.de';
    $companyEmail = $companyEmail ?: 'noreply@mehr-infos-jetzt.de';
    $imprint = $imprint ?: getDefaultImprint();
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>🎉 Herzlichen Glückwunsch!</h1>
            </div>
            <div class='content'>
                " . $template . "
            </div>
            <div class='footer'>
                <hr>
                <small>
                Diese E-Mail wurde im Rahmen des Empfehlungsprogramms von {$companyName} versendet.<br><br>
                {$imprint}
                </small>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "From: {$companyName} <{$companyEmail}>\r\n";
    $headers .= "Reply-To: {$companyEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($toEmail, $subject, $message, $headers);
}

/**
 * Standard-Belohnungs-Template
 */
function getDefaultRewardTemplate() {
    return "
        <p>Sie haben erfolgreich am Empfehlungsprogramm teilgenommen!</p>
        <p>Als Dankeschön für Ihre Unterstützung erhalten Sie eine exklusive Belohnung.</p>
        <p>Weitere Details folgen in Kürze.</p>
        <p>Vielen Dank für Ihr Vertrauen!</p>
    ";
}

/**
 * Fallback-Impressum
 */
function getDefaultImprint() {
    return "
        <strong>KI-Lead-System</strong><br>
        Technischer Dienstleister<br>
        E-Mail: support@mehr-infos-jetzt.de
    ";
}
