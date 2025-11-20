<?php
/**
 * DEBUG SCRIPT: Test API Settings Save
 * Simuliert den Save-Request und zeigt alle Fehler detailliert an
 */

// Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 API Settings Save Debug Script</h1>";
echo "<pre>";

// 1. Includes prüfen
echo "=== STEP 1: Includes laden ===\n";
try {
    require_once __DIR__ . '/../../config/database.php';
    echo "✅ Database config geladen\n";
} catch (Exception $e) {
    echo "❌ Database config Fehler: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../../includes/auth.php';
    echo "✅ Auth geladen\n";
} catch (Exception $e) {
    echo "❌ Auth Fehler: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../../customer/includes/EmailProviders.php';
    echo "✅ EmailProviders geladen\n";
} catch (Exception $e) {
    echo "❌ EmailProviders Fehler: " . $e->getMessage() . "\n";
    exit;
}

// 2. Auth prüfen
echo "\n=== STEP 2: Auth Check ===\n";
$customer_id = check_auth();
if (!$customer_id) {
    echo "❌ Nicht autorisiert - Bitte einloggen!\n";
    exit;
}
echo "✅ Customer ID: $customer_id\n";

// 3. Test-Daten vorbereiten (wie vom Frontend gesendet)
echo "\n=== STEP 3: Test-Daten ===\n";
$testData = [
    'provider' => 'quentn',
    'api_url' => 'https://pk1bh1.eu-1.quentn.com/public/api/v1/',
    'api_key' => 'm-gkCLAXFVewwguCP1ZCm9zFFi_bauieZPl21EkGUqo',
    'start_tag' => null,
    'list_id' => null,
    'campaign_id' => null,
    'double_optin_enabled' => true
];

echo "Provider: {$testData['provider']}\n";
echo "API URL: {$testData['api_url']}\n";
echo "API Key: " . substr($testData['api_key'], 0, 10) . "...\n";
echo "Double Opt-in: " . ($testData['double_optin_enabled'] ? 'Ja' : 'Nein') . "\n";

