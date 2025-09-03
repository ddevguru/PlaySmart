<?php
// Setup Content Headings Script
// This script creates the content_headings table and inserts the required data

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Setting Up Content Headings</h1>";
echo "<hr>";

try {
    // Database configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u968643667_playsmart');
    define('DB_PASS', 'Playsmart@123');
    define('DB_NAME', 'u968643667_playsmart');
    
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Set charset to UTF-8
    $conn->set_charset('utf8mb4');
    
    // Check if content_headings table exists
    echo "<h2>1. Checking Content Headings Table</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'content_headings'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ content_headings table already exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ content_headings table does not exist, creating it...</p>";
        
        // Create the table
        $createTable = "CREATE TABLE `content_headings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `section_name` varchar(100) NOT NULL,
            `heading_text` varchar(255) NOT NULL,
            `sub_heading_text` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `section_name` (`section_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($createTable)) {
            echo "<p style='color: green;'>✓ content_headings table created successfully</p>";
        } else {
            throw new Exception('Failed to create table: ' . $conn->error);
        }
    }
    
    // Check if successful_candidates section exists
    echo "<h2>2. Checking Successful Candidates Section</h2>";
    $stmt = $conn->prepare("SELECT * FROM content_headings WHERE section_name = 'successful_candidates'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        echo "<p style='color: green;'>✓ successful_candidates section already exists</p>";
        echo "<p><strong>Current heading:</strong> {$existing['heading_text']}</p>";
        echo "<p><strong>Current sub-heading:</strong> {$existing['sub_heading_text']}</p>";
    } else {
        echo "<p style='color: orange;'>⚠ successful_candidates section does not exist, creating it...</p>";
        
        // Insert the section
        $insertStmt = $conn->prepare("INSERT INTO content_headings (section_name, heading_text, sub_heading_text, is_active) VALUES (?, ?, ?, 1)");
        $heading = 'Our Successfully Placed';
        $subHeading = 'Candidates';
        
        $insertStmt->bind_param("sss", $heading, $subHeading, $subHeading);
        
        if ($insertStmt->execute()) {
            echo "<p style='color: green;'>✓ successful_candidates section created successfully</p>";
            echo "<p><strong>Heading:</strong> $heading</p>";
            echo "<p><strong>Sub-heading:</strong> $subHeading</p>";
        } else {
            throw new Exception('Failed to insert section: ' . $insertStmt->error);
        }
        
        $insertStmt->close();
    }
    
    // Test the fetch_content_headings.php endpoint
    echo "<h2>3. Testing API Endpoint</h2>";
    
    // Simulate the API call
    $testUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/fetch_content_headings.php?section=successful_candidates';
    echo "<p><strong>API URL:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    
    // Test the query directly
    $testStmt = $conn->prepare("SELECT heading_text, sub_heading_text FROM content_headings WHERE section_name = 'successful_candidates' AND is_active = 1");
    $testStmt->execute();
    $testResult = $testStmt->get_result();
    $testData = $testResult->fetch_assoc();
    
    if ($testData) {
        echo "<p style='color: green;'>✓ API query test successful</p>";
        echo "<p><strong>Test result:</strong></p>";
        echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ API query test failed</p>";
    }
    
    $testStmt->close();
    
    echo "<h2>4. Summary</h2>";
    echo "<p>Your content headings are now set up and ready to use!</p>";
    echo "<ul>";
    echo "<li><strong>Table:</strong> content_headings</li>";
    echo "<li><strong>Section:</strong> successful_candidates</li>";
    echo "<li><strong>API Endpoint:</strong> fetch_content_headings.php</li>";
    echo "</ul>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup error: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><em>Setup completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 