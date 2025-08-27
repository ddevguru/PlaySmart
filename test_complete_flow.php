<?php
// Test Complete Flow Script
// This script tests the entire job application flow

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Complete Flow Test - Job Application to Payment</h1>";
echo "<hr>";

try {
    // Test 1: Database Connection
    echo "<h2>1. Testing Database Connection</h2>";
    require_once 'newcon.php';
    
    $dbCheck = checkDatabaseConnection();
    if ($dbCheck['success']) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed: " . $dbCheck['message'] . "</p>";
        exit;
    }
    
    // Test 2: Session Manager
    echo "<h2>2. Testing Session Manager</h2>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/simple_session_manager.php?action=validate_token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => 'test_token_123']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✓ Session manager working</p>";
        } else {
            echo "<p style='color: red;'>✗ Session manager error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Session manager HTTP error: $httpCode</p>";
    }
    
    // Test 3: Job Application Submission
    echo "<h2>3. Testing Job Application Submission</h2>";
    $testData = [
        'name' => 'Test Applicant ' . date('Y-m-d H:i:s'),
        'email' => 'test.applicant.' . time() . '@example.com',
        'phone' => '9876543210',
        'education' => 'Bachelor\'s Degree',
        'experience' => '2 years',
        'skills' => 'PHP, MySQL, JavaScript',
        'job_id' => 1,
        'referral_code' => 'TEST' . time(),
        'photo_path' => '',
        'resume_path' => '',
        'company_name' => 'Test Company',
        'package' => '5LPA',
        'profile' => 'Software Developer',
        'district' => 'Mumbai'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/submit_job_application_working.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✓ Job application submission working</p>";
            echo "<p>Application ID: " . $data['data']['application_id'] . "</p>";
            echo "<p>Payment ID: " . $data['data']['payment_id'] . "</p>";
            
            // Test 4: Payment Gateway
            echo "<h2>4. Testing Payment Gateway</h2>";
            $paymentData = [
                'payment_amount' => 0.1,
                'job_type' => 'local',
                'student_name' => $testData['name'],
                'email' => $testData['email'],
                'phone' => $testData['phone']
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/simple_payment_working.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $paymentResponse = curl_exec($ch);
            $paymentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($paymentHttpCode == 200) {
                $paymentData = json_decode($paymentResponse, true);
                if ($paymentData && $paymentData['success']) {
                    echo "<p style='color: green;'>✓ Payment gateway working</p>";
                    echo "<p>Order ID: " . $paymentData['data']['razorpay_order_id'] . "</p>";
                    echo "<p>Amount: ₹" . $paymentData['data']['payment_amount'] . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Payment gateway error: " . ($paymentData['message'] ?? 'Unknown error') . "</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Payment gateway HTTP error: $paymentHttpCode</p>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ Job application error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Job application HTTP error: $httpCode</p>";
        echo "<p>Response: $response</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Summary</h2>";
echo "<p>This test verifies the complete flow:</p>";
echo "<ol>";
echo "<li>Database connection</li>";
echo "<li>Session management</li>";
echo "<li>Job application submission</li>";
echo "<li>Payment gateway integration</li>";
echo "</ol>";

echo "<p><strong>Expected Flow:</strong></p>";
echo "<ol>";
echo "<li>User fills job application form</li>";
echo "<li>Form data is submitted and saved to database</li>";
echo "<li>User sees instructions and payment amount</li>";
echo "<li>User proceeds to payment</li>";
echo "<li>Razorpay payment gateway opens</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 