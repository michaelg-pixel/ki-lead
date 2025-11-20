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

// 2. Session und Auth prüfen
echo "\n=== STEP 2: Session & Auth Check ===\n";
startSecureSession();

if (!isLoggedIn()) {
    echo "❌ Nicht eingeloggt!\n";
    echo "Bitte erst einloggen: https://app.mehr-infos-jetzt.de/login.php\n";
    exit;
}

$customer_id = $_SESSION['user_id'] ?? null;
if (!$customer_id) {
    echo "❌ Keine User ID in Session!\n";
    exit;
}

echo "✅ Eingeloggt als User ID: $customer_id\n";
echo "✅ Email: " . ($_SESSION['email'] ?? 'N/A') . "\n";
echo "✅ Role: " . ($_SESSION['role'] ?? 'N/A') . "\n";

// 3. DB-Verbindung testen
echo "\n=== STEP 3: DB-Verbindung ===\n";
try {
    $pdo = getDBConnection();
    echo "✅ DB-Verbindung erfolgreich\n";
} catch (Exception $e) {
    echo "❌ DB-Verbindung fehlgeschlagen: " . $e->getMessage() . "\n";
    exit;
}

// 4. Test-Daten vorbereiten (wie vom Frontend gesendet)
echo "\n=== STEP 4: Test-Daten ===\n";
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

// 5. Provider validieren
echo "\n=== STEP 5: Provider Validierung ===\n";
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

// 6. Custom Settings vorbereiten
echo "\n=== STEP 6: Custom Settings ===\n";
$customSettings = [];

// WICHTIG: api_url muss in custom_settings gespeichert werden!
if (isset($testData['api_url'])) {
    $customSettings['api_url'] = $testData['api_url'];
    echo "✅ API URL zu custom_settings hinzugefügt: {$testData['api_url']}\n";
}

$optionalFields = ['username', 'password', 'account_url', 'base_url', 'sender_email', 'sender_name'];
foreach ($optionalFields as $field) {
    if (isset($testData[$field])) {
        $customSettings[$field] = $testData[$field];
        echo "✅ $field hinzugefügt\n";
    }
}

echo "\nCustom Settings JSON:\n";
echo json_encode($customSettings, JSON_PRETTY_PRINT) . "\n";

// 7. Prüfen ob bereits eine Konfiguration existiert
echo "\n=== STEP 7: Existierende Config prüfen ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT id, provider, is_active, is_verified, custom_settings 
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
        
        if ($existing['custom_settings']) {
            $existingCustom = json_decode($existing['custom_settings'], true);
            echo "   Custom Settings:\n";
            echo "      " . print_r($existingCustom, true) . "\n";
            
            if (isset($existingCustom['api_url'])) {
                echo "   ⚠️  Aktuelle API URL: {$existingCustom['api_url']}\n";
            } else {
                echo "   ⚠️  Keine API URL gespeichert!\n";
            }
        }
        
        echo "   → Wird UPDATE ausführen\n";
    } else {
        echo "ℹ️  Keine existierende Config gefunden\n";
        echo "   → Wird INSERT ausführen\n";
    }
} catch (PDOException $e) {
    echo "❌ DB-Fehler beim Prüfen: " . $e->getMessage() . "\n";
    exit;
}

// 8. SQL Statement vorbereiten
echo "\n=== STEP 8: SQL Statement vorbereiten ===\n";

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
    
    echo "Modus: UPDATE\n";
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
    
    echo "Modus: INSERT\n";
}

echo "\nSQL:\n$sql\n\n";
echo "Parameter:\n";
foreach ($params as $i => $param) {
    if (is_null($param)) {
        echo "  [$i] = NULL\n";
    } else {
        $display = strlen($param) > 100 ? substr($param, 0, 100) . '...' : $param;
        echo "  [$i] = $display\n";
    }
}

// 9. Speichern-Button
echo "\n=== STEP 9: Speichern ===\n";

