<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get form data
    $jobId = $_POST['job_id'] ?? '';
    $companyName = $_POST['company_name'] ?? '';
    $studentName = $_POST['student_name'] ?? '';
    $district = $_POST['district'] ?? '';
    $package = $_POST['package'] ?? '';
    $profile = $_POST['profile'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $paymentId = $_POST['payment_id'] ?? '';
    
    // Validate required fields
    if (empty($jobId) || empty($companyName) || empty($studentName) || empty($district) || empty($package)) {
        throw new Exception('Required fields are missing');
    }
    
    // Handle file uploads
    $photoPath = '';
    $resumePath = '';
    
    // Upload photo if provided
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoFile = $_FILES['photo'];
        $photoExt = strtolower(pathinfo($photoFile['name'], PATHINFO_EXTENSION));
        $allowedPhotoExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($photoExt, $allowedPhotoExts)) {
            throw new Exception('Invalid photo format. Allowed: JPG, JPEG, PNG, GIF');
        }
        
        $photoFileName = 'photo_' . time() . '_' . $studentName . '.' . $photoExt;
        $photoPath = 'uploads/photos/' . $photoFileName;
        
        if (!is_dir('uploads/photos/')) {
            mkdir('uploads/photos/', 0777, true);
        }
        
        if (!move_uploaded_file($photoFile['tmp_name'], $photoPath)) {
            throw new Exception('Failed to upload photo');
        }
    }
    
    // Upload resume if provided
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $resumeFile = $_FILES['resume'];
        $resumeExt = strtolower(pathinfo($resumeFile['name'], PATHINFO_EXTENSION));
        $allowedResumeExts = ['pdf', 'doc', 'docx'];
        
        if (!in_array($resumeExt, $allowedResumeExts)) {
            throw new Exception('Invalid resume format. Allowed: PDF, DOC, DOCX');
        }
        
        $resumeFileName = 'resume_' . time() . '_' . $studentName . '.' . $resumeExt;
        $resumePath = 'uploads/resumes/' . $resumeFileName;
        
        if (!is_dir('uploads/resumes/')) {
            mkdir('uploads/resumes/', 0777, true);
        }
        
        if (!move_uploaded_file($resumeFile['tmp_name'], $resumePath)) {
            throw new Exception('Failed to upload resume');
        }
    }
    
    // Insert job application
    $stmt = $pdo->prepare("
        INSERT INTO job_applications (
            job_id, company_name, student_name, district, package, profile,
            photo_path, resume_path, email, phone, experience, skills,
            payment_id, application_status, applied_date, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), 1)
    ");
    
    $stmt->execute([
        $jobId, $companyName, $studentName, $district, $package, $profile,
        $photoPath, $resumePath, $email, $phone, $experience, $skills, $paymentId
    ]);
    
    $applicationId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Job application submitted successfully',
        'data' => [
            'application_id' => $applicationId,
            'photo_path' => $photoPath,
            'resume_path' => $resumePath
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting job application: ' . $e->getMessage()
    ]);
}
?> 