<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

// Contest ID to check and fix
$contest_id = 83;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING CONTEST ID: $contest_id ===\n\n";
    
    // Check contest details
    $sql = "SELECT * FROM mega_contests WHERE id = :contest_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $contest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contest) {
        echo "Contest not found!\n";
        exit;
    }
    
    echo "Contest Details:\n";
    echo "Name: " . $contest['name'] . "\n";
    echo "Status: " . $contest['status'] . "\n";
    echo "Start Time: " . $contest['start_datetime'] . "\n\n";
    
    // Check participants
    $sql = "SELECT p.*, u.username 
            FROM mega_contest_participants p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.contest_id = :contest_id 
            ORDER BY p.score DESC, p.submitted_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Participants:\n";
    foreach ($participants as $p) {
        echo "User: " . $p['username'] . " (ID: " . $p['user_id'] . ")\n";
        echo "  Score: " . $p['score'] . "\n";
        echo "  Rank: " . ($p['rank'] ?? 'NULL') . "\n";
        echo "  Prize: " . ($p['prize_won'] ?? 'NULL') . "\n";
        echo "  Submitted: " . $p['submitted_at'] . "\n";
        echo "  Has Submitted: " . $p['has_submitted'] . "\n";
        echo "  Has Viewed Results: " . $p['has_viewed_results'] . "\n\n";
    }
    
    // Check rankings
    $sql = "SELECT * FROM mega_contest_rankings WHERE contest_id = :contest_id ORDER BY rank_start ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['contest_id' => $contest_id]);
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Ranking Tiers:\n";
    foreach ($rankings as $r) {
        echo "Rank " . $r['rank_start'] . "-" . $r['rank_end'] . ": ₹" . $r['prize_amount'] . "\n";
    }
    echo "\n";
    
    // Fix rankings if needed
    if (count($participants) > 0) {
        echo "=== FIXING RANKINGS ===\n";
        
        // Calculate correct ranks and prizes
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
                echo "  Added ₹$prize to wallet for user $participant_user_id\n";
            }
        }
        
        // Mark contest as completed
        $sql = "UPDATE mega_contests SET status = 'completed' WHERE id = :contest_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['contest_id' => $contest_id]);
        
        echo "\nContest $contest_id has been fixed!\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 