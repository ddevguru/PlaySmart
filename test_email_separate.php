<?php
/**
 * Test Email Separately - PlaySmart
 * This file tests email functionality without including conflicting files
 */

echo "<h2>📧 Testing Email Functionality Separately - PlaySmart</h2>";
echo "<hr>";

try {
    // Test 1: Check if process_payment.php can be included
    echo "<h3>1. Testing process_payment.php Inclusion</h3>";
    
    if (file_exists('process_payment.php')) {
        echo "✅ process_payment.php file exists<br>";
        
        // Try to include it and check for conflicts
        try {
            require_once 'process_payment.php';
            echo "✅ process_payment.php included successfully<br>";
            echo "✅ No function conflicts detected<br>";
        } catch (Error $e) {
            echo "❌ Fatal Error including process_payment.php: " . $e->getMessage() . "<br>";
            echo "   This indicates a function conflict<br>";
        } catch (Exception $e) {
            echo "⚠️  Exception including process_payment.php: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ process_payment.php file not found<br>";
    }
    
    echo "<br>";
    
    // Test 2: Check email functions
    echo "<h3>2. Email Functions Check</h3>";
    
    $emailFunctions = [
        'sendPaymentSuccessEmail',
        'sendEmailViaSMTP2GO',
        'sendEmailFallback',
        'logEmailToFile'
    ];
    
    foreach ($emailFunctions as $function) {
        if (function_exists($function)) {
            echo "✅ Function '$function' is available<br>";
        } else {
            echo "❌ Function '$function' is NOT available<br>";
        }
    }
    
    echo "<br>";
    
    // Test 3: Test email sending if functions are available
    echo "<h3>3. Testing Email Sending</h3>";
    
    if (function_exists('sendPaymentSuccessEmail')) {
        echo "✅ sendPaymentSuccessEmail function available<br>";
        
        // Test with sample data
        $testApplicationId = 999; // Test ID
        $testAmount = 5.00;
        $testPaymentId = 'test_pay_' . time();
        
        echo "   Testing with Application ID: $testApplicationId<br>";
        echo "   Amount: ₹$testAmount<br>";
        echo "   Payment ID: $testPaymentId<br>";
        
        // Try to send test email
        try {
            $emailResult = sendPaymentSuccessEmail($testApplicationId, $testAmount, $testPaymentId);
            
            if ($emailResult) {
                echo "✅ Test email sent successfully!<br>";
                echo "📧 Email functionality is working<br>";
            } else {
                echo "❌ Test email failed<br>";
                echo "   This might be due to SMTP2GO configuration<br>";
            }
        } catch (Exception $e) {
            echo "❌ Exception during email sending: " . $e->getMessage() . "<br>";
        } catch (Error $e) {
            echo "❌ Fatal Error during email sending: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ sendPaymentSuccessEmail function not available<br>";
        echo "   Cannot test email functionality<br>";
    }
    
    echo "<br>";
    
    // Test 4: Check SMTP2GO configuration
    echo "<h3>4. SMTP2GO Configuration Check</h3>";
    
    if (file_exists('smtp2go_config.php')) {
        echo "✅ smtp2go_config.php exists<br>";
        
        // Check configuration without including
        $configContent = file_get_contents('smtp2go_config.php');
        
        if (strpos($configContent, 'SMTP2GO_USERNAME') !== false) {
            echo "✅ SMTP2GO_USERNAME is defined<br>";
        } else {
            echo "❌ SMTP2GO_USERNAME is NOT defined<br>";
        }
        
        if (strpos($configContent, 'SMTP2GO_PASSWORD') !== false) {
            echo "✅ SMTP2GO_PASSWORD is defined<br>";
        } else {
            echo "❌ SMTP2GO_PASSWORD is NOT defined<br>";
        }
        
        if (strpos($configContent, 'your-smtp2go-username') !== false) {
            echo "⚠️  SMTP2GO_USERNAME still has placeholder value<br>";
        }
        
        if (strpos($configContent, 'your-smtp2go-password') !== false) {
            echo "⚠️  SMTP2GO_PASSWORD still has placeholder value<br>";
        }
        
    } else {
        echo "❌ smtp2go_config.php not found<br>";
    }
    
    echo "<hr>";
    echo "<h3>5. Summary</h3>";
    
    if (function_exists('sendPaymentSuccessEmail')) {
        echo "✅ Email functionality is available<br>";
        echo "✅ No function conflicts detected<br>";
        echo "🎯 Ready to test email sending!<br>";
    } else {
        echo "❌ Email functionality is NOT available<br>";
        echo "❌ Function conflicts detected<br>";
        echo "🔧 Need to resolve function conflicts first<br>";
    }
    
    echo "<br><strong>Next Steps:</strong><br>";
    if (function_exists('sendPaymentSuccessEmail')) {
        echo "1. Test the complete payment flow<br>";
        echo "2. Verify emails are being sent<br>";
        echo "3. Check SMTP2GO configuration<br>";
    } else {
        echo "1. Resolve function conflicts<br>";
        echo "2. Check for duplicate function declarations<br>";
        echo "3. Ensure only one config file is included<br>";
    }
    
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