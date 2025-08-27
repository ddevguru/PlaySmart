<?php
// Test Job Application Submission Script
// This script tests if the job application submission is working

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Job Application Submission Test</h1>";
echo "<hr>";

try {
    // Test data
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
    
    echo "<h2>Test Data</h2>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Test 1: Test the submission endpoint
    echo "<h2>1. Testing Job Application Submission</h2>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/submit_job_application_working.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>✗ cURL Error: $error</p>";
    } else {
        echo "<p style='color: green;'>✓ cURL request completed</p>";
        echo "<p>HTTP Status Code: $httpCode</p>";
        echo "<p>Response: $response</p>";
        
        // Parse response
        $data = json_decode($response, true);
        if ($data) {
            if ($data['success']) {
                echo "<p style='color: green;'>✓ Job application submitted successfully!</p>";
                echo "<p>Application ID: " . $data['data']['application_id'] . "</p>";
                echo "<p>Payment ID: " . $data['data']['payment_id'] . "</p>";
                echo "<p>Status: " . $data['data']['status'] . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Job application failed: " . $data['message'] . "</p>";
                if (isset($data['debug_info'])) {
                    echo "<p><strong>Debug Info:</strong></p>";
                    echo "<ul>";
                    echo "<li>File: " . $data['debug_info']['file'] . "</li>";
                    echo "<li>Line: " . $data['debug_info']['line'] . "</li>";
                    echo "<li>Timestamp: " . $data['debug_info']['timestamp'] . "</li>";
                    echo "</ul>";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Invalid JSON response</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Summary</h2>";
echo "<p>This test verifies that:</p>";
echo "<ul>";
echo "<li>The job application endpoint is accessible</li>";
echo "<li>The database connection is working</li>";
echo "<li>The job_applications table exists and is writable</li>";
echo "<li>The form data is being processed correctly</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 