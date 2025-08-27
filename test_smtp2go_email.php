<?php
/**
 * Test SMTP2GO Email - PlaySmart
 * This file tests the SMTP2GO email functionality
 */

// Include the process_payment.php file to access the email functions
require_once 'process_payment.php';

echo "=== TESTING SMTP2GO EMAIL FUNCTIONALITY ===\n";

// Test data
$testTo = 'test@example.com'; // Change this to your email for testing
$testSubject = 'Test SMTP2GO Email - PlaySmart';
$testMessage = '
<html>
<head>
    <title>Test Email</title>
</head>
<body>
    <h1>Test Email via SMTP2GO</h1>
    <p>This is a test email to verify SMTP2GO functionality.</p>
    <p>If you receive this email, SMTP2GO is working correctly!</p>
    <p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>
</body>
</html>
';

echo "\n1. Testing SMTP2GO email function...\n";
echo "📧 To: $testTo\n";
echo "📧 Subject: $testSubject\n";

// Test the SMTP2GO function
$result = sendEmailViaSMTP2GO($testTo, $testSubject, $testMessage);

if ($result) {
    echo "✅ SUCCESS: SMTP2GO email sent successfully!\n";
    echo "📧 Check your email inbox (and spam folder)\n";
} else {
    echo "❌ FAILED: SMTP2GO email failed\n";
    echo "🔄 Trying fallback method...\n";
    
    // Test fallback method
    $fallbackResult = sendEmailFallback($testTo, $testSubject, $testMessage);
    if ($fallbackResult) {
        echo "✅ SUCCESS: Fallback email method worked!\n";
    } else {
        echo "❌ FAILED: Fallback email method also failed\n";
    }
}

echo "\n2. Testing configuration...\n";

// Check if configuration file exists
if (file_exists('smtp2go_config.php')) {
    echo "✅ Configuration file exists\n";
    
    // Check configuration values
    include_once 'smtp2go_config.php';
    
    if (defined('SMTP2GO_USERNAME') && defined('SMTP2GO_PASSWORD')) {
        $username = SMTP2GO_USERNAME;
        $password = SMTP2GO_PASSWORD;
        
        if ($username === 'your-smtp2go-username' || $password === 'your-smtp2go-password') {
            echo "❌ ERROR: SMTP2GO credentials not configured\n";
            echo "   Please update smtp2go_config.php with your actual credentials\n";
        } else {
            echo "✅ SMTP2GO credentials configured\n";
            echo "   Username: " . substr($username, 0, 3) . "***\n";
            echo "   Password: " . substr($password, 0, 3) . "***\n";
        }
    } else {
        echo "❌ ERROR: SMTP2GO constants not defined\n";
    }
} else {
    echo "❌ ERROR: Configuration file not found\n";
    echo "   Please create smtp2go_config.php with your SMTP2GO credentials\n";
}

echo "\n3. Testing file logging...\n";

// Test file logging
$logResult = logEmailToFile($testTo, $testSubject, $testMessage);
if ($logResult) {
    echo "✅ SUCCESS: Email logged to file\n";
    echo "📁 Check email_logs/ folder for the log file\n";
} else {
    echo "❌ FAILED: File logging failed\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "\n📋 Summary:\n";
echo "✅ SMTP2GO function: Tested\n";
echo "✅ Configuration: Checked\n";
echo "✅ Fallback methods: Tested\n";
echo "✅ File logging: Tested\n";
echo "\n🎯 Next steps:\n";
echo "1. Update smtp2go_config.php with your actual SMTP2GO credentials\n";
echo "2. Run this test again to verify email sending\n";
echo "3. Test the complete payment flow in the Flutter app\n";
echo "4. Check if status button updates correctly\n";

?> 