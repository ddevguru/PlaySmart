<?php
/**
 * Check Payment Status - See current state of all tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Checking Current Payment Status</h2>";
echo "<hr>";

try {
    // Include database configuration
    require_once 'latestdb.php';
    
    echo "<h3>1. Database Connection Test</h3>";
    $pdo = getDBConnection();
    echo "✅ Database connected successfully<br>";
    
    // Use the payment data from the logs
    $paymentId = 'pay_RB6Bu01zsxV1vF';
    $orderId = 'order_RB6BpH4oOac2qR';
    $jobId = 27;
    $userId = 5;
    
    echo "<h3>2. Current Order Status</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM razorpay_orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if ($order) {
        echo "✅ Order Status: <strong>{$order['status']}</strong><br>";
        echo "- Order ID: {$order['order_id']}<br>";
        echo "- User ID: {$order['user_id']}<br>";
        echo "- Job ID: {$order['job_id']}<br>";
        echo "- Amount: ₹{$order['amount']}<br>";
        echo "- Created: {$order['created_at']}<br>";
        echo "- Updated: {$order['updated_at']}<br>";
    } else {
        echo "❌ Order not found<br>";
    }
    
    echo "<h3>3. Current Job Application Status</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE job_id = ? AND user_id = ?");
    $stmt->execute([$jobId, $userId]);
    $application = $stmt->fetch();
    
    if ($application) {
        echo "✅ Job Application Status: <strong>{$application['payment_status']}</strong><br>";
        echo "- ID: {$application['id']}<br>";
        echo "- User ID: {$application['user_id']}<br>";
        echo "- Job ID: {$application['job_id']}<br>";
        echo "- Payment Status: {$application['payment_status']}<br>";
        echo "- Razorpay Payment ID: " . ($application['razorpay_payment_id'] ?? 'NULL') . "<br>";
        echo "- Razorpay Order ID: " . ($application['razorpay_order_id'] ?? 'NULL') . "<br>";
        echo "- Updated: {$application['updated_at']}<br>";
    } else {
        echo "❌ Job application not found<br>";
    }
    
    echo "<h3>4. Current Payment Tracking Status</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM payment_tracking WHERE razorpay_payment_id = ? OR razorpay_order_id = ?");
    $stmt->execute([$paymentId, $orderId]);
    $tracking = $stmt->fetchAll();
    
    if (!empty($tracking)) {
        echo "✅ Payment Tracking Records Found: " . count($tracking) . "<br>";
        foreach ($tracking as $record) {
            echo "- ID: {$record['id']}<br>";
            echo "- User ID: {$record['user_id']}<br>";
            echo "- Job ID: {$record['job_id']}<br>";
            echo "- Order ID: {$record['razorpay_order_id']}<br>";
            echo "- Payment ID: {$record['razorpay_payment_id']}<br>";
            echo "- Amount: ₹{$record['amount']}<br>";
            echo "- Status: {$record['payment_status']}<br>";
            echo "- Created: {$record['created_at']}<br>";
            echo "<hr>";
        }
    } else {
        echo "❌ No payment tracking records found<br>";
    }
    
    echo "<h3>5. Summary</h3>";
    
    if ($order && $order['status'] === 'paid') {
        echo "✅ <strong>Order is marked as PAID</strong><br>";
    } else {
        echo "❌ <strong>Order is NOT marked as PAID</strong><br>";
    }
    
    if ($application && $application['payment_status'] === 'paid') {
        echo "✅ <strong>Job Application is marked as PAID</strong><br>";
    } else {
        echo "❌ <strong>Job Application is NOT marked as PAID</strong><br>";
    }
    
    if (!empty($tracking)) {
        echo "✅ <strong>Payment Tracking record exists</strong><br>";
    } else {
        echo "❌ <strong>Payment Tracking record missing</strong><br>";
    }
    
    echo "<h3>6. Recommendations</h3>";
    
    if ($order['status'] === 'paid' && $application['payment_status'] === 'paid' && !empty($tracking)) {
        echo "🎉 <strong>Payment is fully processed! Everything is working correctly.</strong><br>";
        echo "The duplicate error in the test was expected since the payment was already processed.<br>";
    } else {
        echo "⚠️ <strong>Payment is partially processed. Some updates are missing.</strong><br>";
        echo "We need to manually complete the missing updates.<br>";
    }
    
    echo "<hr>";
    echo "<p><em>Check completed at: " . date('Y-m-d H:i:s') . "</em></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Check Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 