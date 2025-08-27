<?php
// Debug Form Submission Script
// This script helps debug the form submission issues

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug Form Submission</h1>";
echo "<hr>";

try {
    // Test 1: Check if required files exist
    echo "<h2>1. Checking Required Files</h2>";
    
    $requiredFiles = [
        'newcon.php',
        'submit_job_application_working.php',
        'simple_session_manager.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "<p style='color: green;'>✓ $file exists</p>";
        } else {
            echo "<p style='color: red;'>✗ $file missing</p>";
        }
    }
    
    // Test 2: Test database connection
    echo "<h2>2. Testing Database Connection</h2>";
    
    if (file_exists('newcon.php')) {
        require_once 'newcon.php';
        
        try {
            $dbCheck = checkDatabaseConnection();
            if ($dbCheck['success']) {
                echo "<p style='color: green;'>✓ Database connection successful</p>";
            } else {
                echo "<p style='color: red;'>✗ Database connection failed: " . $dbCheck['message'] . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Database connection error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test 3: Test form submission with minimal data
    echo "<h2>3. Testing Form Submission</h2>";
    
    $testData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'education' => 'Bachelor',
        'experience' => '2 years',
        'skills' => 'PHP, MySQL',
        'job_id' => 1,
        'referral_code' => '',
        'photo_path' => '',
        'resume_path' => '',
        'company_name' => 'Test Company',
        'package' => '5LPA',
        'profile' => 'Developer',
        'district' => 'Mumbai'
    ];
    
    echo "<p>Test data:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/submit_job_application_working.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Capture verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Show verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    
    echo "<p><strong>cURL Verbose Output:</strong></p>";
    echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
    
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
            } else {
                echo "<p style='color: red;'>✗ Form submission failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ HTTP Error: $httpCode</p>";
        }
    }
    
    // Test 4: Check if job_applications table exists
    echo "<h2>4. Checking Database Table</h2>";
    
    if (file_exists('newcon.php')) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ job_applications table exists</p>";
                
                // Check table structure
                $stmt = $pdo->query("DESCRIBE job_applications");
                $columns = $stmt->fetchAll();
                echo "<p>Table columns:</p>";
                echo "<ul>";
                foreach ($columns as $column) {
                    echo "<li>{$column['Field']} - {$column['Type']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>✗ job_applications table does not exist</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Database table check error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Debug error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>Debug Summary</h2>";
echo "<p>This debug script checks:</p>";
echo "<ol>";
echo "<li>Required files existence</li>";
echo "<li>Database connection</li>";
echo "<li>Form submission with detailed cURL output</li>";
echo "<li>Database table structure</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 