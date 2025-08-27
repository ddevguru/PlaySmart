<?php
// Simple Working Payment Endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple logging
$logFile = 'simple_payment_working_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== SIMPLE PAYMENT WORKING STARTED ===");

try {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    writeLog("Input received: " . print_r($input, true));
    
    if (!$input) {
        throw new Exception('No input received');
    }
    
    // Validate
    if (empty($input['payment_amount']) || empty($input['job_type'])) {
        throw new Exception('Missing required fields');
    }
    
    $amount = $input['payment_amount'];
    $jobType = $input['job_type'];
    
    // Create working response for Razorpay
    $response = [
        'success' => true,
        'message' => 'Payment gateway ready',
        'data' => [
            'temp_application_id' => 'temp_' . time(),
            'payment_amount' => $amount,
            'currency' => 'INR',
            'razorpay_order_id' => 'order_' . time() . '_' . rand(1000, 9999),
            'receipt' => 'receipt_' . time(),
            'key_id' => 'rzp_live_fgQr0ACWFbL4pN', // Your actual key
            'prefill' => [
                'name' => $input['student_name'] ?? 'Job Applicant',
                'email' => $input['email'] ?? 'applicant@example.com',
                'contact' => $input['phone'] ?? '1234567890'
            ],
            'notes' => [
                'temp_application_id' => 'temp_' . time(),
                'job_type' => $jobType,
                'payment_type' => 'job_application'
            ],
            'description' => "Job Application Fee - $jobType (₹$amount)",
            'job_type' => $jobType
        ]
    ];
    
    writeLog("Response prepared: " . json_encode($response));
    writeLog("=== SIMPLE PAYMENT WORKING SUCCESS ===");
    
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== SIMPLE PAYMENT WORKING ERROR ===");
    writeLog("Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Payment setup failed: ' . $e->getMessage()
    ]);
    
    writeLog("=== SIMPLE PAYMENT WORKING FAILED ===");
}
?> 