<?php
// Test database connection
header('Content-Type: text/html');

echo "<h1>Database Connection Test</h1>";

try {
    echo "<h2>Loading database config...</h2>";
    require_once 'db_config.php';
    
    echo "<p>✅ db_config.php loaded successfully</p>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
    echo "<p>DB_USERNAME: " . DB_USERNAME . "</p>";
    echo "<p>DB_PASSWORD: " . (DB_PASSWORD ? '***SET***' : 'NOT SET') . "</p>";
    
    echo "<h2>Testing database connection...</h2>";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Test a simple query
    echo "<h2>Testing basic query...</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_applications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Query successful: Found " . $result['count'] . " job applications</p>";
    
    // Test table structure
    echo "<h2>Testing table structure...</h2>";
    $stmt = $pdo->query("DESCRIBE job_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Table structure retrieved successfully</p>";
    echo "<h3>Columns in job_applications table:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>" . $column['Field'] . "</strong> - " . $column['Type'] . " (" . $column['Null'] . ")</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
?> 