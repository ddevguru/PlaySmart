<?php
/**
 * Simple Test Capture Payment - Minimal version to debug HTML output
 */

// Enable error reporting to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

echo "<h2>🔍 Testing Simple Payment Capture</h2>";

try {
    echo "<h3>1. Testing File Includes</h3>";
    
    if (file_exists('latestdb.php')) {
        echo "✅ latestdb.php exists<br>";
        require_once 'latestdb.php';
        echo "✅ latestdb.php included successfully<br>";
    } else {
        echo "❌ latestdb.php not found<br>";
        exit;
    }
    
    echo "<h3>2. Testing Database Connection</h3>";
    
    if (function_exists('getDBConnection')) {
        echo "✅ getDBConnection function exists<br>";
        
        try {
            $pdo = getDBConnection();
            echo "✅ Database connection successful<br>";
            
            // Test a simple query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_tracking");
            if ($stmt) {
                $result = $stmt->fetch();
                echo "✅ Payment tracking table accessible - Count: " . $result['count'] . "<br>";
            } else {
                echo "❌ Cannot query payment_tracking table<br>";
            }
        } catch (Exception $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ getDBConnection function not found<br>";
    }
    
    echo "<h3>3. Testing JSON Output</h3>";
    
    // Test if we can output JSON
    try {
        $testData = ['test' => 'data', 'timestamp' => date('Y-m-d H:i:s')];
        $jsonOutput = json_encode($testData);
        
        if ($jsonOutput !== false) {
            echo "✅ JSON encoding works: $jsonOutput<br>";
        } else {
            echo "❌ JSON encoding failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ JSON encoding error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>4. Testing Clean Output</h3>";
    
    // Test if we can clean output and send JSON
    try {
        ob_end_clean();
        ob_start();
        
        $testResponse = ['success' => true, 'message' => 'Test successful'];
        $jsonResponse = json_encode($testResponse);
        
        if ($jsonResponse !== false) {
            echo "✅ Clean JSON response prepared: $jsonResponse<br>";
        } else {
            echo "❌ Clean JSON response failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Clean output test failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Test Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 