<?php
// Payment Status Checking API for PlaySmart Job Applications
// This API allows checking payment status for specific applications

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'payment_status_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT STATUS CHECK STARTED ===");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);

try {
    // Load database config
    writeLog("Loading database config...");
    require_once 'db_config.php';
    
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
    
    // Get input parameters
    $application_id = $_GET['application_id'] ?? $_POST['application_id'] ?? '';
    $payment_id = $_GET['payment_id'] ?? $_POST['payment_id'] ?? '';
    $email = $_GET['email'] ?? $_POST['email'] ?? '';
    
    writeLog("Input parameters:");
    writeLog("application_id: $application_id");
    writeLog("payment_id: $payment_id");
    writeLog("email: $email");
    
    // Validate input
    if (empty($application_id) && empty($payment_id) && empty($email)) {
        throw new Exception('At least one parameter is required: application_id, payment_id, or email');
    }
    
    // Build query based on available parameters
    $whereConditions = [];
    $params = [];
    
    if (!empty($application_id)) {
        $whereConditions[] = "pt.application_id = ?";
        $params[] = $application_id;
    }
    
    if (!empty($payment_id)) {
        $whereConditions[] = "pt.payment_id = ?";
        $params[] = $payment_id;
    }
    
    if (!empty($email)) {
        $whereConditions[] = "ja.email = ?";
        $params[] = $email;
    }
    
    $whereClause = implode(" OR ", $whereConditions);
    
    // Query to get payment status with application details
    $sql = "SELECT 
                pt.id as payment_tracking_id,
                pt.application_id,
                pt.payment_id,
                pt.razorpay_payment_id,
                pt.razorpay_order_id,
                pt.amount,
                pt.currency,
                pt.payment_status,
                pt.payment_method,
                pt.payment_date,
                pt.created_at as payment_created_at,
                ja.student_name,
                ja.company_name,
                ja.profile,
                ja.district,
                ja.package,
                ja.email,
                ja.phone,
                ja.application_status as job_application_status,
                ja.applied_date
            FROM payment_tracking pt
            LEFT JOIN job_applications ja ON pt.application_id = ja.id
            WHERE $whereClause
            ORDER BY pt.created_at DESC";
    
    writeLog("SQL Query: $sql");
    writeLog("Parameters: " . print_r($params, true));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Query executed successfully. Found " . count($results) . " records");
    
    if (empty($results)) {
        // No payment records found, check if application exists
        $checkSql = "SELECT 
                        id, student_name, company_name, profile, district, package, 
                        email, phone, application_status, applied_date
                     FROM job_applications 
                     WHERE " . str_replace("pt.", "", str_replace("ja.", "", $whereClause));
        
        // Replace table aliases in parameters
        $checkParams = $params;
        
        writeLog("Checking if application exists: $checkSql");
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute($checkParams);
        $applicationResults = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($applicationResults)) {
            // Application exists but no payment
            $response = [
                'success' => true,
                'message' => 'Application found but no payment record',
                'data' => [
                    'applications' => $applicationResults,
                    'payment_status' => 'no_payment',
                    'message' => 'No payment has been made for this application'
                ]
            ];
        } else {
            // Neither application nor payment found
            $response = [
                'success' => true,
                'message' => 'No records found',
                'data' => []
            ];
        }
    } else {
        // Payment records found
        $response = [
            'success' => true,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'payments' => $results,
                'total_payments' => count($results)
            ]
        ];
    }
    
    writeLog("Response: " . json_encode($response));
    echo json_encode($response);
    writeLog("=== PAYMENT STATUS CHECK COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== PAYMENT STATUS CHECK ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Payment status check failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== PAYMENT STATUS CHECK FAILED ===");
}
?> 