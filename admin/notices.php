<?php
$page_title = 'Manage Notices';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'notices')) {
    redirect('dashboard.php?error=unauthorized');
}

// Handle delete
if (isset($_GET['delete']) && canPerformAction('delete', 'notices')) {
    $id = sanitizeInteger($_GET['delete']);
    if (executeQuery($conn, "DELETE FROM notices WHERE id = ?", [$id])) {
        logActivity($conn, $_SESSION['admin_id'], 'notice_deleted', "Deleted notice ID: $id");
        $_SESSION['flash_message'] = 'Notice deleted successfully';
        $_SESSION['flash_type'] = 'success';
    }
    redirect('notices.php');
}

// Handle toggle publish
if (isset($_GET['toggle']) && canPerformAction('update', 'notices')) {
    $id = sanitizeInteger($_GET['toggle']);
    $notice = getRow($conn, "SELECT is_published FROM notices WHERE id = ?", [$id]);
    if ($notice) {
        $new_status = $notice['is_published'] ? 0 : 1;
        executeQuery($conn, "UPDATE notices SET is_published = ?, updated_at = NOW() WHERE id = ?", [$new_status, $id]);
        $_SESSION['flash_message'] = $new_status ? 'Notice published' : 'Notice unpublished';
        $_SESSION['flash_type'] = 'success';
    }
    redirect('notices.php');
}

