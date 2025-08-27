<?php
// Test Complete Payment Flow - Track all payment requests
// This file will help identify the exact payment flow

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'complete_payment_flow_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== COMPLETE PAYMENT FLOW TEST STARTED ===");
writeLog("Request URI: " . $_SERVER['REQUEST_URI']);
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not provided'));
writeLog("Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not provided'));

// Log all headers
writeLog("=== REQUEST HEADERS ===");
foreach (getallheaders() as $name => $value) {
    writeLog("$name: $value");
}

// Log raw input
$rawInput = file_get_contents('php://input');
writeLog("=== RAW INPUT ===");
writeLog("Raw input: " . $rawInput);

// Try to decode JSON
$input = json_decode($rawInput, true);
if ($input) {
    writeLog("=== DECODED JSON ===");
    writeLog("Decoded input: " . print_r($input, true));
    
    // Check what type of request this is
    if (isset($input['application_id'])) {
        writeLog("⚠ This is a request with application_id: " . $input['application_id']);
        writeLog("⚠ This should go to payment_integration.php, not here!");
        writeLog("⚠ The old payment flow is still active!");
    } else {
        writeLog("✓ This is a request without application_id - correct for create_payment_without_application.php");
    }
} else {
    writeLog("Failed to decode JSON input");
}

// Check if this is the right endpoint
$currentEndpoint = basename($_SERVER['REQUEST_URI']);
writeLog("=== ENDPOINT ANALYSIS ===");
writeLog("Current endpoint: $currentEndpoint");

if ($currentEndpoint === 'create_payment_without_application.php') {
    writeLog("✓ Correct endpoint for payment without application");
} elseif ($currentEndpoint === 'payment_integration.php') {
    writeLog("⚠ This is the OLD endpoint that requires application_id");
} else {
    writeLog("? Unknown endpoint: $currentEndpoint");
}

// Check if there are any redirects happening
writeLog("=== REDIRECT CHECK ===");
$headers = headers_list();
foreach ($headers as $header) {
    if (stripos($header, 'Location:') !== false) {
        writeLog("⚠ Redirect header found: $header");
    }
}

// Check if this is a test request
if (isset($input['test_mode']) && $input['test_mode'] === true) {
    writeLog("=== TEST MODE DETECTED ===");
    writeLog("This is a test request to verify the payment flow");
    
    // Return test data
    $response = [
        'success' => true,
        'message' => 'Complete payment flow test successful',
        'test_mode' => true,
        'debug_info' => [
            'endpoint_called' => $currentEndpoint,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'has_application_id' => isset($input['application_id']),
            'timestamp' => date('Y-m-d H:i:s'),
            'log_file' => $logFile
        ]
    ];
} else {
    // Return normal response
    $response = [
        'success' => true,
        'message' => 'Complete payment flow test completed',
        'debug_info' => [
            'endpoint_called' => $currentEndpoint,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'has_application_id' => isset($input['application_id']),
            'timestamp' => date('Y-m-d H:i:s'),
            'log_file' => $log_file
        ]
    ];
}

writeLog("=== COMPLETE PAYMENT FLOW TEST RESPONSE ===");
writeLog("Response: " . json_encode($response));
writeLog("=== COMPLETE PAYMENT FLOW TEST COMPLETED ===");

echo json_encode($response);
?> 