<?php
// Test API connection and file upload functionality
header('Content-Type: text/html');

echo "<h1>API Connection Test</h1>";

// Test 1: Check if file exists
echo "<h2>Test 1: File Existence</h2>";
if (file_exists('submit_job_application_with_files.php')) {
    echo "✅ submit_job_application_with_files.php exists<br>";
} else {
    echo "❌ submit_job_application_with_files.php not found<br>";
}

// Test 2: Check database config
echo "<h2>Test 2: Database Configuration</h2>";
if (file_exists('db_config.php')) {
    echo "✅ db_config.php exists<br>";
    include_once 'db_config.php';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Database connection successful<br>";
        
        // Test table existence
        $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
        if ($stmt->rowCount() > 0) {
            echo "✅ job_applications table exists<br>";
        } else {
            echo "❌ job_applications table not found<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db_config.php not found<br>";
}

// Test 3: Check directory permissions
echo "<h2>Test 3: Directory Permissions</h2>";
$uploadDirs = ['Admin/uploads/photos', 'Admin/uploads/resumes'];
foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory exists: $dir<br>";
        if (is_writable($dir)) {
            echo "✅ Directory writable: $dir<br>";
        } else {
            echo "❌ Directory not writable: $dir<br>";
        }
    } else {
        echo "❌ Directory not found: $dir<br>";
        // Try to create it
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir<br>";
        } else {
            echo "❌ Failed to create directory: $dir<br>";
        }
    }
}

// Test 4: Check debug log
echo "<h2>Test 4: Debug Log</h2>";
if (file_exists('debug_log.txt')) {
    echo "✅ debug_log.txt exists<br>";
    $logContent = file_get_contents('debug_log.txt');
    if (strlen($logContent) > 0) {
        echo "✅ debug_log.txt has content (" . strlen($logContent) . " bytes)<br>";
        echo "<h3>Last 10 lines of log:</h3>";
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -10);
        echo "<pre>" . implode("\n", $lastLines) . "</pre>";
    } else {
        echo "⚠️ debug_log.txt is empty<br>";
    }
} else {
    echo "❌ debug_log.txt not found<br>";
}

// Test 5: Simple POST test
echo "<h2>Test 5: Simple POST Test</h2>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='text' name='test_field' value='test_value'><br>";
echo "<input type='file' name='test_file'><br>";
echo "<button type='submit'>Test POST</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    echo "POST: " . print_r($_POST, true) . "\n";
    echo "FILES: " . print_r($_FILES, true) . "\n";
    echo "</pre>";
}

echo "<hr>";
echo "<p><strong>Server Info:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Upload Max Filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>Post Max Size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>Max File Uploads:</strong> " . ini_get('max_file_uploads') . "</p>";
?> 