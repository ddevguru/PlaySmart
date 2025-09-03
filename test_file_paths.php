<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>File Path Testing</h1>";
echo "<hr>";

// Test different possible paths
$testPaths = [
    'uploads/photos/',
    'Admin/uploads/photos/',
    'uploads/resumes/',
    'Admin/uploads/resumes/',
    './uploads/photos/',
    './Admin/uploads/photos/',
    './uploads/resumes/',
    './Admin/uploads/resumes/',
    '../uploads/photos/',
    '../Admin/uploads/photos/',
    '../uploads/resumes/',
    '../Admin/uploads/resumes/'
];

echo "<h2>1. Directory Path Testing</h2>";
foreach ($testPaths as $path) {
    if (is_dir($path)) {
        echo "<p style='color: green;'>✅ Directory exists: $path</p>";
        
        // Count files in directory
        $files = glob($path . '*');
        echo "<p style='color: blue;'>📁 Files found: " . count($files) . "</p>";
        
        // Show first few files
        if (count($files) > 0) {
            echo "<ul>";
            foreach (array_slice($files, 0, 3) as $file) {
                $size = file_exists($file) ? number_format(filesize($file)) . ' bytes' : 'File not accessible';
                echo "<li>" . basename($file) . " ($size)</li>";
            }
            if (count($files) > 3) {
                echo "<li>... and " . (count($files) - 3) . " more files</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Directory not found: $path</p>";
    }
}

echo "<h2>2. Current Working Directory</h2>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Script Location:</strong> " . __FILE__ . "</p>";

echo "<h2>3. File Path Resolution Test</h2>";
$testFiles = [
    'photo_1756533780_Deepak Mishra.jpg',
    'resume_1756533780_Deepak Mishra.pdf'
];

foreach ($testFiles as $filename) {
    echo "<h3>Testing file: $filename</h3>";
    
    $possiblePaths = [
        $filename,
        'uploads/photos/' . $filename,
        'Admin/uploads/photos/' . $filename,
        'uploads/resumes/' . $filename,
        'Admin/uploads/resumes/' . $filename,
        './uploads/photos/' . $filename,
        './Admin/uploads/photos/' . $filename,
        './uploads/resumes/' . $filename,
        './Admin/uploads/resumes/' . $filename
    ];
    
    $found = false;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            echo "<p style='color: green;'>✅ File found at: $path</p>";
            echo "<p>File size: " . number_format(filesize($path)) . " bytes</p>";
            echo "<p>Full path: " . realpath($path) . "</p>";
            $found = true;
            break;
        } else {
            echo "<p style='color: red;'>❌ Not found: $path</p>";
        }
    }
    
    if (!$found) {
        echo "<p style='color: orange;'>⚠️ File not found in any tested path</p>";
    }
}

echo "<h2>4. Database Path Check</h2>";
// Try to connect to database and check actual paths
try {
    $host = 'localhost';
    $username = 'u968643667_playsmart';
    $password = 'Playsmart@123';
    $database = 'u968643667_playsmart';
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Database connected successfully</p>";
        
        // Get a few applications with file paths
        $query = "SELECT id, student_name, photo_path, resume_path FROM job_applications WHERE (photo_path IS NOT NULL AND photo_path != '') OR (resume_path IS NOT NULL AND resume_path != '') LIMIT 5";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo "<h3>Applications with File Paths:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Photo Path</th><th>Resume Path</th><th>Photo Exists</th><th>Resume Exists</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $photoExists = !empty($row['photo_path']) && file_exists($row['photo_path']) ? 'YES' : 'NO';
                $resumeExists = !empty($row['resume_path']) && file_exists($row['resume_path']) ? 'YES' : 'NO';
                
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['photo_path'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['resume_path'] ?? 'NULL') . "</td>";
                echo "<td style='color: " . ($photoExists === 'YES' ? 'green' : 'red') . ";'>" . $photoExists . "</td>";
                echo "<td style='color: " . ($resumeExists === 'YES' ? 'green' : 'red') . ";'>" . $resumeExists . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No applications with file paths found</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Recommendations</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;'>";
echo "<h3 style='color: #1976d2;'>🔧 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check the output above</strong> to see which directories exist</li>";
echo "<li><strong>Update the file paths</strong> in your database if needed</li>";
echo "<li><strong>Use the working directory paths</strong> in your code</li>";
echo "<li><strong>Test file access</strong> with the correct paths</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 