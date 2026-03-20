<?php
/**
 * Security Functions
 * SRMS v2.0.0
 * 
 * Provides security utilities including password hashing, CSRF protection,
 * XSS prevention, and input sanitization
 */

// ============================================================
// Password Hashing Functions
// ============================================================

/**
 * Hash a password using bcrypt
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 * 
 * @param string $hash Current password hash
 * @return bool True if rehashing is needed
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ============================================================
// CSRF Protection
// ============================================================

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if token is valid
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token input field HTML
 * 
 * @return string HTML input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token from POST request
 * 
 * @return bool True if valid, dies with error if invalid
 */
function verifyCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
    return true;
}

// ============================================================
// XSS Prevention
// ============================================================

/**
 * Escape HTML output to prevent XSS
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Escape HTML output (alias for escape)
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function e($string) {
    return escape($string);
}

/**
 * Sanitize HTML input
 * 
 * @param string $html HTML string
 * @return string Sanitized HTML
 */
function sanitizeHTML($html) {
    // Remove potentially dangerous tags
    $html = strip_tags($html, '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6>');
    
    // Remove javascript: and data: protocols from links
    $html = preg_replace('/(<a[^>]+href=[\'"](javascript|data):[^>]+>)/i', '', $html);
    
    return $html;
}

// ============================================================
// Input Sanitization
// ============================================================

/**
 * Sanitize string input
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitizeString($input) {
    if ($input === null || $input === '') return '';
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Alias for sanitizeString to maintain compatibility
 */
function sanitizeInput($input) {
    return sanitizeString($input);
}

/**
 * Sanitize email input
 * 
 * @param string $email Email address
 * @return string|false Sanitized email or false if invalid
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email address
 * 
 * @param string $email Email address
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize integer input
 * 
 * @param mixed $input Input value
 * @return int Sanitized integer
 */
function sanitizeInt($input) {
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Alias for sanitizeInt to maintain compatibility
 */
function sanitizeInteger($input) {
    return sanitizeInt($input);
}

/**
 * Sanitize float input
 * 
 * @param mixed $input Input value
 * @return float Sanitized float
 */
function sanitizeFloat($input) {
    return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize URL input
 * 
 * @param string $url URL string
 * @return string|false Sanitized URL or false if invalid
 */
function sanitizeURL($url) {
    return filter_var(trim($url), FILTER_SANITIZE_URL);
}

// ============================================================
// SQL Injection Prevention
// ============================================================

/**
 * Prepare and execute SQL statement (wrapper for mysqli)
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @param string $types Parameter types (s=string, i=integer, d=double, b=blob)
 * @return mysqli_stmt|false Prepared statement or false on failure
 */
function prepareAndExecute($conn, $sql, $params = [], $types = '') {
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $conn->error);
            return false;
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detect types if not provided
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("SQL Execute Error: " . $stmt->error);
            return false;
        }
        
        return $stmt;
    } catch (mysqli_sql_exception $e) {
        error_log("MySQLi Exception: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("SQL General Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a query that doesn't return rows (UPDATE, INSERT, DELETE)
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @param array $params Parameters
 * @param string $types Parameter types
 * @return bool True if successful
 */
function executeQuery($conn, $sql, $params = [], $types = '') {
    $stmt = prepareAndExecute($conn, $sql, $params, $types);
    if (!$stmt) return false;
    $stmt->close();
    return true;
}

// ============================================================
// File Upload Security
// ============================================================

/**
 * Validate uploaded file
 * 
 * @param array $file $_FILES array element
 * @param array $allowed_extensions Allowed file extensions
 * @param int $max_size Maximum file size in bytes
 * @return array ['valid' => bool, 'error' => string, 'safe_name' => string]
 */
function validateFileUpload($file, $allowed_extensions, $max_size) {
    $result = ['valid' => false, 'error' => '', 'safe_name' => ''];
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        $result['error'] = 'Invalid file upload.';
        return $result;
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $result['error'] = 'File size exceeds limit.';
            return $result;
        case UPLOAD_ERR_NO_FILE:
            $result['error'] = 'No file was uploaded.';
            return $result;
        default:
            $result['error'] = 'Unknown upload error.';
            return $result;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $result['error'] = 'File size exceeds ' . formatBytes($max_size) . ' limit.';
        return $result;
    }
    
    // Check file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        $result['error'] = 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions);
        return $result;
    }
    
    // Generate safe filename
    $safe_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    
    $result['valid'] = true;
    $result['error'] = '';
    $result['safe_name'] = $safe_filename;
    
    return $result;
}

// ============================================================
// Session Security
// ============================================================

/**
 * Regenerate session ID
 */
function regenerateSession() {
    session_regenerate_id(true);
}

/**
 * Destroy session completely
 */
function destroySession() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check if session has timed out
 * 
 * @return bool True if session is valid
 */
function checkSessionTimeout() {
    if (isSessionExpired()) {
        destroySession();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if the session has expired
 * 
 * @return bool True if expired
 */
function isSessionExpired() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            return true;
        }
    }
    return false;
}

// ============================================================
// IP and User Agent Validation
// ============================================================

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Get user agent string
 * 
 * @return string User agent
 */
function getUserAgent() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
}

/**
 * Validate session fingerprint
 * 
 * @return bool True if valid
 */
function validateSessionFingerprint() {
    $fingerprint = md5(getUserAgent() . getClientIP());
    
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
        return true;
    }
    
    return $_SESSION['fingerprint'] === $fingerprint;
}

/**
 * Set session fingerprint for security
 */
function setSessionFingerprint() {
    $_SESSION['fingerprint'] = md5(getUserAgent() . getClientIP());
}

// ============================================================
// Rate Limiting
// ============================================================

/**
 * Check rate limit for login attempts
 * 
 * @param mysqli $conn Database connection (optional/for compatibility)
 * @param string $identifier User identifier (email/username/IP)
 * @param string $type Action type
 * @return bool True if within limit
 */
function checkRateLimit($conn, $identifier, $type = 'login') {
    $key = 'rate_limit_' . $type . '_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $attempts = &$_SESSION[$key];
    
    // Reset if lockout time has passed (assuming LOGIN_LOCKOUT_TIME is defined in config)
    $lockout_time = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900;
    $max_attempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;

    if (time() - $attempts['time'] > $lockout_time) {
        $attempts['count'] = 0;
        $attempts['time'] = time();
    }
    
    // Check if max attempts exceeded
    if ($attempts['count'] >= $max_attempts) {
        return false;
    }
    
    return true;
}

/**
 * Record failed login attempt
 * 
 * @param string $identifier User identifier (IP/username)
 * @param string $type Action type
 */
function recordFailedLogin($identifier, $type = 'login') {
    $key = 'rate_limit_' . $type . '_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $_SESSION[$key]['count']++;
}

/**
 * Reset login attempts
 * 
 * @param string $identifier User identifier
 * @param string $type Action type
 */
function resetLoginAttempts($identifier, $type = 'login') {
    $key = 'rate_limit_' . $type . '_' . md5($identifier);
    unset($_SESSION[$key]);
}
