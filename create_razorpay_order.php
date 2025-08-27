<?php
/**
 * Create Razorpay Order - Prevents auto-refunds by creating proper orders
 */

// Suppress all output and errors to ensure clean JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering immediately
ob_start();

require_once 'razorpay_config.php';
require_once 'db_config.php';

// Function to write logs
function writeLog($message) {
    $logFile = 'payment_logs/razorpay_orders_' . date('Y-m-d') . '.log';
    $timestamp = '[' . date('Y-m-d H:i:s') . '] ';
    $logMessage = $timestamp . $message . "\n";
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    // Don't echo to console - it breaks JSON response
    // echo $logMessage; // Commented out to prevent JSON corruption
}

// Function to create Razorpay order using direct HTTP API
function createRazorpayOrderDirect($amount, $receipt, $notes) {
    try {
        $url = 'https://api.razorpay.com/v1/orders';
        
        $orderData = [
            'amount' => intval($amount * 100), // Convert to paise
            'currency' => 'INR',
            'receipt' => $receipt,
            'payment_capture' => 1, // 1 = auto-capture
            'notes' => $notes
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Razorpay API error: HTTP $httpCode - $response");
        }
        
        $orderData = json_decode($response, true);
        if (!$orderData || !isset($orderData['id'])) {
            throw new Exception("Invalid response from Razorpay API: $response");
        }
        
        return $orderData;
        
    } catch (Exception $e) {
        writeLog("❌ Razorpay API call failed: " . $e->getMessage());
        throw $e;
    }
}

try {
    // Clean any output buffers to ensure clean JSON response
    if (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    writeLog("=== RAZORPAY ORDER CREATION STARTED ===");
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    writeLog("Input data: " . print_r($data, true));
    
    // Extract required fields
    $jobId = $data['job_id'] ?? null;
    $amount = floatval($data['amount'] ?? 0);
    $userEmail = $data['user_email'] ?? '';
    $userName = $data['user_name'] ?? '';
    $userPhone = $data['user_phone'] ?? '';
    
    // Validate required fields
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    if ($amount < RAZORPAY_MIN_AMOUNT) {
        throw new Exception("Amount must be at least ₹" . RAZORPAY_MIN_AMOUNT);
    }
    
    if ($amount > RAZORPAY_MAX_AMOUNT) {
        throw new Exception("Amount cannot exceed ₹" . RAZORPAY_MAX_AMOUNT);
    }
    
    writeLog("Validated data - Job ID: $jobId, Amount: ₹$amount, Email: $userEmail");
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Get job details
    $stmt = $pdo->prepare("SELECT job_title, company_name, package FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception("Job not found with ID: $jobId");
    }
    
    writeLog("Job found: " . $job['job_title'] . " at " . $job['company_name']);
    
    // Create Razorpay order using direct API call
    $receipt = 'job_app_' . $jobId . '_' . time();
    $notes = [
        'job_id' => $jobId,
        'job_title' => $job['job_title'],
        'company_name' => $job['company_name'],
        'package' => $job['package'],
        'user_email' => $userEmail,
        'user_name' => $userName,
        'description' => 'Job Application Fee for ' . $job['job_title']
    ];
    
    writeLog("Creating Razorpay order with data: " . json_encode([
        'amount' => $amount,
        'receipt' => $receipt,
        'notes' => $notes
    ]));
    
    $razorpayOrder = createRazorpayOrderDirect($amount, $receipt, $notes);
    
    writeLog("✅ Razorpay order created successfully");
    writeLog("Order ID: " . $razorpayOrder['id']);
    writeLog("Order Amount: ₹" . ($razorpayOrder['amount'] / 100));
    writeLog("Order Status: " . $razorpayOrder['status']);
    
    // Store order details in database
    $stmt = $pdo->prepare("
        INSERT INTO razorpay_orders (
            order_id, job_id, amount, currency, receipt, 
            payment_capture, status, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $razorpayOrder['id'],
        $jobId,
        $amount,
        'INR',
        $receipt,
        1, // payment_capture = 1 (auto-capture)
        $razorpayOrder['status'],
        json_encode($notes)
    ]);
    
    $orderRecordId = $pdo->lastInsertId();
    writeLog("Order record stored in database with ID: $orderRecordId");
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Razorpay order created successfully',
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
    
    writeLog("Success response: " . json_encode($response));
    writeLog("=== RAZORPAY ORDER CREATION COMPLETED ===");
    
    // Clean output buffer and send clean JSON response
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== RAZORPAY ORDER CREATION ERROR ===");
    writeLog("Error: " . $e->getMessage());
    writeLog("File: " . $e->getFile());
    writeLog("Line: " . $e->getLine());
    
    $errorResponse = [
        'success' => false,
        'message' => 'Failed to create Razorpay order: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    writeLog("=== RAZORPAY ORDER CREATION FAILED ===");
    
    // Clean output buffer and send clean JSON error response
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode($errorResponse);
}
?> 