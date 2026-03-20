<?php
$page_title = 'Import Subjects';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('create', 'subjects')) {
    redirect('dashboard.php?error=unauthorized');
}

// ─── Download CSV Template ────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subjects_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Subject Name*',
        'Subject Code*',
        'Total Marks* (number)',
        'Status (active/inactive)'
    ]);
    // Sample row
    fputcsv($out, ['Mathematics', 'MATH-101', '100', 'active']);
    fputcsv($out, ['Physics', 'PHYS-101', '100', 'active']);
    fclose($out);
    exit;
}

// ─── Handle CSV Upload ────────────────────────────────────────────────────────
$imported   = 0;
$skipped    = [];
$upload_err = null;
$show_result = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $upload_err = 'Invalid request. Please try again.';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_err = 'Please select a CSV file to upload.';
    } else {
        $file      = $_FILES['csv_file'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size  = 10 * 1024 * 1024; // 10 MB

        if ($ext !== 'csv') {
            $upload_err = 'Only CSV files are allowed.';
        } elseif ($file['size'] > $max_size) {
            $upload_err = 'File size exceeds the 10 MB limit.';
        } else {
            $handle   = fopen($file['tmp_name'], 'r');
            $row_num  = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;
                if ($row_num === 1) continue;
                if (empty(array_filter($row))) continue;

                $subject_name = sanitizeInput($row[0] ?? '');
                $subject_code = sanitizeInput($row[1] ?? '');
                $total_marks  = sanitizeInteger($row[2] ?? 100);
                $status       = strtolower(sanitizeInput($row[3] ?? 'active'));
                
                if (!in_array($status, ['active', 'inactive'])) $status = 'active';

                $row_label = "Row $row_num";

                if (empty($subject_name) || empty($subject_code)) { 
                    $skipped[] = "$row_label: Subject Name and Code are required."; 
                    continue; 
                }

                // Check duplicate subject code
                $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND status != 'deleted'");
                $stmt->bind_param("s", $subject_code);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row_data = $result->fetch_assoc();
                    $update_sql = "UPDATE subjects SET subject_name = ?, total_marks = ?, status = ?, updated_at = NOW() WHERE id = ?";
                    if (executeQuery($conn, $update_sql, [$subject_name, $total_marks, $status, $row_data['id']])) {
                        $imported++;
                        logActivity($conn, $_SESSION['admin_id'], 'subject_imported', "Updated existing subject: $subject_name");
                    } else {
                        $skipped[] = "$row_label ($subject_name): Database error — could not update.";
                    }
                } else {
                    $ins_sql = "INSERT INTO subjects (subject_name, subject_code, total_marks, status, created_at) VALUES (?, ?, ?, ?, NOW())";
                    if (executeQuery($conn, $ins_sql, [$subject_name, $subject_code, $total_marks, $status])) {
                        $imported++;
                        logActivity($conn, $_SESSION['admin_id'], 'subject_imported', "Imported new subject: $subject_name");
                    } else {
                        $skipped[] = "$row_label ($subject_name): Database error — could not insert.";
                    }
                }
            }
            fclose($handle);
            $show_result = true;
        }
    }
}

require_once 'header.php';
$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Import Subjects</h1>
            <p class="page-subtitle">Bulk upload subjects from a CSV file</p>
        </div>
        <div class="flex gap-md">
            <a href="?action=template" class="btn btn-outlined">
                <i class="fas fa-download"></i> Download Template
            </a>
            <a href="subjects.php" class="btn btn-outlined">
                <i class="fas fa-arrow-left"></i> Back to Subjects
            </a>
        </div>
    </div>
</div>

<?php if ($upload_err): ?>
<div class="alert alert-error mb-lg">
    <i class="fas fa-exclamation-circle"></i> <?php echo escape($upload_err); ?>
</div>
<?php endif; ?>

