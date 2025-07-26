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
    
    // Check contest status and auto-submit conditions
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
    $time_since_first_submission = 0;
    $auto_submit_triggered = false;
    
    if ($has_any_submitted && !$all_submitted && $participant_stats['first_submission_time']) {
        $first_submission_time = new DateTime($participant_stats['first_submission_time']);
        $time_since_first_submission = $current_time->getTimestamp() - $first_submission_time->getTimestamp();
        
        // If 90 seconds have passed, trigger auto-submit
        if ($time_since_first_submission >= 90) {
            // Auto-submit all remaining participants with 0 score
            $sql = "UPDATE mega_contest_participants SET 
                    score = 0, 
                    has_submitted = 1, 
                    submitted_at = NOW() 
                    WHERE contest_id = :contest_id AND has_submitted = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['contest_id' => $contest_id]);
            
            $auto_submit_triggered = true;
            
            // Update stats after auto-submit
            $sql = "SELECT COUNT(*) as total_participants, 
                    SUM(CASE WHEN has_submitted = 1 THEN 1 ELSE 0 END) as submitted_count 
                    FROM mega_contest_participants 
                    WHERE contest_id = :contest_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['contest_id' => $contest_id]);
            $participant_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $all_submitted = $participant_stats['total_participants'] == $participant_stats['submitted_count'];
            
            // If all participants have submitted, calculate rankings and distribute prizes
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
                for ($i = 0; $i < count($participants); $i++) {
                    $participant_user_id = $participants[$i]['user_id'];
                    $participant_score = $participants[$i]['score'];
                    $rank = $i + 1;
                    $prize = 0;
                    
                    // Determine prize based on rank
                    foreach ($rankings as $ranking) {
                        if ($rank >= $ranking['rank_start'] && $rank <= $ranking['rank_end']) {
                            $prize = $ranking['prize_amount'];
                            break;
                        }
                    }
                    
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
                
                // Mark contest as completed
                $sql = "UPDATE mega_contests SET status = 'completed' WHERE id = :contest_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['contest_id' => $contest_id]);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'has_any_submitted' => $has_any_submitted,
        'all_submitted' => $all_submitted,
        'time_since_first_submission' => $time_since_first_submission,
        'auto_submit_triggered' => $auto_submit_triggered,
        'remaining_participants' => $participant_stats['total_participants'] - $participant_stats['submitted_count'],
        'total_participants' => $participant_stats['total_participants'],
        'submitted_count' => $participant_stats['submitted_count']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 