<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'job_application_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== JOB APPLICATION SUBMISSION STARTED ===");

try {
    require_once 'db_config.php';
    
    // Test database connection first
    $dbCheck = checkDatabaseConnection();
    if (!$dbCheck['success']) {
        throw new Exception('Database connection failed: ' . $dbCheck['message']);
    }
    
    writeLog("Database connection successful");
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    writeLog("Raw input received: " . file_get_contents('php://input'));
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    writeLog("Input data: " . print_r($input, true));
    
    // Validate required fields
    $required_fields = ['name', 'email', 'phone', 'education', 'experience', 'skills', 'job_id'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Extract data
    $name = $input['name'];
    $email = $input['email'];
    $phone = $input['phone'];
    $education = $input['education'];
    $experience = $input['experience'];
    $skills = $input['skills'];
    $job_id = $input['job_id'];
    $referral_code = $input['referral_code'] ?? '';
    $photo_path = $input['photo_path'] ?? '';
    $resume_path = $input['resume_path'] ?? '';
    $company_name = $input['company_name'] ?? '';
    $package = $input['package'] ?? '';
    $profile = $input['profile'] ?? '';
    $district = $input['district'] ?? 'Mumbai';
    
    writeLog("Data extracted successfully");
    
    // Generate unique payment ID
    $payment_id = 'pay_' . time() . '_' . rand(1000, 9999);
    
    // Get current timestamp
    $applied_date = date('Y-m-d H:i:s');
    
    // Get database connection
    $pdo = getDBConnection();
    writeLog("Database connection obtained");
    
    // Check if user already applied for this job
    $checkStmt = $pdo->prepare("SELECT id FROM job_applications WHERE email = ? AND job_id = ?");
    $checkStmt->execute([$email, $job_id]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('You have already applied for this job');
    }
    
    writeLog("Duplicate application check passed");
    
    // Prepare SQL statement with referral_code column
    $sql = "INSERT INTO job_applications (
        job_id, company_name, company_logo, student_name, district, 
        package, profile, photo_path, resume_path, email, phone, 
        experience, skills, referral_code, payment_id, application_status, 
        applied_date, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $pdo->errorInfo()[2]);
    }
    
    writeLog("SQL statement prepared");
    
    // Execute the statement
    $result = $stmt->execute([
        $job_id,
        $company_name,
        '', // company_logo - empty for now
        $name,
        $district,
        $package,
        $profile,
        $photo_path,
        $resume_path,
        $email,
        $phone,
        $experience,
        $skills,
        $referral_code, // New referral_code field
        $payment_id,
        'pending', // application_status
        $applied_date,
        1 // is_active
    ]);
    
    if (!$result) {
        throw new Exception('Database execute error: ' . $stmt->errorInfo()[2]);
    }
    
    $application_id = $pdo->lastInsertId();
    writeLog("Application inserted successfully with ID: $application_id");
    
    // Log the successful submission
    error_log("Job application submitted successfully - ID: $application_id, Name: $name, Email: $email, Referral Code: $referral_code");
    
    // If referral code is provided, log it for tracking
    if (!empty($referral_code)) {
        error_log("Referral code used: $referral_code for application ID: $application_id");
        
        // You can add additional referral tracking logic here
        // For example, update referral statistics, send notifications to referrer, etc.
    }
    
    // Close statement
    $stmt->close();
    
    writeLog("=== JOB APPLICATION SUBMISSION COMPLETED SUCCESSFULLY ===");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Job application submitted successfully',
        'data' => [
            'application_id' => $application_id,
            'payment_id' => $payment_id,
            'referral_code' => $referral_code,
            'status' => 'pending'
        ]
    ]);
    
} catch (Exception $e) {
    writeLog("=== JOB APPLICATION SUBMISSION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    error_log("Job application error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting job application: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    writeLog("=== JOB APPLICATION SUBMISSION FAILED ===");
}
?> 