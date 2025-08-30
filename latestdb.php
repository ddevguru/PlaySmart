<?php
// Database configuration for PlaySmart app
// Update these values according to your server setup

// Database credentials for your hosting server
$host = 'localhost';
$username = 'u968643667_playsmart'; // Your actual database username from logs
$password = 'Playsmart@123'; // Replace with your actual database password
$database = 'u968643667_playsmart'; // Your actual database name from logs

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+05:30'");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials for your hosting server (keeping for backward compatibility)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u968643667_playsmart'); // Your actual database name from logs
define('DB_USERNAME', 'u968643667_playsmart'); // Your actual database username from logs
define('DB_PASSWORD', 'Playsmart@123'); // Replace with your actual database password
define('DB_CHARSET', 'utf8mb4');

// Base URL for the application
define('BASE_URL', 'https://playsmart.co.in');

// File upload paths
define('UPLOAD_DIR', 'images/company_logos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed file types for company logos
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Database connection function
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

// Helper function to validate file uploads
function validateFileUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    return true;
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

// Helper function to check if database is accessible
function checkDatabaseConnection() {
    try {
        $pdo = getDBConnection();
        return ['success' => true, 'message' => 'Database connection successful'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Test database connection (JSON output only)
if (isset($_GET['test_connection'])) {
    header('Content-Type: application/json');
    try {
        $pdo = getDBConnection();
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'data' => [
                'host' => DB_HOST,
                'database' => DB_NAME,
                'username' => DB_USERNAME,
                'charset' => DB_CHARSET,
                'php_version' => PHP_VERSION,
                'pdo_drivers' => PDO::getAvailableDrivers()
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
    }
    exit;
}
?> 