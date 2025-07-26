<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_GET['session_token']) ? $_GET['session_token'] : null;
$contest_id = isset($_GET['contest_id']) ? (int)$_GET['contest_id'] : 0;

if (!$session_token) {
    echo json_encode(['success' => false, 'message' => 'Session token is required']);
    exit;
}

if (!$contest_id) {
    echo json_encode(['success' => false, 'message' => 'Contest ID is required']);
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
    
    // Fetch contest rankings
    $sql = "SELECT rank_start, rank_end, prize_amount FROM mega_contest_rankings WHERE contest_id = :contest_id ORDER BY rank_start ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rankings)) {
        echo json_encode(['success' => false, 'message' => 'No rankings found for this contest']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'rankings' => $rankings
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 