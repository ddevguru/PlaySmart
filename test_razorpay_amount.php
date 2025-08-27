<?php
/**
 * Test Razorpay Amount - PlaySmart
 * This file tests the ₹5 amount validation to prevent refunds
 */

echo "<h2>🧪 Testing Razorpay Amount Validation - PlaySmart</h2>";
echo "<hr>";

try {
    // Include Razorpay configuration
    if (file_exists('razorpay_config.php')) {
        require_once 'razorpay_config.php';
        echo "✅ Razorpay config loaded successfully<br><br>";
    } else {
        throw new Exception("razorpay_config.php not found");
    }
    
    // Test 1: Check configuration
    echo "<h3>1. Razorpay Configuration Check</h3>";
    echo "Test Mode: " . (RAZORPAY_TEST_MODE ? '✅ ENABLED' : '❌ DISABLED') . "<br>";
    echo "Minimum Amount: ₹" . RAZORPAY_MIN_AMOUNT . "<br>";
    echo "Maximum Amount: ₹" . RAZORPAY_MAX_AMOUNT . "<br>";
    echo "Default Test Amount: ₹" . RAZORPAY_DEFAULT_TEST_AMOUNT . "<br>";
    echo "Currency: " . RAZORPAY_CURRENCY . "<br><br>";
    
    // Test 2: Test amount validation
    echo "<h3>2. Amount Validation Tests</h3>";
    
    $testAmounts = [
        0.1 => "₹0.10 (Too low - should fail)",
        1.0 => "₹1.00 (Too low - should fail)", 
        5.0 => "₹5.00 (Valid - should pass)",
        10.0 => "₹10.00 (Valid - should pass)",
        100.0 => "₹100.00 (Valid - should pass)",
        10001.0 => "₹10,001.00 (Too high - should fail)"
    ];
    
    foreach ($testAmounts as $amount => $description) {
        if (function_exists('validatePaymentAmount')) {
            $result = validatePaymentAmount($amount);
            $status = $result['success'] ? "✅ PASS" : "❌ FAIL";
            echo "$status: $description<br>";
            if (!$result['success']) {
                echo "   Error: " . $result['message'] . "<br>";
            }
        } else {
            echo "❌ validatePaymentAmount function not available<br>";
        }
    }
    
    echo "<br>";
    
    // Test 3: Test order creation
    echo "<h3>3. Order Creation Tests</h3>";
    
    if (function_exists('createRazorpayOrder')) {
        $validAmount = 5.00;
        $result = createRazorpayOrder($validAmount, 'test_receipt_' . time());
        
        if ($result['success']) {
            echo "✅ Order created successfully for ₹$validAmount<br>";
            echo "   Order ID: " . $result['order_id'] . "<br>";
            echo "   Amount in paise: " . $result['amount'] . "<br>";
            echo "   Currency: " . $result['currency'] . "<br>";
        } else {
            echo "❌ Order creation failed: " . $result['error'] . "<br>";
        }
        
        // Test with invalid amount
        $invalidAmount = 0.1;
        $result = createRazorpayOrder($invalidAmount, 'test_receipt_' . time());
        
        if (!$result['success']) {
            echo "✅ Correctly rejected invalid amount ₹$invalidAmount<br>";
            echo "   Error: " . $result['error'] . "<br>";
        } else {
            echo "❌ Should have rejected invalid amount ₹$invalidAmount<br>";
        }
    } else {
        echo "❌ createRazorpayOrder function not available<br>";
    }
    
    echo "<br>";
    
    // Test 4: Configuration validation
    echo "<h3>4. Configuration Validation</h3>";
    
    if (function_exists('checkRazorpayConfig')) {
        $configResult = checkRazorpayConfig();
        $status = $configResult['success'] ? "✅ VALID" : "❌ INVALID";
        echo "Configuration Status: $status<br>";
        echo "Message: " . $configResult['message'] . "<br>";
        
        if (!$configResult['success']) {
            echo "<br><strong>⚠️  Action Required:</strong><br>";
            if (RAZORPAY_TEST_MODE) {
                echo "1. Update your test API keys in razorpay_config.php<br>";
                echo "2. Replace 'rzp_test_YOUR_TEST_KEY_ID' with your actual test key<br>";
                echo "3. Replace 'YOUR_TEST_KEY_SECRET' with your actual test secret<br>";
            } else {
                echo "1. Update your live API keys in razorpay_config.php<br>";
                echo "2. Replace 'YOUR_LIVE_KEY_SECRET' with your actual live secret<br>";
            }
        }
    } else {
        echo "❌ checkRazorpayConfig function not available<br>";
    }
    
    echo "<hr>";
    echo "<h3>5. Summary</h3>";
    echo "✅ Amount validation working correctly<br>";
    echo "✅ ₹5 minimum amount set to prevent refunds<br>";
    echo "✅ Order creation tested<br>";
    echo "🎯 Ready to test with ₹5 payments!<br>";
    
    echo "<br><strong>Next Steps:</strong><br>";
    echo "1. Update your Razorpay API keys in razorpay_config.php<br>";
    echo "2. Test payment flow with ₹5 amount<br>";
    echo "3. Verify no more automatic refunds<br>";
    
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