<?php
// Test Script to Verify All Fixes
// This script tests database connection, payment gateway, and job application submission

header('Content-Type: text/html; charset=utf-8');

echo "<h1>PlaySmart Fixes Test</h1>";
echo "<hr>";

// Test 1: Database Connection
echo "<h2>1. Testing Database Connection</h2>";
try {
    require_once 'db_config.php';
    
    $dbCheck = checkDatabaseConnection();
    if ($dbCheck['success']) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test actual connection
        $pdo = getDBConnection();
        echo "<p style='color: green;'>✓ PDO connection working</p>";
        
        // Test query
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        if ($result['test'] == 1) {
            echo "<p style='color: green;'>✓ Database query working</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed: " . $dbCheck['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Razorpay Configuration
echo "<h2>2. Testing Razorpay Configuration</h2>";
try {
    require_once 'razorpay_config.php';
    
    $razorpayCheck = checkRazorpayConfig();
    if ($razorpayCheck['success']) {
        echo "<p style='color: green;'>✓ Razorpay configuration valid</p>";
        echo "<p>Key ID: " . RAZORPAY_KEY_ID . "</p>";
        echo "<p>Test Mode: " . (RAZORPAY_TEST_MODE ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Razorpay configuration issue: " . $razorpayCheck['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Razorpay test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Payment Gateway
echo "<h2>3. Testing Payment Gateway</h2>";
try {
    $testData = [
        'payment_amount' => 0.1,
        'job_type' => 'local',
        'student_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567890'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/simple_payment_working.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✓ Payment gateway working</p>";
            echo "<p>Order ID: " . $data['data']['razorpay_order_id'] . "</p>";
            echo "<p>Amount: ₹" . $data['data']['payment_amount'] . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Payment gateway error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Payment gateway HTTP error: $httpCode</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Payment gateway test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 4: Job Application Submission
echo "<h2>4. Testing Job Application Submission</h2>";
try {
    $testApplication = [
        'name' => 'Test Applicant',
        'email' => 'test.applicant@example.com',
        'phone' => '9876543210',
        'education' => 'Bachelor\'s Degree',
        'experience' => '2 years',
        'skills' => 'PHP, MySQL, JavaScript',
        'job_id' => 1,
        'referral_code' => 'TEST123',
        'photo_path' => '',
        'resume_path' => '',
        'company_name' => 'Test Company',
        'package' => '5LPA',
        'profile' => 'Software Developer',
        'district' => 'Mumbai'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/submit_job_application_with_files_updated.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testApplication));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✓ Job application submission working</p>";
            echo "<p>Application ID: " . $data['data']['application_id'] . "</p>";
            echo "<p>Payment ID: " . $data['data']['payment_id'] . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Job application error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Job application HTTP error: $httpCode</p>";
        echo "<p>Response: $response</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Job application test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 5: Token Validation
echo "<h2>5. Testing Token Validation</h2>";
try {
    $testToken = 'test_token_' . time();
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/validate_token.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => $testToken]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 401) {
        echo "<p style='color: green;'>✓ Token validation working (correctly rejected invalid token)</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Token validation returned unexpected status: $httpCode</p>";
        echo "<p>Response: $response</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Token validation test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Summary
echo "<h2>Test Summary</h2>";
echo "<p>All tests completed. Check the results above to see which components are working correctly.</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Update your database credentials in db_config.php if needed</li>";
echo "<li>Add your actual Razorpay secret key in razorpay_config.php</li>";
echo "<li>Test the Flutter app to see if payment gateway errors are resolved</li>";
echo "<li>Test job application submission to see if database errors are resolved</li>";
echo "<li>Test login persistence to see if session issues are resolved</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 