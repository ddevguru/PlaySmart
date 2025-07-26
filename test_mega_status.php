<?php
// Simple test file to check mega contest status
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

// Test data
$test_data = [
    'session_token' => 'test_token',
    'contest_id' => 78
];

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'test_data' => $test_data,
    'current_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
]);
?> 