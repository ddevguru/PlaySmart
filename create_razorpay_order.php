<?php
// Razorpay Order Creation API for PlaySmart
// This API creates orders in Razorpay for payment processing

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
$logFile = 'razorpay_order_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== RAZORPAY ORDER CREATION STARTED ===");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);

try {
    // Load Razorpay config
    writeLog("Loading Razorpay config...");
    require_once 'razorpay_config.php';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    writeLog("Raw input received: " . file_get_contents('php://input'));
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    writeLog("Input data: " . print_r($input, true));
    
    // Extract order data
    $amount = $input['amount'] ?? '';
    $receipt = $input['receipt'] ?? '';
    $notes = $input['notes'] ?? [];
    
    writeLog("Order data extracted:");
    writeLog("amount: $amount");
    writeLog("receipt: $receipt");
    writeLog("notes: " . print_r($notes, true));
    
    // Validate required fields
    if (empty($amount) || empty($receipt)) {
        throw new Exception('Amount and receipt are required');
    }
    
    if (!is_numeric($amount) || $amount <= 0) {
        throw new Exception('Invalid amount');
    }
    
    // Create Razorpay order
    writeLog("Creating Razorpay order...");
    $orderResult = createRazorpayOrder($amount, $receipt, $notes);
    
    if (!$orderResult['success']) {
        throw new Exception('Failed to create Razorpay order: ' . $orderResult['error']);
    }
    
    writeLog("Razorpay order created successfully: " . print_r($orderResult, true));
    
    // Log order creation
    logPaymentActivity("Razorpay order created", [
        'receipt' => $receipt,
        'amount' => $amount,
        'order_id' => $orderResult['order_id'],
        'currency' => $orderResult['currency']
    ]);
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Order created successfully',
        'data' => $orderResult
    ];
    
    writeLog("Success response: " . json_encode($response));
    echo json_encode($response);
    writeLog("=== RAZORPAY ORDER CREATION COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== RAZORPAY ORDER CREATION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Order creation failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== RAZORPAY ORDER CREATION FAILED ===");
}
?> 