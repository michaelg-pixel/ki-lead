<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            font-size: 11px;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
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
        
        <?php if (isset($_POST['test_tags'])): ?>
        <div class="test-box">
            <h2>🏷️ Test: Alle Tags abrufen</h2>
            <?php
            try {
                $tags = quentnGetAllTags();
                
                if (!empty($tags)) {
                    echo '<div class="result success">✅ ' . count($tags) . ' Tags gefunden!

Suche nach "kunde optinpilot"...' . "\n";
                    
                    $foundTag = false;
                    foreach ($tags as $tag) {
                        if (isset($tag['name']) && strtolower($tag['name']) === 'kunde optinpilot') {
                            $foundTag = true;
                            echo '✅ Tag "kunde optinpilot" gefunden! (ID: ' . $tag['id'] . ')' . "\n";
                            break;
                        }
                    }
                    
                    if (!$foundTag) {
                        echo '⚠️  Tag "kunde optinpilot" NICHT gefunden!
Du musst diesen Tag in Quentn erstellen.' . "\n";
                    }
                    
                    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "ALLE " . count($tags) . " TAGS:\n";
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
                    foreach ($tags as $tag) {
                        echo '• ' . ($tag['name'] ?? 'N/A') . ' (ID: ' . ($tag['id'] ?? 'N/A') . ')' . "\n";
                    }
                    echo '</div>';
                } else {
                    echo '<div class="result error">❌ Keine Tags gefunden oder API Fehler</div>';
                }
            } catch (Exception $e) {
                echo '<div class="result error">❌ Exception: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_create'])): ?>
        <div class="test-box">
            <h2>🧪 Test: Kontakt erstellen mit Tags</h2>
            <?php
            try {
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
            } catch (Exception $e) {
                echo '<div class="result error">❌ Exception: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_find'])): ?>
        <div class="test-box">
            <h2>🔍 Test: Kontakt finden</h2>
            <?php
            try {
                $contact = quentnFindContactByEmail($testEmail);
                
                if ($contact) {
                    echo '<div class="result success">✅ Kontakt gefunden!

' . json_encode($contact, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</div>';
                } else {
                    echo '<div class="result error">❌ Kontakt nicht gefunden
Bitte erst "Kontakt erstellen" ausführen</div>';
                }
            } catch (Exception $e) {
                echo '<div class="result error">❌ Exception: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_password_reset'])): ?>
        <div class="test-box">
            <h2>🔐 Test: Passwort-Reset Flow</h2>
            <?php
            try {
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
                        echo '<div class="result error">❌ Schritt 2: Custom Field konnte nicht gesetzt werden
Stelle sicher dass das Custom Field "reset_link" in Quentn existiert!</div>';
                    }
                    
                    // 3. Füge Tag hinzu
                    $tagAdded = quentnAddTagsByName($contact['id'], ['kunde optinpilot']);
                    
                    if ($tagAdded) {
                        echo '<div class="result success">✅ Schritt 3: Tag "kunde optinpilot" hinzugefügt

🎯 Campaign sollte jetzt getriggert werden!
Prüfe in Quentn ob die E-Mail versendet wird.</div>';
                    } else {
                        echo '<div class="result error">❌ Schritt 3: Tag konnte nicht hinzugefügt werden
Stelle sicher dass der Tag "kunde optinpilot" in Quentn existiert!</div>';
                    }
                }
            } catch (Exception $e) {
                echo '<div class="result error">❌ Exception: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="test-box">
            <h2>🧪 Tests ausführen</h2>
            <p style="margin-bottom: 16px;">Test-E-Mail: <strong><?php echo htmlspecialchars($testEmail); ?></strong></p>
            
            <form method="POST">
                <button type="submit" name="test_tags" class="btn">1. Alle 756 Tags anzeigen</button>
            </form>
            
            <form method="POST" style="margin-top: 12px;">
                <button type="submit" name="test_create" class="btn">2. Kontakt erstellen</button>
            </form>
            
            <form method="POST" style="margin-top: 12px;">
                <button type="submit" name="test_find" class="btn">3. Kontakt finden</button>
            </form>
            
            <form method="POST" style="margin-top: 12px;">
                <button type="submit" name="test_password_reset" class="btn">4. Password-Reset</button>
            </form>
        </div>

        <div class="test-box">
            <h2>📋 Checkliste für Quentn</h2>
            <ol style="line-height: 1.8; color: #374151;">
                <li>✅ API URL: <code>/public/api/V1/contact</code></li>
                <li>✅ 756 Tags verfügbar</li>
                <li>⚠️  Tag "kunde optinpilot" muss existieren</li>
                <li>⚠️  Custom Field "reset_link" muss existieren</li>
                <li>⚠️  Campaign mit Trigger "kunde optinpilot" muss erstellt sein</li>
                <li>⚠️  Campaign muss AKTIV sein</li>
            </ol>
        </div>
        
        <div class="test-box">
            <h2>🔗 Weitere Tools</h2>
            <a href="/public/debug-quentn.php" style="color: #8b5cf6;">→ Debug-Seite</a>
        </div>
    </div>
</body>
</html>
