<?php
// Database configuration for PlaySmart app
// This file provides database connection functions

// Database credentials - Define as constants to match process_payment.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u968643667_playsmart'); // Your actual database name
define('DB_USERNAME', 'u968643667_playsmart'); // Your actual username
define('DB_PASSWORD', 'your_actual_password'); // Replace with your actual password
define('DB_CHARSET', 'utf8mb4');

// Database connection function using PDO
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
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
    try {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset(DB_CHARSET);
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