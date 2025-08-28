<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'db_config.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data received');
    }
    
    // Extract data
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $education = trim($input['education'] ?? '');
    $experience = trim($input['experience'] ?? '');
    $skills = trim($input['skills'] ?? '');
    $jobId = intval($input['job_id'] ?? 0);
    $referralCode = trim($input['referral_code'] ?? '');
    $photoPath = trim($input['photo_path'] ?? '');
    $resumePath = trim($input['resume_path'] ?? '');
    $companyName = trim($input['company_name'] ?? 'Company');
    $package = trim($input['package'] ?? 'N/A');
    $profile = trim($input['profile'] ?? 'N/A');
    $district = trim($input['district'] ?? 'Mumbai');
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($education) || 
        empty($experience) || empty($skills) || empty($jobId)) {
        throw new Exception('All required fields must be filled');
    }
    
    // Check if user already applied for this job
    $stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND email = ? AND is_active = 1");
    $stmt->bind_param("is", $jobId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('You have already applied for this job');
    }
    $stmt->close();
    
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
    
    // Insert application with calculated fee
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
        throw new Exception('Failed to submit application: ' . $stmt->error);
    }
    
    $applicationId = $stmt->insert_id;
    $stmt->close();
    
    // Return success response with application details
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Data stored in database.',
        'data' => [
            'application_id' => $applicationId,
            'job_type' => $jobType,
            'application_fee' => $applicationFee,
            'package' => $package,
            'profile' => $profile
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 