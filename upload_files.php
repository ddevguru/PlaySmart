<?php
// File Upload Handler for PlaySmart
// This script handles file uploads from Flutter and stores them in Admin/uploads directories

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logFile = 'file_upload_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

writeLog("=== FILE UPLOAD REQUEST STARTED ===");

try {
    // Define upload directories
    $photoDir = 'Admin/uploads/photos';
    $resumeDir = 'Admin/uploads/resumes';
    
    // Create directories if they don't exist
    if (!file_exists($photoDir)) {
        mkdir($photoDir, 0755, true);
        writeLog("Created photos directory: $photoDir");
    }
    
    if (!file_exists($resumeDir)) {
        mkdir($resumeDir, 0755, true);
        writeLog("Created resumes directory: $resumeDir");
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    writeLog("Received input: " . print_r($input, true));
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $response = ['success' => true, 'files' => []];
    
    // Handle photo upload
    if (isset($input['photo_data']) && !empty($input['photo_data'])) {
        $photoData = $input['photo_data'];
        $photoName = $input['photo_name'] ?? 'photo_' . time() . '.png';
        
        // Generate unique filename
        $photoFileName = 'photo_' . time() . '_' . rand(1000, 9999) . '.png';
        $photoPath = $photoDir . '/' . $photoFileName;
        
        // Decode base64 data and save
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $photoData = substr($photoData, strpos($photoData, ',') + 1);
            $photoData = base64_decode($photoData);
            
            if ($photoData === false) {
                throw new Exception('Invalid photo data');
            }
        } else {
            // Assume it's already base64 encoded
            $photoData = base64_decode($photoData);
            if ($photoData === false) {
                throw new Exception('Invalid photo data format');
            }
        }
        
        if (file_put_contents($photoPath, $photoData)) {
            $response['files']['photo'] = [
                'path' => $photoPath,
                'url' => 'https://playsmart.co.in/' . $photoPath,
                'filename' => $photoFileName
            ];
            writeLog("Photo uploaded successfully: $photoPath");
        } else {
            throw new Exception('Failed to save photo file');
        }
    }
    
    // Handle resume upload
    if (isset($input['resume_data']) && !empty($input['resume_data'])) {
        $resumeData = $input['resume_data'];
        $resumeName = $input['resume_name'] ?? 'resume_' . time() . '.pdf';
        
        // Get file extension from original name
        $fileExtension = pathinfo($resumeName, PATHINFO_EXTENSION);
        if (empty($fileExtension)) {
            $fileExtension = 'pdf'; // Default to PDF
        }
        
        // Generate unique filename
        $resumeFileName = 'resume_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $resumePath = $resumeDir . '/' . $resumeFileName;
        
        // Decode base64 data and save
        if (preg_match('/^data:application\/(\w+);base64,/', $resumeData, $type)) {
            $resumeData = substr($resumeData, strpos($resumeData, ',') + 1);
            $resumeData = base64_decode($resumeData);
            
            if ($resumeData === false) {
                throw new Exception('Invalid resume data');
            }
        } else {
            // Assume it's already base64 encoded
            $resumeData = base64_decode($resumeData);
            if ($resumeData === false) {
                throw new Exception('Invalid resume data format');
            }
        }
        
        if (file_put_contents($resumePath, $resumeData)) {
            $response['files']['resume'] = [
                'path' => $resumePath,
                'url' => 'https://playsmart.co.in/' . $resumePath,
                'filename' => $resumeFileName
            ];
            writeLog("Resume uploaded successfully: $resumePath");
        } else {
            throw new Exception('Failed to save resume file');
        }
    }
    
    // Handle file paths (if files are already uploaded and we just need to move them)
    if (isset($input['photo_path']) && !empty($input['photo_path'])) {
        $photoPath = $input['photo_path'];
        $photoFileName = basename($photoPath);
        
        // Generate unique filename
        $newPhotoFileName = 'photo_' . time() . '_' . rand(1000, 9999) . '.png';
        $newPhotoPath = $photoDir . '/' . $newPhotoFileName;
        
        // Copy file to upload directory
        if (copy($photoPath, $newPhotoPath)) {
            $response['files']['photo'] = [
                'path' => $newPhotoPath,
                'url' => 'https://playsmart.co.in/' . $newPhotoPath,
                'filename' => $newPhotoFileName
            ];
            writeLog("Photo copied successfully: $newPhotoPath");
        } else {
            writeLog("Warning: Could not copy photo from $photoPath");
        }
    }
    
    if (isset($input['resume_path']) && !empty($input['resume_path'])) {
        $resumePath = $input['resume_path'];
        $resumeFileName = basename($resumePath);
        
        // Get file extension
        $fileExtension = pathinfo($resumeFileName, PATHINFO_EXTENSION);
        if (empty($fileExtension)) {
            $fileExtension = 'pdf';
        }
        
        // Generate unique filename
        $newResumeFileName = 'resume_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $newResumePath = $resumeDir . '/' . $newResumeFileName;
        
        // Copy file to upload directory
        if (copy($resumePath, $newResumePath)) {
            $response['files']['resume'] = [
                'path' => $newResumePath,
                'url' => 'https://playsmart.co.in/' . $newResumePath,
                'filename' => $newResumeFileName
            ];
            writeLog("Resume copied successfully: $newResumePath");
        } else {
            writeLog("Warning: Could not copy resume from $resumePath");
        }
    }
    
    writeLog("=== FILE UPLOAD COMPLETED SUCCESSFULLY ===");
    writeLog("Response: " . json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("=== FILE UPLOAD ERROR ===");
    writeLog("Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'File upload failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 