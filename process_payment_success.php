<?php
header('Content-Type: application/json');
require_once 'latestdb.php';

try {
    $pdo = getDBConnection();
    
    // Get payment details from Razorpay webhook or callback
    $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
    $razorpaySignature = $_POST['razorpay_signature'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    $jobId = $_POST['job_id'] ?? '';
    
    if (empty($razorpayPaymentId) || empty($razorpayOrderId) || empty($jobId)) {
        throw new Exception("Missing required parameters");
    }
    
    // Verify the payment with Razorpay
    $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, 'kFpmvStUlrAys3U9gCkgLAnw');
    
    if ($expectedSignature !== $razorpaySignature) {
        throw new Exception("Invalid signature");
    }
    
    // Update razorpay_orders table
    $stmt = $pdo->prepare("
        UPDATE razorpay_orders 
        SET payment_id = ?, status = 'paid', updated_at = NOW() 
        WHERE order_id = ?
    ");
    $stmt->execute([$razorpayPaymentId, $razorpayOrderId]);
    
    // Update existing job application record
    $stmt = $pdo->prepare("
        UPDATE job_applications 
        SET 
            application_status = 'Applied',
            payment_status = 'Paid',
            payment_id = ?,
            updated_at = NOW()
        WHERE job_id = ? AND email = (
            SELECT email FROM users WHERE id = ?
        )
    ");
    $stmt->execute([$razorpayPaymentId, $jobId, $userId]);
    
    // If no rows were updated, create a new application record
    if ($stmt->rowCount() == 0) {
        // Get user email
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Get job details
            $stmt = $pdo->prepare("SELECT * FROM new_jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            $job = $stmt->fetch();
            
            if ($job) {
                // Insert new application record
                $stmt = $pdo->prepare("
                    INSERT INTO job_applications (
                        user_id, job_id, job_type, company_name, student_name,
                        district, package, profile, email, phone, experience,
                        skills, application_status, payment_status, payment_id,
                        application_fee, applied_date, created_at, updated_at
                    ) VALUES (
                        ?, ?, 'local_job', ?, ?, 'Mumbai', ?, ?, ?, 
                        (SELECT phone FROM users WHERE id = ?), 
                        (SELECT experience FROM users WHERE id = ?),
                        (SELECT skills FROM users WHERE id = ?),
                        'Applied', 'Paid', ?, 1000.00, NOW(), NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    $userId, $jobId, $job['company_name'], $job['student_name'],
                    $job['package'], $job['job_post'], $user['email'], $userId, $userId, $userId, $razorpayPaymentId
                ]);
            }
        }
    }
    
    // Update payment tracking
    $stmt = $pdo->prepare("
        INSERT INTO payment_tracking (
            user_id, job_id, order_id, payment_id, amount, 
            status, created_at
        ) VALUES (?, ?, ?, ?, 
            (SELECT amount FROM razorpay_orders WHERE order_id = ?), 
            'completed', NOW()
        )
    ");
    $stmt->execute([$userId, $jobId, $razorpayOrderId, $razorpayPaymentId, $razorpayOrderId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
            'job_id' => $jobId,
            'application_status' => 'Applied',
            'payment_status' => 'Paid'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage()
    ]);
}
?> 