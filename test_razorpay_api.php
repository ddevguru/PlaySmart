<?php
// Simple test script to test Razorpay API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Razorpay API Directly</h2>";

try {
    // Razorpay API credentials
    $keyId = 'rzp_live_fgQr0ACWFbL4pN';
    $keySecret = 'kFpmvStUlrAys3U9gCkgLAnw';
    
    echo "<h3>1. Testing API Credentials</h3>";
    echo "Key ID: $keyId<br>";
    echo "Key Secret: " . substr($keySecret, 0, 10) . "...<br>";
    
    // Test cURL
    echo "<h3>2. Testing cURL</h3>";
    if (function_exists('curl_version')) {
        $curlInfo = curl_version();
        echo "✅ cURL is available (version: {$curlInfo['version']})<br>";
    } else {
        echo "❌ cURL is not available<br>";
        exit;
    }
    
    // Test Razorpay API connection
    echo "<h3>3. Testing Razorpay API</h3>";
    
    $orderData = [
        'amount' => 100, // 1 rupee in paise
        'currency' => 'INR',
        'receipt' => 'test_' . time()
    ];
    
    echo "Order data: " . json_encode($orderData) . "<br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($keyId . ':' . $keySecret)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    echo "Making request to Razorpay API...<br>";
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    echo "Response: $response<br>";
    
    if ($curlError) {
        echo "❌ cURL error: $curlError<br>";
    } else {
        if ($httpCode === 200) {
            echo "✅ Razorpay API is accessible<br>";
            $orderData = json_decode($response, true);
            if ($orderData && isset($orderData['id'])) {
                echo "✅ Order created successfully!<br>";
                echo "Order ID: " . $orderData['id'] . "<br>";
                echo "Amount: ₹" . ($orderData['amount'] / 100) . "<br>";
                echo "Status: " . $orderData['status'] . "<br>";
            } else {
                echo "❌ Invalid response format<br>";
            }
        } else {
            echo "❌ Razorpay API returned HTTP $httpCode<br>";
        }
    }
    
    echo "<h3>4. cURL Info</h3>";
    echo "<pre>" . print_r($curlInfo, true) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 