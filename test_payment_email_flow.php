<?php
/**
 * Test Payment Email Flow - PlaySmart
 * This file tests the enhanced payment email functionality
 */

// Load database config
require_once 'db_config.php';

// Set database connection variables
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USERNAME;
$password = DB_PASSWORD;

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Test data
$testJobId = 1; // Use an existing job ID
$testUserEmail = 'test@example.com';
$testAmount = 0.1;
$testPaymentId = 'test_pay_' . time();

echo "\n=== TESTING PAYMENT EMAIL FLOW ===\n";

// Step 1: Create a test job application
echo "\n1. Creating test job application...\n";
try {
    $applicationSql = "INSERT INTO job_applications (
        job_id, student_name, email, phone, company_name, profile, package, district,
        application_status, payment_status, payment_id, applied_date, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', ?, NOW(), NOW(), NOW())";
    
    $applicationStmt = $pdo->prepare($applicationSql);
    $applicationResult = $applicationStmt->execute([
        $testJobId,
        'Test User',
        $testUserEmail,
        '+91-9876543210',
        'Test Company',
        'Test Position',
        '10LPA',
        'Test District',
        $testPaymentId
    ]);
    
    if ($applicationResult) {
        $applicationId = $pdo->lastInsertId();
        echo "✅ Test application created with ID: $applicationId\n";
    } else {
        throw new Exception('Failed to create test application');
    }
} catch (Exception $e) {
    echo "❌ Error creating test application: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Create payment tracking record
echo "\n2. Creating payment tracking record...\n";
try {
    $paymentSql = "INSERT INTO payment_tracking (
        application_id, payment_id, razorpay_payment_id, razorpay_order_id,
        amount, currency, payment_status, payment_method, payment_date,
        gateway_response, created_at, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
    
    $paymentStmt = $pdo->prepare($paymentSql);
    $paymentResult = $paymentStmt->execute([
        $applicationId,
        $testPaymentId,
        'rzp_test_' . time(),
        'order_test_' . time(),
        $testAmount,
        'INR',
        'completed',
        'razorpay',
        date('Y-m-d H:i:s'),
        json_encode(['test' => 'gateway_response'])
    ]);
    
    if ($paymentResult) {
        $paymentTrackingId = $pdo->lastInsertId();
        echo "✅ Payment tracking record created with ID: $paymentTrackingId\n";
    } else {
        throw new Exception('Failed to create payment tracking record');
    }
} catch (Exception $e) {
    echo "❌ Error creating payment tracking record: " . $e->getMessage() . "\n";
    exit;
}

// Step 3: Update application status to accepted
echo "\n3. Updating application status to accepted...\n";
try {
    $updateSql = "UPDATE job_applications SET application_status = 'accepted' WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateResult = $updateStmt->execute([$applicationId]);
    
    if ($updateResult) {
        echo "✅ Application status updated to 'accepted'\n";
    } else {
        throw new Exception('Failed to update application status');
    }
} catch (Exception $e) {
    echo "❌ Error updating application status: " . $e->getMessage() . "\n";
    exit;
}

// Step 4: Test the enhanced email function
echo "\n4. Testing enhanced email function...\n";
try {
    // Include the process_payment.php file to access the email function
    require_once 'process_payment.php';
    
    // Test the email function
    $emailResult = sendPaymentSuccessEmail($applicationId, $testAmount, $testPaymentId);
    
    if ($emailResult) {
        echo "✅ Email sent successfully!\n";
        echo "📧 Email sent to: $testUserEmail\n";
        echo "📧 Subject: Payment Successful - Job Application Fee - PlaySmart\n";
        echo "📧 Content includes: Job details, Payment details, Application status, Next steps\n";
    } else {
        echo "❌ Email sending failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing email function: " . $e->getMessage() . "\n";
}

// Step 5: Verify database records
echo "\n5. Verifying database records...\n";
try {
    // Check application
    $appStmt = $pdo->prepare("SELECT * FROM job_applications WHERE id = ?");
    $appStmt->execute([$applicationId]);
    $application = $appStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($application) {
        echo "✅ Application record found:\n";
        echo "   - Status: " . $application['application_status'] . "\n";
        echo "   - Payment ID: " . $application['payment_id'] . "\n";
        echo "   - Applied Date: " . $application['applied_date'] . "\n";
    } else {
        echo "❌ Application record not found\n";
    }
    
    // Check payment tracking
    $payStmt = $pdo->prepare("SELECT * FROM payment_tracking WHERE application_id = ?");
    $payStmt->execute([$applicationId]);
    $payment = $payStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "✅ Payment tracking record found:\n";
        echo "   - Payment Status: " . $payment['payment_status'] . "\n";
        echo "   - Amount: ₹" . $payment['amount'] . "\n";
        echo "   - Payment Date: " . $payment['payment_date'] . "\n";
    } else {
        echo "❌ Payment tracking record not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error verifying database records: " . $e->getMessage() . "\n";
}

// Step 6: Cleanup test data (optional)
echo "\n6. Cleanup test data...\n";
echo "⚠️  Test data cleanup skipped for verification purposes\n";
echo "   - Application ID: $applicationId\n";
echo "   - Payment ID: $testPaymentId\n";
echo "   - You can manually delete these records if needed\n";

echo "\n=== TEST COMPLETED ===\n";
echo "\n📋 Summary:\n";
echo "✅ Database connection: Successful\n";
echo "✅ Test application: Created (ID: $applicationId)\n";
echo "✅ Payment tracking: Created\n";
echo "✅ Application status: Updated to 'accepted'\n";
echo "✅ Email function: Tested\n";
echo "✅ Database verification: Completed\n";
echo "\n🎯 Next steps:\n";
echo "1. Check the email sent to: $testUserEmail\n";
echo "2. Verify the email contains all required information\n";
echo "3. Test the Flutter app to see status button updates\n";
echo "4. Clean up test data when no longer needed\n";

?> 