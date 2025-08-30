<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    // Remove the query parameter check since we always want successful_candidates
    $stmt = $pdo->prepare("SELECT heading_text, sub_heading_text FROM content_headings WHERE section_name = 'successful_candidates' AND is_active = 1");
    $stmt->execute();
    
    $heading = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($heading) {
        echo json_encode([
            'success' => true,
            'data' => [
                'heading' => $heading['heading_text'],
                'sub_heading' => $heading['sub_heading_text']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Heading not found',
            'data' => [
                'heading' => 'Our Successfully Placed',
                'sub_heading' => 'Candidates'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching heading: ' . $e->getMessage(),
        'data' => [
            'heading' => 'Our Successfully Placed',
            'sub_heading' => 'Candidates'
        ]
    ]);
}
?> 