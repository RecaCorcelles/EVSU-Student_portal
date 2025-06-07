<?php
// filepath: c:\xampp\htdocs\student_portal\includes\config.php
/**
 * Configuration file for EVSU Student Portal
 * Using Supabase for all data storage and authentication
 */

// Check if session is already started before setting session configurations
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration - only set if no session is active
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
    ini_set('session.gc_maxlifetime', 3600); // 1 hour session timeout
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 for production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Portal settings
define('PORTAL_NAME', 'EVSU Student Portal');
define('PORTAL_VERSION', '2.0.0');
define('DEFAULT_PROFILE_IMAGE', 'images/default_avatar.png');

// File upload settings
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_PATH', __DIR__ . '/../images/uploads/');

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Supabase Integration Settings
define('SUPABASE_URL', 'https://qxcwdgcexuwjdrgfzxnd.supabase.co/rest/v1');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF4Y3dkZ2NleHV3amRyZ2Z6eG5kIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDg3ODIyODIsImV4cCI6MjA2NDM1ODI4Mn0.PvSAK7iZwD-u6atl3r4ZvnVRfbvgC6gP8MLoG5EQcxQ');

// Application Mode Settings
define('APP_MODE', 'production'); // 'development' or 'production'
define('ENABLE_DEBUG_LOGGING', true);

// Email settings (for OTP and notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@evsu.edu.ph'); // Configure as needed
define('SMTP_PASSWORD', ''); // Configure as needed
define('FROM_EMAIL', 'noreply@evsu.edu.ph');
define('FROM_NAME', 'EVSU Student Portal');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// API Rate Limiting
define('API_RATE_LIMIT', 100); // requests per minute
define('API_TIMEOUT', 30); // seconds

// Student Portal specific settings
define('ACADEMIC_YEAR_START', '2024-08-01');
define('ACADEMIC_YEAR_END', '2025-05-31');
define('ENROLLMENT_DEADLINE', '2024-09-15');

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes

// Wit.ai Configuration
define('WIT_AI_TOKEN', 'NGWRCCMEUJ3AHMTIXL42KNZRXQLA6FFU');
define('CHATBOT_ENABLED', true);
?>