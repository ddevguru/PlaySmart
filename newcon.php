<?php
// Database configuration for PlaySmart app
// This file provides database connection functions

// Database credentials
$host = 'localhost';
$dbname = 'u968643667_playsmart'; // Your actual database name
$username = 'u968643667_playsmart'; // Your actual username
$password = 'your_actual_password'; // Replace with your actual password
$charset = 'utf8mb4';

// Database connection function using PDO
function getDBConnection() {
    global $host, $dbname, $username, $password, $charset;
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Test the connection
        $pdo->query('SELECT 1');
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Alternative database connection using mysqli (for backward compatibility)
function getDBConnectionMysqli() {
    global $host, $username, $password, $dbname, $charset;
    
    try {
        $conn = new mysqli($host, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset($charset);
        return $conn;
    } catch (Exception $e) {
        error_log("MySQLi connection failed: " . $e->getMessage());
        throw new Exception("MySQLi connection failed: " . $e->getMessage());
    }
}

// Function to check database connection
function checkDatabaseConnection() {
    try {
        $pdo = getDBConnection();
        return ['success' => true, 'message' => 'Database connection successful'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Helper function to sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to generate response
function generateResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    return json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Test database connection
if (isset($_GET['test_connection'])) {
    try {
        $pdo = getDBConnection();
        echo "Database connection successful!";
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage();
    }
    exit;
}
?> 