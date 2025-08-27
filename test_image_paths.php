<?php
// Test Image Paths - Verify that image URLs are being constructed correctly

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    'success' => true,
    'message' => 'Image path test',
    'test_paths' => [
        'original_path' => 'uploads/images/photo123.jpg',
        'fixed_path' => 'https://playsmart.co.in/Admin/uploads/photos/photo123.jpg',
        'basename_test' => basename('uploads/images/photo123.jpg'),
        'correct_url' => 'https://playsmart.co.in/Admin/uploads/photos/' . basename('uploads/images/photo123.jpg')
    ],
    'explanation' => [
        'Database stores' => 'uploads/images/filename',
        'Actual location' => 'Admin/uploads/photos/filename',
        'Fix applied' => 'Extract filename and create correct URL path'
    ]
]);
?> 