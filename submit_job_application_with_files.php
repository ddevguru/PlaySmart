<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'debug_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Log session and authentication info
writeLog("Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not active'));
writeLog("Session ID: " . (session_id() ?: 'None'));
writeLog("Cookies: " . print_r($_COOKIE, true));
writeLog("Headers: " . print_r(getallheaders(), true));

// Check if we're in a web context or API context
writeLog("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set'));
writeLog("HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'Not set'));
writeLog("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set'));

writeLog("=== NEW REQUEST STARTED ===");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = ['name', 'email', 'phone', 'education', 'experience', 'skills', 'job_id', 'referral_code'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Extract data
    $name = $input['name'];
    $email = $input['email'];
    $phone = $input['phone'];
    $education = $input['education'];
    $experience = $input['experience'];
    $skills = $input['skills'];
    $job_id = $input['job_id'];
    $referral_code = $input['referral_code'];
    $photo_path = $input['photo_path'] ?? '';
    $resume_path = $input['resume_path'] ?? '';
    
    // Get job details to determine registration fee
    $stmt = $pdo->prepare("SELECT package, company_name, job_title FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception('Job not found');
    }
    
    // Determine registration fee based on package
    $package_value = (float) preg_replace('/[^\d.]/', '', $job['package']);
    $registration_fee = ($package_value >= 20) ? 0.2 : 0.1;
    
    // Check if user already applied for this job
    $stmt = $pdo->prepare("SELECT id FROM job_applications WHERE email = ? AND job_id = ?");
    $stmt->execute([$email, $job_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('You have already applied for this job');
    }
    
    // Insert job application
    $stmt = $pdo->prepare("
        INSERT INTO job_applications (
            job_id, company_name, student_name, district, package, profile,
            photo_path, resume_path, email, phone, experience, skills,
            referral_code, application_status, applied_date, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), 1)
    ");
    
    $stmt->execute([
        $job_id,
        $job['company_name'],
        $name,
        'Not specified', // District will be updated later
        $job['package'],
        $job['job_title'],
        $photo_path,
        $resume_path,
        $email,
        $phone,
        $experience,
        $skills,
        $referral_code
    ]);
    
    $application_id = $pdo->lastInsertId();
    
    // Send email notification
    $subject = "Job Application Submitted - {$job['job_title']} at {$job['company_name']}";
    
    $message = "
    Dear $name,
    
    Thank you for submitting your job application for the position of {$job['job_title']} at {$job['company_name']}.
    
    Application Details:
    - Position: {$job['job_title']}
    - Company: {$job['company_name']}
    - Package: {$job['package']}
    - Registration Fee: ₹$registration_fee
    
    Your application has been received and is under review. Our team will contact you within 24-48 hours with further instructions.
    
    Important Notes:
    - Registration fee of ₹$registration_fee is required to proceed
    - Fee is non-refundable and valid for one year
    - You will receive 2-3 interview calls within a month
    - Our services cover all over India
    
    If you have any questions, please contact our support team.
    
    Best regards,
    PlaySmart Services Team
    ";
    
    $headers = "From: noreply@playsmart.co.in\r\n";
    $headers .= "Reply-To: support@playsmart.co.in\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email
    if (mail($email, $subject, $message, $headers)) {
        // Also send to admin
        $admin_email = "admin@playsmart.co.in";
        $admin_subject = "New Job Application - {$job['job_title']}";
        $admin_message = "
        New job application received:
        
        Candidate: $name
        Email: $email
        Phone: $phone
        Position: {$job['job_title']}
        Company: {$job['company_name']}
        Package: {$job['package']}
        Registration Fee: ₹$registration_fee
        Experience: $experience
        Skills: $skills
        Referral Code: $referral_code
        
        Application ID: $application_id
        ";
        
        mail($admin_email, $admin_subject, $admin_message, $headers);
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully',
            'application_id' => $application_id,
            'registration_fee' => $registration_fee,
            'email_sent' => true
        ]);
    } else {
        // Application saved but email failed
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully, but email notification failed',
            'application_id' => $application_id,
            'registration_fee' => $registration_fee,
            'email_sent' => false
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?> 