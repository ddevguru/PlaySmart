<?php
// Update Last Activity Endpoint for PlaySmart
// This endpoint updates user's last activity timestamp

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
$logFile = 'last_activity_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== UPDATE LAST ACTIVITY STARTED ===");

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
    
    // Get session token from request
    $sessionToken = $_POST['session_token'] ?? '';
    
    if (empty($sessionToken)) {
        throw new Exception('Session token is required');
    }
    
    writeLog("Session token received: " . substr($sessionToken, 0, 10) . "...");
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if session exists and is valid
    $stmt = $pdo->prepare("
        SELECT s.user_id, s.expires_at, u.username
        FROM user_sessions s
        INNER JOIN users u ON s.user_id = u.id
        WHERE s.session_token = ? AND s.expires_at > NOW()
    ");
    
    $stmt->execute([$sessionToken]);
    $session = $stmt->fetch();
    
    if (!$session) {
        writeLog("Invalid or expired session token");
        throw new Exception('Invalid or expired session token');
    }
    
    writeLog("Session validated for user ID: " . $session['user_id']);
    
    // Update last activity in user_sessions table
    $updateSessionStmt = $pdo->prepare("
        UPDATE user_sessions 
        SET last_activity = NOW()
        WHERE session_token = ?
    ");
    
    $updateSessionStmt->execute([$sessionToken]);
    
    // Update last activity in users table
    $updateUserStmt = $pdo->prepare("
        UPDATE users 
        SET last_activity = NOW()
        WHERE id = ?
    ");
    
    $updateUserStmt->execute([$session['user_id']]);
    
    writeLog("Last activity updated for user: " . $session['username']);
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'Last activity updated successfully',
        'data' => [
            'user_id' => $session['user_id'],
            'username' => $session['username'],
            'last_activity' => date('Y-m-d H:i:s'),
            'session_expires' => $session['expires_at']
        ]
    ];
    
    writeLog("=== UPDATE LAST ACTIVITY COMPLETED SUCCESSFULLY ===");
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== UPDATE LAST ACTIVITY ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    writeLog("=== UPDATE LAST ACTIVITY FAILED ===");
}
?> 