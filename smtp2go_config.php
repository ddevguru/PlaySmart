<?php
/**
 * SMTP2GO Configuration File - PlaySmart
 * Replace the placeholder values with your actual SMTP2GO credentials
 */

// SMTP2GO Configuration
define('SMTP2GO_HOST', 'mail.smtp2go.com');
define('SMTP2GO_PORT', 2525); // Changed from 587 to 2525 (non-TLS)
define('SMTP2GO_USERNAME', 'playsmart.co.in');
define('SMTP2GO_PASSWORD', 'PCERlViy6D9psPFZ');
define('SMTP2GO_FROM_EMAIL', 'support@playsmart.co.in');
define('SMTP2GO_FROM_NAME', 'PlaySmart Services');

// TLS Settings
define('SMTP2GO_USE_TLS', false); // Disable TLS to fix connection issues
define('SMTP2GO_USE_AUTH', true);

// Email Configuration
define('EMAIL_SUBJECT_PREFIX', '🎉 Payment Successful - Job Application Fee - PlaySmart');
define('EMAIL_REPLY_TO', 'support@playsmart.co.in');

// Fallback Configuration
define('ENABLE_FALLBACK_EMAIL', true);
define('ENABLE_FILE_LOGGING', true);
define('EMAIL_LOG_DIR', 'email_logs');

// Debug Configuration
define('SMTP2GO_DEBUG', true);
define('LOG_ALL_EMAIL_ATTEMPTS', true);

// Example usage:
// include 'smtp2go_config.php';
// $smtpUsername = SMTP2GO_USERNAME;
// $smtpPassword = SMTP2GO_PASSWORD;
?> 