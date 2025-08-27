<?php
// Test Email Function
// This file tests if the email function is working

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'email_test_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== EMAIL TEST STARTED ===");

try {
    // Test email parameters
    $to = 'deepakm7778@gmail.com'; // Your actual email for testing
    $subject = "🧪 Test Email - PlaySmart Payment System";
    
    $message = "
    <html>
    <head>
        <title>Test Email</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🧪 Test Email</h1>
                <p>This is a test email to verify the email system is working</p>
            </div>
            
            <div class='content'>
                <h2>Test Details</h2>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
                <p><strong>PHP Version:</strong> " . phpversion() . "</p>
                
                <h3>If you receive this email:</h3>
                <ul>
                    <li>✅ Email system is working</li>
                    <li>✅ SMTP configuration is correct</li>
                    <li>✅ Payment confirmation emails will be sent</li>
                </ul>
                
                <div style='text-align: center; margin: 20px 0;'>
                    <p style='color: green; font-weight: bold;'>🎉 Email System Test Successful!</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: PlaySmart Test <noreply@playsmart.co.in>',
        'Reply-To: support@playsmart.co.in',
        'X-Mailer: PHP/' . phpversion()
    );
    
    writeLog("Attempting to send test email to: $to");
    writeLog("Subject: $subject");
    
    $mailResult = mail($to, $subject, $message, implode("\r\n", $headers));
    
    if ($mailResult) {
        writeLog("✅ SUCCESS: Test email sent successfully!");
        $response = [
            'success' => true,
            'message' => 'Test email sent successfully!',
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to
        ];
    } else {
        writeLog("❌ FAILED: Test email could not be sent");
        $response = [
            'success' => false,
            'message' => 'Test email failed to send',
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'error' => 'Mail function returned false'
        ];
    }
    
    writeLog("Mail function result: " . ($mailResult ? "SUCCESS" : "FAILED"));
    writeLog("=== EMAIL TEST ENDED ===");
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    writeLog("❌ ERROR: Exception occurred: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    
    $errorResponse = [
        'success' => false,
        'message' => 'Error occurred during email test',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}
?> 