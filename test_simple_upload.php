<?php
// Simple test script for file upload
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing File Upload...\n";

// Test 1: Check if directories exist
$photoDir = 'Admin/uploads/photos/';
$resumeDir = 'Admin/uploads/resumes/';

echo "1. Checking directories...\n";
if (is_dir($photoDir)) {
    echo "   ✓ Photos directory exists\n";
} else {
    echo "   ✗ Photos directory missing\n";
}

if (is_dir($resumeDir)) {
    echo "   ✓ Resumes directory exists\n";
} else {
    echo "   ✗ Resumes directory missing\n";
}

// Test 2: Check write permissions
echo "\n2. Checking write permissions...\n";
if (is_writable($photoDir)) {
    echo "   ✓ Photos directory writable\n";
} else {
    echo "   ✗ Photos directory not writable\n";
}

if (is_writable($resumeDir)) {
    echo "   ✓ Resumes directory writable\n";
} else {
    echo "   ✗ Resumes directory not writable\n";
}

// Test 3: Test file creation
echo "\n3. Testing file creation...\n";
$testPhotoFile = $photoDir . 'test_' . time() . '.txt';
$testResumeFile = $resumeDir . 'test_' . time() . '.txt';

if (file_put_contents($testPhotoFile, 'test photo content')) {
    echo "   ✓ Test photo file created: $testPhotoFile\n";
    unlink($testPhotoFile); // Clean up
} else {
    echo "   ✗ Failed to create test photo file\n";
}

if (file_put_contents($testResumeFile, 'test resume content')) {
    echo "   ✓ Test resume file created: $testResumeFile\n";
    unlink($testResumeFile); // Clean up
} else {
    echo "   ✗ Failed to create test resume file\n";
}

// Test 4: Test base64 encoding/decoding
echo "\n4. Testing base64 encoding/decoding...\n";
$testData = 'Hello World!';
$encoded = base64_encode($testData);
$decoded = base64_decode($encoded);

if ($decoded === $testData) {
    echo "   ✓ Base64 encoding/decoding works\n";
} else {
    echo "   ✗ Base64 encoding/decoding failed\n";
}

// Test 5: Test the actual upload script
echo "\n5. Testing upload script...\n";
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

// Simulate the upload process
try {
    // Include the database config
    include 'newcon.php';
    
    // Create directories
    if (!is_dir($photoDir)) {
        mkdir($photoDir, 0755, true);
    }
    if (!is_dir($resumeDir)) {
        mkdir($resumeDir, 0755, true);
    }
    
    // Process photo
    $photoData = $testData['photo_data'];
    $photoName = $testData['photo_name'];
    
    if (!empty($photoData) && !empty($photoName)) {
        $decodedPhotoData = base64_decode($photoData);
        $photoExt = pathinfo($photoName, PATHINFO_EXTENSION);
        if (empty($photoExt)) $photoExt = 'jpg';
        
        $photoFileName = 'photo_' . time() . '_' . $testData['name'] . '.' . $photoExt;
        $photoPath = $photoDir . $photoFileName;
        
        if (file_put_contents($photoPath, $decodedPhotoData)) {
            echo "   ✓ Test photo uploaded: $photoPath\n";
            unlink($photoPath); // Clean up
        } else {
            echo "   ✗ Failed to upload test photo\n";
        }
    }
    
    // Process resume
    $resumeData = $testData['resume_data'];
    $resumeName = $testData['resume_name'];
    
    if (!empty($resumeData) && !empty($resumeName)) {
        $decodedResumeData = base64_decode($resumeData);
        $resumeExt = pathinfo($resumeName, PATHINFO_EXTENSION);
        if (empty($resumeExt)) $resumeExt = 'pdf';
        
        $resumeFileName = 'resume_' . time() . '_' . $testData['name'] . '.' . $resumeExt;
        $resumePath = $resumeDir . $resumeFileName;
        
        if (file_put_contents($resumePath, $decodedResumeData)) {
            echo "   ✓ Test resume uploaded: $resumePath\n";
            unlink($resumePath); // Clean up
        } else {
            echo "   ✗ Failed to upload test resume\n";
        }
    }
    
    echo "   ✓ Upload script test completed successfully\n";
    
} catch (Exception $e) {
    echo "   ✗ Upload script test failed: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
?> 