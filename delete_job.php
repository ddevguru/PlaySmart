<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow DELETE/POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    $check_stmt = $pdo->prepare("SELECT id, company_logo FROM jobs WHERE id = ?");
    $check_stmt->execute([$job_id]);
    $job = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception("Job not found with ID: $job_id");
    }
    
    // Delete the job (soft delete by setting is_active to 0)
    $stmt = $pdo->prepare("UPDATE jobs SET is_active = 0 WHERE id = ?");
    $stmt->execute([$job_id]);
    
    // Optionally, you can also delete the company logo file
    if ($job['company_logo'] && file_exists('images/company_logos/' . $job['company_logo'])) {
        unlink('images/company_logos/' . $job['company_logo']);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Job deleted successfully',
        'data' => [
            'id' => $job_id
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