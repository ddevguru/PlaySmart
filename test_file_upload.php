<?php
// Test script for file upload functionality

echo "Testing File Upload Functionality\n";
echo "==================================\n\n";

// Test 1: Check if directories exist
echo "1. Checking upload directories...\n";
$photoDir = 'Admin/uploads/photos/';
$resumeDir = 'Admin/uploads/resumes/';

if (is_dir($photoDir)) {
    echo "   ✓ Photos directory exists: $photoDir\n";
} else {
    echo "   ✗ Photos directory missing: $photoDir\n";
}

if (is_dir($resumeDir)) {
    echo "   ✓ Resumes directory exists: $resumeDir\n";
} else {
    echo "   ✗ Resumes directory missing: $resumeDir\n";
}

// Test 2: Check directory permissions
echo "\n2. Checking directory permissions...\n";
if (is_writable($photoDir)) {
    echo "   ✓ Photos directory is writable\n";
} else {
    echo "   ✗ Photos directory is not writable\n";
}

if (is_writable($resumeDir)) {
    echo "   ✓ Resumes directory is writable\n";
} else {
    echo "   ✗ Resumes directory is not writable\n";
}

// Test 3: Test file creation
echo "\n3. Testing file creation...\n";
$testPhotoFile = $photoDir . 'test_photo.txt';
$testResumeFile = $resumeDir . 'test_resume.txt';

if (file_put_contents($testPhotoFile, 'test photo content')) {
    echo "   ✓ Test photo file created successfully\n";
    unlink($testPhotoFile); // Clean up
} else {
    echo "   ✗ Failed to create test photo file\n";
}

if (file_put_contents($testResumeFile, 'test resume content')) {
    echo "   ✓ Test resume file created successfully\n";
    unlink($testResumeFile); // Clean up
} else {
    echo "   ✗ Failed to create test resume file\n";
}

// Test 4: Check database connection
echo "\n4. Testing database connection...\n";
try {
    include 'db_config.php';
    echo "   ✓ Database connection successful\n";
    
    // Check if job_applications table has photo_path and resume_path columns
    $stmt = $conn->prepare("DESCRIBE job_applications");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hasPhotoPath = false;
    $hasResumePath = false;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'photo_path') $hasPhotoPath = true;
        if ($row['Field'] === 'resume_path') $hasResumePath = true;
    }
    
    if ($hasPhotoPath) {
        echo "   ✓ photo_path column exists in job_applications table\n";
    } else {
        echo "   ✗ photo_path column missing in job_applications table\n";
    }
    
    if ($hasResumePath) {
        echo "   ✓ resume_path column exists in job_applications table\n";
    } else {
        echo "   ✗ resume_path column missing in job_applications table\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 5: Check recent applications for file paths
echo "\n5. Checking recent job applications...\n";
try {
    $stmt = $conn->prepare("
        SELECT id, student_name, photo_path, resume_path, created_at 
        FROM job_applications 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "   Recent applications:\n";
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

echo "\n==================================\n";
echo "File upload test completed!\n";

if (isset($conn)) {
    $conn->close();
}
?> 