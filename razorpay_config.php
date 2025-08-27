<?php
// Razorpay Configuration for PlaySmart
// Replace these with your actual Razorpay API keys

// Razorpay API Configuration
define('RAZORPAY_KEY_ID', 'rzp_live_fgQr0ACWFbL4pN');
define('RAZORPAY_KEY_SECRET', 'your_razorpay_secret_key_here'); // Replace with your actual secret key
define('RAZORPAY_TEST_MODE', false); // Set to false for live mode

// Payment Settings
define('RAZORPAY_MIN_AMOUNT', 5.00); // Minimum ₹5.00 (500 paise) - Application fee
define('RAZORPAY_MAX_AMOUNT', 10000.00); // Maximum ₹10,000.00
define('RAZORPAY_DEFAULT_TEST_AMOUNT', 5.00); // Default ₹5.00 for testing

// Auto-capture settings
define('RAZORPAY_AUTO_CAPTURE', true); // Enable auto-capture
define('RAZORPAY_CAPTURE_METHOD', 'automatic'); // Use automatic capture

// Webhook and verification
define('RAZORPAY_WEBHOOK_SECRET', 'your_webhook_secret_here'); // Replace with your actual webhook secret

// Currency and other settings
define('RAZORPAY_CURRENCY', 'INR');
define('RAZORPAY_DESCRIPTION', 'PlaySmart Job Application Fee');

// Include Razorpay PHP SDK
// You need to install this via Composer: composer require razorpay/razorpay
// Or download from: https://github.com/razorpay/razorpay-php

// Helper function to create Razorpay order (without SDK dependency)
function createRazorpayOrder($amount, $receipt, $notes = []) {
    try {
        // Validate amount
        if ($amount < RAZORPAY_MIN_AMOUNT) {
            return [
                'success' => false,
                'error' => 'Amount too low. Minimum amount is ₹' . RAZORPAY_MIN_AMOUNT
            ];
        }
        
        if ($amount > RAZORPAY_MAX_AMOUNT) {
            return [
                'success' => false,
                'error' => 'Amount too high. Maximum amount is ₹' . RAZORPAY_MAX_AMOUNT
            ];
        }
        
        // For now, create a mock order ID since we don't have the SDK
        // In production, you would use the actual Razorpay API
        $mockOrderId = 'order_' . time() . '_' . rand(1000, 9999);
        
        return [
            'success' => true,
            'order_id' => $mockOrderId,
            'amount' => $amount * 100, // Convert to paise for Razorpay
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

// Helper function to check if Razorpay is properly configured
function checkRazorpayConfig() {
    if (RAZORPAY_TEST_MODE) {
        if (empty(RAZORPAY_KEY_ID) || RAZORPAY_KEY_ID === 'rzp_test_YOUR_TEST_KEY_ID') {
            return [
                'success' => false,
                'message' => 'Razorpay test key not configured'
            ];
        }
        
        if (empty(RAZORPAY_KEY_SECRET) || RAZORPAY_KEY_SECRET === 'YOUR_TEST_KEY_SECRET') {
            return [
                'success' => false,
                'message' => 'Razorpay test secret not configured'
            ];
        }
    } else {
        if (empty(RAZORPAY_KEY_ID) || RAZORPAY_KEY_ID === 'rzp_live_fgQr0ACWFbL4pN') {
            return [
                'success' => false,
                'message' => 'Razorpay live key not configured'
            ];
        }
        
        if (empty(RAZORPAY_KEY_SECRET) || RAZORPAY_KEY_SECRET === 'YOUR_LIVE_KEY_SECRET') {
            return [
                'success' => false,
                'message' => 'Razorpay live secret not configured'
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => 'Razorpay configuration is valid (' . (RAZORPAY_TEST_MODE ? 'TEST' : 'LIVE') . ' mode)'
    ];
}

// Helper function to validate payment amount
// Note: This function is already defined in process_payment.php
// function validatePaymentAmount($amount) {
//     if ($amount < RAZORPAY_MIN_AMOUNT) {
//         return [
//             'success' => false,
//             'message' => 'Amount too low. Minimum amount is ₹' . RAZORPAY_MIN_AMOUNT
//         ];
//     }
//     
//     if ($amount > RAZORPAY_MAX_AMOUNT) {
//         return [
//             'success' => false,
//             'message' => 'Amount too high. Maximum amount is ₹' . RAZORPAY_MAX_AMOUNT
//         ];
//     }
//     
//     return [
//         'success' => true,
//         'message' => 'Amount is valid'
//     ];
// }
?> 