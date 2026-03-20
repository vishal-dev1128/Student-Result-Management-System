<?php
/**
 * Utility Functions
 * SRMS v2.0.0
 * 
 * Common utility functions for the application
 */

// ============================================================
// Grade Calculation Functions
// ============================================================

/**
 * Calculate grade based on percentage
 * 
 * @param float $percentage Percentage score
 * @return string Grade letter
 */
function calculateGrade($percentage, $conn = null) {
    if (!$conn) {
        global $conn;
    }
    
    // Try to get grade from database settings
    if ($conn) {
        try {
            $grade_sql = "SELECT grade FROM grade_settings WHERE min_percentage <= ? AND max_percentage >= ? ORDER BY min_percentage DESC LIMIT 1";
            $stmt = $conn->prepare($grade_sql);
            if ($stmt) {
                $stmt->bind_param('dd', $percentage, $percentage);
                $stmt->execute();
                $result = $stmt->get_result();
                $grade_row = $result->fetch_assoc();
                if ($grade_row) return $grade_row['grade'];
            }
        } catch (Exception $e) {
            // Fallback if table doesn't exist or error
        }
    }
    
    // Fallback to hardcoded constants if DB fail or $conn unavailable
    if ($percentage >= (defined('GRADE_A_PLUS_MIN') ? GRADE_A_PLUS_MIN : 90)) return 'A+';
    if ($percentage >= (defined('GRADE_A_MIN') ? GRADE_A_MIN : 80)) return 'A';
    if ($percentage >= (defined('GRADE_B_PLUS_MIN') ? GRADE_B_PLUS_MIN : 70)) return 'B+';
    if ($percentage >= (defined('GRADE_B_MIN') ? GRADE_B_MIN : 60)) return 'B';
    if ($percentage >= (defined('GRADE_C_MIN') ? GRADE_C_MIN : 50)) return 'C';
    if ($percentage >= (defined('GRADE_D_MIN') ? GRADE_D_MIN : 40)) return 'D';
    return 'F';
}

/**
 * Calculate percentage
 * 
 * @param float $obtained Marks obtained
 * @param float $total Total marks
 * @return float Percentage (rounded to 2 decimals)
 */
function calculatePercentage($obtained, $total) {
    if ($total == 0) return 0;
    return round(($obtained / $total) * 100, 2);
}

/**
 * Determine pass/fail status
 * 
 * @param float $percentage Percentage score
 * @return string 'pass' or 'fail'
 */
function getResultStatus($percentage) {
    return $percentage >= PASS_PERCENTAGE ? 'pass' : 'fail';
}

/**
 * Get grade color for display
 * 
 * @param string $grade Grade letter
 * @return string CSS color class
 */
function getGradeColor($grade) {
    $colors = [
        'A+' => 'grade-a-plus',
        'A' => 'grade-a',
        'B+' => 'grade-b-plus',
        'B' => 'grade-b',
        'C' => 'grade-c',
        'D' => 'grade-d',
        'F' => 'grade-f'
    ];
    return $colors[$grade] ?? 'grade-default';
}

// ============================================================
// Activity Logging Functions
// ============================================================

/**
 * Log activity to database
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID (null for guest)
 * @param string $user_type User type (admin/student/guest)
 * @param string $action Action performed
 * @param string $description Action description
 * @return bool Success status
 */
function logActivity($conn, $user_id, $user_type, $action, $description = '') {
    $ip_address = getClientIP();
    $user_agent = getUserAgent();
    
    $sql = "INSERT INTO activity_logs (user_id, user_type, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param('isssss', $user_id, $user_type, $action, $description, $ip_address, $user_agent);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// ============================================================
// Date and Time Functions
// ============================================================

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * 
 * @param string $datetime Datetime string
 * @param string $format Output format
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = 'd M Y h:i A') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Get time ago string
 * 
 * @param string $datetime Datetime string
 * @return string Time ago (e.g., "2 hours ago")
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}

// ============================================================
// Number Formatting Functions
// ============================================================

/**
 * Format number with decimals
 * 
 * @param float $number Number to format
 * @param int $decimals Number of decimal places
 * @return string Formatted number
 */
function formatNumber($number, $decimals = 2) {
    return number_format((float)($number ?? 0), $decimals);
}

/**
 * Format file size in human-readable format
 * 
 * @param int $bytes File size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted size (e.g., "2.5 MB")
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// ============================================================
// String Functions
// ============================================================

/**
 * Truncate string to specified length
 * 
 * @param string $string String to truncate
 * @param int $length Maximum length
 * @param string $append String to append (e.g., "...")
 * @return string Truncated string
 */
function truncate($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) return $string;
    return substr($string, 0, $length) . $append;
}

/**
 * Generate random string
 * 
 * @param int $length String length
 * @return string Random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate slug from string
 * 
 * @param string $string Input string
 * @return string URL-friendly slug
 */
function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// ============================================================
// Database Helper Functions
// ============================================================

