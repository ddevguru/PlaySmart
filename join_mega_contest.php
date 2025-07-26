<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_POST['session_token']) ? $_POST['session_token'] : null;
$contest_id = isset($_POST['contest_id']) ? (int)$_POST['contest_id'] : 0;
$entry_fee = isset($_POST['entry_fee']) ? (float)$_POST['entry_fee'] : 0.0;

if (!$session_token) {
    echo json_encode(['success' => false, 'message' => 'Session token is required']);
    exit;
}

if (!$contest_id || $entry_fee <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid contest ID or entry fee']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify user session
    $sql = "SELECT id, username, wallet_balance FROM users WHERE session_token = :session_token AND status = 'online'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['session_token' => $session_token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired session']);
        exit;
    }
    
    $user_id = $user['id'];
    $current_balance = (float)$user['wallet_balance'];
    
    // Check if user has sufficient balance
    if ($current_balance < $entry_fee) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }
    
    // Get contest details
    $sql = "SELECT * FROM mega_contests WHERE id = :contest_id AND status = 'open'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $contest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contest) {
        echo json_encode(['success' => false, 'message' => 'Contest not found or not open']);
        exit;
    }
    
    // Check if user has already joined
    $sql = "SELECT * FROM mega_contest_participants WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    $existing_participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_participant) {
        echo json_encode(['success' => false, 'message' => 'You have already joined this contest']);
        exit;
    }
    
    // Check contest timing
    $current_time = new DateTime();
    $start_datetime = new DateTime($contest['start_datetime']);
    $time_diff = $current_time->diff($start_datetime);
    $minutes_until_start = $time_diff->invert ? -$time_diff->i : $time_diff->i;
    
    if ($minutes_until_start <= 1) {
        echo json_encode(['success' => false, 'message' => 'Contest joining is closed']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Deduct entry fee from user's wallet
        $sql = "UPDATE users SET wallet_balance = wallet_balance - :entry_fee WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['entry_fee' => $entry_fee, 'user_id' => $user_id]);
        
        // Add user to contest participants
        $sql = "INSERT INTO mega_contest_participants (contest_id, user_id, entry_fee, joined_at) 
                VALUES (:contest_id, :user_id, :entry_fee, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'contest_id' => $contest_id,
            'user_id' => $user_id,
            'entry_fee' => $entry_fee
        ]);
        
        // Generate match ID
        $match_id = 'mega_' . $contest_id . '_' . time() . '_' . $user_id;
        
        // Check if contest is full
        $sql = "SELECT COUNT(*) as participant_count FROM mega_contest_participants WHERE contest_id = :contest_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['contest_id' => $contest_id]);
        $participant_count = $stmt->fetch(PDO::FETCH_ASSOC)['participant_count'];
        
        $all_players_joined = $participant_count >= $contest['num_players'];
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully joined the contest',
            'match_id' => $match_id,
            'all_players_joined' => $all_players_joined,
            'is_bot' => false
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 