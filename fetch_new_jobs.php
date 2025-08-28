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
    // Fetch all active jobs
    $stmt = $conn->prepare("
        SELECT id, job_post, salary, education, job_type, created_at
        FROM new_jobs 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = [
            'id' => $row['id'],
            'job_post' => $row['job_post'],
            'salary' => $row['salary'],
            'education' => $row['education'],
            'job_type' => $row['job_type'],
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => $jobs,
        'message' => 'Jobs fetched successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching jobs: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 