<?php
// Test Form Fix Script
// This script tests if the form submission error is fixed

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Form Submission Fix</h1>";
echo "<hr>";

try {
    // Test form submission with the same data that was failing
    $testData = [
        'name' => 'Test User ' . date('Y-m-d H:i:s'),
        'email' => 'test.user.' . time() . '@example.com',
        'phone' => '9876543210',
        'education' => 'Bachelor\'s Degree',
        'experience' => '3 years',
        'skills' => 'PHP, MySQL, JavaScript',
        'job_id' => 101,
        'referral_code' => '',
        'photo_path' => '/test/photo.png',
        'resume_path' => '/test/resume.pdf',
        'company_name' => 'Test Company',
        'package' => '8LPA',
        'profile' => 'Software Developer',
        'district' => 'Mumbai'
    ];
    
    echo "<h2>Test Data</h2>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    echo "<h2>Testing Form Submission</h2>";
    
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
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>✗ cURL Error: $error</p>";
    } else {
        echo "<p style='color: green;'>✓ cURL request completed</p>";
        echo "<p>HTTP Status Code: $httpCode</p>";
        echo "<p>Response: $response</p>";
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                echo "<p style='color: green;'>✓ Form submission successful!</p>";
                echo "<p>Application ID: " . $data['data']['application_id'] . "</p>";
                echo "<p>Payment ID: " . $data['data']['payment_id'] . "</p>";
                echo "<p>Status: " . $data['data']['status'] . "</p>";
                
                echo "<hr>";
                echo "<h2>🎉 SUCCESS! The form submission is now working!</h2>";
                echo "<p>The PDO close() error has been fixed.</p>";
                
            } else {
                echo "<p style='color: red;'>✗ Form submission failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ HTTP Error: $httpCode</p>";
            echo "<p>This might indicate another issue that needs fixing.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>What Was Fixed</h2>";
echo "<p>The error was caused by calling <code>\$stmt->close()</code> on a PDO statement.</p>";
echo "<p>PDO statements don't have a <code>close()</code> method - that's a MySQLi method.</p>";
echo "<p>I've replaced it with <code>\$stmt = null;</code> which properly cleans up the PDO statement.</p>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 