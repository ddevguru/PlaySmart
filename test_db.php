<?php
require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    echo "Database connection successful!";
    
    // Test the headings query
    $stmt = $pdo->prepare("SELECT * FROM content_headings WHERE section_name = 'successful_candidates'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
?> 