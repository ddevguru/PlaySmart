<?php
/**
 * Test Capture Payment - Debug version
 */

// Enable error reporting to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Testing Payment Capture Endpoint</h2>";
echo "<hr>";

try {
    echo "<h3>1. Testing Database Connection</h3>";
    
    // Test if latestdb.php exists
    if (file_exists('latestdb.php')) {
        echo "✅ latestdb.php file exists<br>";
        require_once 'latestdb.php';
        
        // Test database connection
        if (isset($pdo) && $pdo) {
            echo "✅ Database connection successful<br>";
            
            // Test a simple query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_tracking");
            if ($stmt) {
                $result = $stmt->fetch();
                echo "✅ Payment tracking table accessible - Count: " . $result['count'] . "<br>";
            } else {
                echo "❌ Cannot query payment_tracking table<br>";
            }
        } else {
            echo "❌ Database connection failed<br>";
        }
    } else {
        echo "❌ latestdb.php file not found<br>";
    }
    
    echo "<hr>";
    
    echo "<h3>2. Testing Required Functions</h3>";
    
    // Test if required functions exist
    if (function_exists('getDBConnection')) {
        echo "✅ getDBConnection function exists<br>";
        
        try {
            $testPdo = getDBConnection();
            echo "✅ getDBConnection() works successfully<br>";
        } catch (Exception $e) {
            echo "❌ getDBConnection() failed: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ getDBConnection function not found<br>";
    }
    
    if (function_exists('getallheaders')) {
        echo "✅ getallheaders function exists<br>";
    } else {
        echo "❌ getallheaders function not found<br>";
    }
    
    echo "<hr>";
    
    echo "<h3>3. Testing File Permissions</h3>";
    
    // Test if we can create log files
    $logDir = 'payment_logs';
    if (is_dir($logDir)) {
        echo "✅ payment_logs directory exists<br>";
        
        if (is_writable($logDir)) {
            echo "✅ payment_logs directory is writable<br>";
        } else {
            echo "❌ payment_logs directory is NOT writable<br>";
        }
    } else {
        echo "❌ payment_logs directory does not exist<br>";
        
        // Try to create it
        if (mkdir($logDir, 0755, true)) {
            echo "✅ Created payment_logs directory<br>";
        } else {
            echo "❌ Failed to create payment_logs directory<br>";
        }
    }
    
    echo "<hr>";
    
    echo "<h3>4. Testing JSON Output</h3>";
    
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
    
    echo "<hr>";
    
    echo "<h3>5. Testing Request Data</h3>";
    
    // Test what request data we're receiving
    echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "<br>";
    echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "<br>";
    
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        echo "Raw input received: " . htmlspecialchars($rawInput) . "<br>";
        
        $decoded = json_decode($rawInput, true);
        if ($decoded) {
            echo "✅ JSON input decoded successfully<br>";
            echo "Decoded data: " . print_r($decoded, true) . "<br>";
        } else {
            echo "❌ JSON input decode failed<br>";
        }
    } else {
        echo "No raw input received<br>";
    }
    
    echo "<hr>";
    
    echo "<h3>6. Recommendations</h3>";
    echo "📋 <strong>Next Steps:</strong><br>";
    echo "1. If any tests failed above, fix those issues first<br>";
    echo "2. Check server error logs for PHP errors<br>";
    echo "3. Test the actual payment flow again<br>";
    
    echo "<hr>";
    echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Test Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?> 