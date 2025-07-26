<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_GET['session_token']) ? $_GET['session_token'] : null;
$match_id = isset($_GET['match_id']) ? $_GET['match_id'] : null;

if (!$session_token) {
    echo json_encode(['success' => false, 'message' => 'Session token is required']);
    exit;
}

if (!$match_id) {
    echo json_encode(['success' => false, 'message' => 'Match ID is required']);
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
    
    // Extract contest ID from match ID (format: mega_contestId_timestamp_userId)
    $match_parts = explode('_', $match_id);
    if (count($match_parts) < 4) {
        echo json_encode(['success' => false, 'message' => 'Invalid match ID format']);
        exit;
    }
    
    $contest_id = (int)$match_parts[1];
    
    // Verify user has joined this contest
    $sql = "SELECT * FROM mega_contest_participants WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        echo json_encode(['success' => false, 'message' => 'You have not joined this contest']);
        exit;
    }
    
    if ($participant['has_submitted'] == 1) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted your score']);
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
    
    // Get questions for the contest
    $sql = "SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option 
            FROM questions 
            WHERE contest_id = :contest_id 
            ORDER BY RAND() 
            LIMIT :num_questions";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':contest_id', $contest_id, PDO::PARAM_INT);
    $stmt->bindValue(':num_questions', $contest['num_questions'], PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($questions) < $contest['num_questions']) {
        echo json_encode(['success' => false, 'message' => 'Not enough questions available']);
        exit;
    }
    
    // Shuffle questions to randomize order
    shuffle($questions);
    
    echo json_encode([
        'success' => true,
        'data' => $questions,
        'total_questions' => count($questions),
        'time_limit' => $contest['num_questions'] * 30 // 30 seconds per question
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 