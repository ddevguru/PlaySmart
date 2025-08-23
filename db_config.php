<?php
// Database configuration for PlaySmart app
// Update these values according to your server setup

define('DB_HOST', 'localhost');
define('DB_NAME', 'playsmart_db');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
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
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
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
?> 