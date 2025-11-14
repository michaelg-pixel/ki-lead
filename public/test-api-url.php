<?php
session_start();

// Teste verschiedene API URLs
$testEmail = '12@abnehmen-fitness.com';
$apiKey = 'm-gkCLAXFVewwguCP1ZCm9zFFi_bauieZPl21EkGUqo';
$baseUrl = 'https://pk1bh1.eu-1.quentn.com';

// Verschiedene API URL Varianten zum Testen
$apiVersions = [
    'V1 (groß)' => $baseUrl . '/public/api/V1/',
    'v1 (klein)' => $baseUrl . '/public/api/v1/',
    'Ohne public' => $baseUrl . '/api/v1/',
    'Ohne Version' => $baseUrl . '/public/api/',
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quentn API URL Finder</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 40px 20px;
        }
        .container {
            max-width: 1200px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Quentn API URL Finder</h1>
        
        <?php if (isset($_POST['test_all'])): ?>
        <div class="test-section">
            <h2>🧪 Teste alle API URL Varianten</h2>
            <table>
                <thead>
                    <tr>
                        <th>API Version</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($apiVersions as $version => $url): ?>
                    <tr>
                        <td><strong><?php echo $version; ?></strong></td>
                        <td style="font-size: 11px; color: #6b7280;"><?php echo $url; ?></td>
                        <td>
                            <?php
                            $ch = curl_init($url . 'contacts?limit=1');
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => [
                                    'Content-Type: application/json',
                                    'Authorization: Bearer ' . $apiKey
                                ],
                                CURLOPT_TIMEOUT => 5
                            ]);
                            
                            $response = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            
                            if ($httpCode == 200) {
                                echo '<span class="status-badge status-success">✅ ' . $httpCode . '</span>';
                            } else {
                                echo '<span class="status-badge status-error">❌ ' . $httpCode . '</span>';
                            }
                            ?>
                        </td>
                        <td style="font-size: 11px;">
                            <?php 
                            $decoded = json_decode($response, true);
                            if ($decoded) {
                                if (isset($decoded['error'])) {
                                    echo '❌ ' . htmlspecialchars($decoded['message'] ?? 'Error');
                                } else {
                                    echo '✅ Success!';
                                }
                            } else {
                                echo htmlspecialchars(substr($response, 0, 100));
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="test-section">
            <h2>🧪 Tests ausführen</h2>
            <form method="POST">
                <button type="submit" name="test_all" class="btn">Alle URLs testen</button>
            </form>
        </div>

        <div class="test-section">
            <h2>📋 Nächste Schritte</h2>
            <ol style="line-height: 1.8; color: #374151;">
                <li>Führe "Alle URLs testen" aus um die richtige URL zu finden</li>
                <li>Die URL mit ✅ 200 Status ist die richtige!</li>
                <li>Update dann die quentn_config.php mit der richtigen URL</li>
            </ol>
        </div>
    </div>
</body>
</html>
