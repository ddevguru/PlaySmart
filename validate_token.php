<?php
// Token Validation Endpoint for PlaySmart
// This endpoint validates user tokens and extends session validity

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
$logFile = 'token_validation_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== TOKEN VALIDATION STARTED ===");

try {
    require_once 'db_config.php';
    
    // Test database connection first
    $dbCheck = checkDatabaseConnection();
    if (!$dbCheck['success']) {
        throw new Exception('Database connection failed: ' . $dbCheck['message']);
    }
    
    writeLog("Database connection successful");
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get token from request
    $token = $_POST['token'] ?? '';
    
    if (empty($token)) {
        throw new Exception('Token is required');
    }
    
    writeLog("Token received: " . substr($token, 0, 10) . "...");
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if token exists and is valid
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.phone, u.status, s.expires_at
        FROM users u
        INNER JOIN user_sessions s ON u.id = s.user_id
        WHERE s.session_token = ? AND s.expires_at > NOW() AND u.status = 'active'
    ");
    
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Check if token exists but is expired
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, s.expires_at
            FROM users u
            INNER JOIN user_sessions s ON u.id = s.user_id
            WHERE s.session_token = ?
        ");
        
        $stmt->execute([$token]);
        $expiredUser = $stmt->fetch();
        
        if ($expiredUser) {
            writeLog("Token expired for user: " . $expiredUser['username']);
            throw new Exception('Token has expired. Please login again.');
        } else {
            writeLog("Invalid token provided");
            throw new Exception('Invalid token. Please login again.');
        }
    }
    
    writeLog("Token validated for user: " . $user['username']);
    
    // Extend session validity (add 24 hours)
    $newExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $updateStmt = $pdo->prepare("
        UPDATE user_sessions 
        SET expires_at = ?, last_activity = NOW()
        WHERE session_token = ?
    ");
    
    $updateStmt->execute([$newExpiry, $token]);
    
    writeLog("Session extended until: $newExpiry");
    
    // Update user's last activity
    $userUpdateStmt = $pdo->prepare("
        UPDATE users 
        SET last_activity = NOW()
        WHERE id = ?
    ");
    
    $userUpdateStmt->execute([$user['id']]);
    
    // Return user data
    $response = [
        'success' => true,
        'message' => 'Token is valid',
        'data' => [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'status' => $user['status'],
            'session_expires' => $newExpiry,
            'token_valid' => true
        ]
    ];
    
    writeLog("=== TOKEN VALIDATION COMPLETED SUCCESSFULLY ===");
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== TOKEN VALIDATION ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'token_valid' => false,
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    writeLog("=== TOKEN VALIDATION FAILED ===");
}
?> 