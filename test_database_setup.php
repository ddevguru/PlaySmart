<?php
// Test Database Setup Script
// This script tests the database connection and table structure

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Database Setup Test</h1>";
echo "<hr>";

try {
    // Test 1: Include database configuration
    echo "<h2>1. Testing Database Configuration</h2>";
    require_once 'newcon.php';
    echo "<p style='color: green;'>✓ Database configuration loaded</p>";
    
    // Test 2: Test database connection
    echo "<h2>2. Testing Database Connection</h2>";
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test 3: Check if job_applications table exists
    echo "<h2>3. Checking job_applications Table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ job_applications table exists</p>";
        
        // Test 4: Check table structure
        echo "<h2>4. Checking Table Structure</h2>";
        $stmt = $pdo->query("DESCRIBE job_applications");
        $columns = $stmt->fetchAll();
        echo "<p>Columns found: " . count($columns) . "</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test 5: Check if application_version column exists
        echo "<h2>5. Checking application_version Column</h2>";
        $hasVersionColumn = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'application_version') {
                $hasVersionColumn = true;
                break;
            }
        }
        
        if ($hasVersionColumn) {
            echo "<p style='color: green;'>✓ application_version column exists</p>";
        } else {
            echo "<p style='color: red;'>✗ application_version column missing</p>";
            echo "<p>You need to run the SQL script to add this column.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ job_applications table does not exist</p>";
        echo "<p>You need to create this table first.</p>";
    }
    
    // Test 6: Test sample query
    echo "<h2>6. Testing Sample Query</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM job_applications");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✓ Sample query successful</p>";
        echo "<p>Total applications in database: " . $result['total'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Sample query failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";

if (isset($hasVersionColumn) && !$hasVersionColumn) {
    echo "<p><strong>1. Add application_version column:</strong></p>";
    echo "<p>Run this SQL in your database:</p>";
    echo "<pre>";
    echo "ALTER TABLE `job_applications` \n";
    echo "ADD COLUMN IF NOT EXISTS `application_version` int(11) NOT NULL DEFAULT 1 AFTER `is_active`;\n\n";
    echo "CREATE INDEX IF NOT EXISTS `idx_job_applications_version` \n";
    echo "ON `job_applications` (`email`, `job_id`, `application_version`);";
    echo "</pre>";
}

echo "<p><strong>2. Test the form submission:</strong></p>";
echo "<p>Use the test script: <code>test_multiple_applications.php</code></p>";

echo "<p><strong>3. Check the logs:</strong></p>";
echo "<p>View <code>job_application_log.txt</code> for detailed information</p>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 