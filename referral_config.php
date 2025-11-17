<?php
/**
 * Referral-System Konfiguration
 * Aktiviere/Deaktiviere das Empfehlungsprogramm
 */

// ========================================
// HAUPTSCHALTER
// ========================================

define('REFERRAL_SYSTEM_ENABLED', true);

// ========================================
// SITE-KONFIGURATION
// ========================================

if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://app.mehr-infos-jetzt.de');
}

// ========================================
// REFERRAL-EINSTELLUNGEN
// ========================================

// Minimale Anzahl aktiver Empfehlungen für Belohnungen
define('MIN_ACTIVE_REFERRALS', 3);

// Cookie-Laufzeit für Referral-Tracking (in Tagen)
define('REFERRAL_COOKIE_LIFETIME', 30);

// Automatische Belohnungsauslieferung aktivieren
define('AUTO_DELIVER_REWARDS', true);

// E-Mail-Benachrichtigungen für neue Empfehlungen
define('NOTIFY_ON_REFERRAL', false);

// ========================================
// BELOHNUNGS-TYPEN
// ========================================

define('REWARD_TYPE_COURSE', 'course_access');
define('REWARD_TYPE_EBOOK', 'ebook_download');
define('REWARD_TYPE_TEMPLATE', 'template_access');
define('REWARD_TYPE_CONSULT', 'consultation');
define('REWARD_TYPE_CUSTOM', 'custom');

// ========================================
// STATUS-DEFINITIONEN
// ========================================

define('REFERRAL_STATUS_PENDING', 'pending');
define('REFERRAL_STATUS_ACTIVE', 'active');
define('REFERRAL_STATUS_INACTIVE', 'inactive');

define('REWARD_STATUS_PENDING', 'pending');
define('REWARD_STATUS_READY', 'ready');
define('REWARD_STATUS_DELIVERED', 'delivered');
define('REWARD_STATUS_FAILED', 'failed');

// ========================================
// FEATURE-FLAGS
// ========================================

// Social Media Sharing aktivieren
define('ENABLE_SOCIAL_SHARING', true);

// Analytics-Tracking für Referral-Links
define('ENABLE_REFERRAL_ANALYTICS', true);

// Gamification-Elemente (Badges, Levels)
define('ENABLE_GAMIFICATION', false);

// ========================================
// HELPER-FUNKTIONEN
// ========================================

/**
 * Prüft ob das Referral-System aktiviert ist
 */
function isReferralSystemEnabled() {
    return REFERRAL_SYSTEM_ENABLED;
}

/**
 * Generiert einen eindeutigen Referral-Code
 */
function generateReferralCode($prefix = 'REF') {
    return $prefix . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Validiert einen Referral-Code
 */
function isValidReferralCode($code) {
    return preg_match('/^[A-Z0-9\-]{8,20}$/', $code);
}

?>
