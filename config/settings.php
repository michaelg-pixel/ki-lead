<?php
/**
 * System-Einstellungen
 * 
 * WICHTIG: Passe BASE_URL an deine Domain an!
 */

// Base URLs (ANPASSEN!)
define('BASE_URL', 'https://app.mehr-infos-jetzt.de');
define('ADMIN_URL', BASE_URL . '/admin');
define('CUSTOMER_URL', BASE_URL . '/customer');
define('FREEBIE_URL', BASE_URL . '/freebie');
define('PUBLIC_URL', BASE_URL . '/public');

// Upload-Verzeichnisse
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('COURSE_UPLOAD_DIR', UPLOAD_DIR . '/courses');
define('THUMBNAIL_UPLOAD_DIR', UPLOAD_DIR . '/thumbnails');
define('PDF_UPLOAD_DIR', UPLOAD_DIR . '/pdfs');

// Sicherheits-Einstellungen
define('SESSION_LIFETIME', 7200); // 2 Stunden
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);

// Digistore24 Settings
define('DIGISTORE24_WEBHOOK_SECRET', 'DEIN_SECRET_KEY'); // ÄNDERN!

// E-Mail-Einstellungen
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@ki-leadsystem.com');
define('SMTP_PASS', '');
define('SMTP_FROM_NAME', 'KI Lead-System');

// System-Status
define('MAINTENANCE_MODE', false);
define('DEBUG_MODE', true); // Temporär auf true für Debugging

// Timezone
date_default_timezone_set('Europe/Berlin');

// Error Reporting (Produktiv: nur Errors loggen!)
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}
