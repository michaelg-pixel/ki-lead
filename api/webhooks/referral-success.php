<?php
/**
 * Webhook: Lead-Empfehlung erfolgreich
 * Wird aufgerufen wenn ein Lead erfolgreich jemanden empfohlen hat
 * Prüft automatisch Belohnungsstufen und versendet Emails
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../rewards/reward-email-service.php';

// Webhook Secret prüfen (optional für Sicherheit)
$expectedSecret = getenv('WEBHOOK_SECRET') ?? 'change-this-secret';
$providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';

if ($providedSecret !== $expectedSecret) {
    error_log("Webhook: Invalid secret provided");
    // In Produktion sollte hier abgebrochen werden
    // http_response_code(401);
    // echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    // exit;
}

// POST-Daten lesen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Eingabedaten']);
    exit;
}

// Pflichtfelder prüfen
if (empty($input['lead_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'lead_id ist erforderlich']);
    exit;
}

try {
    $leadId = (int)$input['lead_id'];
    
    // Lead-Daten laden
    $stmt = $pdo->prepare("
        SELECT lu.*, u.company_name
        FROM lead_users lu
        INNER JOIN users u ON lu.user_id = u.id
        WHERE lu.id = ?
    ");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        throw new Exception("Lead nicht gefunden");
    }
    
    error_log("Webhook: Processing rewards for lead {$leadId} ({$lead['name']})");
    
    // Belohnungs-Service initialisieren
    $rewardService = new RewardEmailService($pdo);
    
    // Belohnungen prüfen und Emails versenden
    $result = $rewardService->checkAndSendRewards($leadId);
    
    if ($result['success']) {
        error_log("Webhook: Successfully processed {$result['rewards_checked']} rewards, sent {$result['emails_sent']} emails");
        
        echo json_encode([
            'success' => true,
            'message' => "Belohnungen geprüft und {$result['emails_sent']} Email(s) versendet",
            'lead_id' => $leadId,
            'lead_name' => $lead['name'],
            'current_referrals' => $result['current_referrals'],
            'rewards_checked' => $result['rewards_checked'],
            'emails_sent' => $result['emails_sent'],
            'details' => $result['details']
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Unbekannter Fehler');
    }
    
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * WEBHOOK USAGE:
 * 
 * POST /api/webhooks/referral-success.php
 * Headers:
 *   Content-Type: application/json
 *   X-Webhook-Secret: your-webhook-secret
 * 
 * Body:
 * {
 *   "lead_id": 123
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Belohnungen geprüft und 2 Email(s) versendet",
 *   "lead_id": 123,
 *   "lead_name": "Max Mustermann",
 *   "current_referrals": 5,
 *   "rewards_checked": 3,
 *   "emails_sent": 2,
 *   "details": [
 *     {
 *       "reward_id": 1,
 *       "reward_name": "Bronze",
 *       "required_referrals": 3,
 *       "success": true,
 *       "message": "Email erfolgreich versendet via brevo"
 *     },
 *     {
 *       "reward_id": 2,
 *       "reward_name": "Silber",
 *       "required_referrals": 5,
 *       "success": true,
 *       "message": "Email erfolgreich versendet via brevo"
 *     }
 *   ]
 * }
 */