/**
 * Get single row from database
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @param array $params Parameters
 * @param string $types Parameter types
 * @return array|null Row data or null
 */
function getRow($conn, $sql, $params = [], $types = '') {
    $stmt = prepareAndExecute($conn, $sql, $params, $types);
    if (!$stmt) return null;
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Get all rows from database
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @param array $params Parameters
 * @param string $types Parameter types
 * @return array Array of rows
 */
function getRows($conn, $sql, $params = [], $types = '') {
    $stmt = prepareAndExecute($conn, $sql, $params, $types);
    if (!$stmt) return [];
    
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    
    return $rows;
}

/**
 * Get single value from database
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @param array $params Parameters
 * @param string $types Parameter types
 * @return mixed Single value or null
 */
function getValue($conn, $sql, $params = [], $types = '') {
    $row = getRow($conn, $sql, $params, $types);
    return $row ? reset($row) : null;
}

/**
 * Check if record exists
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $column Column name
 * @param mixed $value Value to check
 * @return bool True if exists
 */
function recordExists($conn, $table, $column, $value) {
    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
    $count = getValue($conn, $sql, [$value], 's');
    return $count > 0;
}

// ============================================================
// Pagination Functions
// ============================================================

/**
 * Calculate pagination values
 * 
 * @param int $total_records Total number of records
 * @param int $current_page Current page number
 * @param int $per_page Records per page
 * @return array Pagination data
 */
function calculatePagination($total_records, $current_page = 1, $per_page = RESULTS_PER_PAGE) {
    $total_pages = ceil($total_records / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'per_page' => $per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

// ============================================================
// Email Functions
// ============================================================

/**
 * Send email (basic implementation)
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $from_email Sender email
 * @param string $from_name Sender name
 * @return bool Success status
 */
function sendEmail($to, $subject, $message, $from_email = SMTP_FROM_EMAIL, $from_name = SMTP_FROM_NAME) {
    if (!SMTP_ENABLED) {
        error_log("Email not sent (SMTP disabled): To=$to, Subject=$subject");
        return false;
    }
    
    // Basic email headers
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Send email
    $result = mail($to, $subject, $message, $headers);
    
    if (!$result) {
        error_log("Email send failed: To=$to, Subject=$subject");
    }
    
    return $result;
}

/**
 * Send password reset email
 * 
 * @param string $email Recipient email
 * @param string $token Reset token
 * @param string $user_type User type (admin/student)
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $token, $user_type) {
    $reset_link = APP_URL . "/reset-password.php?token=$token&type=$user_type";
    
    $subject = "Password Reset Request - " . APP_NAME;
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Password Reset Request</h2>
        <p>You have requested to reset your password for " . APP_NAME . ".</p>
        <p>Click the link below to reset your password:</p>
        <p><a href='$reset_link' style='background: #1565C0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>Or copy and paste this link into your browser:</p>
        <p>$reset_link</p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this reset, please ignore this email.</p>
        <hr>
        <p style='color: #666; font-size: 12px;'>This is an automated email from " . APP_NAME . ".</p>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

// ============================================================
// Redirect Functions
// ============================================================

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param int $status_code HTTP status code
 */
function redirect($url, $status_code = 302) {
    header("Location: $url", true, $status_code);
    exit;
}

/**
 * Redirect with message
 * 
 * @param string $url URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success/error/warning/info)
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    redirect($url);
}

/**
 * Get and clear flash message
 * 
 * @return array|null Message data or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

// ============================================================
// Settings Functions
// ============================================================

/**
 * Get setting value from database
 * 
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function getSetting($conn, $key, $default = null) {
    $sql = "SELECT setting_value, setting_type FROM settings WHERE setting_key = ?";
    $row = getRow($conn, $sql, [$key], 's');
    
    if (!$row) return $default;
    
    $value = $row['setting_value'];
    $type = $row['setting_type'];
    
    // Convert based on type
    switch ($type) {
        case 'number':
            return (float) $value;
        case 'boolean':
            return (bool) $value;
        case 'json':
            return json_decode($value, true);
        default:
            return $value;
    }
}

/**
 * Update setting value in database
 * 
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success status
 */
function updateSetting($conn, $key, $value) {
    // Convert value based on type
    if (is_array($value)) {
        $value = json_encode($value);
    } elseif (is_bool($value)) {
        $value = $value ? '1' : '0';
    }
    
    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
    $stmt = prepareAndExecute($conn, $sql, [$value, $key], 'ss');
    
    return $stmt !== false;
}

// ============================================================
// Validation Functions
// ============================================================

/**
 * Validate required fields
 * 
 * @param array $data Data array
 * @param array $required Required field names
 * @return array Empty if valid, or array of missing fields
 */
function validateRequired($data, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Validate phone number
 * 
 * @param string $phone Phone number
 * @return bool True if valid
 */
function validatePhone($phone) {
    // Basic validation for Indian phone numbers
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

/**
 * Validate date format
 * 
 * @param string $date Date string
 * @param string $format Expected format
 * @return bool True if valid
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
