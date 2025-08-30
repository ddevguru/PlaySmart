<?php
/**
 * Test Razorpay Setup - PlaySmart
 * This script tests the database connection and Razorpay order creation
 */

require_once 'latestdb.php';

echo "<h2>🔍 Razorpay Setup Test - PlaySmart</h2>";
echo "<hr>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    if ($conn && !$conn->connect_error) {
        echo "✅ Database connection successful<br>";
        echo "   Host: " . DB_HOST . "<br>";
        echo "   Database: " . DB_NAME . "<br>";
        echo "   Username: " . DB_USERNAME . "<br>";
        echo "   Password: " . (DB_PASSWORD === 'Playsmart@123' ? '✅ SET' : '❌ NOT SET') . "<br>";
    } else {
        echo "❌ Database connection failed<br>";
        if ($conn) {
            echo "   Error: " . $conn->connect_error . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: Check if razorpay_orders table exists
echo "<h3>2. Razorpay Orders Table Check</h3>";
try {
    if ($conn) {
        $result = $conn->query("SHOW TABLES LIKE 'razorpay_orders'");
        if ($result && $result->num_rows > 0) {
            echo "✅ razorpay_orders table exists<br>";
            
            // Check table structure
            $result = $conn->query("DESCRIBE razorpay_orders");
            if ($result) {
                echo "   Table structure:<br>";
                while ($row = $result->fetch_assoc()) {
                    echo "   - " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "<br>";
                }
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

// Test 3: Check if users table exists and has required columns
echo "<h3>3. Users Table Check</h3>";
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
        } else {
            echo "❌ users table does NOT exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Users table check error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: Check Razorpay API credentials
echo "<h3>4. Razorpay API Credentials Check</h3>";
$keyId = 'rzp_live_fgQr0ACWFbL4pN';
$keySecret = 'kFpmvStUlrAys3U9gCkgLAnw';

if (!empty($keyId) && !empty($keySecret)) {
    echo "✅ Razorpay credentials are set<br>";
    echo "   Key ID: " . substr($keyId, 0, 10) . "...<br>";
    echo "   Key Secret: " . substr($keySecret, 0, 10) . "...<br>";
} else {
    echo "❌ Razorpay credentials are missing<br>";
}

echo "<hr>";

// Test 5: Test Razorpay API connection
echo "<h3>5. Razorpay API Connection Test</h3>";
try {
    $testOrderData = [
        'amount' => 100, // 1 rupee in paise
        'currency' => 'INR',
        'receipt' => 'test_' . time(),
        'payment_capture' => 1,
        'notes' => ['test' => 'true']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testOrderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($keyId . ':' . $keySecret)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "❌ cURL error: " . $curlError . "<br>";
    } elseif ($httpCode === 200) {
        echo "✅ Razorpay API connection successful<br>";
        $orderData = json_decode($response, true);
        if ($orderData && isset($orderData['id'])) {
            echo "   Test order created: " . $orderData['id'] . "<br>";
        }
    } else {
        echo "❌ Razorpay API error - HTTP Code: $httpCode<br>";
        echo "   Response: " . substr($response, 0, 200) . "...<br>";
    }
} catch (Exception $e) {
    echo "❌ Razorpay API test error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 6: Recommendations
echo "<h3>6. Recommendations</h3>";
echo "📋 <strong>Next Steps:</strong><br>";
echo "1. If any tests failed above, fix those issues first<br>";
echo "2. Make sure the razorpay_orders table exists with correct structure<br>";
echo "3. Test the payment flow from Flutter app<br>";
echo "4. Check server error logs for any PHP errors<br>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 