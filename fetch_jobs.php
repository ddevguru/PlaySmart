<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'playsmart_db';
$username = 'your_username';
$password = 'your_password';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch active jobs
    $stmt = $pdo->prepare("
        SELECT 
            id,
            company_name,
            company_logo,
            student_name,
            district,
            package,
            profile,
            job_title,
            location,
            job_type,
            experience_level,
            skills_required,
            job_description,
            created_at
        FROM jobs 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process company logos to include full URL
    foreach ($jobs as &$job) {
        if ($job['company_logo']) {
            $job['company_logo_url'] = 'https://playsmart.co.in/images/company_logos/' . $job['company_logo'];
        } else {
            $job['company_logo_url'] = 'https://playsmart.co.in/images/company_logos/default_logo.png';
        }
        
        // Clean up the data
        $job['skills_required'] = $job['skills_required'] ? explode(',', $job['skills_required']) : [];
        $job['created_at'] = date('Y-m-d H:i:s', strtotime($job['created_at']));
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Jobs fetched successfully',
        'data' => $jobs,
        'count' => count($jobs)
    ]);
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => []
    ]);
}
?> 