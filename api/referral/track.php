<?php
/**
 * API: Tracking Pixel für externe Seiten
 * URL: /api/referral/track.php?user=123&ref=ABC123
 * Gibt 1x1 transparentes GIF zurück
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ReferralHelper.php';

// Tracking durchführen (ohne Output)
try {
    $db = Database::getInstance()->getConnection();
    $referral = new ReferralHelper($db);
    
    $userId = $_GET['user'] ?? null;
    $refCode = $_GET['ref'] ?? null;
    
    if ($refCode && $userId) {
        // Validiere Referral-Code
        if ($referral->validateRefCode($refCode)) {
            // Hole IP und UserAgent
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // DSGVO-konform hashen
            $ipHash = $referral->hashIP($ip);
            $fingerprint = $referral->createFingerprint($ip, $userAgent);
            
            // Track als Pixel-Conversion
            $referral->trackConversion(
                $userId,
                $refCode,
                $ipHash,
                $userAgent,
                $fingerprint,
                'pixel'
            );
        }
    }
    
} catch (Exception $e) {
    error_log("Tracking Pixel Error: " . $e->getMessage());
}

// Immer 1x1 transparentes GIF zurückgeben
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// 1x1 transparentes GIF (Base64 dekodiert)
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
