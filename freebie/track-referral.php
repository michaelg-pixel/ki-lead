<?php
/**
 * Referral Tracking Helper
 * Speichert ref Parameter aus URL in Session für spätere Verarbeitung
 */

// Session starten falls nicht aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfe ob ref Parameter in URL vorhanden ist
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $referralCode = trim($_GET['ref']);
    
    // In Session speichern für spätere Verwendung
    $_SESSION['referral_code'] = $referralCode;
    $_SESSION['referral_timestamp'] = time();
    
    // Optional: Cookie für längere Persistenz (30 Tage)
    setcookie('referral_code', $referralCode, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    
    error_log("Referral Code gespeichert: {$referralCode}");
}

/**
 * Gibt den gespeicherten Referral Code zurück
 * Prüft zuerst Session, dann Cookie
 */
function getReferralCode() {
    // Session prüfen
    if (isset($_SESSION['referral_code']) && !empty($_SESSION['referral_code'])) {
        return $_SESSION['referral_code'];
    }
    
    // Cookie prüfen
    if (isset($_COOKIE['referral_code']) && !empty($_COOKIE['referral_code'])) {
        return $_COOKIE['referral_code'];
    }
    
    return null;
}

/**
 * Löscht den gespeicherten Referral Code
 */
function clearReferralCode() {
    unset($_SESSION['referral_code']);
    unset($_SESSION['referral_timestamp']);
    setcookie('referral_code', '', time() - 3600, '/', '', true, true);
}
