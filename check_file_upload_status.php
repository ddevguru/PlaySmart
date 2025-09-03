<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>File Upload Status Check</h1>";
echo "<hr>";

// Database connection
$host = 'localhost';
$username = 'u968643667_playsmart';
$password = 'Playsmart@123';
$database = 'u968643667_playsmart';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "<h2>1. Database Connection Status</h2>";
echo "<p style='color: green;'>✅ Database connected successfully</p>";

// Check job applications table
echo "<h2>2. Job Applications Analysis</h2>";
$query = "SELECT 
    COUNT(*) as total_applications,
    COUNT(CASE WHEN photo_path IS NOT NULL AND photo_path != '' THEN 1 END) as with_photos,
    COUNT(CASE WHEN resume_path IS NOT NULL AND resume_path != '' THEN 1 END) as with_resumes,
    COUNT(CASE WHEN photo_path IS NOT NULL AND photo_path != '' AND resume_path IS NOT NULL AND resume_path != '' THEN 1 END) as with_both_files
FROM job_applications WHERE is_active = 1";

$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p><strong>Total Applications:</strong> " . $row['total_applications'] . "</p>";
    echo "<p><strong>With Photos:</strong> " . $row['with_photos'] . "</p>";
    echo "<p><strong>With Resumes:</strong> " . $row['with_resumes'] . "</p>";
    echo "<p><strong>With Both Files:</strong> " . $row['with_both_files'] . "</p>";
    
    $noFiles = $row['total_applications'] - $row['with_both_files'];
    echo "<p style='color: red;'><strong>Without Files:</strong> " . $noFiles . "</p>";
}

// Check recent applications
echo "<h2>3. Recent Applications (Last 10)</h2>";
$recentQuery = "SELECT id, student_name, photo_path, resume_path, applied_date 
                FROM job_applications 
                WHERE is_active = 1 
                ORDER BY applied_date DESC 
                LIMIT 10";

$recentResult = $conn->query($recentQuery);
if ($recentResult) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Photo</th><th>Resume</th><th>Date</th></tr>";
    
    while ($row = $recentResult->fetch_assoc()) {
        $photoStatus = !empty($row['photo_path']) ? "✅ " . basename($row['photo_path']) : "❌ NULL";
        $resumeStatus = !empty($row['resume_path']) ? "✅ " . basename($row['resume_path']) : "❌ NULL";
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . $photoStatus . "</td>";
        echo "<td>" . $resumeStatus . "</td>";
        echo "<td>" . $row['applied_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check upload directories
echo "<h2>4. Upload Directories Status</h2>";
$photoDir = 'Admin/uploads/photos/';
$resumeDir = 'Admin/uploads/resumes/';

echo "<h3>Photos Directory:</h3>";
if (is_dir($photoDir)) {
    echo "<p style='color: green;'>✅ Directory exists: $photoDir</p>";
    if (is_writable($photoDir)) {
        echo "<p style='color: green;'>✅ Directory is writable</p>";
    } else {
        echo "<p style='color: red;'>❌ Directory is not writable</p>";
    }
    
    $photoFiles = glob($photoDir . '*');
    echo "<p><strong>Files in photos directory:</strong> " . count($photoFiles) . "</p>";
    
    if (count($photoFiles) > 0) {
        echo "<ul>";
        foreach (array_slice($photoFiles, 0, 5) as $file) {
            echo "<li>" . basename($file) . " (" . number_format(filesize($file)) . " bytes)</li>";
        }
        if (count($photoFiles) > 5) {
            echo "<li>... and " . (count($photoFiles) - 5) . " more files</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ Directory does not exist: $photoDir</p>";
}

echo "<h3>Resumes Directory:</h3>";
if (is_dir($resumeDir)) {
    echo "<p style='color: green;'>✅ Directory exists: $resumeDir</p>";
    if (is_writable($resumeDir)) {
        echo "<p style='color: green;'>✅ Directory is writable</p>";
    } else {
        echo "<p style='color: red;'>❌ Directory is not writable</p>";
    }
    
    $resumeFiles = glob($resumeDir . '*');
    echo "<p><strong>Files in resumes directory:</strong> " . count($resumeFiles) . "</p>";
    
    if (count($resumeFiles) > 0) {
        echo "<ul>";
        foreach (array_slice($resumeFiles, 0, 5) as $file) {
            echo "<li>" . basename($file) . " (" . number_format(filesize($file)) . " bytes)</li>";
        }
        if (count($resumeFiles) > 5) {
            echo "<li>... and " . (count($resumeFiles) - 5) . " more files</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ Directory does not exist: $resumeDir</p>";
}

// Check if the working PHP script exists
echo "<h2>5. PHP Script Status</h2>";
$workingScript = 'submit_job_application_final.php';
if (file_exists($workingScript)) {
    echo "<p style='color: green;'>✅ Working script exists: $workingScript</p>";
    echo "<p><strong>File size:</strong> " . number_format(filesize($workingScript)) . " bytes</p>";
    echo "<p><strong>Last modified:</strong> " . date('Y-m-d H:i:s', filemtime($workingScript)) . "</p>";
} else {
    echo "<p style='color: red;'>❌ Working script missing: $workingScript</p>";
}

// Check for old scripts
$oldScripts = [
    'submit_job_application.php',
    'submit_job_application_new.php',
    'submit_job_application_working.php',
    'submit_job_application_with_files.php',
    'submit_job_application_with_files_updated.php'
];

echo "<h3>Old Scripts Check:</h3>";
foreach ($oldScripts as $script) {
    if (file_exists($script)) {
        echo "<p style='color: orange;'>⚠️ Old script exists: $script</p>";
    } else {
        echo "<p style='color: green;'>✅ Old script removed: $script</p>";
    }
}

// Check upload debug log
echo "<h2>6. Upload Debug Log</h2>";
$logFile = 'upload_debug.log';
if (file_exists($logFile)) {
    echo "<p style='color: green;'>✅ Debug log exists: $logFile</p>";
    echo "<p><strong>File size:</strong> " . number_format(filesize($logFile)) . " bytes</p>";
    echo "<p><strong>Last modified:</strong> " . date('Y-m-d H:i:s', filemtime($logFile)) . "</p>";
    
    // Show last few lines
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $lastLines = array_slice($lines, -10);
    
    echo "<h4>Last 10 log entries:</h4>";
    echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;'>";
    foreach ($lastLines as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "<br>";
        }
    }
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ Debug log missing: $logFile</p>";
}

echo "<h2>7. Recommendations</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;'>";

if ($noFiles > 0) {
    echo "<h3 style='color: #1976d2;'>🚨 IMMEDIATE ACTION REQUIRED:</h3>";
    echo "<p><strong>Problem:</strong> $noFiles out of " . $row['total_applications'] . " applications are missing files!</p>";
    echo "<p><strong>Solution:</strong> Users need to update their Flutter app to use the new working endpoint.</p>";
    echo "<ol>";
    echo "<li><strong>Update your Flutter app</strong> on app stores</li>";
    echo "<li><strong>Force users to update</strong> by showing mandatory update message</li>";
    echo "<li><strong>Remove old PHP scripts</strong> to prevent confusion</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green;'>✅ All applications have files uploaded!</p>";
}

echo "</div>";

$conn->close();
echo "<hr>";
echo "<p><em>Check completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 