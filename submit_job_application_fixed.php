<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request
$logFile = 'upload_debug.log';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== NEW REQUEST STARTED ===");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));

// Get raw input
$rawInput = file_get_contents('php://input');
writeLog("Raw input length: " . strlen($rawInput));
writeLog("Raw input (first 500 chars): " . substr($rawInput, 0, 500));

try {
    // Database connection
    $host = 'localhost';
    $username = 'u968643667_playsmart';
    $password = 'Playsmart@123';
    $database = 'u968643667_playsmart';
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        writeLog("Database connection failed: " . $conn->connect_error);
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    writeLog("Database connection successful");
    
    // Get POST data
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        $jsonError = json_last_error_msg();
        writeLog("JSON decode error: $jsonError");
        throw new Exception('Invalid JSON data received: ' . $jsonError);
    }
    
    writeLog("JSON decoded successfully");
    writeLog("Input keys: " . implode(', ', array_keys($input)));
    
    // Extract data
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $education = trim($input['education'] ?? '');
    $experience = trim($input['experience'] ?? '');
    $skills = trim($input['skills'] ?? '');
    $jobId = intval($input['job_id'] ?? 0);
    $referralCode = trim($input['referral_code'] ?? '');
    $photoData = $input['photo_data'] ?? '';
    $resumeData = $input['resume_data'] ?? '';
    $photoName = $input['photo_name'] ?? '';
    $resumeName = $input['resume_name'] ?? '';
    $companyName = trim($input['company_name'] ?? 'Company');
    $package = trim($input['package'] ?? 'N/A');
    $profile = trim($input['profile'] ?? 'N/A');
    $district = trim($input['district'] ?? 'Mumbai');
    
    writeLog("Extracted data:");
    writeLog("  Name: $name");
    writeLog("  Email: $email");
    writeLog("  Phone: $phone");
    writeLog("  Education: $education");
    writeLog("  Experience: $experience");
    writeLog("  Skills: $skills");
    writeLog("  Job ID: $jobId");
    writeLog("  Photo data length: " . strlen($photoData));
    writeLog("  Photo name: $photoName");
    writeLog("  Resume data length: " . strlen($resumeData));
    writeLog("  Resume name: $resumeName");
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($education) || 
        empty($experience) || empty($skills) || empty($jobId)) {
        writeLog("Validation failed - missing required fields");
        writeLog("  Name empty: " . (empty($name) ? 'YES' : 'NO'));
        writeLog("  Email empty: " . (empty($email) ? 'YES' : 'NO'));
        writeLog("  Phone empty: " . (empty($phone) ? 'YES' : 'NO'));
        writeLog("  Education empty: " . (empty($education) ? 'YES' : 'NO'));
        writeLog("  Experience empty: " . (empty($experience) ? 'YES' : 'NO'));
        writeLog("  Skills empty: " . (empty($skills) ? 'YES' : 'NO'));
        writeLog("  Job ID empty: " . (empty($jobId) ? 'YES' : 'NO'));
        throw new Exception('All required fields must be filled');
    }
    
    writeLog("Validation passed");
    
    // Check if user already applied for this job
    $stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND email = ? AND is_active = 1");
    $stmt->bind_param("is", $jobId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('You have already applied for this job');
    }
    $stmt->close();
    
    writeLog("Duplicate check passed");
    
    // Calculate application fee based on package
    $applicationFee = 1000.00; // Default fee for local jobs
    
    if (strpos($package, 'LPA') !== false) {
        $packageValue = floatval(preg_replace('/[^0-9.]/', '', $package));
        if ($packageValue >= 10) {
            $applicationFee = 2000.00; // Higher package jobs
        }
    }
    
    // Determine job type based on package
    $jobType = 'local_job';
    if ($applicationFee >= 2000.00) {
        $jobType = 'higher_job';
    }
    
    // Create upload directories if they don't exist
    $photoDir = 'Admin/uploads/photos/';
    $resumeDir = 'Admin/uploads/resumes/';
    
    if (!is_dir($photoDir)) {
        mkdir($photoDir, 0755, true);
        writeLog("Created photos directory: $photoDir");
    }
    if (!is_dir($resumeDir)) {
        mkdir($resumeDir, 0755, true);
        writeLog("Created resumes directory: $resumeDir");
    }
    
    $photoPath = '';
    $resumePath = '';
    
    // Handle photo upload
    if (!empty($photoData) && !empty($photoName)) {
        writeLog("Processing photo upload...");
        
        // Decode base64 data
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $photoData = substr($photoData, strpos($photoData, ',') + 1);
            writeLog("Removed data URI prefix from photo");
        }
        
        $decodedPhotoData = base64_decode($photoData);
        if ($decodedPhotoData === false) {
            writeLog("Photo base64 decode failed");
            throw new Exception('Invalid photo data format');
        }
        
        writeLog("Photo base64 decode successful, size: " . strlen($decodedPhotoData) . " bytes");
        
        // Generate unique filename
        $photoExt = pathinfo($photoName, PATHINFO_EXTENSION);
        if (empty($photoExt)) $photoExt = 'jpg';
        
        $photoFileName = 'photo_' . time() . '_' . $name . '.' . $photoExt;
        $photoPath = $photoDir . $photoFileName;
        
        writeLog("Photo filename: $photoFileName");
        writeLog("Photo full path: $photoPath");
        
        // Save photo file
        if (!file_put_contents($photoPath, $decodedPhotoData)) {
            writeLog("Failed to save photo file");
            throw new Exception('Failed to save photo file');
        }
        
        writeLog("Photo saved successfully: $photoPath");
        writeLog("Photo file size: " . filesize($photoPath) . " bytes");
    } else {
        writeLog("No photo data or name provided");
    }
    
    // Handle resume upload
    if (!empty($resumeData) && !empty($resumeName)) {
        writeLog("Processing resume upload...");
        
        // Decode base64 data
        if (preg_match('/^data:application\/(\w+);base64,/', $resumeData, $type)) {
            $resumeData = substr($resumeData, strpos($resumeData, ',') + 1);
            writeLog("Removed data URI prefix from resume");
        }
        
        $decodedResumeData = base64_decode($resumeData);
        if ($decodedResumeData === false) {
            writeLog("Resume base64 decode failed");
            throw new Exception('Invalid resume data format');
        }
        
        writeLog("Resume base64 decode successful, size: " . strlen($decodedResumeData) . " bytes");
        
        // Generate unique filename
        $resumeExt = pathinfo($resumeName, PATHINFO_EXTENSION);
        if (empty($resumeExt)) $resumeExt = 'pdf';
        
        $resumeFileName = 'resume_' . time() . '_' . $name . '.' . $resumeExt;
        $resumePath = $resumeDir . $resumeFileName;
        
        writeLog("Resume filename: $resumeFileName");
        writeLog("Resume full path: $resumePath");
        
        // Save resume file
        if (!file_put_contents($resumePath, $decodedResumeData)) {
            writeLog("Failed to save resume file");
            throw new Exception('Failed to save resume file');
        }
        
        writeLog("Resume saved successfully: $resumePath");
        writeLog("Resume file size: " . filesize($resumePath) . " bytes");
    } else {
        writeLog("No resume data or name provided");
    }
    
    // Insert application with file paths
    writeLog("Inserting application into database...");
    
    $stmt = $conn->prepare("
        INSERT INTO job_applications (
            job_id, job_type, company_name, company_logo, student_name, 
            district, package, profile, photo_path, resume_path, email, 
            phone, experience, skills, referral_code, application_fee, 
            payment_status, application_status, applied_date, is_active, 
            application_version
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW(), 1, '1.0')
    ");
    
    $stmt->bind_param("issssssssssssssd", 
        $jobId, $jobType, $companyName, '', $name, 
        $district, $package, $profile, $photoPath, $resumePath, $email, 
        $phone, $experience, $skills, $referralCode, $applicationFee
    );
    
    if (!$stmt->execute()) {
        writeLog("Database insert failed: " . $stmt->error);
        throw new Exception('Failed to submit application: ' . $stmt->error);
    }
    
    $applicationId = $stmt->insert_id;
    $stmt->close();
    
    writeLog("Application inserted successfully with ID: $applicationId");
    
    // Return success response with application details
    $response = [
        'success' => true,
        'message' => 'Application submitted successfully! Files uploaded and data stored in database.',
        'data' => [
            'application_id' => $applicationId,
            'job_type' => $jobType,
            'application_fee' => $applicationFee,
            'package' => $package,
            'profile' => $profile,
            'photo_path' => $photoPath,
            'resume_path' => $resumePath
        ]
    ];
    
    writeLog("Sending success response");
    writeLog("=== REQUEST COMPLETED SUCCESSFULLY ===");
    
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    writeLog("=== REQUEST FAILED ===");
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    $conn->close();
}
?> 