<?php
$page_title = 'Manage Exams';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'exams')) redirect('dashboard.php?error=unauthorized');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && canPerformAction('create', 'exams')) {
    $exam_name = sanitizeInput($_POST['exam_name'] ?? '');
    $exam_date = sanitizeInput($_POST['exam_date'] ?? '');
    $class_id = sanitizeInteger($_POST['class_id'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $id = sanitizeInteger($_POST['id'] ?? 0);
    
    if (!empty($exam_name)) {
        if ($id > 0) {
            $sql = "UPDATE exams SET exam_name = ?, exam_date = ?, class_id = ?, is_published = ?, status = ?, updated_at = NOW() WHERE id = ?";
            executeQuery($conn, $sql, [$exam_name, $exam_date, $class_id, $is_published, $status, $id]);
            $_SESSION['flash_message'] = 'Exam updated successfully';
        } else {
            $sql = "INSERT INTO exams (exam_name, exam_date, class_id, is_published, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            executeQuery($conn, $sql, [$exam_name, $exam_date, $class_id, $is_published, $status]);
            $_SESSION['flash_message'] = 'Exam added successfully';
        }
        $_SESSION['flash_type'] = 'success';
        redirect('exams.php');
    }
}

if (isset($_GET['delete']) && canPerformAction('delete', 'exams')) {
    $id = sanitizeInteger($_GET['delete']);
    executeQuery($conn, "UPDATE exams SET status = 'deleted' WHERE id = ?", [$id]);
    $_SESSION['flash_message'] = 'Exam deleted successfully';
    $_SESSION['flash_type'] = 'success';
    redirect('exams.php');
}

require_once 'header.php';

$exams_sql = "SELECT e.*, c.name as class_name FROM exams e 
              LEFT JOIN classes c ON e.class_id = c.id 
              ORDER BY e.exam_date DESC";
$exams = getRows($conn, $exams_sql);
// Get classes for dropdown
$classes = getRows($conn, "SELECT * FROM classes WHERE status = 'active' ORDER BY name");
$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <h1 class="page-title">Manage Exams</h1>
        <?php if (canPerformAction('create', 'exams')): ?>
        <div class="flex gap-sm">
            <a href="import-exams.php" class="btn btn-outlined">
                <i class="fas fa-file-import"></i> Import Exams
            </a>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Exam
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> mb-lg">
    <?php echo escape($_SESSION['flash_message']); ?>
</div>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Class</th>
                    <th>Date</th>
                    <th>Published</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exams as $exam): ?>
                <tr>
                    <td class="font-medium"><?php echo escape($exam['exam_name']); ?></td>
                    <td><?php echo escape($exam['class_name'] ?? 'All Classes'); ?></td>
                    <td><?php echo formatDate($exam['exam_date']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $exam['is_published'] ? 'success' : 'warning'; ?>">
                            <?php echo $exam['is_published'] ? 'Published' : 'Draft'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $exam['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($exam['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-sm">
                            <?php if (canPerformAction('update', 'exams')): ?>
                            <button onclick='editExam(<?php echo htmlspecialchars(json_encode($exam), ENT_QUOTES); ?>)' class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (canPerformAction('delete', 'exams')): ?>
                            <button onclick="confirmDelete(<?php echo $exam['id']; ?>, '<?php echo escape($exam['exam_name']); ?>')" class="btn btn-sm btn-error">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="examModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Exam</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="examId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="exam_name" class="form-label required">Exam Name</label>
                    <input type="text" id="exam_name" name="exam_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="exam_date" class="form-label required">Exam Date</label>
                    <input type="date" id="exam_date" name="exam_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="class_id" class="form-label">Class</label>
                    <select id="class_id" name="class_id" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo escape($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" id="is_published" class="form-checkbox">
                        <span>Publish Results</span>
                    </label>
                </div>
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-outlined">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Exam';
    document.getElementById('examId').value = '';
    document.getElementById('exam_name').value = '';
    document.getElementById('exam_date').value = '';
    document.getElementById('class_id').value = '';
    document.getElementById('is_published').checked = false;
    document.getElementById('status').value = 'active';
    document.getElementById('examModal').classList.add('active');
}

function editExam(data) {
    document.getElementById('modalTitle').textContent = 'Edit Exam';
    document.getElementById('examId').value = data.id;
    document.getElementById('exam_name').value = data.exam_name;
    document.getElementById('exam_date').value = data.exam_date;
    document.getElementById('class_id').value = data.class_id || '';
    document.getElementById('is_published').checked = data.is_published == 1;
    document.getElementById('status').value = data.status;
    document.getElementById('examModal').classList.add('active');
}

function closeModal() {
    document.getElementById('examModal').classList.remove('active');
}

function confirmDelete(id, name) {
    confirmDialog(
        'Delete exam <strong>"' + name + '"</strong>? This action cannot be undone.',
        function() { window.location.href = 'exams.php?delete=' + id; },
        null,
        { title: 'Delete Exam' }
    );
}
</script>
JS;

require_once 'footer.php';
?>
