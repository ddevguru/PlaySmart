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
    
    // Check if photo file was uploaded
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No photo file uploaded or upload error');
    }
    
    $photoFile = $_FILES['photo'];
    $jobId = $_POST['job_id'] ?? null;
    
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($photoFile['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed');
    }
    
    // Validate file size (max 5MB)
    if ($photoFile['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum size is 5MB');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($photoFile['name'], PATHINFO_EXTENSION);
    $filename = 'photo_' . $userId . '_' . $jobId . '_' . time() . '.' . $fileExtension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($photoFile['tmp_name'], $filepath)) {
        throw new Exception('Failed to save photo file');
    }
    
    // Save photo path to database
    $stmt = $pdo->prepare("INSERT INTO job_application_photos (user_id, job_id, photo_path, uploaded_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE photo_path = ?, uploaded_at = NOW()");
    $stmt->execute([$userId, $jobId, $filepath, $filepath]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo uploaded successfully',
        'data' => [
            'photo_path' => $filepath,
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