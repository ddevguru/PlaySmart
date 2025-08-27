<?php
// Razorpay Payment Verification API for PlaySmart
// This API verifies payment signatures and payment details

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
$logFile = 'payment_verification_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT VERIFICATION STARTED ===");
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
    
    // Extract verification data
    $razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $input['razorpay_order_id'] ?? '';
    $razorpaySignature = $input['razorpay_signature'] ?? '';
    
    writeLog("Verification data extracted:");
    writeLog("razorpay_payment_id: $razorpayPaymentId");
    writeLog("razorpay_order_id: $razorpayOrderId");
    writeLog("razorpay_signature: $razorpaySignature");
    
    // Validate required fields
    if (empty($razorpayPaymentId) || empty($razorpayOrderId) || empty($razorpaySignature)) {
        throw new Exception('All verification parameters are required');
    }
    
    // Verify payment signature
    writeLog("Verifying payment signature...");
    $signatureValid = verifyPaymentSignature($razorpayPaymentId, $razorpayOrderId, $razorpaySignature);
    
    if (!$signatureValid) {
        writeLog("Payment signature verification failed");
        throw new Exception('Payment signature verification failed');
    }
    
    writeLog("Payment signature verified successfully");
    
    // Get payment details from Razorpay
    writeLog("Fetching payment details from Razorpay...");
    $paymentDetails = getPaymentDetails($razorpayPaymentId);
    
    if (!$paymentDetails['success']) {
        writeLog("Failed to fetch payment details: " . $paymentDetails['error']);
        throw new Exception('Failed to fetch payment details from Razorpay');
    }
    
    $razorpayPayment = $paymentDetails['payment'];
    writeLog("Payment details fetched: " . print_r($razorpayPayment, true));
    
    // Verify payment status
    if ($razorpayPayment['status'] !== 'captured') {
        writeLog("Payment not captured. Status: " . $razorpayPayment['status']);
        throw new Exception('Payment not completed. Status: ' . $razorpayPayment['status']);
    }
    
    // Verify amount and currency
    $expectedAmount = $razorpayPayment['amount'] / 100; // Convert from paise to rupees
    $expectedCurrency = $razorpayPayment['currency'];
    
    writeLog("Payment verification details:");
    writeLog("Amount: $expectedAmount");
    writeLog("Currency: $expectedCurrency");
    writeLog("Status: " . $razorpayPayment['status']);
    
    // Log verification activity
    logPaymentActivity("Payment verified successfully", [
        'razorpay_payment_id' => $razorpayPaymentId,
        'razorpay_order_id' => $razorpayOrderId,
        'amount' => $expectedAmount,
        'currency' => $expectedCurrency,
        'status' => $razorpayPayment['status'],
        'method' => $razorpayPayment['method'] ?? 'unknown'
    ]);
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Payment verified successfully',
        'data' => [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $expectedAmount,
            'currency' => $expectedCurrency,
            'status' => $razorpayPayment['status'],
            'method' => $razorpayPayment['method'] ?? 'unknown',
            'captured_at' => $razorpayPayment['captured_at'] ?? null,
            'description' => $razorpayPayment['description'] ?? '',
            'email' => $razorpayPayment['email'] ?? '',
            'contact' => $razorpayPayment['contact'] ?? '',
            'name' => $razorpayPayment['name'] ?? ''
        ]
    ];
    
    writeLog("Success response: " . json_encode($response));
    echo json_encode($response);
    writeLog("=== PAYMENT VERIFICATION COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== PAYMENT VERIFICATION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Payment verification failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== PAYMENT VERIFICATION FAILED ===");
}
?> 