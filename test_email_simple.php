<?php
/**
 * Simple Email Test - PlaySmart
 * This file tests basic email functionality
 */

echo "=== SIMPLE EMAIL TEST ===\n";

// Test 1: Check if mail function exists
if (function_exists('mail')) {
    echo "✅ Mail function is available\n";
} else {
    echo "❌ Mail function is NOT available\n";
    exit;
}

// Test 2: Check mail configuration
$mailConfig = ini_get('sendmail_path');
echo "Sendmail path: " . ($mailConfig ?: 'Not set') . "\n";

$smtpHost = ini_get('SMTP');
echo "SMTP host: " . ($smtpHost ?: 'Not set') . "\n";

$smtpPort = ini_get('smtp_port');
echo "SMTP port: " . ($smtpPort ?: 'Not set') . "\n";

// Test 3: Try to send a simple email
$to = 'test@example.com'; // Change this to your email for testing
$subject = 'Test Email from PlaySmart Server';
$message = 'This is a test email to verify mail functionality.';
$headers = 'From: noreply@playsmart.co.in' . "\r\n" .
           'Reply-To: support@playsmart.co.in' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

echo "\nAttempting to send test email to: $to\n";

$mailResult = mail($to, $subject, $message, $headers);

if ($mailResult) {
    echo "✅ Test email sent successfully!\n";
    echo "📧 Check your email inbox (and spam folder)\n";
} else {
    echo "❌ Test email failed to send\n";
    echo "This could indicate a server configuration issue\n";
}

// Test 4: Check error logs
echo "\n=== ERROR LOG CHECK ===\n";
$errorLog = ini_get('error_log');
echo "Error log path: " . ($errorLog ?: 'Default system log') . "\n";

// Test 5: Check if we can write to logs
$testLogFile = 'test_email_log.txt';
$logContent = "Test log entry at " . date('Y-m-d H:i:s') . "\n";
$writeResult = file_put_contents($testLogFile, $logContent);

if ($writeResult !== false) {
    echo "✅ Can write to log file: $testLogFile\n";
    // Clean up test file
    unlink($testLogFile);
} else {
    echo "❌ Cannot write to log file\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "\nIf email is not working, possible issues:\n";
echo "1. Server mail configuration\n";
echo "2. Firewall blocking outgoing mail\n";
echo "3. Mail server not running\n";
echo "4. DNS issues\n";
echo "5. Server provider blocking mail\n";
echo "\nContact your hosting provider to enable mail functionality.\n";
?> 