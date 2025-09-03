<?php
// Complete Test Script for File Upload Solution
// This script tests all components of the file upload system

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "==========================================\n";
echo "COMPLETE FILE UPLOAD SOLUTION TEST\n";
echo "==========================================\n\n";

// Test 1: Check PHP Environment
echo "1. PHP Environment Check\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   File Upload Support: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";
echo "   Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   Post Max Size: " . ini_get('post_max_size') . "\n";
echo "   Max File Uploads: " . ini_get('max_file_uploads') . "\n\n";

// Test 2: Check Required Extensions
echo "2. Required Extensions Check\n";
$required_extensions = ['json', 'mysqli', 'fileinfo'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext extension loaded\n";
    } else {
        echo "   ✗ $ext extension missing\n";
    }
}
echo "\n";

// Test 3: Check Upload Directories
echo "3. Upload Directories Check\n";
$photoDir = 'Admin/uploads/photos/';
$resumeDir = 'Admin/uploads/resumes/';

if (!is_dir('Admin')) {
    echo "   Creating Admin directory...\n";
    mkdir('Admin', 0755, true);
}

if (!is_dir('Admin/uploads')) {
    echo "   Creating uploads directory...\n";
    mkdir('Admin/uploads', 0755, true);
}

if (!is_dir($photoDir)) {
    echo "   Creating photos directory...\n";
    mkdir($photoDir, 0755, true);
}

if (!is_dir($resumeDir)) {
    echo "   Creating resumes directory...\n";
    mkdir($resumeDir, 0755, true);
}

if (is_dir($photoDir)) {
    echo "   ✓ Photos directory: $photoDir\n";
    if (is_writable($photoDir)) {
        echo "     ✓ Writable\n";
    } else {
        echo "     ✗ Not writable\n";
    }
} else {
    echo "   ✗ Photos directory creation failed\n";
}

if (is_dir($resumeDir)) {
    echo "   ✓ Resumes directory: $resumeDir\n";
    if (is_writable($resumeDir)) {
        echo "     ✓ Writable\n";
    } else {
        echo "     ✗ Not writable\n";
    }
} else {
    echo "   ✗ Resumes directory creation failed\n";
}
echo "\n";

// Test 4: Database Connection
echo "4. Database Connection Test\n";
try {
    include 'db_config.php';
    echo "   ✓ Database connection successful\n";
    
    // Check table structure
    $stmt = $conn->prepare("DESCRIBE job_applications");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $required_columns = ['photo_path', 'resume_path', 'job_id', 'student_name', 'email'];
    $existing_columns = [];
    
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "     ✓ Column '$col' exists\n";
        } else {
            echo "     ✗ Column '$col' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test File Creation
echo "5. File Creation Test\n";
$testPhotoFile = $photoDir . 'test_photo.txt';
$testResumeFile = $resumeDir . 'test_resume.txt';

if (file_put_contents($testPhotoFile, 'test photo content')) {
    echo "   ✓ Test photo file created\n";
    unlink($testPhotoFile); // Clean up
} else {
    echo "   ✗ Failed to create test photo file\n";
}

if (file_put_contents($testResumeFile, 'test resume content')) {
    echo "   ✓ Test resume file created\n";
    unlink($testResumeFile); // Clean up
} else {
    echo "   ✗ Failed to create test resume file\n";
}
echo "\n";

// Test 6: Test API Endpoint
echo "6. API Endpoint Test\n";
$testData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '1234567890',
    'education' => 'Test Education',
    'experience' => 'Test Experience',
    'skills' => 'Test Skills',
    'job_id' => 999,
    'referral_code' => 'TEST123',
    'photo_data' => base64_encode('test photo data'),
    'photo_name' => 'test_photo.jpg',
    'resume_data' => base64_encode('test resume data'),
    'resume_name' => 'test_resume.pdf',
    'company_name' => 'Test Company',
    'package' => 'Test Package',
    'profile' => 'Test Profile',
    'district' => 'Test District'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/submit_job_application_with_files_updated.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ✗ cURL Error: $error\n";
} else {
    echo "   ✓ API request completed (HTTP $httpCode)\n";
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "     ✓ API response successful\n";
            if (isset($data['data']['photo_path']) && isset($data['data']['resume_path'])) {
                echo "     ✓ File paths returned in response\n";
                
                // Check if files were actually created
                $photoPath = $data['data']['photo_path'];
                $resumePath = $data['data']['resume_path'];
                
                if (file_exists($photoPath)) {
                    echo "       ✓ Photo file exists: $photoPath\n";
                } else {
                    echo "       ✗ Photo file not found: $photoPath\n";
                }
                
                if (file_exists($resumePath)) {
                    echo "       ✓ Resume file exists: $resumePath\n";
                } else {
                    echo "       ✗ Resume file not found: $resumePath\n";
                }
            } else {
                echo "     ✗ File paths missing from response\n";
            }
        } else {
            echo "     ✗ API response indicates failure: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "     ✗ HTTP Error: $httpCode\n";
    }
}
echo "\n";

// Test 7: Check Recent Applications
echo "7. Recent Applications Check\n";
try {
    $stmt = $conn->prepare("
        SELECT id, student_name, photo_path, resume_path, created_at 
        FROM job_applications 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "   Recent applications (last hour):\n";
        while ($row = $result->fetch_assoc()) {
            $photoStatus = !empty($row['photo_path']) ? '✓' : '✗';
            $resumeStatus = !empty($row['resume_path']) ? '✓' : '✗';
            echo "     ID: {$row['id']}, Name: {$row['student_name']}, Photo: {$photoStatus}, Resume: {$resumeStatus}\n";
        }
    } else {
        echo "   No recent applications found\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error checking recent applications: " . $e->getMessage() . "\n";
}

echo "\n==========================================\n";
echo "TEST COMPLETED\n";
echo "==========================================\n";

if (isset($conn)) {
    $conn->close();
}
?> 