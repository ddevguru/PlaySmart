<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    // Test 1: Check all statuses
    $stmt = $pdo->prepare("
        SELECT application_status, COUNT(*) as count
        FROM job_applications 
        WHERE is_active = 1 
        GROUP BY application_status
    ");
    $stmt->execute();
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 2: Get only accepted candidates
    $stmt = $pdo->prepare("
        SELECT 
            id,
            student_name,
            company_name,
            application_status,
            applied_date
        FROM job_applications 
        WHERE is_active = 1 AND application_status = 'accepted'
        ORDER BY applied_date DESC
    ");
    $stmt->execute();
    $acceptedCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 3: Get shortlisted candidates for comparison
    $stmt = $pdo->prepare("
        SELECT 
            id,
            student_name,
            company_name,
            application_status,
            applied_date
        FROM job_applications 
        WHERE is_active = 1 AND application_status = 'shortlisted'
        ORDER BY applied_date DESC
    ");
    $stmt->execute();
    $shortlistedCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status analysis completed',
        'data' => [
            'status_counts' => $statusCounts,
            'accepted_candidates' => $acceptedCandidates,
            'shortlisted_candidates' => $shortlistedCandidates,
            'total_accepted' => count($acceptedCandidates),
            'total_shortlisted' => count($shortlistedCandidates)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 