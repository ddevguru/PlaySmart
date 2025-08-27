<?php
// Payment Processing API for PlaySmart Job Applications
// This API handles Razorpay payment processing and stores payment details

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
$logFile = 'payment_debug_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT PROCESSING STARTED ===");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));

try {
    // Load database config
    writeLog("Loading database config...");
    require_once 'db_config.php';
    
    // Load Razorpay config
    writeLog("Loading Razorpay config...");
    require_once 'razorpay_config.php';
    
    // Set database connection variables
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USERNAME;
    $password = DB_PASSWORD;
    
    writeLog("Database config loaded - Host: $host, DB: $dbname, User: $username");
    
    // Connect to database
    writeLog("Connecting to database...");
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    writeLog("Database connection successful");
    
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
    
    // Extract payment data
    $application_id = $input['application_id'] ?? '';
    $payment_id = $input['payment_id'] ?? '';
    $amount = $input['amount'] ?? '';
    $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $input['razorpay_order_id'] ?? '';
    $razorpay_signature = $input['razorpay_signature'] ?? '';
    $payment_method = $input['payment_method'] ?? '';
    $gateway_response = $input['gateway_response'] ?? '';
    
    writeLog("Payment data extracted:");
    writeLog("application_id: $application_id");
    writeLog("payment_id: $payment_id");
    writeLog("amount: $amount");
    writeLog("razorpay_payment_id: $razorpay_payment_id");
    writeLog("razorpay_order_id: $razorpay_order_id");
    writeLog("payment_method: $payment_method");
    
    // Validate required fields
    $required_fields = ['application_id', 'payment_id', 'amount', 'razorpay_payment_id', 'razorpay_order_id'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($$field)) {
            $missing_fields[] = $field;
            writeLog("Missing required field: $field");
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }
    
    // Verify payment signature (security check)
    writeLog("Verifying payment signature...");
    if (!verifyPaymentSignature($razorpay_payment_id, $razorpay_order_id, $razorpay_signature)) {
        writeLog("Payment signature verification failed");
        throw new Exception('Payment signature verification failed');
    }
    writeLog("Payment signature verified successfully");
    
    // Get payment details from Razorpay
    writeLog("Fetching payment details from Razorpay...");
    $paymentDetails = getPaymentDetails($razorpay_payment_id);
    
    if (!$paymentDetails['success']) {
        writeLog("Failed to fetch payment details: " . $paymentDetails['error']);
        throw new Exception('Failed to fetch payment details from Razorpay');
    }
    
    $razorpayPayment = $paymentDetails['payment'];
    writeLog("Payment details fetched: " . print_r($razorpayPayment, true));
    
    // Check if payment is already processed
    writeLog("Checking if payment already exists...");
    $stmt = $pdo->prepare("SELECT id FROM payment_tracking WHERE razorpay_payment_id = ?");
    $stmt->execute([$razorpay_payment_id]);
    
    if ($stmt->rowCount() > 0) {
        writeLog("Payment already processed");
        throw new Exception('Payment already processed');
    }
    
    // Insert payment record
    writeLog("Inserting payment record...");
    $sql = "INSERT INTO payment_tracking (
        application_id, payment_id, razorpay_payment_id, razorpay_order_id,
        amount, currency, payment_status, payment_method, payment_date,
        gateway_response, created_at, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $application_id,
        $payment_id,
        $razorpay_payment_id,
        $razorpay_order_id,
        $amount,
        RAZORPAY_CURRENCY,
        'completed', // Payment is completed if we reach here
        $payment_method,
        date('Y-m-d H:i:s'),
        json_encode($gateway_response)
    ]);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        writeLog("Failed to insert payment record: " . print_r($errorInfo, true));
        throw new Exception('Failed to insert payment record: ' . $errorInfo[2]);
    }
    
    $payment_tracking_id = $pdo->lastInsertId();
    writeLog("Payment record inserted with ID: $payment_tracking_id");
    
    // Update job application status to 'paid'
    writeLog("Updating job application status...");
    $updateSql = "UPDATE job_applications SET application_status = 'paid' WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateResult = $updateStmt->execute([$application_id]);
    
    if (!$updateResult) {
        writeLog("Failed to update application status");
        // Don't throw error here as payment is already recorded
    } else {
        writeLog("Application status updated to 'paid'");
    }
    
    // Log payment activity
    logPaymentActivity("Payment completed successfully", [
        'application_id' => $application_id,
        'payment_id' => $payment_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'amount' => $amount,
        'status' => 'completed'
    ]);
    
    // Send success email
    writeLog("Sending success email...");
    $emailResult = sendPaymentSuccessEmail($application_id, $amount, $payment_id);
    if ($emailResult) {
        writeLog("Success email sent");
    } else {
        writeLog("Failed to send success email");
    }
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
            'payment_tracking_id' => $payment_tracking_id,
            'application_id' => $application_id,
            'payment_id' => $payment_id,
            'razorpay_payment_id' => $razorpay_payment_id,
            'amount' => $amount,
            'currency' => RAZORPAY_CURRENCY,
            'status' => 'completed',
            'payment_date' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Success response: " . json_encode($response));
    echo json_encode($response);
    writeLog("=== PAYMENT PROCESSING COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== PAYMENT PROCESSING ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== PAYMENT PROCESSING FAILED ===");
}

// Function to send payment success email
function sendPaymentSuccessEmail($applicationId, $amount, $paymentId) {
    try {
        // Get application details from database
        global $pdo;
        $stmt = $pdo->prepare("SELECT student_name, email, company_name, profile FROM job_applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            return false;
        }
        
        $to = $application['email'];
        $subject = "Payment Successful - Job Application Fee";
        $message = "
        Dear {$application['student_name']},
        
        Your payment of ₹{$amount} for the job application fee has been processed successfully.
        
        Payment Details:
        - Payment ID: {$paymentId}
        - Amount: ₹{$amount}
        - Company: {$application['company_name']}
        - Position: {$application['profile']}
        - Date: " . date('Y-m-d H:i:s') . "
        
        Your application is now being processed. We will contact you soon with further details.
        
        Thank you for choosing PlaySmart!
        
        Best regards,
        PlaySmart Team
        ";
        
        $headers = "From: noreply@playsmart.co.in\r\n";
        $headers .= "Reply-To: support@playsmart.co.in\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        writeLog("Email sending error: " . $e->getMessage());
        return false;
    }
}
?> 