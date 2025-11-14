<?php
require_once '../includes/quentn_helpers.php';

$testEmail = '12@abnehmen-fitness.com';
$testFirstName = 'Michael';
$testLastName = 'Test';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Final Quentn Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 40px 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #1f2937; margin-bottom: 32px; }
        .test-box {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-box h2 { color: #374151; margin-bottom: 16px; }
        .result {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
        }
        .success {
            border-color: #10b981;
            background: #d1fae5;
            color: #065f46;
        }
        .error {
            border-color: #ef4444;
            background: #fee2e2;
            color: #991b1b;
        }
        .btn {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 12px;
        }
        .btn:hover { background: #7c3aed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Quentn API Final Test</h1>
        
        <?php if (isset($_POST['test_create'])): ?>
        <div class="test-box">
            <h2>🧪 Test: Kontakt erstellen mit Tags</h2>
            <?php
            $success = quentnCreateContact(
                $testEmail,
                $testFirstName,
                $testLastName,
                ['registration', 'customer', 'kunde optinpilot']
            );
            
            if ($success) {
                echo '<div class="result success">✅ SUCCESS! Kontakt wurde erstellt!
                
Nächste Schritte:
1. Prüfe in Quentn ob der Kontakt da ist
2. Prüfe ob die Tags gesetzt sind
3. Prüfe ob die Campaign getriggert wird</div>';
            } else {
                echo '<div class="result error">❌ FEHLER! Schaue in die PHP Error Logs für Details</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_find'])): ?>
        <div class="test-box">
            <h2>🔍 Test: Kontakt finden</h2>
            <?php
            $contact = quentnFindContactByEmail($testEmail);
            
            if ($contact) {
                echo '<div class="result success">✅ Kontakt gefunden!

' . json_encode($contact, JSON_PRETTY_PRINT) . '</div>';
            } else {
                echo '<div class="result error">❌ Kontakt nicht gefunden</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_tags'))): ?>
        <div class="test-box">
            <h2>🏷️ Test: Alle Tags abrufen</h2>
            <?php
            $tags = quentnGetAllTags();
            
            if (!empty($tags)) {
                echo '<div class="result success">✅ ' . count($tags) . ' Tags gefunden:

';
                foreach (array_slice($tags, 0, 10) as $tag) {
                    echo '• ' . ($tag['name'] ?? 'N/A') . ' (ID: ' . ($tag['id'] ?? 'N/A') . ')' . "\n";
                }
                if (count($tags) > 10) {
                    echo "\n... und " . (count($tags) - 10) . " weitere";
                }
                echo '</div>';
            } else {
                echo '<div class="result error">❌ Keine Tags gefunden oder API Fehler</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_password_reset'])): ?>
        <div class="test-box">
            <h2>🔐 Test: Passwort-Reset Flow</h2>
            <?php
            // 1. Finde Kontakt
            $contact = quentnFindContactByEmail($testEmail);
            
            if (!$contact) {
                echo '<div class="result error">❌ Kontakt nicht gefunden! Bitte erst "Kontakt erstellen" testen.</div>';
            } else {
                echo '<div class="result success">✅ Schritt 1: Kontakt gefunden (ID: ' . $contact['id'] . ')</div>';
                
                // 2. Setze Custom Field
                $resetLink = 'https://app.mehr-infos-jetzt.de/public/password-reset.php?token=TEST123';
                $updated = quentnUpdateCustomField($contact['id'], 'reset_link', $resetLink);
                
                if ($updated) {
                    echo '<div class="result success">✅ Schritt 2: Custom Field "reset_link" gesetzt</div>';
                } else {
                    echo '<div class="result error">❌ Schritt 2: Custom Field konnte nicht gesetzt werden</div>';
                }
                
                // 3. Füge Tag hinzu
                $tagAdded = quentnAddTagsByName($contact['id'], ['kunde optinpilot']);
                
                if ($tagAdded) {
                    echo '<div class="result success">✅ Schritt 3: Tag "kunde optinpilot" hinzugefügt

🎯 Campaign sollte jetzt getriggert werden!</div>';
                } else {
                    echo '<div class="result error">❌ Schritt 3: Tag konnte nicht hinzugefügt werden</div>';
                }
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="test-box">
            <h2>🧪 Tests ausführen</h2>
            <p style="margin-bottom: 16px;">Test-E-Mail: <strong><?php echo $testEmail; ?></strong></p>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_tags" class="btn">1. Tags abrufen</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_create" class="btn">2. Kontakt erstellen</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_find" class="btn">3. Kontakt finden</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_password_reset" class="btn">4. Password-Reset</button>
            </form>
        </div>

        <div class="test-box">
            <h2>📋 Checkliste</h2>
            <ol style="line-height: 1.8; color: #374151;">
                <li>✅ API URL gefixt: <code>/public/api/V1/contact</code></li>
                <li>✅ Endpoints korrigiert: <code>/contact</code> statt <code>/contacts</code></li>
                <li>✅ Helper-Funktionen erstellt</li>
                <li>🔄 Teste jetzt die API</li>
                <li>🔄 Prüfe ob Campaign in Quentn getriggert wird</li>
            </ol>
        </div>
    </div>
</body>
</html>
