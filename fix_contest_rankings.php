<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

// Contest ID to fix (change this to the contest you want to fix)
$contest_id = 80; // Change this to your contest ID

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Fixing contest ID: $contest_id\n";
    
    // Get all participants for this contest
    $sql = "SELECT user_id, score, submitted_at FROM mega_contest_participants 
            WHERE contest_id = :contest_id AND has_submitted = 1 
            ORDER BY score DESC, submitted_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($participants) . " participants\n";
    
    // Get contest rankings
    $sql = "SELECT rank_start, rank_end, prize_amount FROM mega_contest_rankings 
            WHERE contest_id = :contest_id ORDER BY rank_start ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($rankings) . " ranking tiers\n";
    
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
        
        echo "User ID: $participant_user_id, Score: $participant_score, Rank: $rank, Prize: $prize\n";
        
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
            echo "Added $prize to wallet for user $participant_user_id\n";
        }
    }
    
    // Mark contest as completed
    $sql = "UPDATE mega_contests SET status = 'completed' WHERE id = :contest_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    
    echo "Contest $contest_id has been fixed!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 