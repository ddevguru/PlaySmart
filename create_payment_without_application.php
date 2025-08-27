<?php
// Create Payment Without Application - For PlaySmart Job Applications
// This file handles payment creation when user wants to pay first, then apply

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
$logFile = 'payment_without_app_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT WITHOUT APPLICATION STARTED ===");

try {
    // Load database config
    writeLog("Loading database config...");
    require_once 'db_config.php';
    
    // Load Razorpay config
    writeLog("Loading Razorpay config...");
    try {
        require_once 'razorpay_config.php';
        writeLog("✓ Razorpay config loaded successfully");
        
        // Check if constants are defined
        if (defined('RAZORPAY_KEY_ID')) {
            writeLog("✓ RAZORPAY_KEY_ID defined: " . RAZORPAY_KEY_ID);
        } else {
            writeLog("✗ RAZORPAY_KEY_ID not defined");
        }
        
        if (defined('RAZORPAY_KEY_SECRET')) {
            writeLog("✓ RAZORPAY_KEY_SECRET defined: " . substr(RAZORPAY_KEY_SECRET, 0, 10) . "...");
        } else {
            writeLog("✗ RAZORPAY_KEY_SECRET not defined");
        }
        
    } catch (Exception $e) {
        writeLog("✗ Error loading Razorpay config: " . $e->getMessage());
        throw new Exception('Failed to load Razorpay config: ' . $e->getMessage());
    }
    
    // Set database connection variables
    writeLog("Setting database connection variables...");
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USERNAME;
    $password = DB_PASSWORD;
    
    writeLog("Database config loaded - Host: $host, DB: $dbname, User: $username");
    
    // Connect to database
    writeLog("Connecting to database...");
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        writeLog("✓ Database connection successful");
    } catch (Exception $e) {
        writeLog("✗ Database connection failed: " . $e->getMessage());
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
    
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
    $payment_amount = $input['payment_amount'] ?? '';
    $job_type = $input['job_type'] ?? ''; // 'local' or 'higher_package'
    $student_name = $input['student_name'] ?? 'Job Applicant';
    $email = $input['email'] ?? 'applicant@example.com';
    $phone = $input['phone'] ?? '1234567890';
    
    writeLog("Payment data extracted:");
    writeLog("payment_amount: $payment_amount");
    writeLog("job_type: $job_type");
    writeLog("student_name: $student_name");
    writeLog("email: $email");
    writeLog("phone: $phone");
    
    // Validate required fields
    if (empty($payment_amount) || empty($job_type)) {
        throw new Exception('Missing required fields: payment_amount or job_type');
    }
    
    // Validate payment amount based on job type
    $expectedAmount = ($job_type === 'higher_package') ? 0.2 : 0.1;
    if ($payment_amount != $expectedAmount) {
        throw new Exception("Invalid payment amount. Expected ₹$expectedAmount for $job_type jobs");
    }
    
    // Create a temporary application record for payment tracking
    writeLog("Creating temporary application record...");
    $tempApplicationId = 'temp_' . time() . '_' . rand(1000, 9999);
    
    // Create Razorpay order
    writeLog("Creating Razorpay order...");
    $receipt = 'temp_' . $tempApplicationId . '_' . time();
    $notes = [
        'temp_application_id' => $tempApplicationId,
        'job_type' => $job_type,
        'student_name' => $student_name,
        'payment_amount' => $payment_amount,
        'payment_type' => 'advance_payment'
    ];
    
    writeLog("Order details - Receipt: $receipt, Notes: " . print_r($notes, true));
    
    try {
        $orderResult = createRazorpayOrder($payment_amount, $receipt, $notes);
        writeLog("createRazorpayOrder function called successfully");
        writeLog("Order result: " . print_r($orderResult, true));
        
        if (!$orderResult['success']) {
            writeLog("✗ Razorpay order creation failed: " . $orderResult['error']);
            throw new Exception('Failed to create Razorpay order: ' . $orderResult['error']);
        }
        
        writeLog("✓ Razorpay order created successfully: " . print_r($orderResult, true));
    } catch (Exception $e) {
        writeLog("✗ Exception during Razorpay order creation: " . $e->getMessage());
        throw new Exception('Razorpay order creation failed: ' . $e->getMessage());
    }
    
    // Store temporary payment record in database
    writeLog("Storing temporary payment record...");
    try {
        $insertSql = "INSERT INTO payment_tracking (
            application_id, payment_id, razorpay_order_id, amount, 
            currency, payment_status, payment_method, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        writeLog("SQL Query: $insertSql");
        writeLog("Parameters: " . print_r([
            $tempApplicationId,
            $receipt,
            $orderResult['order_id'],
            $payment_amount,
            'INR',
            'pending',
            'razorpay'
        ], true));
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertResult = $insertStmt->execute([
            $tempApplicationId,
            $receipt,
            $orderResult['order_id'],
            $payment_amount,
            'INR',
            'pending',
            'razorpay'
        ]);
        
        if (!$insertResult) {
            writeLog("✗ Failed to store payment record in database");
            writeLog("PDO Error Info: " . print_r($insertStmt->errorInfo(), true));
        } else {
            writeLog("✓ Payment record stored successfully in database");
        }
    } catch (Exception $e) {
        writeLog("✗ Exception during database insertion: " . $e->getMessage());
        writeLog("Exception trace: " . $e->getTraceAsString());
    }
    
    // Prepare payment response for Flutter
    $response = [
        'success' => true,
        'message' => 'Payment gateway initialized successfully',
        'data' => [
            'temp_application_id' => $tempApplicationId,
            'payment_amount' => $payment_amount,
            'currency' => 'INR',
            'razorpay_order_id' => $orderResult['order_id'],
            'receipt' => $receipt,
            'key_id' => RAZORPAY_KEY_ID,
            'prefill' => [
                'name' => $student_name,
                'email' => $email,
                'contact' => $phone
            ],
            'notes' => $notes,
            'description' => "Job Application Fee - $job_type",
            'job_type' => $job_type,
            'instructions' => getInstructionsForJobType($job_type)
        ]
    ];
    
    writeLog("Success response: " . json_encode($response));
    echo json_encode($response);
    writeLog("=== PAYMENT WITHOUT APPLICATION COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== PAYMENT WITHOUT APPLICATION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Payment creation failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== PAYMENT WITHOUT APPLICATION FAILED ===");
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
                    "6. We provide you job opportunities That means we provide you a Service The registration fee for them is 0.2.",
        "7. Rs. 0.2 Registration charges Will be limited for one year.",
        "8. The fee of Rs. 0.2 is non-refundable.",
            "9. If all the above are acceptable then register today. The company will contact you today for a job according to your education and provide you with further information."
        ];
    } else {
        return [
            "1. Play Smart services only works in company job requirements.",
            "2. Play Smart services working All Over India.",
            "3. We provide Job for candidates on local Place or elsewhere",
            "4. We provide job opportunities for candidates according to their education.",
            "5. We provide 2 to 3 Interview calls within Month for candidates.",
                    "6. We provide you job opportunities That means you provide you a Service The registration fee for them is 0.1.",
        "7. Rs. 0.1 Registration charges Will be limited for one year.",
        "8. The fee of Rs. 0.1 is non-refundable.",
            "9. If all the above are acceptable then register today. The company will contact you today for a job according to your education and provide you with further information."
        ];
    }
}

// Function to create Razorpay order (placeholder - you need to implement this)
function createRazorpayOrder($amount, $receipt, $notes) {
    // This is a placeholder - you need to implement the actual Razorpay order creation
    // For now, return a mock successful response
    
    return [
        'success' => true,
        'order_id' => 'order_' . time() . '_' . rand(1000, 9999),
        'error' => null
    ];
}
?> 