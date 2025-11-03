<?php
/**
 * Admin API: Export Referral Statistics as CSV
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/ReferralHelper.php';

session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    die('Unauthorized');
}

try {
    $db = Database::getInstance()->getConnection();
    $referral = new ReferralHelper($db);
    
    // Hole alle Stats
    $allStats = $referral->getAllStats();
    
    // CSV Headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="referral-stats-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM für UTF-8
    echo "\xEF\xBB\xBF";
    
    // CSV Output
    $output = fopen('php://output', 'w');
    
    // Header-Zeile
    fputcsv($output, [
        'Customer ID',
        'E-Mail',
        'Firmenname',
        'Referral-Code',
        'Status',
        'Gesamt-Klicks',
        'Unique-Klicks',
        'Conversions',
        'Verdächtige Conversions',
        'Registrierte Leads',
        'Bestätigte Leads',
        'Conversion Rate (%)',
        'Letzter Klick',
        'Letzte Conversion',
        'Letzte Aktualisierung'
    ]);
    
    // Daten-Zeilen
    foreach ($allStats as $stat) {
        fputcsv($output, [
            $stat['id'],
            $stat['email'],
            $stat['company_name'] ?: '-',
            $stat['referral_code'],
            $stat['referral_enabled'] ? 'Aktiv' : 'Inaktiv',
            $stat['total_clicks'],
            $stat['unique_clicks'],
            $stat['total_conversions'],
            $stat['suspicious_conversions'],
            $stat['total_leads'],
            $stat['confirmed_leads'],
            $stat['conversion_rate'],
            $stat['last_click_at'] ?: '-',
            $stat['last_conversion_at'] ?: '-',
            $stat['updated_at']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("CSV Export Error: " . $e->getMessage());
    http_response_code(500);
    die('Export fehlgeschlagen');
}
