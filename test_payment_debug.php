<?php
// Test Payment Integration Debug File
// This file helps debug payment issues without authentication requirements

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

// Start logging
$logFile = 'payment_debug_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT DEBUG TEST STARTED ===");

try {
    // Test 1: Check if database config can be loaded
    writeLog("Test 1: Loading database config...");
    if (file_exists('db_config.php')) {
        require_once 'db_config.php';
        writeLog("✓ Database config loaded successfully");
        
        // Test database connection
        try {
            $pdo = getDBConnection();
            writeLog("✓ Database connection successful");
            
            // Test query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_applications");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            writeLog("✓ Database query successful. Total applications: " . $result['count']);
            
        } catch (Exception $e) {
            writeLog("✗ Database connection failed: " . $e->getMessage());
        }
    } else {
        writeLog("✗ Database config file not found");
    }
    
    // Test 2: Check if Razorpay config can be loaded
    writeLog("Test 2: Loading Razorpay config...");
    if (file_exists('razorpay_config.php')) {
        require_once 'razorpay_config.php';
        writeLog("✓ Razorpay config loaded successfully");
        
        // Check if constants are defined
        if (defined('RAZORPAY_KEY_ID')) {
            writeLog("✓ RAZORPAY_KEY_ID defined: " . RAZORPAY_KEY_ID);
        } else {
            writeLog("✗ RAZORPAY_KEY_ID not defined");
        }
        
        if (defined('RAZORPAY_KEY_SECRET')) {
            writeLog("✓ RAZORPAY_KEY_SECRET defined: " . substr(RAZORPAY_KEY_SECRET, 0, 10) . "...");
        } else {
            writeLog("✗ RAZORPAY_KEY_SECRET not defined");
        }
        
    } else {
        writeLog("✗ Razorpay config file not found");
    }
    
    // Test 3: Check request data
    writeLog("Test 3: Checking request data...");
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        writeLog("✓ JSON input received: " . print_r($input, true));
    } else {
        writeLog("✗ No JSON input received");
        writeLog("Raw input: " . file_get_contents('php://input'));
    }
    
    // Test 4: Check server environment
    writeLog("Test 4: Server environment...");
    writeLog("PHP Version: " . phpversion());
    writeLog("Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'));
    writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
    writeLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
    
    // Test 5: Check file permissions
    writeLog("Test 5: File permissions...");
    $filesToCheck = ['db_config.php', 'razorpay_config.php', 'payment_integration.php'];
    foreach ($filesToCheck as $file) {
        if (file_exists($file)) {
            $perms = substr(sprintf('%o', fileperms($file)), -4);
            $readable = is_readable($file) ? 'Yes' : 'No';
            writeLog("$file: Permissions $perms, Readable: $readable");
        } else {
            writeLog("$file: File not found");
        }
    }
    
    // Test 6: Check if any redirects are happening
    writeLog("Test 6: Checking for redirects...");
    $headers = headers_list();
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') !== false) {
            writeLog("⚠ Redirect header found: $header");
        }
    }
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Payment debug test completed successfully',
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
            'files_checked' => $filesToCheck,
            'log_file' => $logFile
        ]
    ];
    
    writeLog("✓ Debug test completed successfully");
    writeLog("Response: " . json_encode($response));
    writeLog("=== PAYMENT DEBUG TEST COMPLETED ===");
    
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== PAYMENT DEBUG TEST ERROR ===");
    writeLog("Error: " . $e->getMessage());
    writeLog("File: " . $e->getFile());
    writeLog("Line: " . $e->getLine());
    writeLog("Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    $errorResponse = [
        'success' => false,
        'message' => 'Debug test failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
    writeLog("=== PAYMENT DEBUG TEST FAILED ===");
}
?> 