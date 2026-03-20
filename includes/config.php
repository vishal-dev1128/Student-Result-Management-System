<?php
/**
 * Database Configuration File
 * SRMS v2.0.0
 * 
 * IMPORTANT: Update these settings for your environment
 */

// ============================================================
// Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'srms');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// Application Configuration
// ============================================================

define('APP_NAME', 'Student Result Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/srms-master');
define('APP_TIMEZONE', 'Asia/Kolkata');

// ============================================================
// Security Configuration
// ============================================================

define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// ============================================================
// File Upload Configuration
// ============================================================

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('MAX_PHOTO_SIZE', 2097152); // 2MB in bytes
define('MAX_BULK_FILE_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_PHOTO_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv']);

// ============================================================
// Email Configuration
// ============================================================

define('SMTP_ENABLED', false); // Set to true to enable email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls'); // tls or ssl
define('SMTP_FROM_EMAIL', 'noreply@srms.local');
define('SMTP_FROM_NAME', 'SRMS Notifications');

// ============================================================
// Pagination Configuration
// ============================================================

define('RESULTS_PER_PAGE', 20);
define('MAX_PAGINATION_LINKS', 5);

// ============================================================
// Grading Configuration
// ============================================================

define('GRADING_SYSTEM', 'percentage'); // 'percentage' or 'letter'
define('GRADE_A_PLUS_MIN', 90);
define('GRADE_A_MIN', 80);
define('GRADE_B_PLUS_MIN', 70);
define('GRADE_B_MIN', 60);
define('GRADE_C_MIN', 50);
define('GRADE_D_MIN', 40); // Pass mark
define('PASS_PERCENTAGE', 40);

// ============================================================
// Database Connection
// ============================================================

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please contact the administrator.");
    }
    
    // Set charset
    $conn->set_charset(DB_CHARSET);
    
    // Set timezone
    date_default_timezone_set(APP_TIMEZONE);
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("A database error occurred. Please contact the administrator.");
}

// ============================================================
// Error Reporting (Disable in production)
// ============================================================

// Development mode - show all errors
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production mode - log errors, don't display
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// ============================================================
// Session Configuration
// ============================================================

// Secure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Auto-load required files
// ============================================================

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';
