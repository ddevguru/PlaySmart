<?php
// Script to create upload directories for job applications

echo "Setting up upload directories...\n";

// Create main Admin directory if it doesn't exist
if (!is_dir('Admin')) {
    if (mkdir('Admin', 0755, true)) {
        echo "Created Admin directory\n";
    } else {
        echo "Failed to create Admin directory\n";
    }
}

// Create photos directory
$photoDir = 'Admin/uploads/photos/';
if (!is_dir($photoDir)) {
    if (mkdir($photoDir, 0755, true)) {
        echo "Created photos directory: $photoDir\n";
    } else {
        echo "Failed to create photos directory: $photoDir\n";
    }
} else {
    echo "Photos directory already exists: $photoDir\n";
}

// Create resumes directory
$resumeDir = 'Admin/uploads/resumes/';
if (!is_dir($resumeDir)) {
    if (mkdir($resumeDir, 0755, true)) {
        echo "Created resumes directory: $resumeDir\n";
    } else {
        echo "Failed to create resumes directory: $resumeDir\n";
    }
} else {
    echo "Resumes directory already exists: $resumeDir\n";
}

// Create .htaccess file to protect uploads directory
$htaccessContent = "Options -Indexes\n";
$htaccessContent .= "RewriteEngine On\n";
$htaccessContent .= "RewriteCond %{REQUEST_FILENAME} -f\n";
$htaccessContent .= "RewriteRule ^(.*)$ $1 [L]\n";

$htaccessFile = 'Admin/uploads/.htaccess';
if (!file_exists($htaccessFile)) {
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "Created .htaccess file for uploads directory\n";
    } else {
        echo "Failed to create .htaccess file\n";
    }
} else {
    echo ".htaccess file already exists\n";
}

// Test write permissions
$testFile = $photoDir . 'test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "Write permission test passed for photos directory\n";
    unlink($testFile); // Clean up test file
} else {
    echo "Write permission test failed for photos directory\n";
}

$testFile = $resumeDir . 'test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "Write permission test passed for resumes directory\n";
    unlink($testFile); // Clean up test file
} else {
    echo "Write permission test failed for resumes directory\n";
}

echo "\nUpload directories setup completed!\n";
echo "Photos directory: $photoDir\n";
echo "Resumes directory: $resumeDir\n";
?> 