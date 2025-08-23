<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    // Fetch active job applications
    $stmt = $pdo->prepare("
        SELECT 
            id,
            job_id,
            company_name,
            company_logo,
            student_name,
            district,
            package,
            profile,
            photo_path,
            resume_path,
            email,
            phone,
            experience,
            skills,
            payment_id,
            application_status,
            applied_date,
            is_active
        FROM job_applications 
        WHERE is_active = 1 
        ORDER BY applied_date DESC
    ");
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process company logo URLs
    foreach ($applications as &$application) {
        if (!empty($application['company_logo'])) {
            $application['company_logo_url'] = 'https://playsmart.co.in/uploads/' . $application['company_logo'];
        } else {
            $application['company_logo_url'] = '';
        }
        unset($application['company_logo']); // Remove old field
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Job applications fetched successfully',
        'data' => $applications,
        'count' => count($applications)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching job applications: ' . $e->getMessage()
    ]);
}
?> 