// 4. Provider validieren
echo "\n=== STEP 4: Provider Validierung ===\n";
try {
    $supportedProviders = EmailProviderFactory::getSupportedProviders();
    if (isset($supportedProviders[$testData['provider']])) {
        echo "✅ Provider '{$testData['provider']}' ist gültig\n";
        echo "Provider Info: " . print_r($supportedProviders[$testData['provider']], true) . "\n";
    } else {
        echo "❌ Provider '{$testData['provider']}' ist nicht unterstützt\n";
        echo "Verfügbare Provider: " . implode(', ', array_keys($supportedProviders)) . "\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Provider-Validierung fehlgeschlagen: " . $e->getMessage() . "\n";
    exit;
}

// 5. Custom Settings vorbereiten
echo "\n=== STEP 5: Custom Settings ===\n";
$customSettings = [];

// WICHTIG: api_url muss in custom_settings gespeichert werden!
if (isset($testData['api_url'])) {
    $customSettings['api_url'] = $testData['api_url'];
    echo "✅ API URL zu custom_settings hinzugefügt\n";
}

$optionalFields = ['username', 'password', 'account_url', 'base_url', 'sender_email', 'sender_name'];
foreach ($optionalFields as $field) {
    if (isset($testData[$field])) {
        $customSettings[$field] = $testData[$field];
        echo "✅ $field hinzugefügt\n";
    }
}

echo "Custom Settings JSON:\n";
echo json_encode($customSettings, JSON_PRETTY_PRINT) . "\n";

// 6. Prüfen ob bereits eine Konfiguration existiert
echo "\n=== STEP 6: Existierende Config prüfen ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT id, provider, is_active, is_verified 
        FROM customer_email_api_settings 
        WHERE customer_id = ? AND provider = ?
    ");
    $stmt->execute([$customer_id, $testData['provider']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "⚠️  Existierende Config gefunden:\n";
        echo "   ID: {$existing['id']}\n";
        echo "   Provider: {$existing['provider']}\n";
        echo "   Active: " . ($existing['is_active'] ? 'Ja' : 'Nein') . "\n";
        echo "   Verified: " . ($existing['is_verified'] ? 'Ja' : 'Nein') . "\n";
        echo "   → Wird UPDATE ausführen\n";
    } else {
        echo "ℹ️  Keine existierende Config gefunden\n";
        echo "   → Wird INSERT ausführen\n";
    }
} catch (PDOException $e) {
    echo "❌ DB-Fehler beim Prüfen: " . $e->getMessage() . "\n";
    exit;
}

// 7. SQL ausführen (DRY RUN)
echo "\n=== STEP 7: SQL Statement (DRY RUN) ===\n";

if ($existing) {
    $sql = "
        UPDATE customer_email_api_settings SET
            api_key = ?,
            api_secret = ?,
            start_tag = ?,
            list_id = ?,
            campaign_id = ?,
            double_optin_enabled = ?,
            double_optin_form_id = ?,
            custom_settings = ?,
            is_active = TRUE,
            is_verified = FALSE,
            updated_at = NOW()
        WHERE customer_id = ? AND provider = ?
    ";
    
    $params = [
        $testData['api_key'],
        $testData['api_secret'] ?? null,
        $testData['start_tag'] ?? null,
        $testData['list_id'] ?? null,
        $testData['campaign_id'] ?? null,
        $testData['double_optin_enabled'] ? 1 : 0,
        $testData['double_optin_form_id'] ?? null,
        json_encode($customSettings),
        $customer_id,
        $testData['provider']
    ];
} else {
    $sql = "
        INSERT INTO customer_email_api_settings (
            customer_id,
            provider,
            api_key,
            api_secret,
            start_tag,
            list_id,
            campaign_id,
            double_optin_enabled,
            double_optin_form_id,
            custom_settings,
            is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
    ";
    
    $params = [
        $customer_id,
        $testData['provider'],
        $testData['api_key'],
        $testData['api_secret'] ?? null,
        $testData['start_tag'] ?? null,
        $testData['list_id'] ?? null,
        $testData['campaign_id'] ?? null,
        $testData['double_optin_enabled'] ? 1 : 0,
        $testData['double_optin_form_id'] ?? null,
        json_encode($customSettings)
    ];
}

echo "SQL:\n$sql\n\n";
echo "Parameter:\n";
foreach ($params as $i => $param) {
    echo "  [$i] = " . (is_null($param) ? 'NULL' : (strlen($param) > 50 ? substr($param, 0, 50) . '...' : $param)) . "\n";
}

// 8. Tatsächlich speichern
echo "\n=== STEP 8: Tatsächlich speichern ===\n";
echo "⚠️  Soll ich wirklich speichern? (YES = speichern, NO = abbrechen)\n";
echo "<form method='post'>";
echo "<input type='hidden' name='confirm' value='1'>";
echo "<button type='submit' name='action' value='save' style='padding: 10px 20px; background: green; color: white; border: none; cursor: pointer; margin-right: 10px;'>✅ JA, SPEICHERN</button>";
echo "<button type='submit' name='action' value='cancel' style='padding: 10px 20px; background: red; color: white; border: none; cursor: pointer;'>❌ ABBRECHEN</button>";
echo "</form>";

if (isset($_POST['confirm']) && $_POST['action'] === 'save') {
    echo "\n=== SPEICHERN WIRD AUSGEFÜHRT ===\n";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo "✅ ERFOLGREICH GESPEICHERT!\n";
        echo "Betroffene Zeilen: " . $stmt->rowCount() . "\n";
        
        // Config nochmal laden zur Bestätigung
        $stmt = $pdo->prepare("
            SELECT * FROM customer_email_api_settings 
            WHERE customer_id = ? AND provider = ?
        ");
        $stmt->execute([$customer_id, $testData['provider']]);
        $saved = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($saved) {
            echo "\n=== GESPEICHERTE DATEN ===\n";
            echo "ID: {$saved['id']}\n";
            echo "Provider: {$saved['provider']}\n";
            echo "API Key: " . substr($saved['api_key'], 0, 10) . "...\n";
            echo "Active: " . ($saved['is_active'] ? 'Ja' : 'Nein') . "\n";
            echo "Verified: " . ($saved['is_verified'] ? 'Ja' : 'Nein') . "\n";
            echo "Custom Settings:\n";
            $customSettingsDecoded = json_decode($saved['custom_settings'], true);
            echo print_r($customSettingsDecoded, true);
            
            if (isset($customSettingsDecoded['api_url'])) {
                echo "\n✅ API URL wurde korrekt gespeichert: {$customSettingsDecoded['api_url']}\n";
            } else {
                echo "\n⚠️  API URL fehlt in custom_settings!\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ FEHLER BEIM SPEICHERN:\n";
        echo "Error Code: " . $e->getCode() . "\n";
        echo "Error Message: " . $e->getMessage() . "\n";
        echo "SQL State: " . $e->errorInfo[0] . "\n";
    }
} elseif (isset($_POST['confirm']) && $_POST['action'] === 'cancel') {
    echo "\n⚠️  Speichern abgebrochen!\n";
}

echo "\n=== ENDE DES DEBUG SCRIPTS ===\n";
echo "</pre>";
?>