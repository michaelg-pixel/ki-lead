<?php
/**
 * Backup System - Konfiguration
 * Separate Admin-Oberfläche für Backup-Verwaltung
 */

// Backup-System Authentifizierung (UNBEDINGT ÄNDERN!)
define('BACKUP_ADMIN_USER', 'admin');
define('BACKUP_ADMIN_PASS', password_hash('DeinSicheresPasswort123!', PASSWORD_DEFAULT));

// Backup-Verzeichnisse
define('BACKUP_ROOT_DIR', __DIR__ . '/backups');
define('BACKUP_DB_DIR', BACKUP_ROOT_DIR . '/database');
define('BACKUP_FILES_DIR', BACKUP_ROOT_DIR . '/files');
define('BACKUP_LOGS_DIR', BACKUP_ROOT_DIR . '/logs');

// Projekt-Root (wird gesichert)
define('PROJECT_ROOT', dirname(__DIR__));

// Backup-Einstellungen
define('BACKUP_RETENTION_DAYS', 30); // Backups älter als X Tage werden gelöscht
define('MAX_BACKUPS_PER_TYPE', 50);  // Maximale Anzahl Backups pro Typ

// Datenbank-Konfiguration (aus deiner config laden)
require_once PROJECT_ROOT . '/config/database.php';

// Externe Speicherorte (Optional)
$externalStorageConfig = [
    'ftp' => [
        'enabled' => false,
        'host' => '',
        'port' => 21,
        'username' => '',
        'password' => '',
        'remote_path' => '/backups'
    ],
    'local_external' => [
        'enabled' => false,
        'path' => '/mnt/external-backup' // Z.B. externe Festplatte
    ],
    'cloud' => [
        'enabled' => false,
        'provider' => 'generic', // generic, aws, google, etc.
        'endpoint' => '',
        'access_key' => '',
        'secret_key' => '',
        'bucket' => ''
    ]
];

// Backup-Zeitplan (für Cronjobs)
$backupSchedule = [
    'database' => '0 2 * * *',  // Täglich um 2:00 Uhr
    'files' => '0 3 * * 0',     // Wöchentlich Sonntags um 3:00 Uhr
    'full' => '0 4 1 * *'       // Monatlich am 1. um 4:00 Uhr
];

// Excludierte Verzeichnisse beim Datei-Backup
$excludeDirectories = [
    '/backup-system',
    '/backups',
    '/.git',
    '/node_modules',
    '/vendor/cache'
];

// Kompression aktivieren
define('BACKUP_COMPRESS', true); // .tar.gz statt .tar

// Backup-Benachrichtigungen
define('BACKUP_NOTIFY_EMAIL', ''); // Leer lassen = keine E-Mail-Benachrichtigungen
define('BACKUP_NOTIFY_ON_SUCCESS', false);
define('BACKUP_NOTIFY_ON_ERROR', true);

// Sicherheit: Verzeichnisse erstellen falls nicht vorhanden
$dirs = [BACKUP_ROOT_DIR, BACKUP_DB_DIR, BACKUP_FILES_DIR, BACKUP_LOGS_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// .htaccess für Backup-Verzeichnis erstellen (Schutz)
$htaccess = BACKUP_ROOT_DIR . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}
