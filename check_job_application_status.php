<?php
// Check Job Application Status API
// This API checks if a user has already applied for a specific job

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'job_application_status_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== JOB APPLICATION STATUS CHECK STARTED ===");

try {
    // Load database config
    require_once 'db_config.php';
    
    // Set database connection variables
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USERNAME;
    $password = DB_PASSWORD;
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request for checking application status
        $job_id = $_GET['job_id'] ?? '';
        $user_email = $_GET['user_email'] ?? '';
        
        if (empty($job_id) || empty($user_email)) {
            throw new Exception('Both job_id and user_email are required');
        }
        
        writeLog("Checking application status for job_id: $job_id, user_email: $user_email");
        
        // Check if user has applied for this job
        $stmt = $pdo->prepare("
            SELECT id, application_status, applied_date, payment_id, company_name, profile, package
            FROM job_applications 
            WHERE job_id = ? AND email = ? AND is_active = 1
            ORDER BY applied_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$job_id, $user_email]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application) {
            // User has applied for this job
            $response = [
                'success' => true,
                'has_applied' => true,
                'data' => [
                    'application_id' => $application['id'],
                    'status' => $application['application_status'],
                    'applied_date' => $application['applied_date'],
                    'payment_id' => $application['payment_id'],
                    'company_name' => $application['company_name'],
                    'profile' => $application['profile'],
                    'package' => $application['package']
                ]
            ];
            writeLog("Application found: " . json_encode($response));
        } else {
            // User has not applied for this job
            $response = [
                'success' => true,
                'has_applied' => false,
                'data' => null
            ];
            writeLog("No application found for this job and user");
        }
        
        echo json_encode($response);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST request for checking multiple jobs
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $user_email = $input['user_email'] ?? '';
        $job_ids = $input['job_ids'] ?? [];
        
        if (empty($user_email) || empty($job_ids)) {
            throw new Exception('Both user_email and job_ids array are required');
        }
        
        writeLog("Checking application status for user_email: $user_email, job_ids: " . implode(',', $job_ids));
        
        // Check application status for multiple jobs
        $placeholders = str_repeat('?,', count($job_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT job_id, application_status, applied_date, payment_id
            FROM job_applications 
            WHERE job_id IN ($placeholders) AND email = ? AND is_active = 1
        ");
        
        $params = array_merge($job_ids, [$user_email]);
        $stmt->execute($params);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a map of job_id to application status
        $statusMap = [];
        foreach ($applications as $app) {
            $statusMap[$app['job_id']] = [
                'has_applied' => true,
                'status' => $app['application_status'],
                'applied_date' => $app['applied_date'],
                'payment_id' => $app['payment_id']
            ];
        }
        
        // Fill in jobs that don't have applications
        foreach ($job_ids as $job_id) {
            if (!isset($statusMap[$job_id])) {
                $statusMap[$job_id] = [
                    'has_applied' => false,
                    'status' => null,
                    'applied_date' => null,
                    'payment_id' => null
                ];
            }
        }
        
        $response = [
            'success' => true,
            'data' => $statusMap
        ];
        
        writeLog("Multiple job status check completed: " . json_encode($response));
        echo json_encode($response);
        
    } else {
        throw new Exception('Only GET and POST methods allowed');
    }
    
    writeLog("=== JOB APPLICATION STATUS CHECK COMPLETED SUCCESSFULLY ===");
    
} catch (Exception $e) {
    writeLog("=== JOB APPLICATION STATUS CHECK ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Job application status check failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== JOB APPLICATION STATUS CHECK FAILED ===");
}
?> 