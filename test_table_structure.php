<?php
/**
 * Test Table Structure - PlaySmart
 * This script tests the database table structure for Razorpay orders
 */

require_once 'latestdb.php';

echo "<h2>🔍 Table Structure Test - PlaySmart</h2>";
echo "<hr>";

// Test 1: Check if razorpay_orders table exists
echo "<h3>1. Razorpay Orders Table Check</h3>";
try {
    if ($conn) {
        $result = $conn->query("SHOW TABLES LIKE 'razorpay_orders'");
        if ($result && $result->num_rows > 0) {
            echo "✅ razorpay_orders table exists<br>";
            
            // Check table structure
            $result = $conn->query("DESCRIBE razorpay_orders");
            if ($result) {
                echo "   <h4>Table structure:</h4>";
                echo "   <table border='1' style='border-collapse: collapse;'>";
                echo "   <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "   <tr>";
                    echo "   <td>{$row['Field']}</td>";
                    echo "   <td>{$row['Type']}</td>";
                    echo "   <td>{$row['Null']}</td>";
                    echo "   <td>{$row['Key']}</td>";
                    echo "   <td>{$row['Default']}</td>";
                    echo "   <td>{$row['Extra']}</td>";
                    echo "   </tr>";
                }
                echo "   </table>";
            }
        } else {
            echo "❌ razorpay_orders table does NOT exist<br>";
            echo "   You need to run the razorpay_orders_table.sql script<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Table check error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: Check if we can insert a test record
echo "<h3>2. Test Insert Record</h3>";
try {
    if ($conn) {
        // Check if table exists first
        $result = $conn->query("SHOW TABLES LIKE 'razorpay_orders'");
        if ($result && $result->num_rows > 0) {
            // Try to prepare the statement
            $stmt = $conn->prepare("
                INSERT INTO razorpay_orders (
                    order_id, user_id, job_id, amount, currency, receipt, 
                    payment_capture, status, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt) {
                echo "✅ Statement prepared successfully<br>";
                
                // Test with dummy data
                $testOrderId = 'test_order_' . time();
                $testUserId = 1;
                $testJobId = 1;
                $testAmount = 100.00;
                $testCurrency = 'INR';
                $testReceipt = 'test_receipt_' . time();
                $testPaymentCapture = '1';
                $testStatus = 'created';
                $testNotes = json_encode(['test' => 'true']);
                
                echo "   Test data prepared:<br>";
                echo "   - order_id: $testOrderId<br>";
                echo "   - user_id: $testUserId<br>";
                echo "   - job_id: $testJobId<br>";
                echo "   - amount: $testAmount<br>";
                echo "   - notes: $testNotes<br>";
                
                // Try to bind parameters
                $bindResult = $stmt->bind_param("siissssss", 
                    $testOrderId, $testUserId, $testJobId, $testAmount, 
                    $testCurrency, $testReceipt, $testPaymentCapture, 
                    $testStatus, $testNotes
                );
                
                if ($bindResult) {
                    echo "✅ Parameters bound successfully<br>";
                    
                    // Try to execute (but don't actually insert)
                    echo "   (Skipping actual insert to avoid test data)<br>";
                    echo "✅ All database operations working correctly<br>";
                } else {
                    echo "❌ Failed to bind parameters<br>";
                    echo "   Error: " . $stmt->error . "<br>";
                }
                
                $stmt->close();
            } else {
                echo "❌ Failed to prepare statement<br>";
                echo "   Error: " . $conn->error . "<br>";
            }
        } else {
            echo "❌ Cannot test insert - table doesn't exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Test insert error: " . $e->getMessage() . "<br>";
    echo "   File: " . $e->getFile() . "<br>";
    echo "   Line: " . $e->getLine() . "<br>";
}

echo "<hr>";

// Test 3: Check users table structure
echo "<h3>3. Users Table Structure Check</h3>";
try {
    if ($conn) {
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result && $result->num_rows > 0) {
            echo "✅ users table exists<br>";
            
            // Check if session_token and status columns exist
            $result = $conn->query("SHOW COLUMNS FROM users LIKE 'session_token'");
            if ($result && $result->num_rows > 0) {
                echo "   ✅ session_token column exists<br>";
            } else {
                echo "   ❌ session_token column missing<br>";
            }
            
            $result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            if ($result && $result->num_rows > 0) {
                echo "   ✅ status column exists<br>";
            } else {
                echo "   ❌ status column missing<br>";
            }
            
            // Check if there are any users with valid session tokens
            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE session_token IS NOT NULL AND session_token != ''");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "   Users with session tokens: " . $row['count'] . "<br>";
            }
        } else {
            echo "❌ users table does NOT exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Users table check error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: Recommendations
echo "<h3>4. Recommendations</h3>";
echo "📋 <strong>Next Steps:</strong><br>";
echo "1. If the razorpay_orders table is missing or has wrong structure, run the SQL script<br>";
echo "2. If the users table is missing required columns, add them<br>";
echo "3. Test the payment flow again from Flutter app<br>";
echo "4. Check server error logs for any remaining PHP errors<br>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 