<?php if ($show_result): ?>
<div class="card mb-lg">
    <div class="card-body">
        <h3 class="mb-md"><i class="fas fa-chart-bar"></i> Import Summary</h3>

        <div class="grid grid-cols-2 gap-md mb-lg" style="max-width:500px;">
            <div class="card" style="background:#1b5e20;border:none;">
                <div class="card-body text-center" style="padding:1.2rem;">
                    <div style="font-size:2.5rem;font-weight:700;color:#ffffff;"><?php echo $imported; ?></div>
                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.85);margin-top:4px;">Subjects Imported</div>
                </div>
            </div>
            <div class="card" style="background:<?php echo count($skipped) > 0 ? '#b71c1c' : '#37474f'; ?>;border:none;">
                <div class="card-body text-center" style="padding:1.2rem;">
                    <div style="font-size:2.5rem;font-weight:700;color:#ffffff;"><?php echo count($skipped); ?></div>
                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.85);margin-top:4px;">Rows Skipped</div>
                </div>
            </div>
        </div>

        <?php if (!empty($skipped)): ?>
        <h4 class="mb-sm" style="color:var(--color-warning);">
            <i class="fas fa-exclamation-triangle"></i> Skipped Rows
        </h4>
        <div style="max-height:280px;overflow-y:auto;border:1px solid var(--color-border);border-radius:6px;padding:0.75rem;">
            <ul style="margin:0;padding-left:1.2rem;">
                <?php foreach ($skipped as $reason): ?>
                <li style="font-size:0.85rem;margin-bottom:4px;color:var(--color-text-primary);"><?php echo escape($reason); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($imported > 0): ?>
        <div class="alert alert-success mt-md">
            <i class="fas fa-check-circle"></i>
            <?php echo $imported; ?> subject<?php echo $imported !== 1 ? 's' : ''; ?> imported successfully.
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

<div class="card slide-in-up">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="mb-lg">
                <h3 class="mb-md">Upload CSV File</h3>
                <p class="text-secondary mb-md">
                    Please ensure your CSV file strictly matches the template structure. Minimum required fields are <strong>Subject Name</strong> and <strong>Subject Code</strong>.
                </p>
                <div class="upload-zone" id="uploadZone" style="border: 2px dashed var(--color-border); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center; cursor: pointer; transition: all 0.2s ease;">
                    <i class="fas fa-file-csv mb-sm" style="font-size: 3rem; color: var(--color-primary); opacity: 0.8;"></i>
                    <h4 class="mb-xs font-semibold">Click or drag a CSV file to upload</h4>
                    <p class="text-sm text-secondary" id="fileNameDisplay">Maximum file size: 10 MB</p>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" class="sr-only" style="display:none;" required>
                </div>
            </div>

            <div class="flex justify-end gap-md pt-lg" style="border-top: 1px solid var(--color-border);">
                <a href="subjects.php" class="btn btn-outlined">Cancel</a>
                <button type="submit" class="btn btn-primary" id="importBtn" disabled>
                    <i class="fas fa-cloud-upload-alt"></i> Import Now
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('csv_file');
const display = document.getElementById('fileNameDisplay');
const importBtn = document.getElementById('importBtn');

uploadZone.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', function() {
    if (this.files && this.files.length > 0) {
        display.innerHTML = `<span style="color:var(--color-success);font-weight:600;">Selected: ${this.files[0].name}</span>`;
        uploadZone.style.borderColor = 'var(--color-primary)';
        uploadZone.style.backgroundColor = 'rgba(21, 101, 192, 0.03)';
        importBtn.disabled = false;
    } else {
        display.textContent = 'Maximum file size: 10 MB';
        uploadZone.style.borderColor = 'var(--color-border)';
        uploadZone.style.backgroundColor = 'transparent';
        importBtn.disabled = true;
    }
});

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadZone.addEventListener(eventName, preventDefaults, false);
});
function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

['dragenter', 'dragover'].forEach(eventName => {
    uploadZone.addEventListener(eventName, () => uploadZone.style.borderColor = 'var(--color-primary)', false);
});
['dragleave', 'drop'].forEach(eventName => {
    uploadZone.addEventListener(eventName, () => {
        if(!fileInput.files.length) uploadZone.style.borderColor = 'var(--color-border)';
    }, false);
});

uploadZone.addEventListener('drop', function(e) {
    let dt = e.dataTransfer;
    let files = dt.files;
    if (files.length) {
        fileInput.files = files;
        let event = new Event('change');
        fileInput.dispatchEvent(event);
    }
});
</script>

<?php require_once 'footer.php'; ?>
