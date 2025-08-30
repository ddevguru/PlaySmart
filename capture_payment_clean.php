<?php
/**
 * Capture Payment - Clean version
 */

// Suppress all output and errors
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Include database configuration
require_once 'latestdb.php';

// Function to write logs
function writeLog($message) {
    $logFile = 'payment_logs/payment_capture_' . date('Y-m-d') . '.log';
    $timestamp = '[' . date('Y-m-d H:i:s') . '] ';
    $logMessage = $timestamp . $message . "\n";
    
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    writeLog("=== PAYMENT CAPTURE STARTED ===");
    
    // Check request method
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
    
    // Get authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    // Extract token
    $token = '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
    
    if (empty($token)) {
        throw new Exception('Authorization token is required');
    }
    
    // Extract payment data
    $paymentId = $data['payment_id'] ?? '';
    $orderId = $data['order_id'] ?? '';
    $signature = $data['signature'] ?? '';
    $jobId = $data['job_id'] ?? null;
    $amount = floatval($data['amount'] ?? 0);
    $referralCode = $data['referral_code'] ?? '';
    
    // Validate required fields
    if (empty($paymentId) || empty($orderId) || empty($signature) || !$jobId || $amount <= 0) {
        throw new Exception('Missing or invalid required fields');
    }
    
    writeLog("Validated payment data - Payment ID: $paymentId, Order ID: $orderId, Job ID: $jobId, Amount: ₹$amount");
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Verify token and get user ID
    $userStmt = $pdo->prepare("SELECT id, email FROM users WHERE session_token = ? AND status = 'online'");
    $userStmt->execute([$token]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('Invalid or expired token');
    }
    
    $userId = $user['id'];
    $userEmail = $user['email'];
    
    writeLog("User verified - ID: $userId, Email: $userEmail");
    
    // Verify payment signature
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, 'kFpmvStUlrAys3U9gCkgLAnw');
    
    if (!hash_equals($expectedSignature, $signature)) {
        writeLog("❌ Signature verification failed");
        throw new Exception('Invalid payment signature');
    }
    
    writeLog("✅ Payment signature verified successfully");
    
    // Check if payment already exists
    $checkStmt = $pdo->prepare("SELECT id FROM payment_tracking WHERE razorpay_payment_id = ? OR razorpay_order_id = ?");
    $checkStmt->execute([$paymentId, $orderId]);
    
    if ($checkStmt->rowCount() > 0) {
        writeLog("⚠️ Payment already processed");
        
        $response = [
            'success' => true,
            'message' => 'Payment already processed successfully',
            'payment_id' => $paymentId,
            'order_id' => $orderId
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Update razorpay_orders table
    $updateOrderStmt = $pdo->prepare("UPDATE razorpay_orders SET status = 'paid', updated_at = NOW() WHERE order_id = ?");
    $updateOrderStmt->execute([$orderId]);
    
    if ($updateOrderStmt->rowCount() === 0) {
        writeLog("⚠️ No order found to update for Order ID: $orderId");
    } else {
        writeLog("✅ Order status updated to 'paid'");
    }
    
    // Update job application status
    $updateAppStmt = $pdo->prepare("
        UPDATE job_applications 
        SET payment_status = 'paid', 
            razorpay_payment_id = ?, 
            razorpay_order_id = ?,
            updated_at = NOW()
        WHERE job_id = ? AND user_id = ?
    ");
    $updateAppStmt->execute([$paymentId, $orderId, $jobId, $userId]);
    
    if ($updateAppStmt->rowCount() === 0) {
        writeLog("⚠️ No job application found to update");
    } else {
        writeLog("✅ Job application payment status updated to 'paid'");
    }
    
    // Insert payment tracking record
    $insertTrackingStmt = $pdo->prepare("
        INSERT INTO payment_tracking (
            user_id, job_id, razorpay_order_id, razorpay_payment_id, 
            amount, payment_status, referral_code, created_at
        ) VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())
    ");
    
    $insertTrackingStmt->execute([$userId, $jobId, $orderId, $paymentId, $amount, $referralCode]);
    $trackingId = $pdo->lastInsertId();
    
    writeLog("✅ Payment tracking record created with ID: $trackingId");
    
    // Process referral code if provided
    if (!empty($referralCode)) {
        writeLog("Processing referral code: $referralCode");
        
        try {
            $referrerStmt = $pdo->prepare("SELECT id, email FROM users WHERE referral_code = ?");
            $referrerStmt->execute([$referralCode]);
            $referrer = $referrerStmt->fetch();
            
            if ($referrer) {
                writeLog("Referrer found - ID: {$referrer['id']}, Email: {$referrer['email']}");
                
                $bonusAmount = 50.00;
                $updateWalletStmt = $pdo->prepare("UPDATE users SET wallet_balance = COALESCE(wallet_balance, 0) + ? WHERE id = ?");
                $updateWalletStmt->execute([$bonusAmount, $referrer['id']]);
                
                if ($updateWalletStmt->rowCount() > 0) {
                    writeLog("✅ Referral bonus of ₹$bonusAmount added to user {$referrer['id']}");
                }
            } else {
                writeLog("⚠️ Referrer not found for code: $referralCode");
            }
        } catch (Exception $e) {
            writeLog("⚠️ Error processing referral: " . $e->getMessage());
        }
    }
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Payment captured and processed successfully',
        'data' => [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'job_id' => $jobId,
            'amount' => $amount,
            'user_id' => $userId,
            'referral_code' => $referralCode,
            'tracking_id' => $trackingId
        ]
    ];
    
    writeLog("✅ Payment capture completed successfully");
    writeLog("=== PAYMENT CAPTURE COMPLETED ===");
    
    // Send clean JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== PAYMENT CAPTURE ERROR ===");
    writeLog("Error: " . $e->getMessage());
    writeLog("File: " . $e->getFile());
    writeLog("Line: " . $e->getLine());
    
    $errorResponse = [
        'success' => false,
        'message' => 'Failed to capture payment: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    writeLog("=== PAYMENT CAPTURE FAILED ===");
    
    // Send clean JSON error response
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode($errorResponse);
}
?> 