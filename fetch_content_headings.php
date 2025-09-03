<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u968643667_playsmart');
define('DB_PASS', 'Playsmart@123');
define('DB_NAME', 'u968643667_playsmart');

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset('utf8mb4');
    
    // Get section from query parameter, default to successful_candidates
    $section = $_GET['section'] ?? 'successful_candidates';
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT heading_text, sub_heading_text FROM content_headings WHERE section_name = ? AND is_active = 1");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $section);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $heading = $result->fetch_assoc();
    
    if ($heading) {
        echo json_encode([
            'success' => true,
            'data' => [
                'heading' => $heading['heading_text'],
                'sub_heading' => $heading['sub_heading_text']
            ]
        ]);
    } else {
        // Return default values if no heading found
        echo json_encode([
            'success' => false,
            'message' => 'Heading not found for section: ' . $section,
            'data' => [
                'heading' => 'Our Successfully Placed',
                'sub_heading' => 'Candidates'
            ]
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching heading: ' . $e->getMessage(),
        'data' => [
            'heading' => 'Our Successfully Placed',
            'sub_heading' => 'Candidates'
        ]
    ]);
}
?> 