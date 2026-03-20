<?php
$page_title = 'Manage Results';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'results')) {
    redirect('dashboard.php?error=unauthorized');
}

// Handle delete
if (isset($_GET['delete']) && canPerformAction('delete', 'results')) {
    $id = sanitizeInteger($_GET['delete']);
    
    // Fetch student_id and exam_id to safely delete from both tables
    $summary = getRow($conn, "SELECT student_id, exam_id FROM result_summary WHERE id = ?", [$id]);
    
    if ($summary) {
        $conn->begin_transaction();
        try {
            // Delete all subject marks for this student + exam
            executeQuery($conn, "DELETE FROM results WHERE student_id = ? AND exam_id = ?", [$summary['student_id'], $summary['exam_id']]);
            
            // Delete the summary result record
            executeQuery($conn, "DELETE FROM result_summary WHERE id = ?", [$id]);
            
            $conn->commit();
            logActivity($conn, $_SESSION['admin_id'], 'result_deleted', "Deleted result ID: $id");
            $_SESSION['flash_message'] = 'Result deleted successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = 'Error deleting result: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
    redirect('results.php');
}

$page_title = 'Manage Results';
require_once 'header.php';

// Get filters
$exam_filter = sanitizeInteger($_GET['exam'] ?? '');
$class_filter = sanitizeInteger($_GET['class'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$where = ["1=1"];
$params = [];

if (!empty($exam_filter)) {
    $where[] = "r.exam_id = ?";
    $params[] = $exam_filter;
}

if (!empty($class_filter)) {
    $where[] = "s.class_id = ?";
    $params[] = $class_filter;
}

if (!empty($search)) {
    $where[] = "(s.full_name LIKE ? OR s.roll_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$count_sql = "SELECT COUNT(*) FROM result_summary r 
              INNER JOIN students s ON r.student_id = s.id 
              WHERE $where_clause";
$total_records = getValue($conn, $count_sql, $params);
$total_pages = ceil($total_records / $per_page);

// Get results
$sql = "SELECT r.*, r.total_marks as total_marks_obtained, r.max_marks as total_marks, s.roll_number, s.full_name, e.exam_name, e.exam_date, c.name as class_name
        FROM result_summary r
        JOIN students s ON r.student_id = s.id
        JOIN exams e ON r.exam_id = e.id
        JOIN classes c ON s.class_id = c.id
        WHERE $where_clause
        ORDER BY e.exam_date DESC, s.roll_number ASC
        LIMIT $per_page OFFSET $offset";
$results = getRows($conn, $sql, $params);

// Get exams and classes for filters
$exams = getRows($conn, "SELECT id, exam_name FROM exams WHERE status = 'active' ORDER BY exam_date DESC");
$classes = getRows($conn, "SELECT id, name as class_name FROM classes WHERE status = 'active' ORDER BY name");
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Manage Results</h1>
            <p class="page-subtitle">Total: <?php echo number_format($total_records); ?> results</p>
        </div>
        <div class="flex gap-md">
            <?php if (canPerformAction('create', 'results')): ?>
            <a href="add-result.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Result
            </a>
            <a href="import-results.php" class="btn btn-success">
                <i class="fas fa-file-import"></i> Import
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> mb-lg">
    <?php echo escape($_SESSION['flash_message']); ?>
</div>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>

<!-- Filters -->
<div class="card mb-lg">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-4 gap-md">
            <div class="form-group mb-0">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Search by name or roll number"
                    value="<?php echo escape($search); ?>"
                >
            </div>
            
            <div class="form-group mb-0">
                <select name="exam" class="form-control">
                    <option value="">All Exams</option>
                    <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo $exam['id']; ?>" <?php echo $exam_filter == $exam['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($exam['exam_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-0">
                <select name="class" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($class['class_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex gap-sm">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="results.php" class="btn btn-outlined">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Exam</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                <tr>
                    <td colspan="8" class="text-center text-secondary py-xl">No results found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td class="font-medium"><?php echo escape($result['roll_number']); ?></td>
                        <td><?php echo escape($result['full_name']); ?></td>
                        <td><?php echo escape($result['class_name'] ?? 'N/A'); ?></td>
                        <td>
                            <div><?php echo escape($result['exam_name']); ?></div>
                            <div class="text-sm text-secondary"><?php echo formatDate($result['exam_date']); ?></div>
                        </td>
                        <td><?php echo $result['total_marks_obtained']; ?>/<?php echo $result['total_marks']; ?></td>
                        <td><?php echo number_format($result['percentage'], 2); ?>%</td>
                        <td>
                            <span class="grade-badge grade-<?php echo strtolower($result['grade']); ?>">
                                <?php echo $result['grade']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-sm">
                                <a href="view-result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outlined" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (canPerformAction('update', 'results')): ?>
                                <a href="edit-result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (canPerformAction('delete', 'results')): ?>
                                <button onclick="confirmDelete(<?php echo $result['id']; ?>, '<?php echo escape($result['full_name']); ?>')" class="btn btn-sm btn-error" title="Delete">
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
        'Delete result for <strong>"' + name + '"</strong>? This action cannot be undone.',
        function() { window.location.href = 'results.php?delete=' + id; },
        null,
        { title: 'Delete Result' }
    );
}
</script>
JS;

require_once 'footer.php';
?>
