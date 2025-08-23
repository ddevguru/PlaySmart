<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'playsmart_db';
$username = 'your_username';
$password = 'your_password';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch active featured content
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            description,
            image_url,
            action_text,
            action_url,
            is_active,
            created_at
        FROM featured_content 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $featuredContent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process image URLs to include full path
    foreach ($featuredContent as &$content) {
        if ($content['image_url']) {
            $content['image_url'] = 'https://playsmart.co.in/images/featured_content/' . $content['image_url'];
        } else {
            $content['image_url'] = '';
        }
        
        // Clean up the data
        $content['created_at'] = date('Y-m-d H:i:s', strtotime($content['created_at']));
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Featured content fetched successfully',
        'data' => $featuredContent,
        'count' => count($featuredContent)
    ]);
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => []
    ]);
}
?> 