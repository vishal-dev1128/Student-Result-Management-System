<?php
$page_title = 'Manage Students';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'students')) {
    redirect('dashboard.php?error=unauthorized');
}

// Handle delete
if (isset($_GET['delete']) && canPerformAction('delete', 'students')) {
    $id = sanitizeInteger($_GET['delete']);
    $delete_sql = "UPDATE students SET status = 'deleted' WHERE id = ?";
    if (executeQuery($conn, $delete_sql, [$id])) {
        logActivity($conn, $_SESSION['admin_id'], 'student_deleted', "Deleted student ID: $id");
        $_SESSION['flash_message'] = 'Student deleted successfully';
        $_SESSION['flash_type'] = 'success';
    }
    redirect('students.php');
}

require_once 'header.php';

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$class_filter = sanitizeInteger($_GET['class'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? 'active');

// Build query
$where = ["s.status != 'deleted'"];
$params = [];

if (!empty($search)) {
    $where[] = "(s.full_name LIKE ? OR s.roll_number LIKE ? OR s.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($class_filter)) {
    $where[] = "s.class_id = ?";
    $params[] = $class_filter;
}

if (!empty($status_filter) && $status_filter !== 'all') {
    $where[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$count_sql = "SELECT COUNT(*) FROM students s WHERE $where_clause";
$total_records = getValue($conn, $count_sql, $params);
$total_pages = ceil($total_records / $per_page);

// Get students
$sql = "SELECT s.*, c.name as class_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE $where_clause 
        ORDER BY s.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$students = getRows($conn, $sql, $params);

// Get classes for filter
$classes = getRows($conn, "SELECT * FROM classes WHERE status = 'active' ORDER BY name");
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Manage Students</h1>
            <p class="page-subtitle">Total: <?php echo number_format($total_records); ?> students</p>
        </div>
        <div class="flex gap-md">
            <?php if (canPerformAction('create', 'students')): ?>
            <a href="add-student.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Student
            </a>
            <a href="import-students.php" class="btn btn-success">
                <i class="fas fa-file-import"></i> Import
            </a>
            <?php endif; ?>
            <a href="export-students.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outlined">
                <i class="fas fa-file-export"></i> Export
            </a>
        </div>
    </div>
</div>

<!-- Flash Message -->
<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> mb-lg">
    <i class="fas fa-<?php echo $_SESSION['flash_type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
    <?php echo escape($_SESSION['flash_message']); ?>
</div>
<?php 
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);
endif; 
?>

<!-- Filters -->
<div class="card mb-lg">
    <div class="card-body">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:2;min-width:200px;">
                <input type="text" name="search" class="form-control"
                    placeholder="Search by name, roll number, or email"
                    value="<?php echo escape($search); ?>">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                <select name="class" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($class['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:130px;">
                <select name="status" class="form-control">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active"   <?php echo $status_filter === 'active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="students.php" class="btn btn-outlined">
                <i class="fas fa-redo"></i> Reset
            </a>
        </form>
    </div>
</div>

<!-- Students Table -->

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr>
                    <td colspan="7" class="text-center text-secondary py-xl">
                        No students found
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="font-medium"><?php echo escape($student['roll_number']); ?></td>
                        <td>
                            <div class="flex items-center gap-sm">
                                <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                </div>
                                <?php echo escape($student['full_name']); ?>
                            </div>
                        </td>
                        <td><?php echo escape($student['class_name'] ?? 'N/A'); ?></td>
                        <td><?php echo escape($student['email']); ?></td>
                        <td><?php echo escape($student['phone'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-sm">
                                <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outlined" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (canPerformAction('update', 'students')): ?>
                                <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (canPerformAction('delete', 'students')): ?>
                                <button onclick="confirmDelete(<?php echo $student['id']; ?>, '<?php echo escape($student['full_name']); ?>')" class="btn btn-sm btn-error" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="pagination-item">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
               class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="pagination-item">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$extra_js = <<<'JS'
<script>
function confirmDelete(id, name) {
    confirmDialog(
        'Are you sure you want to delete student <strong>"' + name + '"</strong>? This action cannot be undone.',
        function() { window.location.href = 'students.php?delete=' + id; },
        null,
        { title: 'Delete Student' }
    );
}
</script>
JS;

require_once 'footer.php';
?>
