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
    
    $start_datetime = new DateTime($contest['start_datetime']);
    $time_diff = $current_time->diff($start_datetime);
    $minutes_until_start = $time_diff->invert ? -$time_diff->i : $time_diff->i;
    $seconds_until_start = $time_diff->invert ? -$time_diff->s : $time_diff->s;
    
    // Check if user has joined this contest
    $sql = "SELECT * FROM mega_contest_participants WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id, 'user_id' => $user_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_joined = !empty($participant);
    $has_submitted = $has_joined && isset($participant['has_submitted']) && $participant['has_submitted'] == 1;
    $has_viewed_results = $has_joined && isset($participant['has_viewed_results']) && $participant['has_viewed_results'] == 1;
    
    // Determine contest status based on timing
    $is_joinable = false;
    $is_active = false;
    
    // Contest is joinable if:
    // 1. More than 1 minute until start time
    // 2. User hasn't joined yet
    // 3. Contest status is 'open'
    if ($contest['status'] == 'open' && !$has_joined && $minutes_until_start > 1) {
        $is_joinable = true;
    }
    
    // Contest is active if:
    // 1. User has joined
    // 2. Start time has been reached
    // 3. Less than 2 minutes have passed since start time
    // 4. User hasn't submitted yet
    if ($has_joined && !$has_submitted) {
        $start_time_reached = $current_time >= $start_datetime;
        $within_start_window = $current_time <= $start_datetime->modify('+2 minutes');
        
        if ($start_time_reached && $within_start_window) {
            $is_active = true;
        }
    }
    
    echo json_encode([
        'success' => true,
        'is_joinable' => $is_joinable,
        'has_joined' => $has_joined,
        'is_active' => $is_active,
        'has_submitted' => $has_submitted,
        'has_viewed_results' => $has_viewed_results,
        'start_datetime' => $contest['start_datetime'],
        'minutes_until_start' => $minutes_until_start,
        'seconds_until_start' => $seconds_until_start,
        'contest_status' => $contest['status']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 