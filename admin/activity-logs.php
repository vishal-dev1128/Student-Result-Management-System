<?php
$page_title = 'Activity Logs';
require_once '../includes/auth.php';
requireAdminAuth();

if (!hasAnyRole(['super_admin', 'admin'])) {
    redirect('dashboard.php?error=unauthorized');
}

require_once 'header.php';

// Filters
$search      = sanitizeInput($_GET['search'] ?? '');
$action_filter = sanitizeInput($_GET['action'] ?? '');
$user_filter = sanitizeInput($_GET['user_type'] ?? '');
$date_from   = sanitizeInput($_GET['date_from'] ?? '');
$date_to     = sanitizeInput($_GET['date_to'] ?? '');

// Build WHERE
$where = ['1=1'];
$params = [];

if (!empty($search)) {
    $where[] = '(al.description LIKE ? OR au.full_name LIKE ? OR st.full_name LIKE ?)';
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}
if (!empty($action_filter)) {
    $where[] = 'al.action = ?';
    $params[] = $action_filter;
}
if (!empty($user_filter)) {
    $where[] = 'al.user_type = ?';
    $params[] = $user_filter;
}
if (!empty($date_from)) {
    $where[] = 'DATE(al.created_at) >= ?';
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where[] = 'DATE(al.created_at) <= ?';
    $params[] = $date_to;
}
$where_clause = implode(' AND ', $where);

// Pagination
$per_page = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$total = 0;
$logs  = [];

try {
    $total = (int)getValue($conn,
        "SELECT COUNT(*) FROM activity_logs al
         LEFT JOIN admin_users au ON al.user_type='admin' AND al.user_id=au.id
         LEFT JOIN students st    ON al.user_type='student' AND al.user_id=st.id
         WHERE $where_clause", $params) ?: 0;

    $logs = getRows($conn,
        "SELECT al.*,
                CASE al.user_type WHEN 'admin' THEN au.full_name WHEN 'student' THEN st.full_name ELSE 'System' END as actor_name
         FROM activity_logs al
         LEFT JOIN admin_users au ON al.user_type='admin' AND al.user_id=au.id
         LEFT JOIN students st    ON al.user_type='student' AND al.user_id=st.id
         WHERE $where_clause
         ORDER BY al.created_at DESC
         LIMIT $per_page OFFSET $offset", $params) ?: [];
} catch (Exception $e) {
    $logs = [];
}

$total_pages = max(1, ceil($total / $per_page));

// Get distinct action types for filter
$action_types = [];
try {
    $action_types = getRows($conn, "SELECT DISTINCT action FROM activity_logs ORDER BY action") ?: [];
} catch (Exception $e) {}

// Icon map
function actLog_icon($a) {
    $m = ['admin_login'=>'sign-in-alt','admin_logout'=>'sign-out-alt','student_login'=>'user',
          'student_created'=>'user-plus','student_updated'=>'user-edit','student_deleted'=>'user-minus',
          'result_created'=>'plus-circle','result_updated'=>'edit','result_deleted'=>'trash',
          'notice_created'=>'bell','ticket_created'=>'ticket-alt','failed_login'=>'ban'];
    return $m[$a] ?? 'circle';
}
function actLog_color($a) {
    if (str_contains($a,'delete') || str_contains($a,'failed')) return '#ef4444';
    if (str_contains($a,'login'))  return '#10b981';
    if (str_contains($a,'create')) return '#4f46e5';
    if (str_contains($a,'update')) return '#f59e0b';
    return '#6b7280';
}
?>

<style>
.log-icon { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff;flex-shrink:0; }
.filter-row { display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end; }
.filter-row .form-group { margin:0; }
.filter-row input, .filter-row select { min-width:160px; }
</style>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Activity Logs</h1>
            <p class="page-subtitle">Total: <?php echo number_format($total); ?> entries</p>
        </div>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 1])); ?>" class="btn btn-outlined">
            <i class="fas fa-download"></i> Export
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-lg">
    <div class="card-body">
        <form method="GET" class="filter-row" id="filterForm">
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="Search description or user…" value="<?php echo escape($search); ?>">
            </div>
            <div class="form-group">
                <select name="action" class="form-control">
                    <option value="">All Actions</option>
                    <?php foreach ($action_types as $at): ?>
                    <option value="<?php echo escape($at['action']); ?>" <?php echo $action_filter === $at['action'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $at['action'])); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <select name="user_type" class="form-control">
                    <option value="">All Users</option>
                    <option value="admin"   <?php echo $user_filter === 'admin'   ? 'selected' : ''; ?>>Admin</option>
                    <option value="student" <?php echo $user_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="date_from" class="form-control" value="<?php echo escape($date_from); ?>" placeholder="From date">
            </div>
            <div class="form-group">
                <input type="date" name="date_to" class="form-control" value="<?php echo escape($date_to); ?>" placeholder="To date">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            <a href="activity-logs.php" class="btn btn-outlined"><i class="fas fa-redo"></i> Reset</a>
        </form>
    </div>
</div>

<!-- Logs -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($logs)): ?>
        <div style="text-align:center;padding:48px;color:var(--color-text-secondary);">
            <i class="fas fa-history fa-3x mb-lg" style="opacity:.3;"></i>
            <p>No activity logs found matching your filters.</p>
        </div>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <div style="display:flex;gap:14px;padding:14px 20px;border-bottom:1px solid var(--color-border);align-items:flex-start;">
            <div class="log-icon" style="background:<?php echo actLog_color($log['action']); ?>;">
                <i class="fas fa-<?php echo actLog_icon($log['action']); ?>"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:14px;font-weight:500;"><?php echo escape($log['description']); ?></div>
                <div style="font-size:12px;color:var(--color-text-secondary);margin-top:3px;">
                    <?php if (!empty($log['actor_name'])): ?>
                    <strong><?php echo escape($log['actor_name']); ?></strong> &bull;
                    <?php endif; ?>
                    <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?> &bull;
                    <?php echo ucfirst($log['user_type'] ?? 'system'); ?>
                </div>
            </div>
            <div style="font-size:12px;color:var(--color-text-secondary);white-space:nowrap;text-align:right;">
                <div><?php echo timeAgo($log['created_at']); ?></div>
                <div><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <div class="pagination">
            <?php if ($page > 1): ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>" class="pagination-item"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>" class="pagination-item"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
