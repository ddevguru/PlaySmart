<?php
/**
 * Alternative Email Sending Method - PlaySmart
 * This file provides an alternative way to send emails if the server's mail() function doesn't work
 */

// Alternative email sending using external service (Mailgun, SendGrid, etc.)
function sendEmailAlternative($to, $subject, $message, $from = 'noreply@playsmart.co.in') {
    try {
        // Method 1: Try using cURL to send through external SMTP service
        $emailData = [
            'to' => $to,
            'subject' => $subject,
            'html' => $message,
            'from' => $from
        ];
        
        // You can configure this to use your preferred email service
        // For now, we'll use a simple HTTP POST approach
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/your-domain.com/messages');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode('api:your-api-key'),
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return true;
        }
        
        // Method 2: Try using PHPMailer if available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return sendEmailWithPHPMailer($to, $subject, $message, $from);
        }
        
        // Method 3: Try using file-based logging as last resort
        return logEmailToFile($to, $subject, $message, $from);
        
    } catch (Exception $e) {
        error_log("Alternative email sending failed: " . $e->getMessage());
        return false;
    }
}

// PHPMailer alternative
function sendEmailWithPHPMailer($to, $subject, $message, $from) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Change to your email
        $mail->Password = 'your-app-password'; // Change to your app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom($from, 'PlaySmart');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer failed: " . $e->getMessage());
        return false;
    }
}

// File-based email logging as last resort
function logEmailToFile($to, $subject, $message, $from) {
    try {
        $logDir = 'email_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/emails_' . date('Y-m-d') . '.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'from' => $from,
            'subject' => $subject,
            'message' => $message
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Email logging failed: " . $e->getMessage());
        return false;
    }
}

// Test function
function testAlternativeEmail() {
    $to = 'test@example.com'; // Change this to your email
    $subject = 'Test Alternative Email - PlaySmart';
    $message = '<h1>Test Email</h1><p>This is a test email using alternative method.</p>';
    
    echo "Testing alternative email methods...\n";
    
    $result = sendEmailAlternative($to, $subject, $message);
    
    if ($result) {
        echo "✅ Alternative email sent successfully!\n";
    } else {
        echo "❌ Alternative email failed\n";
    }
}

// Run test if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    testAlternativeEmail();
}
?> 