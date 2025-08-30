<?php
/**
 * Simple Razorpay Test - PlaySmart
 * This script tests the database operations step by step
 */

require_once 'latestdb.php';

echo "<h2>🔍 Simple Razorpay Test - PlaySmart</h2>";
echo "<hr>";

// Test 1: Basic database connection
echo "<h3>1. Database Connection Test</h3>";
try {
    if ($conn && !$conn->connect_error) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        if ($conn) {
            echo "   Error: " . $conn->connect_error . "<br>";
        }
        exit;
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// Test 2: Test the exact bind_param operation that's failing
echo "<h3>2. Test bind_param Operation</h3>";
try {
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'razorpay_orders'");
    if (!$result || $result->num_rows === 0) {
        echo "❌ razorpay_orders table does not exist<br>";
        exit;
    }
    
    echo "✅ razorpay_orders table exists<br>";
    
    // Prepare the statement
    $stmt = $conn->prepare("
        INSERT INTO razorpay_orders (
            order_id, user_id, job_id, amount, currency, receipt, 
            payment_capture, status, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        echo "❌ Failed to prepare statement: " . $conn->error . "<br>";
        exit;
    }
    
    echo "✅ Statement prepared successfully<br>";
    
    // Create test data with the exact same structure
    $testOrderId = 'test_order_' . time();
    $testUserId = 1;
    $testJobId = 1;
    $testAmount = 100.00;
    $testCurrency = 'INR';
    $testReceipt = 'test_receipt_' . time();
    $testPaymentCapture = '1';
    $testStatus = 'created';
    $testNotes = json_encode(['test' => 'true']);
    
    echo "   Test data created:<br>";
    echo "   - order_id: $testOrderId<br>";
    echo "   - user_id: $testUserId<br>";
    echo "   - job_id: $testJobId<br>";
    echo "   - amount: $testAmount<br>";
    echo "   - currency: $testCurrency<br>";
    echo "   - receipt: $testReceipt<br>";
    echo "   - payment_capture: $testPaymentCapture<br>";
    echo "   - status: $testStatus<br>";
    echo "   - notes: $testNotes<br>";
    
    // Test bind_param with the exact same call
    echo "<br>   Testing bind_param...<br>";
    
    try {
        $bindResult = $stmt->bind_param("siissssss", 
            $testOrderId, $testUserId, $testJobId, $testAmount, 
            $testCurrency, $testReceipt, $testPaymentCapture, 
            $testStatus, $testNotes
        );
        
        if ($bindResult) {
            echo "✅ bind_param completed successfully!<br>";
            
            // Test execute (but don't actually insert)
            echo "   Testing execute (will rollback)...<br>";
            $conn->begin_transaction();
            
            $executeResult = $stmt->execute();
            if ($executeResult) {
                echo "✅ execute completed successfully!<br>";
                $conn->rollback();
                echo "   Transaction rolled back (no test data inserted)<br>";
            } else {
                echo "❌ execute failed: " . $stmt->error . "<br>";
            }
            
        } else {
            echo "❌ bind_param failed: " . $stmt->error . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ bind_param exception: " . $e->getMessage() . "<br>";
        echo "   File: " . $e->getFile() . "<br>";
        echo "   Line: " . $e->getLine() . "<br>";
        echo "   Stack trace: " . $e->getTraceAsString() . "<br>";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "<br>";
    echo "   File: " . $e->getFile() . "<br>";
    echo "   Line: " . $e->getLine() . "<br>";
}

echo "<hr>";

// Test 3: Check PHP version and mysqli info
echo "<h3>3. PHP and MySQLi Information</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "MySQLi Client Version: " . mysqli_get_client_info() . "<br>";
echo "MySQLi Server Version: " . ($conn ? mysqli_get_server_info($conn) : 'N/A') . "<br>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 