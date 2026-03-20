<?php
$page_title = 'Manage Classes';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'classes')) {
    redirect('dashboard.php?error=unauthorized');
}

// Handle delete
if (isset($_GET['delete']) && canPerformAction('delete', 'classes')) {
    $id = sanitizeInteger($_GET['delete']);
    
    // Check if class has students
    $student_count = (int)getValue($conn, "SELECT COUNT(*) FROM students WHERE class_id = ?", [$id]);
    
    if ($student_count > 0) {
        $_SESSION['flash_message'] = "Cannot delete class — it has $student_count students assigned. Please move or delete them first.";
        $_SESSION['flash_type'] = 'error';
    } else {
        $delete_sql = "DELETE FROM classes WHERE id = ?";
        if (executeQuery($conn, $delete_sql, [$id])) {
            logActivity($conn, $_SESSION['admin_id'], 'class_deleted', "Deleted class ID: $id");
            $_SESSION['flash_message'] = 'Class deleted successfully';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Cannot delete class — it may be linked to other records (like exams).';
            $_SESSION['flash_type'] = 'error';
        }
    }
    redirect('classes.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && canPerformAction('create', 'classes')) {
    $class_name = sanitizeInput($_POST['class_name'] ?? '');
    $section = sanitizeInput($_POST['section'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $id = sanitizeInteger($_POST['id'] ?? 0);
    
    if (!empty($class_name)) {
        if ($id > 0) {
            // Update
            $sql = "UPDATE classes SET name = ?, section = ?, status = ?, updated_at = NOW() WHERE id = ?";
            executeQuery($conn, $sql, [$class_name, $section, $status, $id]);
            $_SESSION['flash_message'] = 'Class updated successfully';
        } else {
            // Insert
            $sql = "INSERT INTO classes (name, section, status, created_at) VALUES (?, ?, ?, NOW())";
            executeQuery($conn, $sql, [$class_name, $section, $status]);
            $_SESSION['flash_message'] = 'Class added successfully';
        }
        $_SESSION['flash_type'] = 'success';
        redirect('classes.php');
    }
}

require_once 'header.php';

$classes = getRows($conn, "SELECT id, name as class_name, section, status FROM classes WHERE status != 'deleted' ORDER BY name");
$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <h1 class="page-title">Manage Classes</h1>
        <?php if (canPerformAction('create', 'classes')): ?>
        <div class="flex gap-sm">
            <a href="import-classes.php" class="btn btn-outlined">
                <i class="fas fa-file-import"></i> Import Classes
            </a>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Class
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
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Students</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): 
                    $count_sql = "SELECT COUNT(*) FROM students WHERE class_id = ?";
                    $student_count = getValue($conn, $count_sql, [$class['id']]);
                ?>
                <tr>
                    <td class="font-medium"><?php echo escape($class['class_name']); ?></td>
                    <td><?php echo escape($class['section'] ?? 'N/A'); ?></td>
                    <td><?php echo $student_count; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $class['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($class['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-sm">
                            <?php if (canPerformAction('update', 'classes')): ?>
                            <button onclick='editClass(<?php echo htmlspecialchars(json_encode($class), ENT_QUOTES); ?>)' class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (canPerformAction('delete', 'classes')): ?>
                                <?php if ($student_count == 0): ?>
                                <button onclick="confirmDelete(<?php echo $class['id']; ?>, '<?php echo escape($class['class_name']); ?>')" class="btn btn-sm btn-error" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <button disabled class="btn btn-sm btn-error" title="Cannot delete: Class has <?php echo $student_count; ?> active students">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="classModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Class</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="classId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="class_name" class="form-label required">Class Name</label>
                    <input type="text" id="class_name" name="class_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="section" class="form-label">Section</label>
                    <input type="text" id="section" name="section" class="form-control">
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-backdrop" onclick="closeDeleteModal()"></div>
    <div class="modal-content" style="max-width:420px">
        <div class="modal-header" style="background:var(--color-error);color:white">
            <h3><i class="fas fa-exclamation-triangle" style="margin-right:8px"></i>Delete Class</h3>
            <button class="modal-close" onclick="closeDeleteModal()" style="color:white">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center;padding:var(--spacing-xl)">
            <p style="font-size:var(--font-size-lg);margin-bottom:var(--spacing-sm)">Are you sure you want to delete</p>
            <p id="deleteClassName" style="font-weight:var(--font-weight-bold);font-size:var(--font-size-xl);color:var(--color-error);margin-bottom:0"></p>
        </div>
        <div class="modal-footer" style="justify-content:center;gap:var(--spacing-md)">
            <button type="button" onclick="closeDeleteModal()" class="btn btn-outlined">Cancel</button>
            <a id="deleteConfirmLink" href="#" class="btn btn-error"><i class="fas fa-trash"></i> Delete</a>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Class';
    document.getElementById('classId').value = '';
    document.getElementById('class_name').value = '';
    document.getElementById('section').value = '';
    document.getElementById('status').value = 'active';
    document.getElementById('classModal').classList.add('active');
}

function editClass(classData) {
    document.getElementById('modalTitle').textContent = 'Edit Class';
    document.getElementById('classId').value = classData.id;
    document.getElementById('class_name').value = classData.class_name;
    document.getElementById('section').value = classData.section || '';
    document.getElementById('status').value = classData.status;
    document.getElementById('classModal').classList.add('active');
}

function closeModal() {
    document.getElementById('classModal').classList.remove('active');
}

function confirmDelete(id, name) {
    document.getElementById('deleteClassName').textContent = '"' + name + '"';
    document.getElementById('deleteConfirmLink').href = 'classes.php?delete=' + id;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}
</script>
JS;

require_once 'footer.php';
?>
