<?php
/**
 * Test Real Payment Capture - Simulate Flutter app request
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Testing Real Payment Capture Request</h2>";
echo "<hr>";

try {
    // Include database configuration
    require_once 'latestdb.php';
    
    echo "<h3>1. Database Connection Test</h3>";
    $pdo = getDBConnection();
    echo "✅ Database connected successfully<br>";
    
    echo "<h3>2. Simulate Flutter App Request</h3>";
    
    // Use the exact data from the logs
    $testData = [
        'payment_id' => 'pay_RB6Bu01zsxV1vF',
        'order_id' => 'order_RB6BpH4oOac2qR',
        'signature' => 'test_signature_for_debug',
        'job_id' => 27,
        'amount' => 2.0,
        'referral_code' => ''
    ];
    
    echo "Test data:<br>";
    echo "- Payment ID: {$testData['payment_id']}<br>";
    echo "- Order ID: {$testData['order_id']}<br>";
    echo "- Job ID: {$testData['job_id']}<br>";
    echo "- Amount: ₹{$testData['amount']}<br>";
    echo "- Referral Code: '{$testData['referral_code']}'<br>";
    
    echo "<h3>3. Check if Order Exists</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM razorpay_orders WHERE order_id = ?");
    $stmt->execute([$testData['order_id']]);
    $order = $stmt->fetch();
    
    if ($order) {
        echo "✅ Order found:<br>";
        echo "- Status: {$order['status']}<br>";
        echo "- User ID: {$order['user_id']}<br>";
        echo "- Job ID: {$order['job_id']}<br>";
        echo "- Amount: ₹{$order['amount']}<br>";
        echo "- Created: {$order['created_at']}<br>";
    } else {
        echo "❌ Order NOT found!<br>";
        exit;
    }
    
    echo "<h3>4. Check if Job Application Exists</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE job_id = ? AND user_id = ?");
    $stmt->execute([$testData['job_id'], $order['user_id']]);
    $application = $stmt->fetch();
    
    if ($application) {
        echo "✅ Job application found:<br>";
        echo "- ID: {$application['id']}<br>";
        echo "- Payment Status: {$application['payment_status']}<br>";
        echo "- User ID: {$application['user_id']}<br>";
        echo "- Job ID: {$application['job_id']}<br>";
    } else {
        echo "❌ Job application NOT found!<br>";
        exit;
    }
    
    echo "<h3>5. Check Required Columns in job_applications</h3>";
    
    // Check if the required columns exist
    $stmt = $pdo->query("DESCRIBE job_applications");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = ['razorpay_payment_id', 'razorpay_order_id'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columnNames)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' MISSING!<br>";
        }
    }
    
    echo "<h3>6. Test Database Updates Step by Step</h3>";
    
    // Test 1: Update razorpay_orders
    try {
        $updateStmt = $pdo->prepare("UPDATE razorpay_orders SET status = 'paid', updated_at = NOW() WHERE order_id = ?");
        $result = $updateStmt->execute([$testData['order_id']]);
        echo "✅ razorpay_orders update: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
        echo "Rows affected: " . $updateStmt->rowCount() . "<br>";
    } catch (Exception $e) {
        echo "❌ razorpay_orders update failed: " . $e->getMessage() . "<br>";
    }
    
    // Test 2: Update job_applications
    try {
        $updateStmt = $pdo->prepare("
            UPDATE job_applications 
            SET payment_status = 'paid', 
                razorpay_payment_id = ?, 
                razorpay_order_id = ?,
                updated_at = NOW()
            WHERE job_id = ? AND user_id = ?
        ");
        $result = $updateStmt->execute([$testData['payment_id'], $testData['order_id'], $testData['job_id'], $order['user_id']]);
        echo "✅ job_applications update: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
        echo "Rows affected: " . $updateStmt->rowCount() . "<br>";
    } catch (Exception $e) {
        echo "❌ job_applications update failed: " . $e->getMessage() . "<br>";
    }
    
    // Test 3: Insert into payment_tracking
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO payment_tracking (
                user_id, job_id, razorpay_order_id, razorpay_payment_id, 
                amount, payment_status, referral_code, created_at
            ) VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())
        ");
        $result = $insertStmt->execute([$order['user_id'], $testData['job_id'], $testData['order_id'], $testData['payment_id'], $testData['amount'], $testData['referral_code']]);
        echo "✅ payment_tracking insert: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
        echo "Insert ID: " . $pdo->lastInsertId() . "<br>";
    } catch (Exception $e) {
        echo "❌ payment_tracking insert failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>7. Verify Updates</h3>";
    
    // Check final status
    $stmt = $pdo->query("SELECT * FROM razorpay_orders WHERE order_id = '{$testData['order_id']}'");
    $updatedOrder = $stmt->fetch();
    echo "Order status: " . ($updatedOrder['status'] ?? 'NOT FOUND') . "<br>";
    
    $stmt = $pdo->query("SELECT * FROM job_applications WHERE job_id = {$testData['job_id']} AND user_id = {$order['user_id']}");
    $updatedApp = $stmt->fetch();
    echo "Application payment status: " . ($updatedApp['payment_status'] ?? 'NOT FOUND') . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_tracking WHERE razorpay_order_id = '{$testData['order_id']}'");
    $trackingCount = $stmt->fetch();
    echo "Payment tracking records: " . ($trackingCount['count'] ?? '0') . "<br>";
    
    echo "<hr>";
    echo "<h3>8. Recommendations</h3>";
    echo "📋 <strong>Next Steps:</strong><br>";
    echo "1. If any tests failed above, fix those issues first<br>";
    echo "2. Check if the Flutter app is sending the correct data<br>";
    echo "3. Verify the capture_payment_clean.php endpoint is working<br>";
    
    echo "<hr>";
    echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Test Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 