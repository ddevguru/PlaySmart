<?php
// Test Database Connection Script
// This script tests if the database connection is working

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

try {
    // Test 1: Include the configuration file
    echo "<h2>1. Testing Configuration File</h2>";
    require_once 'newcon.php';
    echo "<p style='color: green;'>✓ Configuration file loaded successfully</p>";
    
    // Test 2: Test database connection
    echo "<h2>2. Testing Database Connection</h2>";
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test 3: Test a simple query
    echo "<h2>3. Testing Database Query</h2>";
    $stmt = $pdo->query('SELECT 1 as test');
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "<p style='color: green;'>✓ Database query working</p>";
    }
    
    // Test 4: Check if job_applications table exists
    echo "<h2>4. Testing Job Applications Table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ job_applications table exists</p>";
        
        // Test 5: Check table structure
        echo "<h2>5. Testing Table Structure</h2>";
        $stmt = $pdo->query("DESCRIBE job_applications");
        $columns = $stmt->fetchAll();
        echo "<p style='color: green;'>✓ Table structure retrieved</p>";
        echo "<p>Columns found: " . count($columns) . "</p>";
        
        // Show column names
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>✗ job_applications table does not exist</p>";
        echo "<p>You need to create this table first.</p>";
    }
    
    // Test 6: Check if user_sessions table exists
    echo "<h2>6. Testing User Sessions Table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ user_sessions table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ user_sessions table does not exist</p>";
        echo "<p>This table is needed for session management.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Debug Info:</strong></p>";
    echo "<ul>";
    echo "<li>File: " . $e->getFile() . "</li>";
    echo "<li>Line: " . $e->getLine() . "</li>";
    echo "<li>Trace: " . $e->getTraceAsString() . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Make sure your database credentials are correct in newcon.php</li>";
echo "<li>Create the job_applications table if it doesn't exist</li>";
echo "<li>Create the user_sessions table if it doesn't exist</li>";
echo "<li>Test the job application submission</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 