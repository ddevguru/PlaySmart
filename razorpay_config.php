<?php
/**
 * Razorpay Configuration - PlaySmart
 */

// Razorpay API credentials
$keyId = 'rzp_live_fgQr0ACWFbL4pN';
$keySecret = 'kFpmvStUlrAys3U9gCkgLAnw';

// Initialize Razorpay API (if SDK is available)
$api = null;

try {
    // Check if Razorpay SDK is available
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        
        if (class_exists('Razorpay\Api\Api')) {
            $api = new Razorpay\Api\Api($keyId, $keySecret);
        }
    }
} catch (Exception $e) {
    // SDK not available, will use direct API calls
    $api = null;
}

// Export variables for use in other files
return [
    'key_id' => $keyId,
    'key_secret' => $keySecret,
    'api' => $api
];
?> 