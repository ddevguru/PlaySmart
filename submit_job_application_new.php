<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include database configuration
    if (!file_exists('db_config.php')) {
        throw new Exception('Database configuration file not found');
    }
    
    include 'db_config.php';
    
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->error);
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
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data received. Raw input: ' . file_get_contents('php://input'));
    }
    
    // Log the input for debugging
    error_log('Job Application Input: ' . json_encode($input));
    
    // Extract data and ensure all variables are properly defined
    $jobId = intval($input['job_id'] ?? 0);
    $jobType = trim($input['job_type'] ?? 'higher_job');
    $studentName = trim($input['student_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $experience = trim($input['experience'] ?? '');
    $skills = trim($input['skills'] ?? '');
    $referralCode = trim($input['referral_code'] ?? '');
    $district = trim($input['district'] ?? 'Mumbai');
    
    // Validation
    if (empty($jobId) || $jobId <= 0) {
        throw new Exception('Invalid job ID: ' . $jobId);
    }
    
    if (empty($studentName) || empty($email) || empty($phone) || empty($experience) || empty($skills)) {
        throw new Exception('All required fields must be filled');
    }
    
    // Verify email matches authenticated user
    if ($email !== $userEmail) {
        throw new Exception('Email does not match authenticated user');
    }
    
    // Check if user already applied for this job
    $checkStmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND user_id = ? AND is_active = 1");
    if (!$checkStmt) {
        throw new Exception('Prepare error for duplicate check: ' . $conn->error);
    }
    
    $checkStmt->bind_param("ii", $jobId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception('You have already applied for this job');
    }
    $checkStmt->close();
    
    // Get job details to calculate application fee
    $jobDetails = null;
    
    // First check in new_jobs table
    $jobStmt = $conn->prepare("SELECT job_post, salary, job_type FROM new_jobs WHERE id = ? AND is_active = 1");
    if (!$jobStmt) {
        throw new Exception('Prepare error for new_jobs: ' . $conn->error);
    }
    
    $jobStmt->bind_param("i", $jobId);
    $jobStmt->execute();
    $jobResult = $jobStmt->get_result();
    
    if ($jobResult->num_rows > 0) {
        $jobDetails = $jobResult->fetch_assoc();
    } else {
        // Check in old jobs table
        $jobStmt = $conn->prepare("SELECT job_title, package FROM jobs WHERE id = ? AND is_active = 1");
        if (!$jobStmt) {
            throw new Exception('Prepare error for jobs: ' . $conn->error);
        }
        
        $jobStmt->bind_param("i", $jobId);
        $jobStmt->execute();
        $jobResult = $jobStmt->get_result();
        
        if ($jobResult->num_rows > 0) {
            $jobDetails = $jobResult->fetch_assoc();
        }
    }
    $jobStmt->close();
    
    // Calculate application fee based on job details
    $applicationFee = 1000.00; // Default fee for local jobs
    
    if ($jobDetails) {
        if (isset($jobDetails['job_type']) && $jobDetails['job_type'] === 'higher_job') {
            $applicationFee = 2000.00;
        } elseif (isset($jobDetails['salary'])) {
            // Check if salary contains LPA and is 10 or above
            if (strpos($jobDetails['salary'], 'LPA') !== false) {
                $salaryValue = floatval(preg_replace('/[^0-9.]/', '', $jobDetails['salary']));
                if ($salaryValue >= 10) {
                    $applicationFee = 2000.00;
                }
            }
        } elseif (isset($jobDetails['package'])) {
            // Check if package contains LPA and is 10 or above
            if (strpos($jobDetails['package'], 'LPA') !== false) {
                $packageValue = floatval(preg_replace('/[^0-9.]/', '', $jobDetails['package']));
                if ($packageValue >= 10) {
                    $applicationFee = 2000.00;
                }
            }
        }
    }
    
    // Get package and profile from job details
    $package = $jobDetails['salary'] ?? $jobDetails['package'] ?? 'N/A';
    $profile = $jobDetails['job_post'] ?? $jobDetails['job_title'] ?? 'N/A';
    $companyName = 'Company'; // Default for new jobs
    
    // Ensure all variables are properly defined before bind_param
    $companyLogo = ''; // Empty string for company logo
    
    // Insert application with calculated fee and user_id
    $insertStmt = $conn->prepare("
        INSERT INTO job_applications (
            user_id, job_id, job_type, company_name, company_logo, student_name, 
            district, package, profile, email, phone, experience, 
            skills, referral_code, application_fee, payment_status, 
            application_status, applied_date, is_active, application_version,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW(), 1, '1.0', NOW(), NOW())
    ");
    
    if (!$insertStmt) {
        throw new Exception('Prepare error for insert: ' . $conn->error);
    }
    
    // Bind parameters with properly defined variables including user_id
    $insertStmt->bind_param("iissssssssssssd", 
        $userId,          // user_id (NEW)
        $jobId,           // job_id
        $jobType,         // job_type
        $companyName,     // company_name
        $companyLogo,     // company_logo
        $studentName,     // student_name
        $district,        // district
        $package,         // package
        $profile,         // profile
        $email,           // email
        $phone,           // phone
        $experience,      // experience
        $skills,          // skills
        $referralCode,    // referral_code
        $applicationFee   // application_fee
    );
    
    if (!$insertStmt->execute()) {
        throw new Exception('Execute error for insert: ' . $insertStmt->error);
    }
    
    $applicationId = $insertStmt->insert_id;
    $insertStmt->close();
    
    // Log successful insertion
    error_log('Job Application inserted successfully. ID: ' . $applicationId . ', User ID: ' . $userId);
    
    // Return success response with application details
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!',
        'data' => [
            'application_id' => $applicationId,
            'user_id' => $userId,
            'job_type' => $jobType,
            'application_fee' => $applicationFee,
            'package' => $package,
            'profile' => $profile
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log('Job Application Error: ' . $e->getMessage());
    error_log('Input data: ' . json_encode($input ?? []));
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 