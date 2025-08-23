<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow PUT/POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'playsmart_db';
$username = 'your_username';
$password = 'your_password';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate required fields
    if (empty($input['id'])) {
        throw new Exception("Job ID is required");
    }
    
    $job_id = intval($input['id']);
    
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if job exists
    $check_stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ?");
    $check_stmt->execute([$job_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception("Job not found with ID: $job_id");
    }
    
    // Build update query dynamically
    $update_fields = [];
    $update_values = [];
    
    $allowed_fields = [
        'company_name', 'student_name', 'district', 'package', 'profile',
        'job_title', 'location', 'job_type', 'experience_level', 
        'skills_required', 'job_description', 'is_active'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = ?";
            $update_values[] = $input[$field];
        }
    }
    
    if (empty($update_fields)) {
        throw new Exception("No valid fields to update");
    }
    
    // Add job_id to values array
    $update_values[] = $job_id;
    
    // Handle file upload for company logo
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/company_logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }
        
        $filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $upload_path)) {
            $update_fields[] = "company_logo = ?";
            $update_values[] = $filename;
        }
    }
    
    // Build and execute update query
    $sql = "UPDATE jobs SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($update_values);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Job updated successfully',
        'data' => [
            'id' => $job_id,
            'updated_fields' => array_keys(array_filter($input, function($value) {
                return $value !== null && $value !== '';
            }))
        ]
    ]);
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 