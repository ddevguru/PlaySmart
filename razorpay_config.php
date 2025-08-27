<?php
// Razorpay Configuration for PlaySmart
// Replace these with your actual Razorpay API keys

// Test Mode (set to false for production)
define('RAZORPAY_TEST_MODE', false);

// Razorpay API Keys
if (RAZORPAY_TEST_MODE) {
    // Test Mode Keys
    define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_TEST_KEY_ID');
    define('RAZORPAY_KEY_SECRET', 'YOUR_TEST_KEY_SECRET');
} else {
    // Production Mode Keys
    define('RAZORPAY_KEY_ID', 'rzp_live_fgQr0ACWFbL4pN'); // Your live key
    define('RAZORPAY_KEY_SECRET', 'YOUR_LIVE_KEY_SECRET'); // You need to add your actual secret
}

// Currency and other settings
define('RAZORPAY_CURRENCY', 'INR');
define('RAZORPAY_DESCRIPTION', 'PlaySmart Job Application Fee');

// Include Razorpay PHP SDK
// You need to install this via Composer: composer require razorpay/razorpay
// Or download from: https://github.com/razorpay/razorpay-php

// Helper function to create Razorpay order (without SDK dependency)
function createRazorpayOrder($amount, $receipt, $notes = []) {
    try {
        // For now, create a mock order ID since we don't have the SDK
        // In production, you would use the actual Razorpay API
        
        $mockOrderId = 'order_' . time() . '_' . rand(1000, 9999);
        
        return [
            'success' => true,
            'order_id' => $mockOrderId,
            'amount' => $amount,
            'currency' => RAZORPAY_CURRENCY
        ];
        
    } catch (Exception $e) {
        error_log("Razorpay order creation failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Helper function to verify payment signature (without SDK dependency)
function verifyPaymentSignature($razorpayPaymentId, $razorpayOrderId, $razorpaySignature) {
    try {
        // For now, return true as a mock verification
        // In production, you would use the actual Razorpay signature verification
        
        return true;
    } catch (Exception $e) {
        error_log("Payment signature verification failed: " . $e->getMessage());
        return false;
    }
}

// Helper function to get payment details (without SDK dependency)
function getPaymentDetails($razorpayPaymentId) {
    try {
        // For now, return mock payment details
        // In production, you would use the actual Razorpay API
        
        return [
            'success' => true,
            'payment' => [
                'id' => $razorpayPaymentId,
                'status' => 'captured',
                'amount' => 200000,
                'currency' => 'INR'
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Payment details retrieval failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Helper function to process refund (without SDK dependency)
function processRefund($razorpayPaymentId, $amount = null, $reason = '') {
    try {
        // For now, return mock refund details
        // In production, you would use the actual Razorpay API
        
        $mockRefundId = 'refund_' . time() . '_' . rand(1000, 9999);
        
        return [
            'success' => true,
            'refund_id' => $mockRefundId,
            'amount' => $amount ?: 2000,
            'status' => 'processed'
        ];
        
    } catch (Exception $e) {
        error_log("Refund processing failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Helper function to format amount for display
function formatAmount($amount) {
    return '₹' . number_format($amount, 2);
}

// Helper function to get payment status text
function getPaymentStatusText($status) {
    $statusMap = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded'
    ];
    
    return $statusMap[$status] ?? 'Unknown';
}

// Helper function to log payment activities
function logPaymentActivity($message, $data = []) {
    $logFile = 'payment_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($data)) {
        $logMessage .= " - " . json_encode($data);
    }
    
    $logMessage .= "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Helper function to check if Razorpay is properly configured
function checkRazorpayConfig() {
    if (empty(RAZORPAY_KEY_ID) || RAZORPAY_KEY_ID === 'rzp_test_YOUR_TEST_KEY_ID') {
        return [
            'success' => false,
            'message' => 'Razorpay key not configured'
        ];
    }
    
    if (empty(RAZORPAY_KEY_SECRET) || RAZORPAY_KEY_SECRET === 'YOUR_LIVE_KEY_SECRET') {
        return [
            'success' => false,
            'message' => 'Razorpay secret not configured'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Razorpay configuration is valid'
    ];
}
?> 