<?php
/**
 * Student Header
 * Common header for all student portal pages
 */

require_once __DIR__ . '/../includes/auth.php';
requireStudentAuth();

$current_student = getCurrentStudent($conn);

if (!$current_student) {
    redirect('logout.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Student Portal'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/animations.css">
    
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-graduation-cap" style="font-size: 1.4rem;"></i>
                <span>Student Portal</span>
            </a>
            
            <button class="navbar-toggle" data-mobile-menu-toggle>
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-menu">
                <a href="dashboard.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="results.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) === 'results.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> My Results
                </a>
                <a href="profile.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
                
                <div class="navbar-divider"></div>
                
                <div class="dropdown">
                    <button class="navbar-link dropdown-toggle flex items-center justify-center gap-sm" data-dropdown-toggle style="padding: 6px 16px; border-radius: 99px; background: var(--color-surface-variant); border: none; height: 42px;">
                        <div class="admin-avatar flex items-center justify-center" style="width: 28px; height: 28px; font-size: 13px; margin: 0; background: var(--color-primary); color: white; border-radius: 50%;">
                            <?php echo strtoupper(substr($current_student['full_name'], 0, 1)); ?>
                        </div>
                        <span class="font-medium" style="display: flex; align-items: center;"><?php echo escape(explode(' ', $current_student['full_name'])[0]); ?></span>
                        <i class="fas fa-chevron-down text-secondary text-sm" style="display: flex; align-items: center;"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="dropdown-header" style="padding: 12px 16px;">
                            <div class="font-medium"><?php echo escape($current_student['full_name']); ?></div>
                            <div class="text-sm text-secondary"><?php echo escape($current_student['roll_number']); ?></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="profile.php" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                            <i class="fas fa-user" style="width: 16px; text-align: center;"></i> My Profile
                        </a>
                        <a href="change-password.php" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                            <i class="fas fa-key" style="width: 16px; text-align: center;"></i> Change Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item text-error" style="display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                            <i class="fas fa-sign-out-alt" style="width: 16px; text-align: center;"></i> Logout
                        </a>
                    </div>
                </div>
                
                <button data-theme-toggle class="navbar-link" style="padding: 0 12px; height: 42px; display: flex; align-items: center; border: none; background: transparent; cursor: pointer;">
                    <i class="fas fa-moon text-lg"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="container py-xl">
