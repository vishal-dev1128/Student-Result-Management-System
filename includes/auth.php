<?php
declare(strict_types=1);

/**
 * Authentication Middleware
 * Handles role-based access control and session validation
 */

require_once __DIR__ . '/config.php';

/**
 * Check if user is logged in as admin
 * @param bool $redirect Whether to redirect to login page if not authenticated
 * @return bool
 */
function requireAdminAuth($redirect = true) {
    if (!isAdminLoggedIn()) {
        if ($redirect) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('../login.php');
        }
        return false;
    }
    
    // Validate session fingerprint
    if (!validateSessionFingerprint()) {
        destroySession();
        if ($redirect) {
            redirect('../login.php?error=session_invalid');
        }
        return false;
    }
    
    // Check session timeout
    if (isSessionExpired()) {
        destroySession();
        if ($redirect) {
            redirect('../login.php?error=session_expired');
        }
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Check if user is logged in as student
 * @param bool $redirect Whether to redirect to login page if not authenticated
 * @return bool
 */
function requireStudentAuth($redirect = true) {
    if (!isStudentLoggedIn()) {
        if ($redirect) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('../student/login.php');
        }
        return false;
    }
    
    // Validate session fingerprint
    if (!validateSessionFingerprint()) {
        destroySession();
        if ($redirect) {
            redirect('../student/login.php?error=session_invalid');
        }
        return false;
    }
    
    // Check session timeout
    if (isSessionExpired()) {
        destroySession();
        if ($redirect) {
            redirect('../student/login.php?error=session_expired');
        }
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Check if admin is logged in
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Check if student is logged in
 * @return bool
 */
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

/**
 * Get current admin user data
 * @param object $conn Database connection
 * @return array|null
 */
function getCurrentAdmin($conn) {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT * FROM admin_users WHERE id = ? AND status = 'active'";
    return getRow($conn, $sql, [$_SESSION['admin_id']]);
}

/**
 * Get current student user data
 * @param object $conn Database connection
 * @return array|null
 */
function getCurrentStudent($conn) {
    if (!isStudentLoggedIn()) {
        return null;
    }
    
    // Get full student details including class name
    $sql = "SELECT s.*, c.name as class_name FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id 
            WHERE s.id = ? AND s.status = 'active'";
    $student = getRow($conn, $sql, [$_SESSION['student_id']]);
    
    if (!$student) {
        // Session exists but student not found - clear session
        session_destroy();
        return null;
    }
    
    return $student;
}

/**
 * Check if admin has specific role
 * @param string $role Role to check (super_admin, admin, teacher)
 * @return bool
 */
function hasRole($role) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
}

/**
 * Check if admin has any of the specified roles
 * @param array $roles Array of roles to check
 * @return bool
 */
function hasAnyRole($roles) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    return isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], $roles);
}

/**
 * Require specific role or redirect
 * @param string|array $roles Single role or array of roles
 * @param bool $redirect Whether to redirect if unauthorized
 * @return bool
 */
function requireRole($roles, $redirect = true) {
    if (!isAdminLoggedIn()) {
        if ($redirect) {
            redirect('../login.php');
        }
        return false;
    }
    
    $roles = is_array($roles) ? $roles : [$roles];
    
    if (!hasAnyRole($roles)) {
        if ($redirect) {
            redirect('../admin/dashboard.php?error=unauthorized');
        }
        return false;
    }
    
    return true;
}

/**
 * Check if admin can perform action
 * @param string $action Action to check (create, read, update, delete)
 * @param string $resource Resource type (students, results, classes, etc.)
 * @return bool
 */
function canPerformAction($action, $resource) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $role = $_SESSION['admin_role'] ?? '';
    
    // Super admin can do everything
    if ($role === 'super_admin') {
        return true;
    }
    
    // Define permissions matrix
    $permissions = [
        'admin' => [
            'students' => ['create', 'read', 'update', 'delete'],
            'classes' => ['create', 'read', 'update', 'delete'],
            'subjects' => ['create', 'read', 'update', 'delete'],
            'exams' => ['create', 'read', 'update', 'delete'],
            'results' => ['create', 'read', 'update', 'delete'],
            'notices' => ['create', 'read', 'update', 'delete'],
            'faqs' => ['create', 'read', 'update', 'delete'],
            'tickets' => ['read', 'update'],
            'settings' => ['read'],
        ],
        'teacher' => [
            'students' => ['read'],
            'classes' => ['read'],
            'subjects' => ['read'],
            'exams' => ['read'],
            'results' => ['create', 'read', 'update'],
            'notices' => ['read'],
            'faqs' => ['read'],
            'tickets' => ['create', 'read'],
            'settings' => [],
        ],
    ];
    
    if (!isset($permissions[$role][$resource])) {
        return false;
    }
    
    return in_array($action, $permissions[$role][$resource]);
}

/**
 * Get admin permissions for a resource
 * @param string $resource Resource type
 * @return array Array of allowed actions
 */
function getPermissions($resource) {
    if (!isAdminLoggedIn()) {
        return [];
    }
    
    $role = $_SESSION['admin_role'] ?? '';
    
    if ($role === 'super_admin') {
        return ['create', 'read', 'update', 'delete'];
    }
    
    $permissions = [
        'admin' => [
            'students' => ['create', 'read', 'update', 'delete'],
            'classes' => ['create', 'read', 'update', 'delete'],
            'subjects' => ['create', 'read', 'update', 'delete'],
            'exams' => ['create', 'read', 'update', 'delete'],
            'results' => ['create', 'read', 'update', 'delete'],
            'notices' => ['create', 'read', 'update', 'delete'],
            'faqs' => ['create', 'read', 'update', 'delete'],
            'tickets' => ['read', 'update'],
            'settings' => ['read'],
        ],
        'teacher' => [
            'students' => ['read'],
            'classes' => ['read'],
            'subjects' => ['read'],
            'exams' => ['read'],
            'results' => ['create', 'read', 'update'],
            'notices' => ['read'],
            'faqs' => ['read'],
            'tickets' => ['create', 'read'],
            'settings' => [],
        ],
    ];
    
    return $permissions[$role][$resource] ?? [];
}

/**
 * Logout admin user
 */
function logoutAdmin($conn) {
    if (isAdminLoggedIn()) {
        logActivity($conn, $_SESSION['admin_id'], 'admin_logout', 'Admin logged out');
        destroySession();
    }
    redirect('../login.php');
}

/**
 * Logout student user
 */
function logoutStudent($conn) {
    if (isStudentLoggedIn()) {
        logActivity($conn, $_SESSION['student_id'], 'student_logout', 'Student logged out', 'student');
        destroySession();
    }
    redirect('login.php');
}
