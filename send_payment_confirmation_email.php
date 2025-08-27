<?php
// Send Payment Confirmation Email API
// This API sends confirmation emails to users after successful payment

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'email_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== PAYMENT CONFIRMATION EMAIL STARTED ===");

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    writeLog("Raw input received: " . file_get_contents('php://input'));
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    writeLog("Input data: " . print_r($input, true));
    
    // Validate required fields
    $required_fields = ['job_id', 'job_title', 'company_name', 'package', 'payment_id'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Extract data
    $job_id = $input['job_id'];
    $job_title = $input['job_title'];
    $company_name = $input['company_name'];
    $package = $input['package'];
    $payment_id = $input['payment_id'];
    $user_email = $input['email'] ?? 'user@playsmart.co.in'; // Default email if not provided
    
    writeLog("Data extracted successfully");
    
    // Email content
    $subject = "Payment Confirmation - Job Application for $job_title";
    
    $message = "
    <html>
    <head>
        <title>Payment Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
            .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .details { background: white; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #667eea; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Payment Confirmation</h1>
                <p>Your job application has been submitted successfully!</p>
            </div>
            
            <div class='content'>
                <div class='success-box'>
                    <h3>✅ Payment Successful</h3>
                    <p>Your payment of <strong>₹0.1</strong> has been processed successfully.</p>
                </div>
                
                <h2>Application Details</h2>
                <div class='details'>
                    <p><strong>Job Title:</strong> $job_title</p>
                    <p><strong>Company:</strong> $company_name</p>
                    <p><strong>Package:</strong> $package</p>
                    <p><strong>Payment ID:</strong> $payment_id</p>
                    <p><strong>Application Date:</strong> " . date('F j, Y') . "</p>
                </div>
                
                <h3>What's Next?</h3>
                <ul>
                    <li>Your application has been submitted and is under review</li>
                    <li>You will receive updates on your application status</li>
                    <li>Check your application status in the app</li>
                    <li>Keep your contact information updated</li>
                </ul>
                
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='https://playsmart.co.in' class='button'>View Application Status</a>
                </div>
                
                <div class='footer'>
                    <p>Thank you for using PlaySmart!</p>
                    <p>If you have any questions, please contact our support team.</p>
                    <p>© " . date('Y') . " PlaySmart. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: PlaySmart <noreply@playsmart.co.in>',
        'Reply-To: support@playsmart.co.in',
        'X-Mailer: PHP/' . phpversion()
    );
    
    writeLog("Email content prepared");
    
    // Send email
    $mail_sent = mail($user_email, $subject, $message, implode("\r\n", $headers));
    
    if ($mail_sent) {
        writeLog("Email sent successfully to: $user_email");
        
        // Log the successful email
        error_log("Payment confirmation email sent successfully - Job: $job_title, Company: $company_name, Payment ID: $payment_id, Email: $user_email");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment confirmation email sent successfully',
            'data' => [
                'email_sent_to' => $user_email,
                'payment_id' => $payment_id,
                'job_title' => $job_title,
                'company_name' => $company_name,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        
        writeLog("=== PAYMENT CONFIRMATION EMAIL COMPLETED SUCCESSFULLY ===");
        
    } else {
        throw new Exception('Failed to send email');
    }
    
} catch (Exception $e) {
    writeLog("=== PAYMENT CONFIRMATION EMAIL ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    error_log("Payment confirmation email error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error sending payment confirmation email: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    writeLog("=== PAYMENT CONFIRMATION EMAIL FAILED ===");
}
?> 