<?php
// Test File Upload Script
// This script tests the file upload functionality

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test File Upload Functionality</h1>";
echo "<hr>";

try {
    // Test 1: Check if upload directories exist
    echo "<h2>1. Checking Upload Directories</h2>";
    
    $photoDir = 'Admin/uploads/photos';
    $resumeDir = 'Admin/uploads/resumes';
    
    if (file_exists($photoDir)) {
        echo "<p style='color: green;'>✓ Photos directory exists: $photoDir</p>";
        if (is_writable($photoDir)) {
            echo "<p style='color: green;'>✓ Photos directory is writable</p>";
        } else {
            echo "<p style='color: red;'>✗ Photos directory is not writable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Photos directory does not exist: $photoDir</p>";
    }
    
    if (file_exists($resumeDir)) {
        echo "<p style='color: green;'>✓ Resumes directory exists: $resumeDir</p>";
        if (is_writable($resumeDir)) {
            echo "<p style='color: green;'>✓ Resumes directory is writable</p>";
        } else {
            echo "<p style='color: red;'>✗ Resumes directory is not writable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Resumes directory does not exist: $resumeDir</p>";
    }
    
    // Test 2: Test file upload with sample data
    echo "<h2>2. Testing File Upload</h2>";
    
    // Create sample photo data (1x1 pixel PNG)
    $samplePhotoData = base64_encode(file_get_contents('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    
    // Create sample resume data (empty PDF)
    $sampleResumeData = base64_encode('%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
>>
endobj
xref
0 4
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000111 00000 n 
trailer
<<
/Size 4
/Root 1 0 R
>>
startxref
149
%%EOF');
    
    $uploadData = [
        'photo_data' => $samplePhotoData,
        'photo_name' => 'test_photo.png',
        'resume_data' => $sampleResumeData,
        'resume_name' => 'test_resume.pdf'
    ];
    
    echo "<p>Testing upload with sample photo and resume data...</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/upload_files.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($uploadData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>✗ cURL Error: $error</p>";
    } else {
        echo "<p style='color: green;'>✓ cURL request completed</p>";
        echo "<p>HTTP Status Code: $httpCode</p>";
        echo "<p>Response: $response</p>";
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                echo "<p style='color: green;'>✓ File upload successful!</p>";
                
                if (isset($data['files']['photo'])) {
                    echo "<p><strong>Photo:</strong></p>";
                    echo "<ul>";
                    echo "<li>Path: " . $data['files']['photo']['path'] . "</li>";
                    echo "<li>URL: " . $data['files']['photo']['url'] . "</li>";
                    echo "<li>Filename: " . $data['files']['photo']['filename'] . "</li>";
                    echo "</ul>";
                }
                
                if (isset($data['files']['resume'])) {
                    echo "<p><strong>Resume:</strong></p>";
                    echo "<ul>";
                    echo "<li>Path: " . $data['files']['resume']['path'] . "</li>";
                    echo "<li>URL: " . $data['files']['resume']['url'] . "</li>";
                    echo "<li>Filename: " . $data['files']['resume']['filename'] . "</li>";
                    echo "</ul>";
                }
                
                // Test 3: Verify files were actually created
                echo "<h2>3. Verifying Uploaded Files</h2>";
                
                if (isset($data['files']['photo']['path'])) {
                    $photoPath = $data['files']['photo']['path'];
                    if (file_exists($photoPath)) {
                        echo "<p style='color: green;'>✓ Photo file exists: $photoPath</p>";
                        echo "<p>File size: " . filesize($photoPath) . " bytes</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Photo file not found: $photoPath</p>";
                    }
                }
                
                if (isset($data['files']['resume']['path'])) {
                    $resumePath = $data['files']['resume']['path'];
                    if (file_exists($resumePath)) {
                        echo "<p style='color: green;'>✓ Resume file exists: $resumePath</p>";
                        echo "<p>File size: " . filesize($resumePath) . " bytes</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Resume file not found: $resumePath</p>";
                    }
                }
                
            } else {
                echo "<p style='color: red;'>✗ File upload failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ HTTP Error: $httpCode</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Summary</h2>";
echo "<p>This test verifies:</p>";
echo "<ol>";
echo "<li>Upload directories exist and are writable</li>";
echo "<li>File upload API is working</li>";
echo "<li>Files are actually saved to the correct locations</li>";
echo "</ol>";

echo "<p><strong>Expected Result:</strong></p>";
echo "<ul>";
echo "<li>Photos will be stored in: <code>Admin/uploads/photos/</code></li>";
echo "<li>Resumes will be stored in: <code>Admin/uploads/resumes/</code></li>";
echo "<li>Files will have unique names with timestamps</li>";
echo "<li>Files will be accessible via URLs</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 