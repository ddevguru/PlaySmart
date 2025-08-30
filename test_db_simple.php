<?php
// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...<br>";

// Check if db_config.php exists
if (!file_exists('db_config.php')) {
    die("db_config.php file not found!");
}

echo "db_config.php found<br>";

// Include the config
require_once 'db_config.php';

echo "db_config.php included<br>";

// Check if getDBConnection function exists
if (!function_exists('getDBConnection')) {
    die("getDBConnection function not found in db_config.php!");
}

echo "getDBConnection function found<br>";

try {
    echo "Attempting database connection...<br>";
    $pdo = getDBConnection();
    echo "✅ Database connection successful!<br>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Basic query test passed<br>";
    
    // Test the content_headings table
    echo "Testing content_headings table...<br>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_headings");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ content_headings table has {$count['count']} rows<br>";
    
    // Test the specific query
    $stmt = $pdo->prepare("SELECT * FROM content_headings WHERE section_name = 'successful_candidates'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Found heading data:<br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "⚠️ No heading data found for 'successful_candidates'<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 