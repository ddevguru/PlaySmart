<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

try {
    // Check if user is authenticated
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }
    
    if (!$token) {
        throw new Exception('No authentication token provided');
    }
    
    // Verify token and get user ID
    $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $session = $stmt->fetch();
    
    if (!$session) {
        throw new Exception('Invalid or expired token');
    }
    
    $userId = $session['user_id'];
    
    // Check if resume file was uploaded
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No resume file uploaded or upload error');
    }
    
    $resumeFile = $_FILES['resume'];
    $jobId = $_POST['job_id'] ?? null;
    
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $allowedExtensions = ['pdf', 'doc', 'docx'];
    
    $fileExtension = strtolower(pathinfo($resumeFile['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only PDF, DOC, and DOCX are allowed');
    }
    
    // Validate file size (max 10MB)
    if ($resumeFile['size'] > 10 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum size is 10MB');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/resumes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = 'resume_' . $userId . '_' . $jobId . '_' . time() . '.' . $fileExtension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($resumeFile['tmp_name'], $filepath)) {
        throw new Exception('Failed to save resume file');
    }
    
    // Save resume path to database
    $stmt = $pdo->prepare("INSERT INTO job_application_resumes (user_id, job_id, resume_path, uploaded_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE resume_path = ?, uploaded_at = NOW()");
    $stmt->execute([$userId, $jobId, $filepath, $filepath]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Resume uploaded successfully',
        'data' => [
            'resume_path' => $filepath,
            'filename' => $filename
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 