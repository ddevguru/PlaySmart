<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_POST['session_token']) ? $_POST['session_token'] : null;
$contest_id = isset($_POST['contest_id']) ? (int)$_POST['contest_id'] : 0;
$match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;

if (!$session_token) {
    echo json_encode(['success' => false, 'message' => 'Session token is required']);
    exit;
}

if (!$contest_id || !$match_id) {
    echo json_encode(['success' => false, 'message' => 'Contest ID and Match ID are required']);
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
    $current_time = new DateTime();
    
    // Get contest details
    $sql = "SELECT * FROM mega_contests WHERE id = :contest_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $contest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contest) {
        echo json_encode(['success' => false, 'message' => 'Contest not found']);
        exit;
    }
    
    // Check if user has joined this contest
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
    
    // Check contest timing
    $start_datetime = new DateTime($contest['start_datetime']);
    $time_diff = $current_time->diff($start_datetime);
    $minutes_until_start = $time_diff->invert ? -$time_diff->i : $time_diff->i;
    
    // Contest can be started if:
    // 1. Start time has been reached
    // 2. Less than 2 minutes have passed since start time
    $start_time_reached = $current_time >= $start_datetime;
    $within_start_window = $current_time <= $start_datetime->modify('+2 minutes');
    
    if (!$start_time_reached) {
        echo json_encode(['success' => false, 'message' => 'Contest has not started yet']);
        exit;
    }
    
    if (!$within_start_window) {
        echo json_encode(['success' => false, 'message' => 'Contest start window has expired']);
        exit;
    }
    
    // Get questions for the contest
    $sql = "SELECT * FROM questions WHERE contest_id = :contest_id ORDER BY RAND() LIMIT :num_questions";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':contest_id', $contest_id, PDO::PARAM_INT);
    $stmt->bindValue(':num_questions', $contest['num_questions'], PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($questions) < $contest['num_questions']) {
        echo json_encode(['success' => false, 'message' => 'Not enough questions available']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Contest started successfully',
        'questions_count' => count($questions),
        'time_limit' => $contest['num_questions'] * 30, // 30 seconds per question
        'match_id' => $match_id
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 