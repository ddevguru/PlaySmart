<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// This script forces users to update their app
// It will be called by old app versions that try to use old endpoints

$response = [
    'success' => false,
    'message' => 'APP UPDATE REQUIRED',
    'error_code' => 'APP_UPDATE_REQUIRED',
    'details' => [
        'current_version' => 'OUTDATED',
        'required_version' => '2.0.0',
        'update_message' => 'Your app version is outdated and no longer supported. Please update to the latest version to continue using the job application feature.',
        'update_actions' => [
            'android' => 'Update from Google Play Store',
            'ios' => 'Update from App Store',
            'manual' => 'Download latest APK from our website'
        ],
        'whats_new' => [
            '✅ File upload support for photos and resumes',
            '✅ Improved job application process',
            '✅ Better user experience',
            '✅ Bug fixes and performance improvements'
        ],
        'contact_support' => 'If you have issues updating, contact support at support@playsmart.co.in'
    ],
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => 'PlaySmart Job Application System v2.0'
];

// Log the update request
$logMessage = "[" . date('Y-m-d H:i:s') . "] APP UPDATE REQUIRED - User with old app version attempted to submit application\n";
$logMessage .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
$logMessage .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
$logMessage .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$logMessage .= "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
$logMessage .= "Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'Unknown') . "\n";
$logMessage .= "==========================================\n";

file_put_contents('app_update_required.log', $logMessage, FILE_APPEND | LOCK_EX);

// Return update required response
http_response_code(426); // 426 Upgrade Required
echo json_encode($response, JSON_PRETTY_PRINT);
?> 