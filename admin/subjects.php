<?php
$page_title = 'Manage Subjects';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'subjects')) redirect('dashboard.php?error=unauthorized');

// Handle operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && canPerformAction('create', 'subjects')) {
    $subject_name = sanitizeInput($_POST['subject_name'] ?? '');
    $subject_code = sanitizeInput($_POST['subject_code'] ?? '');
    $total_marks = sanitizeInteger($_POST['total_marks'] ?? 100);
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $id = sanitizeInteger($_POST['id'] ?? 0);
    
    if (!empty($subject_name)) {
        if ($id > 0) {
            $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, total_marks = ?, status = ?, updated_at = NOW() WHERE id = ?";
            executeQuery($conn, $sql, [$subject_name, $subject_code, $total_marks, $status, $id]);
            $_SESSION['flash_message'] = 'Subject updated successfully';
        } else {
            $sql = "INSERT INTO subjects (subject_name, subject_code, total_marks, status, created_at) VALUES (?, ?, ?, ?, NOW())";
            executeQuery($conn, $sql, [$subject_name, $subject_code, $total_marks, $status]);
            $_SESSION['flash_message'] = 'Subject added successfully';
        }
        $_SESSION['flash_type'] = 'success';
        redirect('subjects.php');
    }
}

if (isset($_GET['delete']) && canPerformAction('delete', 'subjects')) {
    $id = sanitizeInteger($_GET['delete']);
    executeQuery($conn, "UPDATE subjects SET status = 'deleted' WHERE id = ?", [$id]);
    $_SESSION['flash_message'] = 'Subject deleted successfully';
    $_SESSION['flash_type'] = 'success';
    redirect('subjects.php');
}

require_once 'header.php';

$subjects = getRows($conn, "SELECT * FROM subjects WHERE status != 'deleted' ORDER BY subject_name");
$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <h1 class="page-title">Manage Subjects</h1>
        <?php if (canPerformAction('create', 'subjects')): ?>
        <div class="flex gap-sm">
            <a href="import-subjects.php" class="btn btn-outlined">
                <i class="fas fa-file-import"></i> Import Subjects
            </a>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Subject
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
                    <th>Subject Name</th>
                    <th>Subject Code</th>
                    <th>Total Marks</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td class="font-medium"><?php echo escape($subject['subject_name']); ?></td>
                    <td><?php echo escape($subject['subject_code'] ?? 'N/A'); ?></td>
                    <td><?php echo $subject['total_marks']; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $subject['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($subject['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-sm">
                            <?php if (canPerformAction('update', 'subjects')): ?>
                            <button onclick='editSubject(<?php echo htmlspecialchars(json_encode($subject), ENT_QUOTES); ?>)' class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (canPerformAction('delete', 'subjects')): ?>
                            <button onclick="confirmDelete(<?php echo $subject['id']; ?>, '<?php echo escape($subject['subject_name']); ?>')" class="btn btn-sm btn-error">
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

<div id="subjectModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Subject</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="subjectId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="subject_name" class="form-label required">Subject Name</label>
                    <input type="text" id="subject_name" name="subject_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject_code" class="form-label">Subject Code</label>
                    <input type="text" id="subject_code" name="subject_code" class="form-control">
                </div>
                <div class="form-group">
                    <label for="total_marks" class="form-label required">Total Marks</label>
                    <input type="number" id="total_marks" name="total_marks" class="form-control" value="100" required>
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
    document.getElementById('modalTitle').textContent = 'Add Subject';
    document.getElementById('subjectId').value = '';
    document.getElementById('subject_name').value = '';
    document.getElementById('subject_code').value = '';
    document.getElementById('total_marks').value = '100';
    document.getElementById('status').value = 'active';
    document.getElementById('subjectModal').classList.add('active');
}

function editSubject(data) {
    document.getElementById('modalTitle').textContent = 'Edit Subject';
    document.getElementById('subjectId').value = data.id;
    document.getElementById('subject_name').value = data.subject_name;
    document.getElementById('subject_code').value = data.subject_code || '';
    document.getElementById('total_marks').value = data.total_marks;
    document.getElementById('status').value = data.status;
    document.getElementById('subjectModal').classList.add('active');
}

function closeModal() {
    document.getElementById('subjectModal').classList.remove('active');
}

function confirmDelete(id, name) {
    confirmDialog(
        'Delete subject <strong>"' + name + '"</strong>? This action cannot be undone.',
        function() { window.location.href = 'subjects.php?delete=' + id; },
        null,
        { title: 'Delete Subject' }
    );
}
</script>
JS;

require_once 'footer.php';
?>
