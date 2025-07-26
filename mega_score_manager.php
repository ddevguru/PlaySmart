<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

$session_token = isset($_POST['session_token']) ? $_POST['session_token'] : null;
$contest_id = isset($_POST['contest_id']) ? (int)$_POST['contest_id'] : 0;
$score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
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
    
    // Check if contest time has elapsed (auto-submit logic)
    $start_datetime = new DateTime($contest['start_datetime']);
    $time_elapsed = $current_time->getTimestamp() - $start_datetime->getTimestamp();
    $max_time_allowed = $contest['num_questions'] * 30; // 30 seconds per question
    
    // If time has elapsed, force submit with current answers or 0 score
    if ($time_elapsed > $max_time_allowed) {
        // Auto-submit with current score or 0 if no score provided
        $score = max(0, $score);
    }
    
    // Update participant score
    $sql = "UPDATE mega_contest_participants SET 
            score = :score, 
            has_submitted = 1, 
            submitted_at = NOW() 
            WHERE contest_id = :contest_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'score' => $score,
        'contest_id' => $contest_id,
        'user_id' => $user_id
    ]);
    
    // Check if any participant has submitted and if 90 seconds have passed since first submission
    $sql = "SELECT 
                COUNT(*) as total_participants,
                SUM(CASE WHEN has_submitted = 1 THEN 1 ELSE 0 END) as submitted_count,
                MIN(CASE WHEN has_submitted = 1 THEN submitted_at END) as first_submission_time
            FROM mega_contest_participants 
            WHERE contest_id = :contest_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $participant_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_any_submitted = $participant_stats['submitted_count'] > 0;
    $all_submitted = $participant_stats['total_participants'] == $participant_stats['submitted_count'];
    $auto_submit_triggered = false;
    $time_since_first_submission = 0;
    
    // Auto-submit logic: If any user has submitted and 90 seconds have passed, auto-submit all remaining participants
    if ($has_any_submitted && !$all_submitted && $participant_stats['first_submission_time']) {
        $first_submission_time = new DateTime($participant_stats['first_submission_time']);
        $time_since_first_submission = $current_time->getTimestamp() - $first_submission_time->getTimestamp();
        
        if ($time_since_first_submission >= 90) { // 90 seconds
            // Auto-submit all remaining participants with 0 score
            $sql = "UPDATE mega_contest_participants SET 
                    score = 0, 
                    has_submitted = 1, 
                    submitted_at = NOW() 
                    WHERE contest_id = :contest_id AND has_submitted = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['contest_id' => $contest_id]);
            
            $auto_submit_triggered = true;
            
            // Update participant stats after auto-submit
            $sql = "SELECT COUNT(*) as total_participants, 
                    SUM(CASE WHEN has_submitted = 1 THEN 1 ELSE 0 END) as submitted_count 
                    FROM mega_contest_participants 
                    WHERE contest_id = :contest_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['contest_id' => $contest_id]);
            $participant_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $all_submitted = $participant_stats['total_participants'] == $participant_stats['submitted_count'];
        }
    }
    
    if ($all_submitted) {
        // Calculate rankings and distribute prizes
        $sql = "SELECT user_id, score FROM mega_contest_participants 
                WHERE contest_id = :contest_id 
                ORDER BY score DESC, submitted_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['contest_id' => $contest_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get contest rankings
        $sql = "SELECT rank_start, rank_end, prize_amount FROM mega_contest_rankings 
                WHERE contest_id = :contest_id ORDER BY rank_start ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['contest_id' => $contest_id]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate ranks and prizes for all participants
        $participant_ranks = [];
        $participant_prizes = [];
        
        for ($i = 0; $i < count($participants); $i++) {
            $participant_user_id = $participants[$i]['user_id'];
            $participant_score = $participants[$i]['score'];
            $rank = $i + 1;
            $prize = 0;
            $is_winner = false;
            
            // Determine prize based on rank
            foreach ($rankings as $ranking) {
                if ($rank >= $ranking['rank_start'] && $rank <= $ranking['rank_end']) {
                    $prize = $ranking['prize_amount'];
                    $is_winner = true;
                    break;
                }
            }
            
            $participant_ranks[$participant_user_id] = $rank;
            $participant_prizes[$participant_user_id] = $prize;
            
            // Update participant's rank and prize in database
            $sql = "UPDATE mega_contest_participants SET 
                    rank = :rank, 
                    prize_won = :prize 
                    WHERE contest_id = :contest_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'rank' => $rank,
                'prize' => $prize,
                'contest_id' => $contest_id,
                'user_id' => $participant_user_id
            ]);
            
            // Update user's wallet if they won
            if ($prize > 0) {
                $sql = "UPDATE users SET wallet_balance = wallet_balance + :prize WHERE id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['prize' => $prize, 'user_id' => $participant_user_id]);
            }
        }
        
        // Get current user's rank and prize
        $user_rank = $participant_ranks[$user_id] ?? 0;
        $prize_won = $participant_prizes[$user_id] ?? 0;
        $is_winner = $prize_won > 0;
        
        // Check for ties
        $same_score_count = 0;
        foreach ($participants as $p) {
            if ($p['score'] == $score) {
                $same_score_count++;
            }
        }
        $is_tie = $same_score_count > 1;
        
        // Mark contest as completed
        $sql = "UPDATE mega_contests SET status = 'completed' WHERE id = :contest_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['contest_id' => $contest_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Score submitted successfully',
            'score' => $score,
            'rank' => $user_rank,
            'prize_won' => $prize_won,
            'is_winner' => $is_winner,
            'is_tie' => $is_tie,
            'total_participants' => $participant_stats['total_participants'],
            'auto_submitted' => $time_elapsed > $max_time_allowed,
            'auto_submit_triggered' => $auto_submit_triggered
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Score submitted successfully. Waiting for other participants.',
            'score' => $score,
            'auto_submitted' => $time_elapsed > $max_time_allowed,
            'auto_submit_triggered' => $auto_submit_triggered,
            'time_since_first_submission' => $time_since_first_submission,
            'remaining_participants' => $participant_stats['total_participants'] - $participant_stats['submitted_count']
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 