<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING RANKING TIERS ===\n\n";
    
    // Check all mega contests and their ranking tiers
    $sql = "SELECT mc.id, mc.name, mc.total_winning_amount, 
                   mcr.rank_start, mcr.rank_end, mcr.prize_amount
            FROM mega_contests mc 
            LEFT JOIN mega_contest_rankings mcr ON mc.id = mcr.contest_id 
            ORDER BY mc.id, mcr.rank_start";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $current_contest = null;
    foreach ($results as $row) {
        if ($current_contest != $row['id']) {
            if ($current_contest !== null) {
                echo "\n";
            }
            $current_contest = $row['id'];
            echo "Contest ID: " . $row['id'] . " - " . $row['name'] . "\n";
            echo "Total Winning Amount: ₹" . $row['total_winning_amount'] . "\n";
            echo "Ranking Tiers:\n";
        }
        if ($row['rank_start'] !== null) {
            echo "  Rank " . $row['rank_start'] . "-" . $row['rank_end'] . ": ₹" . $row['prize_amount'] . "\n";
        } else {
            echo "  No ranking tiers found!\n";
        }
    }
    
    echo "\n=== FIXING MISSING RANKING TIERS ===\n";
    
    // Find contests without ranking tiers
    $sql = "SELECT mc.id, mc.name, mc.total_winning_amount 
            FROM mega_contests mc 
            LEFT JOIN mega_contest_rankings mcr ON mc.id = mcr.contest_id 
            WHERE mcr.contest_id IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $contests_without_rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contests_without_rankings as $contest) {
        echo "Adding ranking tiers for Contest ID: " . $contest['id'] . "\n";
        
        // For 2-player contests, only rank 1 gets the prize
        $sql = "INSERT INTO mega_contest_rankings (contest_id, rank_start, rank_end, prize_amount) 
                VALUES (:contest_id, 1, 1, :prize_amount)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'contest_id' => $contest['id'],
            'prize_amount' => $contest['total_winning_amount']
        ]);
        
        echo "  Added: Rank 1-1: ₹" . $contest['total_winning_amount'] . "\n";
    }
    
    echo "\nRanking tiers have been fixed!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 