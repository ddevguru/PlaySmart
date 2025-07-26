<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_GET['session_token']) ? $_GET['session_token'] : null;

if (!$session_token) {
    echo json_encode(['success' => false, 'message' => 'Session token is required']);
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
    
    // Fetch all mega contests that are 'open' or 'completed'
    // The client-side will then filter based on detailed status from get_mega_contest_status.php
    $sql = "SELECT id, name, type, entry_fee, num_players, num_questions, start_datetime, status, total_winning_amount FROM mega_contests WHERE status IN ('open', 'completed') ORDER BY start_datetime ASC";
    // Order by start_datetime to show upcoming first
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $contests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contests as &$contest) {
        $contest_id = $contest['id'];
        $sql_ranks = "SELECT rank_start, rank_end, prize_amount FROM mega_contest_rankings WHERE contest_id = :contest_id ORDER BY rank_start";
        $stmt_ranks = $pdo->prepare($sql_ranks);
        $stmt_ranks->execute(['contest_id' => $contest_id]);
        $contest['rankings'] = $stmt_ranks->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'data' => $contests]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 