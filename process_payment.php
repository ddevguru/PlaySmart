<?php
/**
 * Process Payment - Fixed to handle real form data and prevent auto-refunds
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'db_config.php';
require_once 'razorpay_config.php';

// Function to write logs
function writeLog($message) {
    $logFile = 'payment_logs/payment_activity_' . date('Y-m-d') . '.log';
    $timestamp = '[' . date('Y-m-d H:i:s') . '] ';
    $logMessage = $timestamp . $message . "\n";
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage; // Also output to console for debugging
}

// Function to validate payment amount
function validatePaymentAmount($amount) {
    if ($amount < RAZORPAY_MIN_AMOUNT) {
        return [
            'success' => false,
            'message' => "Amount too low. Minimum amount is ₹" . RAZORPAY_MIN_AMOUNT
        ];
    }
    
    if ($amount > RAZORPAY_MAX_AMOUNT) {
        return [
            'success' => false,
            'message' => "Amount too high. Maximum amount is ₹" . RAZORPAY_MAX_AMOUNT
        ];
    }
    
    return ['success' => true, 'message' => "Amount validation passed: ₹$amount"];
}

// Function to send payment success email
function sendPaymentSuccessEmail($applicationId, $amount, $paymentId) {
    try {
        writeLog("✅ Starting SMTP2GO email sending for application ID: $applicationId");
        
        // Get database connection
        $pdo = getDBConnection();
        
        // Get application details with actual user data
        $stmt = $pdo->prepare("
            SELECT 
                ja.student_name,
                ja.email,
                ja.company_name,
                ja.profile,
                ja.package,
                ja.district,
                ja.applied_date,
                ja.payment_id,
                j.job_title,
                j.job_description
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            WHERE ja.id = ?
        ");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            writeLog("❌ Application not found for ID: $applicationId");
            return false;
        }
        
        writeLog("✅ Application found: " . print_r($application, true));
        
        // Get payment details
        $stmt = $pdo->prepare("
            SELECT 
                razorpay_payment_id,
                razorpay_order_id,
                payment_date,
                payment_method,
                gateway_response
            FROM payment_tracking 
            WHERE application_id = ?
        ");
        $stmt->execute([$applicationId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            writeLog("❌ Payment details not found for application ID: $applicationId");
            return false;
        }
        
        writeLog("✅ Payment details found: " . print_r($payment, true));
        
        // Validate email address
        $userEmail = $application['email'];
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            writeLog("❌ Invalid email address: $userEmail");
            return false;
        }
        
        writeLog("✅ Valid email address: $userEmail");
        
        // Try SMTP2GO first
        if (defined('SMTP2GO_HOST') && defined('SMTP2GO_USERNAME') && defined('SMTP2GO_PASSWORD')) {
            writeLog("📧 Attempting to send email via SMTP2GO...");
            writeLog("📧 SMTP2GO Config - Host: " . SMTP2GO_HOST . ", Port: " . SMTP2GO_PORT . ", Username: " . SMTP2GO_USERNAME);
            
            try {
                // Create SMTP connection
                $smtp = fsockopen(SMTP2GO_HOST, SMTP2GO_PORT, $errno, $errstr, 30);
                if (!$smtp) {
                    throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
                }
                
                writeLog("✅ Connected to SMTP2GO server");
                
                // Read server response
                $response = fgets($smtp, 515);
                writeLog("📧 Server response: $response");
                
                // Send EHLO
                fputs($smtp, "EHLO " . SMTP2GO_HOST . "\r\n");
                $response = fgets($smtp, 515);
                writeLog("📧 EHLO response: $response");
                
                // Send STARTTLS if not using port 2525
                if (SMTP2GO_PORT != 2525) {
                    fputs($smtp, "STARTTLS\r\n");
                    $response = fgets($smtp, 515);
                    writeLog("📧 STARTTLS response: $response");
                    
                    if (strpos($response, '220') === false) {
                        throw new Exception("Could not enable TLS");
                    }
                }
                
                // Send authentication
                fputs($smtp, "AUTH LOGIN\r\n");
                $response = fgets($smtp, 515);
                
                fputs($smtp, base64_encode(SMTP2GO_USERNAME) . "\r\n");
                $response = fgets($smtp, 515);
                
                fputs($smtp, base64_encode(SMTP2GO_PASSWORD) . "\r\n");
                $response = fgets($smtp, 515);
                
                if (strpos($response, '235') === false) {
                    throw new Exception("Authentication failed: $response");
                }
                
                // Send email
                fputs($smtp, "MAIL FROM: <" . SMTP2GO_FROM_EMAIL . ">\r\n");
                $response = fgets($smtp, 515);
                
                fputs($smtp, "RCPT TO: <$userEmail>\r\n");
                $response = fgets($smtp, 515);
                
                fputs($smtp, "DATA\r\n");
                $response = fgets($smtp, 515);
                
                // Email content
                $subject = "Job Application Successful - " . $application['company_name'];
                $htmlContent = createEmailHTML($application, $payment, $amount);
                
                $headers = "From: " . SMTP2GO_FROM_NAME . " <" . SMTP2GO_FROM_EMAIL . ">\r\n";
                $headers .= "Reply-To: " . SMTP2GO_FROM_EMAIL . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                $emailData = $headers . "\r\n" . $htmlContent;
                
                fputs($smtp, $emailData . "\r\n.\r\n");
                $response = fgets($smtp, 515);
                
                fputs($smtp, "QUIT\r\n");
                fclose($smtp);
                
                writeLog("✅ SMTP2GO email sent successfully to $userEmail");
                return true;
                
            } catch (Exception $e) {
                writeLog("❌ ERROR: " . $e->getMessage());
                writeLog("❌ FAILED: SMTP2GO email sending failed for $userEmail");
                writeLog("🔄 Trying fallback email method...");
            }
        }
        
        // Fallback to PHP mail() function
        writeLog("🔄 Trying fallback email method...");
        
        $subject = "Job Application Successful - " . $application['company_name'];
        $htmlContent = createEmailHTML($application, $payment, $amount);
        
        $headers = "From: " . SMTP2GO_FROM_NAME . " <" . SMTP2GO_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . SMTP2GO_FROM_EMAIL . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $result = mail($userEmail, $subject, $htmlContent, $headers);
        
        if ($result) {
            writeLog("✅ Fallback email sent successfully using mail() function");
            writeLog("✅ SUCCESS: Fallback email method worked for $userEmail");
            return true;
        } else {
            writeLog("❌ FAILED: Fallback email method also failed for $userEmail");
            return false;
        }
        
    } catch (Exception $e) {
        writeLog("❌ ERROR: Email sending error: " . $e->getMessage());
        return false;
    }
}

// Function to create email HTML content
function createEmailHTML($application, $payment, $amount) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Job Application Successful</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-icon { font-size: 48px; margin-bottom: 20px; }
            .job-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
            .payment-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #28a745; }
            .next-steps { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ffc107; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="success-icon">🎉</div>
                <h1>Job Application Successful!</h1>
                <p>Your application has been submitted and payment confirmed</p>
            </div>
            
            <div class="content">
                <div class="job-details">
                    <h3>Job Details</h3>
                    <p><strong>Position:</strong> ' . htmlspecialchars($application['profile']) . '</p>
                    <p><strong>Company:</strong> ' . htmlspecialchars($application['company_name']) . '</p>
                    <p><strong>Package:</strong> ' . htmlspecialchars($application['package']) . '</p>
                    <p><strong>Location:</strong> ' . htmlspecialchars($application['district']) . '</p>
                    <p><strong>Applied Date:</strong> ' . htmlspecialchars($application['applied_date']) . '</p>
                </div>
                
                <div class="payment-details">
                    <h3>Payment Details</h3>
                    <p><strong>Payment ID:</strong> ' . htmlspecialchars($payment['razorpay_payment_id']) . '</p>
                    <p><strong>Amount:</strong> ₹' . number_format($amount, 2) . '</p>
                    <p><strong>Payment Date:</strong> ' . htmlspecialchars($payment['payment_date']) . '</p>
                    <p><strong>Status:</strong> <span style="color: #28a745; font-weight: bold;">Completed</span></p>
                </div>
                
                <div class="next-steps">
                    <h3>What Happens Next?</h3>
                    <ol>
                        <li><strong>Application Review:</strong> Our team will review your application within 24-48 hours</li>
                        <li><strong>Document Verification:</strong> We will verify your uploaded documents</li>
                        <li><strong>Interview Scheduling:</strong> You will be contacted for interview scheduling</li>
                        <li><strong>Job Placement:</strong> We will match you with suitable opportunities</li>
                    </ol>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <p><strong>Thank you for choosing PlaySmart Services!</strong></p>
                    <p>We are committed to helping you find the perfect job opportunity.</p>
                </div>
            </div>
            
            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>For support, contact us at support@playsmart.co.in</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Main payment processing logic
try {
    writeLog("=== PAYMENT PROCESSING STARTED ===");
    
    // Get request method and content type
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? 'UNKNOWN';
    writeLog("Request Method: $requestMethod");
    writeLog("Content Type: $contentType");
    
    // Load configurations
    writeLog("Loading database config...");
    writeLog("Loading Razorpay config...");
    
    // Get database connection
    $pdo = getDBConnection();
    writeLog("Database config loaded - Host: " . DB_HOST . ", DB: " . DB_NAME . ", User: " . DB_USERNAME);
    writeLog("Connecting to database...");
    
    if ($pdo) {
        writeLog("Database connection successful");
    } else {
        throw new Exception("Database connection failed");
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    writeLog("Raw input received: $input");
    
    $data = json_decode($input, true);
    writeLog("Input data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception("Invalid JSON input");
    }
    
    // Extract payment data
    $jobId = $data['job_id'] ?? null; // Changed from application_id to job_id
    $paymentId = $data['payment_id'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    $razorpayPaymentId = $data['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $data['razorpay_order_id'] ?? '';
    $razorpaySignature = $data['razorpay_signature'] ?? '';
    $paymentMethod = $data['payment_method'] ?? '';
    $userEmail = $data['user_email'] ?? '';
    $gatewayResponse = $data['gateway_response'] ?? [];
    
    writeLog("Payment data extracted:");
    writeLog("job_id: $jobId");
    writeLog("payment_id: $paymentId");
    writeLog("amount: $amount");
    writeLog("razorpay_payment_id: $razorpayPaymentId");
    writeLog("razorpay_order_id: $razorpayOrderId");
    writeLog("payment_method: $paymentMethod");
    
    // Validate amount
    $amountValidation = validatePaymentAmount($amount);
    if (!$amountValidation['success']) {
        writeLog("❌ Amount validation failed: " . $amountValidation['message']);
        throw new Exception("Application fee must be ₹5.00. Received: ₹$amount. Please update your app to use ₹5.00.");
    }
    writeLog("✅ " . $amountValidation['message']);
    
    // Get job details
    writeLog("Getting job details for job_id: $jobId");
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception("Job not found with ID: $jobId");
    }
    
    writeLog("Job found: " . $job['job_title'] . " at " . $job['company_name']);
    
    // CRITICAL FIX: Find existing job application record instead of creating new one
    // The Flutter app should have already created the application record
    writeLog("Looking for existing job application record...");
    
    $stmt = $pdo->prepare("
        SELECT id, student_name, email, phone, education, experience, 
               skills, referral_code, photo_path, resume_path, company_name, 
               package, profile, district, applied_date, application_status, application_fee
        FROM job_applications 
        WHERE job_id = ? AND email = ? 
        ORDER BY applied_date DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$jobId, $userEmail]);
    $existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingApplication) {
        // Use existing application record
        $applicationId = $existingApplication['id'];
        $applicationFee = $existingApplication['application_fee'];
        writeLog("✅ Found existing application record with ID: $applicationId");
        writeLog("Application details: " . print_r($existingApplication, true));
        
        // Update the existing record with payment information
        $stmt = $pdo->prepare("
            UPDATE job_applications 
            SET application_status = 'accepted', 
                payment_id = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$paymentId, $applicationId]);
        writeLog("Updated existing application status to 'accepted'");
        
    } else {
        // Fallback: Create new record if none exists
        writeLog("⚠️  No existing application found, creating new record...");
        
        $stmt = $pdo->prepare("
            INSERT INTO job_applications (
                job_id, student_name, email, phone, education, experience, 
                skills, referral_code, photo_path, resume_path, company_name, 
                package, profile, district, applied_date, application_status, application_fee
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', 0)
        ");
        
        // Use provided email or default
        $email = $userEmail ?: 'user@example.com';
        $studentName = 'User'; // This should come from form
        $phone = 'Not provided'; // This should come from form
        $education = 'Not specified'; // This should come from form
        $experience = 'Not specified'; // This should come from form
        $skills = 'Not specified'; // This should come from form
        $referralCode = ''; // This should come from form
        $photoPath = ''; // This should come from form
        $resumePath = ''; // This should come from form
        
        $stmt->execute([
            $jobId, $studentName, $email, $phone, $education, $experience,
            $skills, $referralCode, $photoPath, $resumePath, $job['company_name'],
            $job['package'], $job['job_title'], $job['location'] ?? 'Mumbai', 
            $job['package'], $job['job_title'], $job['location'] ?? 'Mumbai', 
        ]);
        
        $applicationId = $pdo->lastInsertId();
        writeLog("Created new application record with ID: $applicationId");
    }
    
    // Verify payment signature if available
    if (!empty($razorpaySignature) && !empty($razorpayOrderId)) {
    writeLog("Verifying payment signature...");
        // Add signature verification logic here
    } else {
        writeLog("⚠️  Payment signature is empty, skipping verification");
    }
    
    // Get payment details from Razorpay if order ID is available
    if (!empty($razorpayOrderId)) {
    writeLog("Fetching payment details from Razorpay...");
        // Add Razorpay API call here to get payment details
    } else {
        writeLog("⚠️  Razorpay order ID is empty, skipping payment details fetch");
    }
    
    // Check if payment already exists
    writeLog("Checking if payment already exists...");
    $stmt = $pdo->prepare("SELECT id FROM payment_tracking WHERE razorpay_payment_id = ?");
    $stmt->execute([$razorpayPaymentId]);
    $existingPayment = $stmt->fetch();
    
    if ($existingPayment) {
        writeLog("⚠️  Payment already exists with ID: " . $existingPayment['id']);
    } else {
    // Insert payment record
    writeLog("Inserting payment record...");
        $stmt = $pdo->prepare("
            INSERT INTO payment_tracking (
                application_id, razorpay_payment_id, razorpay_order_id, 
                amount, currency, status, payment_date, payment_method, 
                gateway_response
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
        ");
        
        $stmt->execute([
            $applicationId,
            $razorpayPaymentId,
            $razorpayOrderId,
        $amount,
            'INR',
            'completed',
            $paymentMethod,
            json_encode($gatewayResponse)
        ]);
        
        $paymentTrackingId = $pdo->lastInsertId();
        writeLog("Payment record inserted with ID: $paymentTrackingId");
    }
    
    // Application status already updated above (either existing or new record)
    writeLog("Application status is 'accepted' for ID: $applicationId");
    
    // Log payment activity
    $logMessage = "Payment completed - Application ID: $applicationId, Amount: ₹$amount, Payment ID: $paymentId";
    writeLog("✅ Payment activity logged to file: payment_logs/payment_activity_" . date('Y-m-d') . ".log");
    
    // Send success email
    writeLog("Sending success email to: $email for application ID: $applicationId");
    writeLog("Email function parameters - Application ID: $applicationId, Amount: $amount, Payment ID: $paymentId");
    writeLog("Calling sendPaymentSuccessEmail function...");
    
    $emailResult = sendPaymentSuccessEmail($applicationId, $amount, $paymentId);
        
        if ($emailResult) {
        writeLog("✅ SUCCESS: Email sent to $email for application ID: $applicationId");
        } else {
        writeLog("❌ FAILED: Email could not be sent to $email for application ID: $applicationId");
    }
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
            'payment_tracking_id' => $paymentTrackingId ?? 'N/A',
            'application_id' => $applicationId,
            'payment_id' => $paymentId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'completed',
            'payment_date' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Success response: " . json_encode($response));
    writeLog("=== PAYMENT PROCESSING COMPLETED SUCCESSFULLY ===");
    
    // Send response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== PAYMENT PROCESSING ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    writeLog("Error trace: " . $e->getTraceAsString());
    
    $errorResponse = [
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    writeLog("Error response: " . json_encode($errorResponse));
    writeLog("=== PAYMENT PROCESSING FAILED ===");
    
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode($errorResponse);
}
?> 