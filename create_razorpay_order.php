<?php
/**
 * Create Razorpay Order - Simple version without SDK dependency
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

require_once 'latestdb.php';

try {
    // Log the request for debugging
    error_log("=== RAZORPAY ORDER CREATION START ===");
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request Headers: " . print_r(getallheaders(), true));
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    error_log("Raw input received: " . $input);
    
    $data = json_decode($input, true);
    error_log("Parsed input data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid JSON input: ' . $input);
    }
    
    // Get authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    // Extract token from "Bearer <token>" format
    $token = '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
    
    if (empty($token)) {
        throw new Exception('Authorization token is required');
    }
    
    // Verify token and get user ID directly from users table
    $userStmt = $conn->prepare("
        SELECT id, email 
        FROM users 
        WHERE session_token = ? AND status = 'online'
    ");
    if (!$userStmt) {
        throw new Exception('Prepare error for user verification: ' . $conn->error);
    }
    
    $userStmt->bind_param("s", $token);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        throw new Exception('Invalid or expired token');
    }
    
    $user = $userResult->fetch_assoc();
    $userId = $user['id'];
    $userEmail = $user['email'];
    $userStmt->close();
    
    // Extract required fields
    $jobId = $data['job_id'] ?? null;
    $amount = floatval($data['amount'] ?? 0);
    
    // Ensure amount is properly converted to a number
    if (!is_numeric($amount)) {
        throw new Exception("Invalid amount value: " . var_export($amount, true));
    }
    
    // Validate required fields
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    if ($amount <= 0) {
        throw new Exception("Amount must be greater than 0");
    }
    
    // Try to find the job in multiple tables
    $job = null;
    
    // Try new_jobs table first (since this seems to be where local jobs are stored)
    $stmt = $conn->prepare("SELECT job_post as job_title, 'Company' as company_name, salary as package FROM new_jobs WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $jobId);
        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        $stmt->close();
    }
    
    // Try jobs table if not found in new_jobs
    if (!$job) {
        $stmt = $conn->prepare("SELECT job_title, company_name, package FROM jobs WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $jobId);
            $stmt->execute();
            $result = $stmt->get_result();
            $job = $result->fetch_assoc();
            $stmt->close();
        }
    }
    
    if (!$job) {
        throw new Exception("Job not found with ID: $jobId");
    }
    
    // Create Razorpay order using direct API call
    $receipt = 'job_app_' . $jobId . '_' . time();
    $notes = [
        'job_id' => $jobId,
        'job_title' => $job['job_title'],
        'company_name' => $job['company_name'],
        'user_id' => $userId,
        'user_email' => $userEmail
    ];
    
    // Razorpay API credentials
    $keyId = 'rzp_live_fgQr0ACWFbL4pN';
    $keySecret = 'kFpmvStUlrAys3U9gCkgLAnw';
    
    // Prepare order data
    // Note: Flutter already sends amount in paise, so don't multiply by 100 again
    $orderData = [
        'amount' => intval($amount), // Amount is already in paise from Flutter
        'currency' => 'INR',
        'receipt' => $receipt,
        'payment_capture' => 1, // 1 = auto-capture
        'notes' => $notes
    ];
    
    // Create cURL request to Razorpay API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($keyId . ':' . $keySecret)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("cURL error: " . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Razorpay API error - HTTP Code: $httpCode, Response: $response");
    }
    
    $razorpayOrder = json_decode($response, true);
    if (!$razorpayOrder || !isset($razorpayOrder['id'])) {
        throw new Exception("Invalid response from Razorpay: $response");
    }
    
        // Store order details in database
    $stmt = $conn->prepare("
        INSERT INTO razorpay_orders (
            order_id, user_id, job_id, amount, currency, receipt, 
            payment_capture, status, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception('Prepare error for order insert: ' . $conn->error);
    }
    
    // Prepare variables for bind_param to avoid PHP 8.2 reference issues
    $orderId = (string)$razorpayOrder['id'];
    $orderStatus = (string)$razorpayOrder['status'];
    $notesJson = (string)json_encode($notes);
    $currency = 'INR';
    $paymentCapture = '1';
    
    // Ensure all variables are properly defined and have correct types
    if (!isset($orderId) || !isset($userId) || !isset($jobId) || !isset($amount) || 
        !isset($currency) || !isset($receipt) || !isset($paymentCapture) || 
        !isset($orderStatus) || !isset($notesJson)) {
        throw new Exception('One or more required variables are not defined before bind_param');
    }
    
    error_log("DEBUG: About to bind parameters:");
    error_log("  orderId: $orderId");
    error_log("  userId: $userId");
    error_log("  jobId: $jobId");
    error_log("  amount: $amount");
    error_log("  currency: $currency");
    error_log("  receipt: $receipt");
    error_log("  paymentCapture: $paymentCapture");
    error_log("  orderStatus: $orderStatus");
    error_log("  notesJson: $notesJson");
    
    error_log("DEBUG: Binding parameters with types: siissssss");
    error_log("DEBUG: Parameter 1 (order_id): " . gettype($orderId) . " = " . $orderId);
    error_log("DEBUG: Parameter 2 (user_id): " . gettype($userId) . " = " . $userId);
    error_log("DEBUG: Parameter 3 (job_id): " . gettype($jobId) . " = " . $jobId);
    error_log("DEBUG: Parameter 4 (amount): " . gettype($amount) . " = " . $amount);
    error_log("DEBUG: Parameter 5 (currency): " . gettype($currency) . " = " . $currency);
    error_log("DEBUG: Parameter 6 (receipt): " . gettype($receipt) . " = " . $receipt);
    error_log("DEBUG: Parameter 7 (payment_capture): " . gettype($paymentCapture) . " = " . $paymentCapture);
    error_log("DEBUG: Parameter 8 (status): " . gettype($orderStatus) . " = " . $orderStatus);
    error_log("DEBUG: Parameter 9 (notes): " . gettype($notesJson) . " = " . $notesJson);
    
    try {
        $stmt->bind_param("siissssss", 
            $orderId,                       // order_id
            $userId,                        // user_id
            $jobId,                         // job_id
            $amount,                        // amount
            $currency,                      // currency
            $receipt,                       // receipt
            $paymentCapture,                // payment_capture
            $orderStatus,                   // status
            $notesJson                      // notes
        );
        error_log("DEBUG: bind_param completed successfully");
    } catch (Exception $e) {
        error_log("ERROR: bind_param failed: " . $e->getMessage());
        error_log("ERROR: bind_param error details: " . print_r($e->getTrace(), true));
        throw new Exception('Failed to bind parameters: ' . $e->getMessage());
    }
    
    $stmt->execute();
    
    $orderRecordId = $conn->insert_id;
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Razorpay order created successfully',
        'order_id' => $razorpayOrder['id'], // Move order_id to top level for Flutter compatibility
        'data' => [
            'order_id' => $razorpayOrder['id'],
            'amount' => $razorpayOrder['amount'] / 100,
            'currency' => $razorpayOrder['currency'],
            'receipt' => $receipt,
            'status' => $razorpayOrder['status'],
            'created_at' => $razorpayOrder['created_at'],
            'notes' => $notes
        ]
    ];
    
    // Clean output buffer and send clean JSON response
    ob_end_clean();
    header('Content-Type: application/json');
    error_log("Sending success response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("ERROR in create_razorpay_order.php: " . $e->getMessage());
    error_log("Error file: " . $e->getFile() . " line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $errorResponse = [
        'success' => false,
        'message' => 'Failed to create Razorpay order: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s'),
            'input_data' => $data ?? null,
            'token_length' => strlen($token ?? ''),
            'job_id' => $jobId ?? null,
            'amount' => $amount ?? null
        ]
    ];
    
    // Clean output buffer and send clean JSON error response
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    error_log("Sending error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
}

if (isset($conn)) {
    $conn->close();
}
?> 