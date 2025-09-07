<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $user_id = $input['user_id'] ?? null;
    $job_id = $input['job_id'] ?? null;
    $token = $input['token'] ?? null;
    
    if (!$user_id || !$job_id || !$token) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate token (you can add more validation here)
    // For now, we'll just check if the user exists
    
    // Check if user has applied for this job
    $stmt = $pdo->prepare("
        SELECT 
            ja.id,
            ja.application_status,
            ja.payment_status,
            ja.razorpay_payment_id,
            ja.razorpay_order_id,
            ja.application_fee,
            ja.applied_date
        FROM job_applications ja
        WHERE ja.user_id = ? AND ja.job_id = ? AND ja.is_active = 1
        ORDER BY ja.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$user_id, $job_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($application) {
        // User has applied for this job
        $response = [
            'success' => true,
            'has_applied' => true,
            'application_id' => $application['id'],
            'application_status' => $application['application_status'],
            'payment_status' => $application['payment_status'],
            'razorpay_payment_id' => $application['razorpay_payment_id'],
            'razorpay_order_id' => $application['razorpay_order_id'],
            'application_fee' => $application['application_fee'],
            'applied_date' => $application['applied_date']
        ];
        
        // Determine the current state
        if ($application['razorpay_payment_id'] && $application['payment_status'] == 'completed') {
            $response['current_state'] = 'paid';
        } elseif ($application['application_status'] == 'pending') {
            $response['current_state'] = 'submitted';
        } else {
            $response['current_state'] = 'unknown';
        }
        
    } else {
        // User has not applied for this job
        $response = [
            'success' => true,
            'has_applied' => false,
            'current_state' => 'not_applied'
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 