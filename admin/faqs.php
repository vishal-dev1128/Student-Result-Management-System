<?php
$page_title = 'FAQs';
require_once '../includes/auth.php';
requireAdminAuth();

// Handle delete
if (isset($_GET['delete']) && canPerformAction('delete', 'notices')) {
    $id = sanitizeInteger($_GET['delete']);
    executeQuery($conn, "DELETE FROM faqs WHERE id = ?", [$id]);
    logActivity($conn, $_SESSION['admin_id'], 'faq_deleted', "Deleted FAQ ID: $id");
    $_SESSION['flash_message'] = 'FAQ deleted';
    $_SESSION['flash_type'] = 'success';
    redirect('faqs.php');
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $id = sanitizeInteger($_GET['toggle']);
    $row = getRow($conn, "SELECT is_published FROM faqs WHERE id = ?", [$id]);
    if ($row) {
        $new = $row['is_published'] ? 0 : 1;
        executeQuery($conn, "UPDATE faqs SET is_published = ? WHERE id = ?", [$new, $id]);
    }
    redirect('faqs.php');
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) redirect('faqs.php');
    $id          = sanitizeInteger($_POST['id'] ?? 0);
    $question    = sanitizeInput($_POST['question'] ?? '');
    $answer      = sanitizeInput($_POST['answer'] ?? '');
    $category    = sanitizeInput($_POST['category'] ?? 'General');
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if (!empty($question) && !empty($answer)) {
        if ($id > 0) {
            executeQuery($conn, "UPDATE faqs SET question=?, answer=?, category=?, is_published=?, updated_at=NOW() WHERE id=?",
                [$question, $answer, $category, $is_published, $id]);
            $_SESSION['flash_message'] = 'FAQ updated';
        } else {
            executeQuery($conn, "INSERT INTO faqs (question, answer, category, is_published, created_by, created_at) VALUES (?,?,?,?,?,NOW())",
                [$question, $answer, $category, $is_published, $_SESSION['admin_id']]);
            logActivity($conn, $_SESSION['admin_id'], 'faq_created', "Created FAQ: $question");
            $_SESSION['flash_message'] = 'FAQ created';
        }
        $_SESSION['flash_type'] = 'success';
    }
    redirect('faqs.php');
}

require_once 'header.php';

$faqs = [];
try {
    $faqs = getRows($conn, "SELECT f.*, au.full_name as created_by_name FROM faqs f LEFT JOIN admin_users au ON f.created_by = au.id ORDER BY f.category, f.sort_order, f.created_at DESC") ?: [];
} catch (Exception $e) { $faqs = []; }

// Group by category
$grouped = [];
foreach ($faqs as $faq) {
    $grouped[$faq['category']][] = $faq;
}

$csrf_token = generateCSRFToken();
?>

<style>
.faq-category-header { font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--color-text-secondary);padding:12px 0 6px;border-bottom:1px solid var(--color-border);margin-bottom:8px; }
.faq-item { padding:14px;border-radius:10px;border:1px solid var(--color-border);margin-bottom:8px;transition:box-shadow .15s; }
.faq-item:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.btn-icon { display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:7px;font-size:12px;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:all .15s; }
.btn-icon:hover { transform:translateY(-1px); }
</style>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">FAQs</h1>
            <p class="page-subtitle">Frequently asked questions for students</p>
        </div>
        <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Add FAQ</button>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> mb-lg">
    <?php echo escape($_SESSION['flash_message']); ?>
</div>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>

<?php if (empty($faqs)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:60px;">
        <i class="fas fa-question-circle fa-4x mb-lg" style="opacity:.2;"></i>
        <h3>No FAQs yet</h3>
        <p class="text-secondary">Add your first FAQ to help students get answers quickly.</p>
        <button onclick="openModal()" class="btn btn-primary mt-lg"><i class="fas fa-plus"></i> Add First FAQ</button>
    </div>
</div>
<?php else: ?>

<?php if (!empty($grouped)): ?>
    <?php foreach ($grouped as $category => $items): ?>
    <div class="card mb-lg">
        <div class="card-body">
            <div class="faq-category-header"><i class="fas fa-folder"></i> <?php echo escape($category); ?> (<?php echo count($items); ?>)</div>
            <?php foreach ($items as $faq): ?>
            <div class="faq-item">
                <div class="flex justify-between items-start gap-md">
                    <div style="flex:1;">
                        <div style="font-weight:600;margin-bottom:4px;"><?php echo escape($faq['question']); ?></div>
                        <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.5;"><?php echo escape(substr($faq['answer'], 0, 120)); ?>...</div>
                    </div>
                    <div class="flex gap-sm" style="flex-shrink:0;">
                        <span class="badge badge-<?php echo $faq['is_published'] ? 'success' : 'warning'; ?>" style="font-size:11px;">
                            <?php echo $faq['is_published'] ? 'Live' : 'Draft'; ?>
                        </span>
                        <a href="faqs.php?toggle=<?php echo $faq['id']; ?>" class="btn-icon" style="background:<?php echo $faq['is_published'] ? '#fef3c7;color:#d97706' : '#dcfce7;color:#16a34a'; ?>">
                            <i class="fas fa-<?php echo $faq['is_published'] ? 'eye-slash' : 'eye'; ?>"></i>
                        </a>
                        <button onclick='editFaq(<?php echo htmlspecialchars(json_encode($faq), ENT_QUOTES); ?>)' class="btn-icon" style="background:#dbeafe;color:#2563eb;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDeleteFaq(<?php echo $faq['id']; ?>, '<?php echo escape($faq['question']); ?>')" class="btn-icon" style="background:#fee2e2;color:#dc2626;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<!-- FAQ Modal -->
<div id="faqModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="faqModalTitle">Add FAQ</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="faqId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Question</label>
                    <input type="text" name="question" id="faqQuestion" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Answer</label>
                    <textarea name="answer" id="faqAnswer" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" id="faqCategory" class="form-control" placeholder="e.g. Results, Login, Profile" value="General">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" id="faqPublished" class="form-checkbox">
                        <span>Publish immediately</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-outlined">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save FAQ</button>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function openModal() {
    document.getElementById('faqModalTitle').textContent = 'Add FAQ';
    document.getElementById('faqId').value = '';
    document.getElementById('faqQuestion').value = '';
    document.getElementById('faqAnswer').value = '';
    document.getElementById('faqCategory').value = 'General';
    document.getElementById('faqPublished').checked = false;
    document.getElementById('faqModal').classList.add('active');
}
function editFaq(data) {
    document.getElementById('faqModalTitle').textContent = 'Edit FAQ';
    document.getElementById('faqId').value = data.id;
    document.getElementById('faqQuestion').value = data.question;
    document.getElementById('faqAnswer').value = data.answer;
    document.getElementById('faqCategory').value = data.category || 'General';
    document.getElementById('faqPublished').checked = data.is_published == 1;
    document.getElementById('faqModal').classList.add('active');
}
function closeModal() {
    document.getElementById('faqModal').classList.remove('active');
}
function confirmDeleteFaq(id, q) {
    confirmDialog('Delete FAQ: <strong>"' + q + '"</strong>?',
        function() { window.location.href = 'faqs.php?delete=' + id; },
        null, { title: 'Delete FAQ' });
}
</script>
JS;
require_once 'footer.php';
?>
