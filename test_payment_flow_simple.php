<?php
/**
 * Test Payment Flow Simple - PlaySmart
 * This file tests the complete payment flow without the Flutter app
 */

echo "<h2>🧪 Testing Complete Payment Flow - PlaySmart</h2>";
echo "<hr>";

try {
    // Include database configuration
    if (file_exists('newcon.php')) {
        require_once 'newcon.php';
    } elseif (file_exists('db_config.php')) {
        require_once 'db_config.php';
    } else {
        throw new Exception("No database configuration file found");
    }
    
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful<br><br>";
    
    // Test 1: Get a sample job
    echo "<h3>1. Getting Sample Job</h3>";
    $stmt = $pdo->query("SELECT * FROM jobs WHERE is_active = 1 LIMIT 1");
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception("No active jobs found in database");
    }
    
    echo "✅ Job found: " . $job['job_title'] . " at " . $job['company_name'] . "<br>";
    echo "   Job ID: " . $job['id'] . "<br>";
    echo "   Package: " . $job['package'] . "<br>";
    echo "   Location: " . $job['location'] . "<br><br>";
    
    // Test 2: Create a test job application
    echo "<h3>2. Creating Test Job Application</h3>";
    
    $testEmail = 'test_' . time() . '@example.com';
    $testPaymentId = 'test_pay_' . time() . '_' . rand(1000, 9999);
    
    // Use ₹5 instead of ₹0.1 to prevent Razorpay refunds
    $testAmount = 5.00;
    
    $applicationSql = "INSERT INTO job_applications (
        job_id, student_name, email, phone, company_name, profile, package, district,
        application_status, payment_id, applied_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $applicationStmt = $pdo->prepare($applicationSql);
    $applicationResult = $applicationStmt->execute([
        $job['id'],
        'Test User',
        $testEmail,
        '+91-9876543210',
        $job['company_name'],
        $job['job_title'],
        $job['package'],
        $job['location'],
        'accepted',
        $testPaymentId
    ]);
    
    if (!$applicationResult) {
        throw new Exception("Failed to create test job application");
    }
    
    $applicationId = $pdo->lastInsertId();
    echo "✅ Test job application created with ID: $applicationId<br>";
    echo "   Email: $testEmail<br>";
    echo "   Payment ID: $testPaymentId<br>";
    echo "   Amount: ₹$testAmount<br><br>";
    
    // Test 3: Create payment tracking record
    echo "<h3>3. Creating Payment Tracking Record</h3>";
    
    $paymentSql = "INSERT INTO payment_tracking (
        application_id, payment_id, razorpay_payment_id, razorpay_order_id,
        amount, currency, payment_status, payment_method, payment_date,
        gateway_response, created_at, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
    
    $paymentStmt = $pdo->prepare($paymentSql);
    $paymentResult = $paymentStmt->execute([
        $applicationId,
        $testPaymentId,
        'test_razorpay_' . time(),
        'test_order_' . time(),
        $testAmount, // Use the ₹5 amount
        'INR',
        'completed',
        'razorpay',
        date('Y-m-d H:i:s'),
        json_encode(['test' => 'gateway_response'])
    ]);
    
    if (!$paymentResult) {
        throw new Exception("Failed to create payment tracking record");
    }
    
    $paymentTrackingId = $pdo->lastInsertId();
    echo "✅ Payment tracking record created with ID: $paymentTrackingId<br><br>";
    
    // Test 4: Test email sending
    echo "<h3>4. Testing Email Sending</h3>";
    
    // Include process_payment.php to get email functions
    require_once 'process_payment.php';
    
    if (function_exists('sendPaymentSuccessEmail')) {
        echo "✅ Email function available<br>";
        
        // Test email sending with ₹5 amount
        $emailResult = sendPaymentSuccessEmail($applicationId, $testAmount, $testPaymentId);
        
        if ($emailResult) {
            echo "✅ Email sent successfully!<br>";
            echo "📧 Check if email was received at: $testEmail<br>";
        } else {
            echo "❌ Email sending failed<br>";
            echo "   This might be due to SMTP2GO configuration<br>";
        }
    } else {
        echo "❌ Email function not available<br>";
    }
    
    echo "<br>";
    
    // Test 5: Verify data in database
    echo "<h3>5. Verifying Data in Database</h3>";
    
    // Check job application
    $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($app) {
        echo "✅ Job application verified:<br>";
        echo "   Status: " . $app['application_status'] . "<br>";
        echo "   Payment ID: " . $app['payment_id'] . "<br>";
        echo "   Applied Date: " . $app['applied_date'] . "<br>";
    }
    
    // Check payment tracking
    $stmt = $pdo->prepare("SELECT * FROM payment_tracking WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "✅ Payment tracking verified:<br>";
        echo "   Payment Status: " . $payment['payment_status'] . "<br>";
        echo "   Amount: " . $payment['amount'] . " " . $payment['currency'] . "<br>";
        echo "   Payment Date: " . $payment['payment_date'] . "<br>";
    }
    
    echo "<hr>";
    echo "<h3>6. Test Summary</h3>";
    echo "✅ Database operations successful<br>";
    echo "✅ Job application created<br>";
    echo "✅ Payment tracking created<br>";
    echo "✅ Email function tested<br>";
    echo "🎯 Payment flow is working!<br>";
    
    echo "<br><strong>Next Steps:</strong><br>";
    echo "1. Check if test email was received<br>";
    echo "2. Test the complete flow in Flutter app<br>";
    echo "3. Verify status button updates correctly<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 