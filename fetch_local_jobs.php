<?php
// Fetch Local Jobs API
// This API fetches jobs with package less than 10 LPA for local job seekers

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'local_jobs_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== LOCAL JOBS FETCH REQUEST STARTED ===");

try {
    // Include database configuration
    require_once 'newcon.php';
    
    // Test database connection first
    $dbCheck = checkDatabaseConnection();
    if (!$dbCheck['success']) {
        throw new Exception('Database connection failed: ' . $dbCheck['message']);
    }
    
    writeLog("Database connection successful");
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $location = isset($_GET['location']) ? $_GET['location'] : '';
    $jobType = isset($_GET['job_type']) ? $_GET['job_type'] : '';
    $experience = isset($_GET['experience']) ? $_GET['experience'] : '';
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build the SQL query for local jobs (package < 10 LPA)
    $sql = "SELECT 
                id,
                company_name,
                company_logo,
                job_title,
                package,
                location,
                job_type,
                experience_level,
                skills_required,
                job_description,
                created_at,
                company_logo_url
            FROM jobs 
            WHERE 1=1";
    
    $params = [];
    
    // Filter for local jobs (package < 10 LPA)
    $sql .= " AND (
        CASE 
            WHEN package LIKE '%LPA%' THEN 
                CAST(REPLACE(REPLACE(package, 'LPA', ''), '₹', '') AS DECIMAL(10,2)) < 10
            WHEN package LIKE '%PA%' THEN 
                CAST(REPLACE(REPLACE(REPLACE(package, 'PA', ''), '₹', ''), ',', '') AS DECIMAL(10,2)) < 1000000
            ELSE 1
        END
    )";
    
    // Add location filter if provided
    if (!empty($location)) {
        $sql .= " AND location LIKE ?";
        $params[] = "%$location%";
    }
    
    // Add job type filter if provided
    if (!empty($jobType)) {
        $sql .= " AND job_type = ?";
        $params[] = $jobType;
    }
    
    // Add experience filter if provided
    if (!empty($experience)) {
        $sql .= " AND experience_level LIKE ?";
        $params[] = "%$experience%";
    }
    
    // Add sorting and pagination
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    writeLog("SQL Query: " . $sql);
    writeLog("Parameters: " . print_r($params, true));
    
    // Prepare and execute the statement
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Fetch all results
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM jobs WHERE (
        CASE 
            WHEN package LIKE '%LPA%' THEN 
                CAST(REPLACE(REPLACE(package, 'LPA', ''), '₹', '') AS DECIMAL(10,2)) < 10
            WHEN package LIKE '%PA%' THEN 
                CAST(REPLACE(REPLACE(REPLACE(package, 'PA', ''), '₹', ''), ',', '') AS DECIMAL(10,2)) < 1000000
            ELSE 1
        END
    )";
    
    if (!empty($location)) {
        $countSql .= " AND location LIKE '%$location%'";
    }
    if (!empty($jobType)) {
        $countSql .= " AND job_type = '$jobType'";
    }
    if (!empty($experience)) {
        $countSql .= " AND experience_level LIKE '%$experience%'";
    }
    
    $countStmt = $pdo->query($countSql);
    $totalJobs = $countStmt->fetch()['total'];
    
    // Process skills_required field (convert from JSON if needed)
    foreach ($jobs as &$job) {
        if (isset($job['skills_required']) && is_string($job['skills_required'])) {
            // Try to decode JSON, if it fails, split by comma
            $skills = json_decode($job['skills_required'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not JSON, split by comma and clean up
                $skills = array_map('trim', explode(',', $job['skills_required']));
            }
            $job['skills_required'] = is_array($skills) ? $skills : [$job['skills_required']];
        }
        
        // Ensure company_logo_url is set
        if (empty($job['company_logo_url']) && !empty($job['company_logo'])) {
            $job['company_logo_url'] = 'https://playsmart.co.in/images/company_logos/' . $job['company_logo'];
        }
        
        // Format package for display
        if (strpos($job['package'], 'LPA') !== false) {
            $job['package_display'] = $job['package'];
            $job['package_numeric'] = (float)str_replace(['LPA', '₹', ' '], '', $job['package']);
        } else {
            $job['package_display'] = $job['package'];
            $job['package_numeric'] = 0;
        }
    }
    
    writeLog("Fetched " . count($jobs) . " local jobs out of $totalJobs total");
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Local jobs fetched successfully',
        'data' => [
            'jobs' => $jobs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_jobs' => $totalJobs,
                'total_pages' => ceil($totalJobs / $limit),
                'has_next' => ($page * $limit) < $totalJobs,
                'has_prev' => $page > 1
            ],
            'filters' => [
                'location' => $location,
                'job_type' => $jobType,
                'experience' => $experience
            ]
        ]
    ];
    
    writeLog("=== LOCAL JOBS FETCH COMPLETED SUCCESSFULLY ===");
    writeLog("Response: " . json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== LOCAL JOBS FETCH ERROR ===");
    writeLog("Error message: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching local jobs: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    writeLog("=== LOCAL JOBS FETCH FAILED ===");
}
?> 