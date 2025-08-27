<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'fetch_candidates_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== FETCH SUCCESSFUL CANDIDATES STARTED ===");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    // Fetch only successfully placed candidates (accepted status)
    $stmt = $pdo->prepare("
        SELECT 
            ja.id,
            ja.job_id,
            ja.company_name,
            ja.company_logo,
            ja.student_name,
            ja.district,
            ja.package,
            ja.profile,
            ja.photo_path,
            ja.resume_path,
            ja.email,
            ja.phone,
            ja.experience,
            ja.skills,
            ja.payment_id,
            ja.application_status,
            ja.applied_date,
            ja.is_active
        FROM job_applications ja
        WHERE ja.is_active = 1 AND ja.application_status = 'accepted'
        ORDER BY ja.applied_date DESC
    ");
    
    $stmt->execute();
    $successfulCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Found " . count($successfulCandidates) . " successful candidates");
    
    // Process URLs and format data
    foreach ($successfulCandidates as &$candidate) {
                        // Process company logo URL - Fix the path to include Admin folder
                if (!empty($candidate['company_logo'])) {
                    $candidate['company_logo_url'] = 'https://playsmart.co.in/Admin/uploads/' . $candidate['company_logo'];
                } else {
                    $candidate['company_logo_url'] = '';
                }
                unset($candidate['company_logo']); // Remove old field
        
                        // Process photo URL - Fix the path to include Admin folder
                if (!empty($candidate['photo_path'])) {
                    // Extract just the filename from the stored path
                    $filename = basename($candidate['photo_path']);
                    // Create the correct URL path
                    $candidate['photo_url'] = 'https://playsmart.co.in/Admin/uploads/photos/' . $filename;
                    // Also keep photo_path for backward compatibility
                    $candidate['photo_path'] = $candidate['photo_url'];
                    writeLog("Photo path fixed: {$candidate['photo_path']} -> {$candidate['photo_url']}");
                } else {
                    $candidate['photo_url'] = '';
                    $candidate['photo_path'] = '';
                }
        
                        // Process resume URL - Fix the path to include Admin folder
                if (!empty($candidate['resume_path'])) {
                    $candidate['resume_url'] = 'https://playsmart.co.in/Admin/uploads/resumes/' . basename($candidate['resume_path']);
                } else {
                    $candidate['resume_url'] = '';
                }
                unset($candidate['resume_path']); // Remove old field
        
        // Format applied date
        if (!empty($candidate['applied_date'])) {
            $candidate['formatted_date'] = date('F j, Y', strtotime($candidate['applied_date']));
        } else {
            $candidate['formatted_date'] = '';
        }
        
        // Add placement status
        $candidate['placement_status'] = 'Successfully Placed';
        $candidate['status_color'] = '#28a745'; // Green color for success
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully placed candidates fetched successfully',
        'data' => $successfulCandidates,
        'count' => count($successfulCandidates),
        'last_updated' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching successful candidates: ' . $e->getMessage()
    ]);
}
?> 