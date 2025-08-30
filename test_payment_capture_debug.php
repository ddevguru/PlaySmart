<?php
/**
 * Test Payment Capture Debug - Test with real payment data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Testing Payment Capture with Real Data</h2>";
echo "<hr>";

try {
    // Include database configuration
    require_once 'latestdb.php';
    
    echo "<h3>1. Database Connection Test</h3>";
    $pdo = getDBConnection();
    echo "✅ Database connected successfully<br>";
    
    echo "<h3>2. Check Existing Orders</h3>";
    $stmt = $pdo->query("SELECT * FROM razorpay_orders ORDER BY created_at DESC LIMIT 3");
    $orders = $stmt->fetchAll();
    
    echo "Found " . count($orders) . " recent orders:<br>";
    foreach ($orders as $order) {
        echo "- Order ID: {$order['order_id']}, Status: {$order['status']}, Job ID: {$order['job_id']}<br>";
    }
    
    echo "<h3>3. Check Job Applications</h3>";
    $stmt = $pdo->query("SELECT * FROM job_applications WHERE payment_status = 'pending' ORDER BY created_at DESC LIMIT 3");
    $applications = $stmt->fetchAll();
    
    echo "Found " . count($applications) . " pending applications:<br>";
    foreach ($applications as $app) {
        echo "- Job ID: {$app['job_id']}, Status: {$app['payment_status']}, User ID: {$app['user_id']}<br>";
    }
    
    echo "<h3>4. Test Payment Capture Process</h3>";
    
    // Simulate a payment capture with the most recent order
    if (!empty($orders)) {
        $latestOrder = $orders[0];
        $orderId = $latestOrder['order_id'];
        $jobId = $latestOrder['job_id'];
        $userId = $latestOrder['user_id'];
        $amount = $latestOrder['amount'];
        
        echo "Testing with Order ID: $orderId<br>";
        echo "Job ID: $jobId<br>";
        echo "User ID: $userId<br>";
        echo "Amount: ₹$amount<br>";
        
        // Generate a fake payment ID and signature for testing
        $fakePaymentId = 'pay_test_' . time();
        $fakeSignature = hash_hmac('sha256', $orderId . '|' . $fakePaymentId, 'kFpmvStUlrAys3U9gCkgLAnw');
        
        echo "Fake Payment ID: $fakePaymentId<br>";
        echo "Generated Signature: $fakeSignature<br>";
        
        echo "<h4>4.1 Test Database Updates</h4>";
        
        // Test updating razorpay_orders
        try {
            $updateStmt = $pdo->prepare("UPDATE razorpay_orders SET status = 'paid', updated_at = NOW() WHERE order_id = ?");
            $result = $updateStmt->execute([$orderId]);
            echo "✅ razorpay_orders update: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
            echo "Rows affected: " . $updateStmt->rowCount() . "<br>";
        } catch (Exception $e) {
            echo "❌ razorpay_orders update failed: " . $e->getMessage() . "<br>";
        }
        
        // Test updating job_applications
        try {
            $updateStmt = $pdo->prepare("
                UPDATE job_applications 
                SET payment_status = 'paid', 
                    razorpay_payment_id = ?, 
                    razorpay_order_id = ?,
                    updated_at = NOW()
                WHERE job_id = ? AND user_id = ?
            ");
            $result = $updateStmt->execute([$fakePaymentId, $orderId, $jobId, $userId]);
            echo "✅ job_applications update: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
            echo "Rows affected: " . $updateStmt->rowCount() . "<br>";
        } catch (Exception $e) {
            echo "❌ job_applications update failed: " . $e->getMessage() . "<br>";
        }
        
        // Test inserting into payment_tracking
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO payment_tracking (
                    user_id, job_id, razorpay_order_id, razorpay_payment_id, 
                    amount, payment_status, referral_code, created_at
                ) VALUES (?, ?, ?, ?, ?, 'completed', '', NOW())
            ");
            $result = $insertStmt->execute([$userId, $jobId, $orderId, $fakePaymentId, $amount]);
            echo "✅ payment_tracking insert: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
            echo "Insert ID: " . $pdo->lastInsertId() . "<br>";
        } catch (Exception $e) {
            echo "❌ payment_tracking insert failed: " . $e->getMessage() . "<br>";
        }
        
        echo "<h4>4.2 Verify Updates</h4>";
        
        // Check if updates were successful
        $stmt = $pdo->query("SELECT * FROM razorpay_orders WHERE order_id = '$orderId'");
        $updatedOrder = $stmt->fetch();
        echo "Order status: " . ($updatedOrder['status'] ?? 'NOT FOUND') . "<br>";
        
        $stmt = $pdo->query("SELECT * FROM job_applications WHERE job_id = $jobId AND user_id = $userId");
        $updatedApp = $stmt->fetch();
        echo "Application payment status: " . ($updatedApp['payment_status'] ?? 'NOT FOUND') . "<br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_tracking WHERE razorpay_order_id = '$orderId'");
        $trackingCount = $stmt->fetch();
        echo "Payment tracking records: " . ($trackingCount['count'] ?? '0') . "<br>";
        
    } else {
        echo "❌ No orders found to test with<br>";
    }
    
    echo "<hr>";
    echo "<h3>5. Recommendations</h3>";
    echo "📋 <strong>Next Steps:</strong><br>";
    echo "1. Check the payment_logs directory for error logs<br>";
    echo "2. Test the actual payment flow again<br>";
    echo "3. Look for any PHP errors in server logs<br>";
    
    echo "<hr>";
    echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Test Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 