if (!isset($_POST['confirm'])) {
    echo "⚠️  Bereit zum Speichern!\n\n";
    echo "<form method='post'>";
    echo "<button type='submit' name='confirm' value='save' style='padding: 15px 30px; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; cursor: pointer; border-radius: 8px; font-size: 16px; font-weight: 600; margin-right: 10px;'>✅ JA, JETZT SPEICHERN</button>";
    echo "<a href='?' style='padding: 15px 30px; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; display: inline-block;'>❌ ABBRECHEN & NEU LADEN</a>";
    echo "</form>";
} elseif ($_POST['confirm'] === 'save') {
    echo "🚀 SPEICHERN WIRD AUSGEFÜHRT...\n\n";
    
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo "✅ ✅ ✅ ERFOLGREICH GESPEICHERT! ✅ ✅ ✅\n\n";
            echo "Betroffene Zeilen: " . $stmt->rowCount() . "\n";
            
            // Config nochmal laden zur Bestätigung
            $stmt = $pdo->prepare("
                SELECT * FROM customer_email_api_settings 
                WHERE customer_id = ? AND provider = ?
            ");
            $stmt->execute([$customer_id, $testData['provider']]);
            $saved = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($saved) {
                echo "\n=== ✅ GESPEICHERTE DATEN VERIFIZIERT ===\n";
                echo "ID: {$saved['id']}\n";
                echo "Customer ID: {$saved['customer_id']}\n";
                echo "Provider: {$saved['provider']}\n";
                echo "API Key: " . substr($saved['api_key'], 0, 10) . "..." . substr($saved['api_key'], -4) . "\n";
                echo "Start Tag: " . ($saved['start_tag'] ?? 'NULL') . "\n";
                echo "List ID: " . ($saved['list_id'] ?? 'NULL') . "\n";
                echo "Campaign ID: " . ($saved['campaign_id'] ?? 'NULL') . "\n";
                echo "Double Opt-in: " . ($saved['double_optin_enabled'] ? 'Ja' : 'Nein') . "\n";
                echo "Active: " . ($saved['is_active'] ? 'Ja' : 'Nein') . "\n";
                echo "Verified: " . ($saved['is_verified'] ? 'Ja' : 'Nein') . "\n";
                echo "Created: {$saved['created_at']}\n";
                echo "Updated: {$saved['updated_at']}\n";
                
                echo "\n=== 🔍 CUSTOM SETTINGS ===\n";
                if ($saved['custom_settings']) {
                    $customSettingsDecoded = json_decode($saved['custom_settings'], true);
                    echo json_encode($customSettingsDecoded, JSON_PRETTY_PRINT) . "\n";
                    
                    if (isset($customSettingsDecoded['api_url'])) {
                        echo "\n✅ ✅ ✅ API URL WURDE KORREKT GESPEICHERT! ✅ ✅ ✅\n";
                        echo "API URL: {$customSettingsDecoded['api_url']}\n";
                    } else {
                        echo "\n❌ ❌ ❌ API URL FEHLT IN CUSTOM_SETTINGS! ❌ ❌ ❌\n";
                    }
                } else {
                    echo "❌ Keine custom_settings gespeichert!\n";
                }
                
                echo "\n\n🎉 🎉 🎉 ERFOLG! 🎉 🎉 🎉\n";
                echo "Du kannst jetzt zurück zum Dashboard gehen und die normale Speichern-Funktion testen.\n";
                echo "\n<a href='/customer/dashboard.php?page=empfehlungsprogramm' style='padding: 15px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; display: inline-block; margin-top: 20px;'>📊 Zurück zum Dashboard</a>";
            }
        } else {
            echo "❌ Speichern fehlgeschlagen (kein Result)\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ ❌ ❌ FEHLER BEIM SPEICHERN! ❌ ❌ ❌\n\n";
        echo "Error Code: " . $e->getCode() . "\n";
        echo "Error Message: " . $e->getMessage() . "\n";
        
        if (isset($e->errorInfo)) {
            echo "SQL State: " . $e->errorInfo[0] . "\n";
            echo "Error Info: " . print_r($e->errorInfo, true) . "\n";
        }
        
        echo "\nStack Trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
}

echo "\n=== ENDE DES DEBUG SCRIPTS ===\n";
echo "</pre>";
?>