<?php
// Setup Upload Directories Script
// This script creates the necessary upload directories and sets proper permissions

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Setting Up Upload Directories</h1>";
echo "<hr>";

try {
    // Define upload directories
    $photoDir = 'Admin/uploads/photos';
    $resumeDir = 'Admin/uploads/resumes';
    
    echo "<h2>1. Creating Photo Upload Directory</h2>";
    if (!file_exists($photoDir)) {
        if (mkdir($photoDir, 0755, true)) {
            echo "<p style='color: green;'>✓ Created directory: $photoDir</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create directory: $photoDir</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Directory already exists: $photoDir</p>";
    }
    
    echo "<h2>2. Creating Resume Upload Directory</h2>";
    if (!file_exists($resumeDir)) {
        if (mkdir($resumeDir, 0755, true)) {
            echo "<p style='color: green;'>✓ Created directory: $resumeDir</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create directory: $resumeDir</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Directory already exists: $resumeDir</p>";
    }
    
    echo "<h2>3. Setting Directory Permissions</h2>";
    
    // Set permissions for photo directory
    if (file_exists($photoDir)) {
        if (chmod($photoDir, 0755)) {
            echo "<p style='color: green;'>✓ Set permissions for photos directory</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not set permissions for photos directory</p>";
        }
    }
    
    // Set permissions for resume directory
    if (file_exists($resumeDir)) {
        if (chmod($resumeDir, 0755)) {
            echo "<p style='color: green;'>✓ Set permissions for resumes directory</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not set permissions for resumes directory</p>";
        }
    }
    
    echo "<h2>4. Creating .htaccess for Security</h2>";
    
    // Create .htaccess for photos directory to allow only images
    $photoHtaccess = $photoDir . '/.htaccess';
    if (!file_exists($photoHtaccess)) {
        $htaccessContent = "Order Deny,Allow\nDeny from all\n<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\nAllow from all\n</FilesMatch>";
        if (file_put_contents($photoHtaccess, $htaccessContent)) {
            echo "<p style='color: green;'>✓ Created .htaccess for photos directory</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not create .htaccess for photos directory</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ .htaccess already exists for photos directory</p>";
    }
    
    // Create .htaccess for resumes directory to allow only documents
    $resumeHtaccess = $resumeDir . '/.htaccess';
    if (!file_exists($resumeHtaccess)) {
        $htaccessContent = "Order Deny,Allow\nDeny from all\n<FilesMatch \"\\.(pdf|doc|docx|txt)$\">\nAllow from all\n</FilesMatch>";
        if (file_put_contents($resumeHtaccess, $htaccessContent)) {
            echo "<p style='color: green;'>✓ Created .htaccess for resumes directory</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not create .htaccess for resumes directory</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ .htaccess already exists for resumes directory</p>";
    }
    
    echo "<h2>5. Testing Directory Access</h2>";
    
    // Test if directories are writable
    if (is_writable($photoDir)) {
        echo "<p style='color: green;'>✓ Photos directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Photos directory is not writable</p>";
    }
    
    if (is_writable($resumeDir)) {
        echo "<p style='color: green;'>✓ Resumes directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Resumes directory is not writable</p>";
    }
    
    echo "<h2>6. Directory Structure</h2>";
    echo "<p>Your upload directories are now set up:</p>";
    echo "<ul>";
    echo "<li><strong>Photos:</strong> $photoDir</li>";
    echo "<li><strong>Resumes:</strong> $resumeDir</li>";
    echo "</ul>";
    
    echo "<p>Files will be automatically uploaded to these directories when users submit job applications.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Setup completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 