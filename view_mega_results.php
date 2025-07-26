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
    
    $user_id = $user['id'];
    
    // Check if user has joined and submitted for this contest
    $sql = "SELECT * FROM mega_contest_participants WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        echo json_encode(['success' => false, 'message' => 'You have not joined this contest']);
        exit;
    }
    
    if ($participant['has_submitted'] != 1) {
        echo json_encode(['success' => false, 'message' => 'You have not submitted your score yet']);
        exit;
    }
    
    // Get contest details
    $sql = "SELECT * FROM mega_contests WHERE id = :contest_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $contest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contest) {
        echo json_encode(['success' => false, 'message' => 'Contest not found']);
        exit;
    }
    
    // Get user's stored rank and prize from database
    $sql = "SELECT p.rank, p.prize_won, p.score 
            FROM mega_contest_participants p 
            WHERE p.contest_id = :contest_id AND p.user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $user_rank = $user_result['rank'] ?? 0;
    $prize_won = $user_result['prize_won'] ?? 0;
    $user_score = $user_result['score'] ?? 0;
    $is_winner = $prize_won > 0;
    
    // Get all participants with their scores and ranks
    $sql = "SELECT p.user_id, p.score, p.rank, p.submitted_at, u.username 
            FROM mega_contest_participants p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.contest_id = :contest_id AND p.has_submitted = 1 
            ORDER BY p.rank ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for ties (same score)
    $same_score_count = 0;
    foreach ($participants as $p) {
        if ($p['score'] == $user_score) {
            $same_score_count++;
        }
    }
    $is_tie = $same_score_count > 1;
    
    // Get opponent info (for 2-player contests)
    $opponent_name = null;
    $opponent_score = null;
    
    if (count($participants) == 2) {
        foreach ($participants as $p) {
            if ($p['user_id'] != $user_id) {
                $opponent_name = $p['username'];
                $opponent_score = $p['score'];
                break;
            }
        }
    }
    
    // Mark results as viewed
    $sql = "UPDATE mega_contest_participants SET has_viewed_results = 1 WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    
    echo json_encode([
        'success' => true,
        'user_score' => $user_score,
        'user_rank' => $user_rank,
        'prize_won' => $prize_won,
        'is_winner' => $is_winner,
        'is_tie' => $is_tie,
        'opponent_name' => $opponent_name,
        'opponent_score' => $opponent_score,
        'total_participants' => count($participants),
        'contest_name' => $contest['name'],
        'match_completed' => true
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 