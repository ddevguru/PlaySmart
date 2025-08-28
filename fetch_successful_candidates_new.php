<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'config.php';

try {
    // Fetch all active successful candidates
    $stmt = $conn->prepare("
        SELECT id, company_name, candidate_name, salary, job_location, created_at
        FROM successful_candidates 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $candidates = [];
    while ($row = $result->fetch_assoc()) {
        $candidates[] = [
            'id' => $row['id'],
            'company_name' => $row['company_name'],
            'candidate_name' => $row['candidate_name'],
            'salary' => $row['salary'],
            'job_location' => $row['job_location'],
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => $candidates,
        'message' => 'Successful candidates fetched successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching successful candidates: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 