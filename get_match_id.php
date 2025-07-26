<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_GET['session_token']) ? $_GET['session_token'] : null;
$contest_id = isset($_GET['contest_id']) ? (int)$_GET['contest_id'] : 0;
$contest_type = isset($_GET['contest_type']) ? $_GET['contest_type'] : '';

if (!$session_token) {
    echo json_encode(['success' => false, 'message' => 'Session token is required']);
    exit;
}

if (!$contest_id) {
    echo json_encode(['success' => false, 'message' => 'Contest ID is required']);
    exit;
}

if ($contest_type !== 'mega') {
    echo json_encode(['success' => false, 'message' => 'Invalid contest type']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify user session
    $sql = "SELECT id, username FROM users WHERE session_token = :session_token AND status = 'online'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['session_token' => $session_token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired session']);
        exit;
    }
    
    $user_id = $user['id'];
    
    // Get match ID for the user in this contest
    $sql = "SELECT match_id FROM mega_contest_participants WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        echo json_encode(['success' => false, 'message' => 'User has not joined this contest']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'match_id' => $participant['match_id']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 