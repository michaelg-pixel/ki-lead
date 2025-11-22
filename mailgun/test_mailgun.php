<?php
/**
 * Mailgun Test-Script
 * Testet den Email-Versand über Mailgun
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/MailgunService.php';

echo "🧪 Mailgun Test-Script\n";
echo "====================\n\n";

try {
    $pdo = getDBConnection();
    echo "✅ Datenbankverbindung hergestellt\n\n";
    
    // Test-Daten
    $testLead = [
        'email' => 'test@optinpilot.de', // <-- HIER DEINE EMAIL EINTRAGEN!
        'name' => 'Test Lead',
        'referral_code' => 'TEST123',
        'successful_referrals' => 5,
        'freebie_id' => 1
    ];
    
    $testReward = [
        'title' => 'Test-Belohnung',
        'description' => 'Dies ist eine Test-Belohnung zur Überprüfung des Email-Versands.',
        'warning_text' => 'Dies ist nur ein Test!'
    ];
    
    $testCustomer = [
        'company_name' => 'Opt-in Pilot Test',
        'company_imprint_html' => 'Opt-in Pilot<br>Teststraße 123<br>12345 Teststadt',
        'id' => 1
    ];
    
    echo "📧 Sende Test-Email an: " . $testLead['email'] . "\n\n";
    
    // Mailgun Service initialisieren
    $mailgun = new MailgunService();
    
    // Belohnungs-Email senden
    $result = $mailgun->sendRewardEmail($testLead, $testReward, $testCustomer);
    
    if ($result['success']) {
        echo "✅ EMAIL ERFOLGREICH VERSENDET!\n";
        echo "Message ID: " . $result['message_id'] . "\n\n";
        echo "📬 Prüfe dein Postfach (auch Spam-Ordner!)\n";
    } else {
        echo "❌ EMAIL-VERSAND FEHLGESCHLAGEN!\n";
        echo "Fehler: " . ($result['error'] ?? 'Unbekannter Fehler') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n====================\n";
echo "Test abgeschlossen.\n";
