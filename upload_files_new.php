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
    // Check if files were uploaded
    if (!isset($_FILES['photo']) && !isset($_FILES['resume'])) {
        throw new Exception('No files received');
    }
    
    // Get application ID
    $applicationId = intval($_POST['application_id'] ?? 0);
    
    if (empty($applicationId) || $applicationId <= 0) {
        throw new Exception('Invalid application ID');
    }
    
    // Check if application exists
    $stmt = $conn->prepare("SELECT id FROM job_applications WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Application not found');
    }
    $stmt->close();
    
    // Create upload directories if they don't exist
    $photoDir = 'Admin/uploads/photos/';
    $resumeDir = 'Admin/uploads/resumes/';
    
    if (!is_dir($photoDir)) {
        mkdir($photoDir, 0755, true);
    }
    if (!is_dir($resumeDir)) {
        mkdir($resumeDir, 0755, true);
    }
    
    $photoPath = '';
    $resumePath = '';
    
    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoName = 'photo_' . $applicationId . '_' . time() . '_' . basename($_FILES['photo']['name']);
        $photoPath = $photoDir . $photoName;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
            throw new Exception('Failed to upload photo');
        }
        echo "Photo uploaded: $photoPath\n";
    }
    
    // Handle resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $resumeName = 'resume_' . $applicationId . '_' . time() . '_' . basename($_FILES['resume']['name']);
        $resumePath = $resumeDir . $resumeName;
        
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resumePath)) {
            throw new Exception('Failed to upload resume');
        }
        echo "Resume uploaded: $resumePath\n";
    }
    
    // Update database with file paths
    if (!empty($photoPath) || !empty($resumePath)) {
        $stmt = $conn->prepare("
            UPDATE job_applications 
            SET photo_path = COALESCE(NULLIF(?, ''), photo_path), 
                resume_path = COALESCE(NULLIF(?, ''), resume_path)
            WHERE id = ?
        ");
        
        $stmt->bind_param("ssi", $photoPath, $resumePath, $applicationId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update file paths in database: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Files uploaded successfully',
        'data' => [
            'application_id' => $applicationId,
            'photo_path' => $photoPath,
            'resume_path' => $resumePath
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