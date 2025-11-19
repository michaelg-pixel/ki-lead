<?php
/**
 * DEBUG WEBHOOK - Zeigt genau was Digistore24 sendet
 * Gibt IMMER HTTP 200 zurück!
 */

// Logging
function logWebhook($data, $type = 'info') {
    $logFile = __DIR__ . '/webhook-logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Webhook-Daten empfangen
$rawInput = file_get_contents('php://input');

logWebhook([
    'debug' => 'Webhook received',
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'raw_input' => $rawInput,
    'raw_input_length' => strlen($rawInput)
], 'debug');

// Versuch 1: JSON
$webhookData = json_decode($rawInput, true);

if ($webhookData) {
    logWebhook(['info' => 'Parsed as JSON', 'data' => $webhookData], 'info');
} else {
    // Versuch 2: Form-Data
    parse_str($rawInput, $formData);
    logWebhook(['info' => 'Parsed as form-data', 'data' => $formData], 'info');
    
    if (!empty($formData)) {
        $webhookData = [
            'event' => $formData['event'] ?? 'purchase',
            'buyer' => [
                'email' => $formData['email'] ?? '',
                'first_name' => $formData['first_name'] ?? '',
                'last_name' => $formData['last_name'] ?? ''
            ],
            'order_id' => $formData['order_id'] ?? $formData['transaction_id'] ?? '',
            'product_id' => $formData['product_id'] ?? '',
            'product_name' => $formData['product_name'] ?? $formData['product_name_intern'] ?? ''
        ];
        
        logWebhook(['info' => 'Converted to webhook format', 'data' => $webhookData], 'info');
    }
}

// IMMER HTTP 200 zurückgeben, auch bei leeren Test-Daten!
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Webhook received and logged',
    'received_data' => [
        'email' => $webhookData['buyer']['email'] ?? 'empty',
        'product_id' => $webhookData['product_id'] ?? 'empty',
        'order_id' => $webhookData['order_id'] ?? 'empty'
    ]
]);

logWebhook(['success' => 'Webhook processed and HTTP 200 returned'], 'success');
