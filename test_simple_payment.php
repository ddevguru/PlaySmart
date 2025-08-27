<?php
// Simple Payment Test - Basic functionality test
// This file tests the payment flow without complex dependencies

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
$logFile = 'simple_payment_test_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== SIMPLE PAYMENT TEST STARTED ===");
writeLog("Request URI: " . $_SERVER['REQUEST_URI']);
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);

try {
    // Test 1: Basic functionality
    writeLog("Test 1: Basic functionality check");
    
    // Test 2: JSON input handling
    writeLog("Test 2: JSON input handling");
    $rawInput = file_get_contents('php://input');
    writeLog("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    if ($input) {
        writeLog("✓ JSON decoded successfully: " . print_r($input, true));
    } else {
        writeLog("✗ JSON decode failed");
    }
    
    // Test 3: Required fields validation
    writeLog("Test 3: Required fields validation");
    $payment_amount = $input['payment_amount'] ?? null;
    $job_type = $input['job_type'] ?? null;
    
    if ($payment_amount && $job_type) {
        writeLog("✓ Required fields present: amount=$payment_amount, type=$job_type");
    } else {
        writeLog("✗ Missing required fields");
        throw new Exception('Missing required fields');
    }
    
    // Test 4: Mock payment creation
    writeLog("Test 4: Mock payment creation");
    $mockOrderId = 'test_order_' . time() . '_' . rand(1000, 9999);
    $mockPaymentId = 'test_payment_' . time() . '_' . rand(1000, 9999);
    
    writeLog("✓ Mock order created: $mockOrderId");
    writeLog("✓ Mock payment created: $mockPaymentId");
    
    // Test 5: Response preparation
    writeLog("Test 5: Response preparation");
    $response = [
        'success' => true,
        'message' => 'Simple payment test successful',
        'data' => [
            'temp_application_id' => 'test_app_' . time(),
            'payment_amount' => $payment_amount,
            'currency' => 'INR',
            'razorpay_order_id' => $mockOrderId,
            'receipt' => $mockPaymentId,
            'key_id' => 'rzp_live_fgQr0ACWFbL4pN', // Your actual key
            'prefill' => [
                'name' => 'Test Applicant',
                'email' => 'test@example.com',
                'contact' => '1234567890'
            ],
            'notes' => [
                'temp_application_id' => 'test_app_' . time(),
                'job_type' => $job_type,
                'test_mode' => true
            ],
            'description' => "Job Application Fee - $job_type (TEST)",
            'job_type' => $job_type
        ]
    ];
    
    writeLog("✓ Response prepared successfully");
    writeLog("Response: " . json_encode($response));
    
    // Test 6: Output
    writeLog("Test 6: Output response");
    echo json_encode($response);
    writeLog("✓ Response sent successfully");
    
    writeLog("=== SIMPLE PAYMENT TEST COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== SIMPLE PAYMENT TEST ERROR ===");
    writeLog("Error: " . $e->getMessage());
    writeLog("File: " . $e->getFile());
    writeLog("Line: " . $e->getLine());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Simple payment test failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== SIMPLE PAYMENT TEST FAILED ===");
}
?> 