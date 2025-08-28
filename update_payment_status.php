<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'db_config.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data received');
    }
    
    // Extract data
    $applicationId = intval($input['application_id'] ?? 0);
    $paymentId = trim($input['payment_id'] ?? '');
    $status = trim($input['payment_status'] ?? 'completed');
    
    // Validation
    if (empty($applicationId) || $applicationId <= 0) {
        throw new Exception('Invalid application ID');
    }
    
    if (empty($paymentId)) {
        throw new Exception('Payment ID is required');
    }
    
    // Update payment status
    $stmt = $conn->prepare("
        UPDATE job_applications 
        SET payment_status = ?, payment_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $status, $paymentId, $applicationId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update payment status: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Application not found or no changes made');
    }
    
    $stmt->close();
    
    // Get application details for response
    $stmt = $conn->prepare("
        SELECT application_fee, job_type, package, profile 
        FROM job_applications 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $application = $result->fetch_assoc();
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully',
        'data' => [
            'application_id' => $applicationId,
            'payment_status' => $status,
            'payment_id' => $paymentId,
            'application_fee' => $application['application_fee'] ?? 0,
            'job_type' => $application['job_type'] ?? '',
            'package' => $application['package'] ?? '',
            'profile' => $application['profile'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 