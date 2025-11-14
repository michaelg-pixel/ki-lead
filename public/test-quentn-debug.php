<?php
session_start();
require_once '../config/database.php';
require_once '../config/quentn_config.php';
require_once '../includes/quentn_api.php';

// Teste mit einer spezifischen E-Mail
$testEmail = 'michael@info-xxl.de'; // Ändere diese E-Mail auf deine Test-E-Mail
$testName = 'Michael Test';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quentn API Debug Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 32px;
        }
        .test-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #374151;
            margin-bottom: 16px;
            font-size: 20px;
        }
        .result {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
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
            margin-top: 12px;
        }
        .btn:hover {
            background: #7c3aed;
        }
        .info-box {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            color: #1e40af;
        }
        .step {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            align-items: flex-start;
        }
        .step-number {
            background: #8b5cf6;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }
        .step-content {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Quentn API Debug Test</h1>
        
        <div class="info-box">
            <strong>Test-E-Mail:</strong> <?php echo htmlspecialchars($testEmail); ?>
            <br>
            Ändere die E-Mail oben im Code, falls du eine andere testen möchtest.
        </div>

        <?php if (isset($_POST['test_registration'])): ?>
        <!-- TEST 1: Registrierung -->
        <div class="test-section">
            <h2>✅ Test 1: Registrierung mit Quentn</h2>
            <?php
            try {
                echo "<div class='step'><div class='step-number'>1</div><div class='step-content'>Sende Kontakt zu Quentn...</div></div>";
                
                $contactData = [
                    'email' => $testEmail,
                    'first_name' => 'Michael',
                    'last_name' => 'Test',
                    'skip_double_opt_in' => true,
                    'tags' => ['registration', 'customer', 'kunde optinpilot']
                ];
                
                $ch = curl_init(QUENTN_API_BASE_URL . 'contacts');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($contactData),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . QUENTN_API_KEY
                    ],
                    CURLOPT_TIMEOUT => 10
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                echo "<div class='step'><div class='step-number'>2</div><div class='step-content'>HTTP Status: $httpCode</div></div>";
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    echo "<div class='result success'>";
                    echo "✅ SUCCESS!\n\n";
                    echo "Response:\n";
                    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT);
                    echo "</div>";
                } else {
                    echo "<div class='result error'>";
                    echo "❌ FEHLER!\n\n";
                    echo "HTTP Code: $httpCode\n";
                    echo "Response:\n$response\n";
                    if ($curlError) echo "Curl Error: $curlError\n";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='result error'>Exception: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['test_password_reset'])): ?>
        <!-- TEST 2: Passwort Reset -->
        <div class="test-section">
            <h2>🔐 Test 2: Passwort Reset Flow</h2>
            <?php
            try {
                $resetLink = 'https://app.mehr-infos-jetzt.de/public/password-reset.php?token=TEST123456';
                
                // Schritt 1: Kontakt finden
                echo "<div class='step'><div class='step-number'>1</div><div class='step-content'>Suche Kontakt in Quentn...</div></div>";
                
                $ch = curl_init(QUENTN_API_BASE_URL . 'contacts?email=' . urlencode($testEmail));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . QUENTN_API_KEY
                    ],
                    CURLOPT_TIMEOUT => 10
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode == 200) {
                    $data = json_decode($response, true);
                    if (!empty($data) && isset($data[0]['id'])) {
                        $contactId = $data[0]['id'];
                        echo "<div class='result success'>✅ Kontakt gefunden! ID: $contactId</div>";
                        
                        // Schritt 2: Custom Field setzen
                        echo "<div class='step'><div class='step-number'>2</div><div class='step-content'>Setze Custom Field 'reset_link'...</div></div>";
                        
                        $updateData = [
                            'custom_fields' => [
                                'reset_link' => $resetLink
                            ]
                        ];
                        
                        $ch = curl_init(QUENTN_API_BASE_URL . 'contacts/' . $contactId);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST => 'PUT',
                            CURLOPT_POSTFIELDS => json_encode($updateData),
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . QUENTN_API_KEY
                            ],
                            CURLOPT_TIMEOUT => 10
                        ]);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode >= 200 && $httpCode < 300) {
                            echo "<div class='result success'>✅ Custom Field gesetzt!</div>";
                        } else {
                            echo "<div class='result error'>❌ Custom Field konnte nicht gesetzt werden\nHTTP: $httpCode\n$response</div>";
                        }
                        
                        // Schritt 3: Tag setzen
                        echo "<div class='step'><div class='step-number'>3</div><div class='step-content'>Setze Tag 'kunde optinpilot'...</div></div>";
                        
                        $tagData = [
                            'tags' => ['kunde optinpilot']
                        ];
                        
                        $ch = curl_init(QUENTN_API_BASE_URL . 'contacts/' . $contactId . '/tags');
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => json_encode($tagData),
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . QUENTN_API_KEY
                            ],
                            CURLOPT_TIMEOUT => 10
                        ]);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode >= 200 && $httpCode < 300) {
                            echo "<div class='result success'>✅ Tag 'kunde optinpilot' gesetzt!\n\n";
                            echo "🎯 Campaign sollte jetzt getriggert werden!</div>";
                        } else {
                            echo "<div class='result error'>❌ Tag konnte nicht gesetzt werden\nHTTP: $httpCode\n$response</div>";
                        }
                        
                    } else {
                        echo "<div class='result error'>❌ Kontakt nicht gefunden! Bitte erst Registrierung testen.</div>";
                    }
                } else {
                    echo "<div class='result error'>❌ API Fehler beim Suchen: HTTP $httpCode\n$response</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='result error'>Exception: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['check_config'])): ?>
        <!-- TEST 3: Config Check -->
        <div class="test-section">
            <h2>⚙️ Test 3: Quentn Config Check</h2>
            <?php
            echo "<div class='step'><div class='step-number'>1</div><div class='step-content'>API Base URL</div></div>";
            echo "<div class='result'>" . htmlspecialchars(QUENTN_API_BASE_URL) . "</div>";
            
            echo "<div class='step'><div class='step-number'>2</div><div class='step-content'>API Key (erste 10 Zeichen)</div></div>";
            echo "<div class='result'>" . substr(QUENTN_API_KEY, 0, 10) . "..." . (strlen(QUENTN_API_KEY) > 10 ? '✅' : '❌') . "</div>";
            
            echo "<div class='step'><div class='step-number'>3</div><div class='step-content'>Test API Verbindung</div></div>";
            
            $ch = curl_init(QUENTN_API_BASE_URL . 'contacts?limit=1');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . QUENTN_API_KEY
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                echo "<div class='result success'>✅ API Verbindung erfolgreich!</div>";
            } else {
                echo "<div class='result error'>❌ API Verbindung fehlgeschlagen!\nHTTP: $httpCode\n$response</div>";
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Test Buttons -->
        <div class="test-section">
            <h2>🧪 Tests ausführen</h2>
            <p style="margin-bottom: 16px;">Führe die Tests in dieser Reihenfolge aus:</p>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="check_config" class="btn">1. Config prüfen</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_registration" class="btn">2. Registrierung testen</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_password_reset" class="btn">3. Passwort-Reset testen</button>
            </form>
        </div>

        <div class="test-section">
            <h2>📋 Checkliste für Quentn Campaign</h2>
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <strong>Campaign in Quentn erstellen</strong><br>
                    Trigger: Tag "kunde optinpilot" wird gesetzt
                </div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <strong>Custom Field "reset_link" erstellen</strong><br>
                    In Quentn → Einstellungen → Custom Fields → Neues Feld "reset_link" (Typ: Text)
                </div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <strong>E-Mail Template erstellen</strong><br>
                    Mit Platzhalter: {{contact.field_custom_field_reset_link}}
                </div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <strong>Campaign aktivieren</strong><br>
                    Status auf "Aktiv" setzen
                </div>
            </div>
        </div>
    </div>
</body>
</html>
