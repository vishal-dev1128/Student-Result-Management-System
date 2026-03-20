<?php
/**
 * Admin Header — Premium v3.0
 * Collapsible sidebar, profile card, notification bell, premium topbar
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdminAuth();
$current_admin = getCurrentAdmin($conn);

// Notification counts
$open_tickets = 0;
$unpublished_notices = 0;
try { $open_tickets = (int)getValue($conn, "SELECT COUNT(*) FROM support_tickets WHERE status='open'"); } catch(Exception $e){}
try { $unpublished_notices = (int)getValue($conn, "SELECT COUNT(*) FROM notices WHERE is_published=0"); } catch(Exception $e){}
$notif_count = $open_tickets + $unpublished_notices;

// Role badge class
$role_class = 'role-' . ($current_admin['role'] ?? 'viewer');
$role_label = ucfirst(str_replace('_', ' ', $current_admin['role'] ?? 'Staff'));

$current_page = basename($_SERVER['PHP_SELF']);

function navLink($href, $icon, $label, $current): string {
    $active = $current === $href ? 'active' : '';
    return sprintf(
        '<a href="%s" class="nav-link %s" data-label="%s">
            <i class="fas fa-%s nav-icon"></i>
            <span class="sidebar-label">%s</span>
         </a>',
        $href, $active, htmlspecialchars($label, ENT_QUOTES),
        $icon, htmlspecialchars($label, ENT_QUOTES)
    );
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Panel — <?php echo APP_NAME; ?>">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> — <?php echo APP_NAME; ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Core CSS -->
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/animations.css">

    <!-- ✨ Premium Admin CSS -->
    <link rel="stylesheet" href="../css/admin-premium.css">

    <style>
        /* Font override for admin */
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        h1,h2,h3,h4,h5,h6 { font-family: 'Inter', sans-serif; }

        /* Apply stored theme before paint */
        html[data-theme="dark"] { color-scheme: dark; }
    </style>

    <?php if (isset($extra_css)) echo $extra_css; ?>

    <script>
        // Apply theme immediately to prevent flash
        (function() {
            const t = localStorage.getItem('admin_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>

<!-- Page Transition Overlay -->
<div class="page-transition-overlay" id="pageOverlay"></div>

<div class="admin-layout" id="adminLayout">

    <!-- ═══════════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════════ -->
    <aside class="admin-sidebar" id="adminSidebar">

        <!-- Brand Header -->
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <span class="sidebar-brand-text">
                    <span style="display:block;">SRMS</span>
                    <span class="sidebar-brand-sub">Admin Panel</span>
                </span>
            </a>
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
                <i class="fas fa-chevrons-left" id="collapseIcon"></i>
            </button>
        </div>

        <!-- Admin Profile Card -->
        <div class="sidebar-profile">
            <div class="sidebar-profile-avatar">
                <?php echo strtoupper(substr($current_admin['full_name'], 0, 1)); ?>
            </div>
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name"><?php echo escape($current_admin['full_name']); ?></div>
                <span class="role-badge <?php echo $role_class; ?>"><?php echo $role_label; ?></span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav" id="sidebarNav">

            <!-- Main -->
            <div class="sidebar-section-title">Main</div>
            <?php echo navLink('dashboard.php', 'home', 'Dashboard', $current_page); ?>

            <!-- Students -->
            <?php if (canPerformAction('read', 'students')): ?>
            <div class="sidebar-section-title">Students</div>
            <?php echo navLink('students.php',    'user-graduate', 'Students',    $current_page); ?>
            <?php if (canPerformAction('create', 'students')): ?>
            <?php echo navLink('add-student.php', 'user-plus',     'Add Student', $current_page); ?>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Academic -->
            <?php if (canPerformAction('read', 'classes')): ?>
            <div class="sidebar-section-title">Academic</div>
            <?php echo navLink('classes.php',  'school',   'Classes',  $current_page); ?>
            <?php echo navLink('subjects.php', 'book',     'Subjects', $current_page); ?>
            <?php echo navLink('exams.php',    'file-alt', 'Exams',    $current_page); ?>
            <?php endif; ?>

            <!-- Results -->
            <?php if (canPerformAction('read', 'results')): ?>
            <div class="sidebar-section-title">Results</div>
            <?php echo navLink('results.php',    'chart-bar',    'Manage Results', $current_page); ?>
            <?php echo navLink('grade-settings.php', 'sliders-h', 'Grade Settings', $current_page); ?>
            <?php if (canPerformAction('create', 'results')): ?>
            <?php echo navLink('add-result.php', 'plus-circle', 'Add Result',     $current_page); ?>
            <?php echo navLink('import-results.php', 'file-import', 'Import Result', $current_page); ?>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Communication -->
            <?php if (canPerformAction('read', 'notices')): ?>
            <div class="sidebar-section-title">Communication</div>
            <?php echo navLink('notices.php', 'bell',            'Notices',         $current_page); ?>
            <?php echo navLink('faqs.php',    'question-circle', 'FAQs',            $current_page); ?>
            <?php echo navLink('tickets.php', 'ticket-alt',      'Support Tickets', $current_page); ?>
            <?php endif; ?>

            <!-- System -->
            <?php if (hasAnyRole(['super_admin', 'admin'])): ?>
            <div class="sidebar-section-title">System</div>
            <?php echo navLink('activity-logs.php', 'history', 'Activity Logs', $current_page); ?>
            <?php endif; ?>

        </nav>

        <!-- Sidebar Footer -->
        <div style="padding:16px 20px;border-top:1px solid var(--color-border);flex-shrink:0;">
            <a href="logout.php" class="nav-link" data-label="Logout" style="color:var(--premium-rose,#f43f5e);">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="sidebar-label">Logout</span>
            </a>
        </div>

    </aside>

    <!-- ═══════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════ -->
    <main class="admin-main" id="adminMain">

        <!-- Topbar -->
        <div class="admin-topbar">
            <!-- Left -->
            <div class="topbar-left">
                <!-- Mobile hamburger -->
                <button class="sidebar-toggle-btn" id="mobileSidebarToggle" title="Open menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-page-title"><?php echo $page_title ?? 'Admin Panel'; ?></div>
                    <div class="topbar-breadcrumb">
                        <a href="dashboard.php" style="color:var(--color-text-secondary);text-decoration:none;">Dashboard</a>
                        <?php if (($page_title ?? '') !== 'Dashboard'): ?>
                        <span style="margin:0 4px;opacity:.4;">›</span>
                        <span><?php echo $page_title ?? ''; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right -->
            <div class="topbar-right">

                <!-- Dark mode toggle -->
                <button class="theme-toggle-btn" id="themeToggle" title="Toggle dark mode">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>

                <!-- View site -->
                <a href="../index.php" target="_blank" class="sidebar-toggle-btn" title="View site" style="text-decoration:none;">
                    <i class="fas fa-external-link-alt" style="font-size:13px;"></i>
                </a>

                <!-- Notification Bell -->
                <div style="position:relative;">
                    <button class="notif-btn" id="notifBtn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notif_count > 0): ?>
                        <span class="notif-badge"><?php echo min($notif_count, 9); ?><?php echo $notif_count > 9 ? '+' : ''; ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notification Dropdown -->
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dropdown-header">
                            <span>Notifications</span>
                            <?php if ($notif_count > 0): ?>
                            <span style="font-size:11px;background:rgba(244,63,94,.12);color:#f43f5e;padding:2px 8px;border-radius:20px;font-weight:600;"><?php echo $notif_count; ?> new</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($open_tickets > 0): ?>
                        <a href="tickets.php" class="notif-item" style="text-decoration:none;">
                            <div class="notif-icon" style="background:rgba(244,63,94,.12);color:#f43f5e;"><i class="fas fa-ticket-alt"></i></div>
                            <div>
                                <div class="notif-text"><?php echo $open_tickets; ?> open support ticket<?php echo $open_tickets > 1 ? 's' : ''; ?> waiting</div>
                                <div class="notif-time">Requires attention</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if ($unpublished_notices > 0): ?>
                        <a href="notices.php" class="notif-item" style="text-decoration:none;">
                            <div class="notif-icon" style="background:rgba(245,158,11,.12);color:#f59e0b;"><i class="fas fa-bell"></i></div>
                            <div>
                                <div class="notif-text"><?php echo $unpublished_notices; ?> unpublished notice<?php echo $unpublished_notices > 1 ? 's' : ''; ?></div>
                                <div class="notif-time">Draft — not visible to students</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if ($notif_count === 0): ?>
                        <div style="padding:24px;text-align:center;color:var(--color-text-secondary);font-size:13px;">
                            <i class="fas fa-check-circle" style="font-size:24px;opacity:.3;display:block;margin-bottom:8px;"></i>
                            All caught up!
                        </div>
                        <?php endif; ?>
                        <div style="padding:12px 20px;border-top:1px solid var(--color-border);text-align:center;">
                            <a href="activity-logs.php" style="font-size:12px;color:var(--color-primary);">View all activity →</a>
                        </div>
                    </div>
                </div>

                <!-- Admin chip -->
                <div class="dropdown" id="adminChipDropdown">
                    <div class="admin-user-chip dropdown-toggle" data-dropdown-toggle>
                        <div class="admin-avatar-sm">
                            <?php echo strtoupper(substr($current_admin['full_name'], 0, 1)); ?>
                        </div>
                        <div style="line-height:1.2;">
                            <div style="font-size:13px;font-weight:600;color:var(--color-text-primary);"><?php echo escape($current_admin['full_name']); ?></div>
                            <div style="font-size:11px;color:var(--color-text-secondary);"><?php echo $role_label; ?></div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size:11px;color:var(--color-text-secondary);"></i>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="profile.php" class="dropdown-item" style="white-space: nowrap;"><i class="fas fa-user" style="width:16px;"></i> My Profile</a>
                        <a href="change-password.php" class="dropdown-item" style="white-space: nowrap;"><i class="fas fa-key" style="width:16px;"></i> Change Password</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item" style="color:var(--color-error); white-space: nowrap;"><i class="fas fa-sign-out-alt" style="width:16px;"></i> Logout</a>
                    </div>
                </div>

            </div>
        </div><!-- /topbar -->

        <div class="admin-content">
