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
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));

try {
    // Include database configuration
    require_once 'newcon.php';
    
    // Test database connection first
    $dbCheck = checkDatabaseConnection();
    if (!$dbCheck['success']) {
        throw new Exception('Database connection failed: ' . $dbCheck['message']);
    }
    
    writeLog("Database connection successful");
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    writeLog("Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input. Raw input: ' . $rawInput);
    }
    
    writeLog("Input data: " . print_r($input, true));
    
    // Validate required fields
    $required_fields = ['name', 'email', 'phone', 'education', 'experience', 'skills', 'job_id'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Extract and sanitize data
    $name = sanitizeInput($input['name']);
    $email = sanitizeInput($input['email']);
    $phone = sanitizeInput($input['phone']);
    $education = sanitizeInput($input['education']);
    $experience = sanitizeInput($input['experience']);
    $skills = sanitizeInput($input['skills']);
    $job_id = (int)$input['job_id'];
    $referral_code = sanitizeInput($input['referral_code'] ?? '');
    $photo_path = sanitizeInput($input['photo_path'] ?? '');
    $resume_path = sanitizeInput($input['resume_path'] ?? '');
    $company_name = sanitizeInput($input['company_name'] ?? '');
    $package = sanitizeInput($input['package'] ?? '');
    $profile = sanitizeInput($input['profile'] ?? '');
    $district = sanitizeInput($input['district'] ?? 'Mumbai');
    
    writeLog("Data extracted and sanitized successfully");
    
    // Get database connection
    $pdo = getDBConnection();
    writeLog("Database connection obtained");
    
    // Allow multiple applications for the same job (user might want to update files or information)
    writeLog("Allowing multiple applications for the same job");
    
    // Optional: Check if user has too many recent applications (e.g., max 5 per day)
    $recentCheckStmt = $pdo->prepare("SELECT COUNT(*) as recent_count FROM job_applications WHERE email = ? AND job_id = ? AND applied_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $recentCheckStmt->execute([$email, $job_id]);
    $recentCount = $recentCheckStmt->fetch()['recent_count'];
    
    if ($recentCount >= 5) {
        writeLog("User has submitted 5 applications in the last 24 hours for this job");
        // You can uncomment the line below if you want to limit applications per day
        // throw new Exception('You have submitted too many applications for this job today. Please wait 24 hours.');
    }
    
    writeLog("Application count check passed. Recent applications: $recentCount");
    
    // Get application version number for this user and job
    $versionStmt = $pdo->prepare("SELECT MAX(application_version) as max_version FROM job_applications WHERE email = ? AND job_id = ?");
    $versionStmt->execute([$email, $job_id]);
    $maxVersion = $versionStmt->fetch()['max_version'];
    $application_version = ($maxVersion ?? 0) + 1;
    
    writeLog("Application version: $application_version for user $email and job $job_id");
    
    // Upload files to Admin/uploads directories
    writeLog("Starting file upload process...");
    
    $uploadData = [
        'photo_path' => $photo_path,
        'resume_path' => $resume_path
    ];
    
    // Call file upload handler
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/upload_files.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($uploadData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $uploadResponse = curl_exec($ch);
    $uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($uploadHttpCode == 200) {
        $uploadResult = json_decode($uploadResponse, true);
        if ($uploadResult && $uploadResult['success']) {
            // Update file paths with uploaded file paths
            if (isset($uploadResult['files']['photo'])) {
                $photo_path = $uploadResult['files']['photo']['path'];
                writeLog("Photo uploaded to: $photo_path");
            }
            if (isset($uploadResult['files']['resume'])) {
                $resume_path = $uploadResult['files']['resume']['path'];
                writeLog("Resume uploaded to: $resume_path");
            }
            writeLog("File upload successful");
        } else {
            writeLog("File upload failed: " . ($uploadResult['message'] ?? 'Unknown error'));
            // Continue with original paths if upload fails
        }
    } else {
        writeLog("File upload HTTP error: $uploadHttpCode");
        // Continue with original paths if upload fails
    }
    
    // Generate unique payment ID
    $payment_id = 'pay_' . time() . '_' . rand(1000, 9999);
    
    // Get current timestamp
    $applied_date = date('Y-m-d H:i:s');
    
    // Prepare SQL statement with referral_code and application_version columns
    $sql = "INSERT INTO job_applications (
        job_id, company_name, company_logo, student_name, district, 
        package, profile, photo_path, resume_path, email, phone, 
        experience, skills, referral_code, payment_id, application_status, 
        applied_date, is_active, application_version
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $pdo->errorInfo()[2]);
    }
    
    writeLog("SQL statement prepared");
    writeLog("SQL: " . $sql);
    
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
        $referral_code,
        $payment_id,
        'pending', // application_status
        $applied_date,
        1, // is_active
        $application_version // application version
    ]);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Database execute error: ' . ($errorInfo[2] ?? 'Unknown error'));
    }
    
    $application_id = $pdo->lastInsertId();
    writeLog("Application inserted successfully with ID: $application_id");
    
    // Log the successful submission
    error_log("Job application submitted successfully - ID: $application_id, Name: $name, Email: $email, Referral Code: $referral_code, Version: $application_version");
    
    // If referral code is provided, log it for tracking
    if (!empty($referral_code)) {
        error_log("Referral code used: $referral_code for application ID: $application_id");
    }
    
    // Close statement (PDO doesn't need explicit close, but we can set to null)
    $stmt = null;
    
    writeLog("=== JOB APPLICATION SUBMISSION COMPLETED SUCCESSFULLY ===");
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'Job application submitted successfully',
        'data' => [
            'application_id' => $application_id,
            'payment_id' => $payment_id,
            'referral_code' => $referral_code,
            'status' => 'pending',
            'application_version' => $application_version,
            'total_applications' => $recentCount + 1
        ]
    ];
    
    writeLog("Success response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== JOB APPLICATION SUBMISSION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    error_log("Job application error: " . $e->getMessage());
    
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => 'Error submitting job application: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    
    writeLog("=== JOB APPLICATION SUBMISSION FAILED ===");
}
?> 