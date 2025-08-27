<?php
// Test New Payment Flow - Simple test without application ID requirement

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    'success' => true,
    'message' => 'New payment flow test successful',
    'test_data' => [
        'payment_amount' => 2000,
        'job_type' => 'higher_package',
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'ready_for_razorpay'
    ],
    'razorpay_data' => [
        'key_id' => 'rzp_test_YOUR_TEST_KEY_ID', // Replace with your actual key
        'amount' => 200000, // 2000 * 100 (in paise)
        'currency' => 'INR',
        'name' => 'PlaySmart',
        'description' => 'Job Application Fee - higher_package',
        'order_id' => 'test_order_' . time(),
        'prefill' => [
            'name' => 'Test Applicant',
            'email' => 'test@example.com',
            'contact' => '1234567890'
        ]
    ]
]);
?> 