// Handle create/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && canPerformAction('create', 'notices')) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Invalid request.';
        $_SESSION['flash_type'] = 'error';
        redirect('notices.php');
    }
    $id          = sanitizeInteger($_POST['id'] ?? 0);
    $title       = sanitizeInput($_POST['title'] ?? '');
    $content     = sanitizeInput($_POST['content'] ?? '');
    $priority    = sanitizeInput($_POST['priority'] ?? 'normal');
    $expiry_date = sanitizeInput($_POST['expiry_date'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if (empty($title) || empty($content)) {
        $_SESSION['flash_message'] = 'Title and content are required.';
        $_SESSION['flash_type'] = 'error';
        redirect('notices.php');
    }

    $expiry = !empty($expiry_date) ? $expiry_date : null;

    if ($id > 0) {
        $sql = "UPDATE notices SET title=?, content=?, priority=?, expiry_date=?, is_published=?, updated_at=NOW() WHERE id=?";
        executeQuery($conn, $sql, [$title, $content, $priority, $expiry, $is_published, $id]);
        logActivity($conn, $_SESSION['admin_id'], 'notice_updated', "Updated notice: $title");
        $_SESSION['flash_message'] = 'Notice updated successfully';
    } else {
        $sql = "INSERT INTO notices (title, content, priority, expiry_date, is_published, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        executeQuery($conn, $sql, [$title, $content, $priority, $expiry, $is_published, $_SESSION['admin_id']]);
        logActivity($conn, $_SESSION['admin_id'], 'notice_created', "Created notice: $title");
        $_SESSION['flash_message'] = 'Notice created successfully';
    }
    $_SESSION['flash_type'] = 'success';
    redirect('notices.php');
}

require_once 'header.php';

$notices = getRows($conn, "SELECT n.*, au.full_name as created_by_name FROM notices n LEFT JOIN admin_users au ON n.created_by = au.id ORDER BY n.created_at DESC") ?: [];
$csrf_token = generateCSRFToken();
?>

<style>
.notice-priority { display:inline-block; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.priority-urgent  { background:#fee2e2;color:#dc2626; }
.priority-high    { background:#fef3c7;color:#d97706; }
.priority-normal  { background:#dbeafe;color:#2563eb; }
.priority-low     { background:#f0fdf4;color:#16a34a; }
.btn-action { display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:500;border:none;cursor:pointer;text-decoration:none;transition:all .15s; }
.btn-action:hover { transform:translateY(-1px); }
</style>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Manage Notices</h1>
            <p class="page-subtitle">Create and manage announcements for students</p>
        </div>
        <?php if (canPerformAction('create', 'notices')): ?>
        <button onclick="openModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Notice
        </button>
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
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Expires</th>
                    <th>Created By</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notices)): ?>
                <tr><td colspan="7" class="text-center text-secondary py-xl">No notices yet. Create your first notice!</td></tr>
                <?php else: ?>
                <?php foreach ($notices as $notice): ?>
                <tr>
                    <td>
                        <div class="font-medium"><?php echo escape($notice['title']); ?></div>
                        <div class="text-sm text-secondary"><?php echo escape(substr($notice['content'], 0, 60)); ?>...</div>
                    </td>
                    <td>
                        <span class="notice-priority priority-<?php echo $notice['priority']; ?>">
                            <?php echo ucfirst($notice['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $notice['is_published'] ? 'success' : 'warning'; ?>">
                            <?php echo $notice['is_published'] ? 'Published' : 'Draft'; ?>
                        </span>
                    </td>
                    <td class="text-sm"><?php echo $notice['expiry_date'] ? formatDate($notice['expiry_date']) : '—'; ?></td>
                    <td class="text-sm"><?php echo escape($notice['created_by_name'] ?? 'System'); ?></td>
                    <td class="text-sm text-secondary"><?php echo timeAgo($notice['created_at']); ?></td>
                    <td>
                        <div class="flex gap-sm">
                            <?php if (canPerformAction('update', 'notices')): ?>
                            <a href="notices.php?toggle=<?php echo $notice['id']; ?>" class="btn-action" style="background:<?php echo $notice['is_published'] ? '#fef3c7;color:#d97706' : '#dcfce7;color:#16a34a'; ?>" title="<?php echo $notice['is_published'] ? 'Unpublish' : 'Publish'; ?>">
                                <i class="fas fa-<?php echo $notice['is_published'] ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <button onclick='editNotice(<?php echo htmlspecialchars(json_encode($notice), ENT_QUOTES); ?>)' class="btn-action" style="background:#dbeafe;color:#2563eb;" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (canPerformAction('delete', 'notices')): ?>
                            <button onclick="confirmDelete(<?php echo $notice['id']; ?>, '<?php echo escape($notice['title']); ?>')" class="btn-action" style="background:#fee2e2;color:#dc2626;" title="Delete">
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
</div>

<!-- Notice Modal -->
<div id="noticeModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content" style="max-width:580px;">
        <div class="modal-header">
            <h3 id="modalTitle">New Notice</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="noticeId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Title</label>
                    <input type="text" name="title" id="noticeTitle" class="form-control" required maxlength="200">
                </div>
                <div class="form-group">
                    <label class="form-label required">Content</label>
                    <textarea name="content" id="noticeContent" class="form-control" rows="4" required></textarea>
                </div>
                <div class="grid grid-cols-2 gap-md">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" id="noticePriority" class="form-control">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" id="noticeExpiry" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" id="noticePublished" class="form-checkbox">
                        <span>Publish immediately</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-outlined">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Notice</button>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function openModal() {
    document.getElementById('modalTitle').textContent = 'New Notice';
    document.getElementById('noticeId').value = '';
    document.getElementById('noticeTitle').value = '';
    document.getElementById('noticeContent').value = '';
    document.getElementById('noticePriority').value = 'normal';
    document.getElementById('noticeExpiry').value = '';
    document.getElementById('noticePublished').checked = false;
    document.getElementById('noticeModal').classList.add('active');
}
function editNotice(data) {
    document.getElementById('modalTitle').textContent = 'Edit Notice';
    document.getElementById('noticeId').value = data.id;
    document.getElementById('noticeTitle').value = data.title;
    document.getElementById('noticeContent').value = data.content;
    document.getElementById('noticePriority').value = data.priority;
    document.getElementById('noticeExpiry').value = data.expiry_date || '';
    document.getElementById('noticePublished').checked = data.is_published == 1;
    document.getElementById('noticeModal').classList.add('active');
}
function closeModal() {
    document.getElementById('noticeModal').classList.remove('active');
}
function confirmDelete(id, title) {
    confirmDialog(
        'Delete notice <strong>"' + title + '"</strong>? This cannot be undone.',
        function() { window.location.href = 'notices.php?delete=' + id; },
        null, { title: 'Delete Notice' }
    );
}
</script>
JS;
require_once 'footer.php';
?>
