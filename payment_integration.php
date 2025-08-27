<?php
// Payment Integration for PlaySmart Job Applications
// This file handles the payment flow after job application submission

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
$logFile = 'payment_integration_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT INTEGRATION STARTED ===");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);

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
    
    writeLog("Database config loaded");
    
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
    $payment_amount = $input['payment_amount'] ?? '';
    $job_type = $input['job_type'] ?? ''; // 'local' or 'higher_package'
    
    writeLog("Payment data extracted:");
    writeLog("application_id: $application_id");
    writeLog("payment_amount: $payment_amount");
    writeLog("job_type: $job_type");
    
    // Validate required fields
    if (empty($application_id) || empty($payment_amount) || empty($job_type)) {
        throw new Exception('Missing required fields: application_id, payment_amount, or job_type');
    }
    
    // Validate payment amount based on job type
    $expectedAmount = ($job_type === 'higher_package') ? 2000 : 1000;
    if ($payment_amount != $expectedAmount) {
        throw new Exception("Invalid payment amount. Expected ₹$expectedAmount for $job_type jobs");
    }
    
    // Get application details
    writeLog("Fetching application details...");
    $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    writeLog("Application found: " . print_r($application, true));
    
    // Create Razorpay order
    writeLog("Creating Razorpay order...");
    $receipt = 'app_' . $application_id . '_' . time();
    $notes = [
        'application_id' => $application_id,
        'job_type' => $job_type,
        'student_name' => $application['student_name'],
        'company_name' => $application['company_name']
    ];
    
    $orderResult = createRazorpayOrder($payment_amount, $receipt, $notes);
    
    if (!$orderResult['success']) {
        throw new Exception('Failed to create Razorpay order: ' . $orderResult['error']);
    }
    
    writeLog("Razorpay order created: " . print_r($orderResult, true));
    
    // Update application with order details
    writeLog("Updating application with order details...");
    $updateSql = "UPDATE job_applications SET 
                   payment_id = ?, 
                   application_status = 'payment_pending'
                   WHERE id = ?";
    
    $updateStmt = $pdo->prepare($updateSql);
    $updateResult = $updateStmt->execute([$receipt, $application_id]);
    
    if (!$updateResult) {
        writeLog("Failed to update application status");
    } else {
        writeLog("Application status updated to 'payment_pending'");
    }
    
    // Prepare payment response for Flutter
    $response = [
        'success' => true,
        'message' => 'Payment gateway initialized successfully',
        'data' => [
            'application_id' => $application_id,
            'payment_amount' => $payment_amount,
            'currency' => 'INR',
            'razorpay_order_id' => $orderResult['order_id'],
            'receipt' => $receipt,
            'key_id' => RAZORPAY_KEY_ID,
            'prefill' => [
                'name' => $application['student_name'],
                'email' => $application['email'],
                'contact' => $application['phone']
            ],
            'notes' => $notes,
            'description' => "Job Application Fee - $job_type",
            'job_type' => $job_type,
            'instructions' => getInstructionsForJobType($job_type)
        ]
    ];
    
    writeLog("Success response: " . json_encode($response));
    echo json_encode($response);
    writeLog("=== PAYMENT INTEGRATION COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== PAYMENT INTEGRATION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Payment integration failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== PAYMENT INTEGRATION FAILED ===");
}

// Function to get instructions based on job type
function getInstructionsForJobType($jobType) {
    if ($jobType === 'higher_package') {
        return [
            "1. Play Smart services only works in company job requirements.",
            "2. Play Smart services working All Over India.",
            "3. We provide Job for candidates on local Place or elsewhere",
            "4. We provide job opportunities for candidates according to their education.",
            "5. We provide 2 to 3 Interview calls within Month for candidates.",
            "6. We provide you job opportunities That means we provide you a Service The registration fee for them is 2000.",
            "7. Rs. 2000 Registration charges Will be limited for one year.",
            "8. The fee of Rs. 2000 is non-refundable.",
            "9. If all the above are acceptable then register today. The company will contact you today for a job according to your education and provide you with further information."
        ];
    } else {
        return [
            "1. Play Smart services only works in company job requirements.",
            "2. Play Smart services working All Over India.",
            "3. We provide Job for candidates on local Place or elsewhere",
            "4. We provide job opportunities for candidates according to their education.",
            "5. We provide 2 to 3 Interview calls within Month for candidates.",
            "6. We provide you job opportunities That means we provide you a Service The registration fee for them is 1000.",
            "7. Rs. 1000 Registration charges Will be limited for one year.",
            "8. The fee of Rs. 1000 is non-refundable.",
            "9. If all the above are acceptable then register today. The company will contact you today for a job according to your education and provide you with further information."
        ];
    }
}
?> 