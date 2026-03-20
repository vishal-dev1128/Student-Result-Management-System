<?php
$page_title = 'Import Students';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('create', 'students')) {
    redirect('dashboard.php?error=unauthorized');
}

// ─── Download CSV Template ────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Full Name*',
        'Roll Number*',
        'Class Name*',
        'Email*',
        'Phone',
        'Date of Birth* (YYYY-MM-DD)',
        'Gender (male/female/other)',
        'Address',
        'Parent Name',
        'Parent Phone',
    ]);
    // Sample row
    fputcsv($out, [
        'John Doe', 'STU-2025-001', 'Class 10A',
        'john.doe@example.com', '9876543210', '2010-05-15',
        'male', '123 Main Street', 'Mr. Doe', '9876543211',
    ]);
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
            // Load all classes into a lookup map  [name => id]
            $class_rows = getRows($conn, "SELECT id, name FROM classes WHERE status = 'active'");
            $class_map  = [];
            foreach ($class_rows as $cr) {
                $class_map[strtolower(trim($cr['name']))] = $cr['id'];
            }

            $handle   = fopen($file['tmp_name'], 'r');
            $row_num  = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;
                // Skip header row (first row)
                if ($row_num === 1) {
                    continue;
                }
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map columns
                $full_name    = sanitizeInput($row[0] ?? '');
                $roll_number  = sanitizeInput($row[1] ?? '');
                $class_name   = trim($row[2] ?? '');
                $email        = sanitizeEmail($row[3] ?? '');
                $phone        = sanitizeInput($row[4] ?? '');
                $dob          = sanitizeInput($row[5] ?? '');
                $gender       = strtolower(sanitizeInput($row[6] ?? ''));
                $address      = sanitizeInput($row[7] ?? '');
                $parent_name  = sanitizeInput($row[8] ?? '');
                $parent_phone = sanitizeInput($row[9] ?? '');

                $row_label = "Row $row_num";

                // Required field validation
                if (empty($full_name))   { $skipped[] = "$row_label: Full Name is required."; continue; }
                if (empty($roll_number)) { $skipped[] = "$row_label ($full_name): Roll Number is required."; continue; }
                if (empty($class_name))  { $skipped[] = "$row_label ($full_name): Class Name is required."; continue; }
                if (empty($email))       { $skipped[] = "$row_label ($full_name): Email is required."; continue; }
                if (empty($dob))         { $skipped[] = "$row_label ($full_name): Date of Birth is required."; continue; }

                // Validate date format
                $dob_parsed = DateTime::createFromFormat('Y-m-d', $dob);
                if (!$dob_parsed || $dob_parsed->format('Y-m-d') !== $dob) {
                    $skipped[] = "$row_label ($full_name): Date of Birth must be in YYYY-MM-DD format.";
                    continue;
                }

                // Validate gender
                if (!empty($gender) && !in_array($gender, ['male', 'female', 'other'])) {
                    $gender = null;
                }

                // Resolve class name to id — auto-create if missing
                $class_key = strtolower($class_name);
                if (!isset($class_map[$class_key])) {
                    // Class doesn't exist yet — create it automatically
                    $create_class_sql = "INSERT INTO classes (name, status, created_at) VALUES (?, 'active', NOW())";
                    if (executeQuery($conn, $create_class_sql, [$class_name])) {
                        $new_class_id = $conn->insert_id;
                        $class_map[$class_key] = $new_class_id;
                        logActivity($conn, $_SESSION['admin_id'], 'class_created',
                            "Auto-created class during import: $class_name");
                    } else {
                        $skipped[] = "$row_label ($full_name): Could not create class \"$class_name\".";
                        continue;
                    }
                }
                $class_id = $class_map[$class_key];

                // Check duplicate roll number
                $dup_roll = getValue($conn,
                    "SELECT COUNT(*) FROM students WHERE roll_number = ? AND status != 'deleted'",
                    [$roll_number]);
                if ($dup_roll > 0) {
                    $skipped[] = "$row_label ($full_name): Roll number \"$roll_number\" already exists.";
                    continue;
                }

                // Check duplicate email
                $dup_email = getValue($conn,
                    "SELECT COUNT(*) FROM students WHERE email = ? AND status != 'deleted'",
                    [$email]);
                if ($dup_email > 0) {
                    $skipped[] = "$row_label ($full_name): Email \"$email\" already exists.";
                    continue;
                }

                // Default password = "password" for all students
                $hashed_password = hashPassword('password');

                // Insert student
                $ins_sql = "INSERT INTO students
                    (full_name, roll_number, class_id, email, phone, date_of_birth,
                     gender, address, parent_name, parent_phone, password, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

                $ins_params = [
                    $full_name, $roll_number, $class_id, $email,
                    $phone ?: null, $dob,
                    $gender ?: null, $address ?: null,
                    $parent_name ?: null, $parent_phone ?: null,
                    $hashed_password,
                ];

                if (executeQuery($conn, $ins_sql, $ins_params)) {
                    $imported++;
                    logActivity($conn, $_SESSION['admin_id'], 'student_imported',
                        "Imported student: $full_name ($roll_number)");
                } else {
                    $skipped[] = "$row_label ($full_name): Database error — could not insert.";
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
            <h1 class="page-title">Import Students</h1>
            <p class="page-subtitle">Bulk upload students from a CSV file</p>
        </div>
        <div class="flex gap-md">
            <a href="?action=template" class="btn btn-outlined">
                <i class="fas fa-download"></i> Download Template
            </a>
            <a href="students.php" class="btn btn-outlined">
                <i class="fas fa-arrow-left"></i> Back to Students
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
<!-- Import Result Summary -->
<div class="card mb-lg">
    <div class="card-body">
        <h3 class="mb-md"><i class="fas fa-chart-bar"></i> Import Summary</h3>

        <div class="grid grid-cols-2 gap-md mb-lg" style="max-width:500px;">
            <!-- Imported card -->
            <div class="card" style="background:#1b5e20;border:none;">
                <div class="card-body text-center" style="padding:1.2rem;">
                    <div style="font-size:2.5rem;font-weight:700;color:#ffffff;"><?php echo $imported; ?></div>
                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.85);margin-top:4px;">Students Imported</div>
                </div>
            </div>
            <!-- Skipped card -->
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
            <?php echo $imported; ?> student<?php echo $imported !== 1 ? 's' : ''; ?> imported successfully.
            Default password for each student is <strong>password</strong>.
        </div>
        <?php endif; ?>

        <div class="flex gap-md mt-md">
            <a href="students.php" class="btn btn-primary">
                <i class="fas fa-users"></i> View Students
            </a>
            <a href="import-students.php" class="btn btn-outlined">
                <i class="fas fa-redo"></i> Import More
            </a>
        </div>
    </div>
</div>

<?php else: ?>

<!-- How-to Instructions -->
<div class="grid gap-md mb-lg" style="grid-template-columns: 1fr 1fr;">

    <div class="card">
        <div class="card-body">
            <h3 class="mb-md"><i class="fas fa-info-circle text-primary"></i> How to Import</h3>
            <ol style="padding-left:1.2rem;line-height:2;">
                <li>Download the <strong>CSV template</strong> using the button above.</li>
                <li>Open in Excel / Google Sheets and fill in student data.</li>
                <li>Save the file as <strong>CSV (comma-separated values)</strong>.</li>
                <li>Upload the file using the form below.</li>
                <li>Review the import summary for any skipped rows.</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="mb-md"><i class="fas fa-table text-success"></i> CSV Column Format</h3>
            <div class="table-responsive">
                <table class="table" style="font-size:0.82rem;">
                    <thead>
                        <tr><th>Column</th><th>Required</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Full Name</td><td><span class="badge badge-error">Yes</span></td><td></td></tr>
                        <tr><td>Roll Number</td><td><span class="badge badge-error">Yes</span></td><td>Must be unique</td></tr>
                        <tr><td>Class Name</td><td><span class="badge badge-error">Yes</span></td><td>Exact class name</td></tr>
                        <tr><td>Email</td><td><span class="badge badge-error">Yes</span></td><td>Must be unique</td></tr>
                        <tr><td>Phone</td><td><span class="badge badge-secondary">No</span></td><td></td></tr>
                        <tr><td>Date of Birth</td><td><span class="badge badge-error">Yes</span></td><td>YYYY-MM-DD format</td></tr>
                        <tr><td>Gender</td><td><span class="badge badge-secondary">No</span></td><td>male/female/other</td></tr>
                        <tr><td>Address</td><td><span class="badge badge-secondary">No</span></td><td></td></tr>
                        <tr><td>Parent Name</td><td><span class="badge badge-secondary">No</span></td><td></td></tr>
                        <tr><td>Parent Phone</td><td><span class="badge badge-secondary">No</span></td><td></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Upload Form -->
<div class="card">
    <div class="card-body">
        <h3 class="mb-md"><i class="fas fa-upload"></i> Upload CSV File</h3>

        <form method="POST" enctype="multipart/form-data" id="importForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div id="dropZone" style="
                border: 2px dashed var(--color-border);
                border-radius: 12px;
                padding: 3rem;
                text-align: center;
                cursor: pointer;
                transition: border-color 0.2s, background 0.2s;
                margin-bottom: 1.5rem;
                background: var(--color-surface-variant);
            " onclick="document.getElementById('csv_file').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size:3rem;color:var(--color-text-secondary);margin-bottom:0.75rem;display:block;"></i>
                <p style="font-size:1rem;font-weight:600;margin-bottom:0.3rem;color:var(--color-text-primary);">
                    Click to browse or drag & drop your CSV file
                </p>
                <p class="text-secondary" style="font-size:0.85rem;">Only .csv files allowed — maximum 10 MB</p>
                <p id="fileName" style="margin-top:0.75rem;font-size:0.85rem;color:var(--color-primary);font-weight:600;display:none;"></p>
            </div>

            <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display:none;">

            <div class="flex justify-between items-center">
                <a href="?action=template" class="btn btn-outlined">
                    <i class="fas fa-download"></i> Download Template
                </a>
                <button type="submit" class="btn btn-success" id="uploadBtn" disabled>
                    <i class="fas fa-file-import"></i> Import Students
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php
$extra_js = <<<'JS'
<script>
const fileInput  = document.getElementById('csv_file');
const dropZone   = document.getElementById('dropZone');
const fileLabel  = document.getElementById('fileName');
const uploadBtn  = document.getElementById('uploadBtn');

if (fileInput) {
    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            fileLabel.textContent = '✓  ' + this.files[0].name;
            fileLabel.style.display = 'block';
            uploadBtn.disabled = false;
            dropZone.style.borderColor = 'var(--color-success)';
        }
    });
}

// Drag & drop
if (dropZone) {
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.style.borderColor = 'var(--color-primary)';
        this.style.background  = 'rgba(21,101,192,0.06)';
    });

    dropZone.addEventListener('dragleave', function () {
        this.style.borderColor = 'var(--color-border)';
        this.style.background  = 'var(--color-surface-variant)';
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.style.borderColor = 'var(--color-border)';
        this.style.background  = 'var(--color-surface-variant)';

        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;

            fileLabel.textContent = '✓  ' + file.name;
            fileLabel.style.display = 'block';
            uploadBtn.disabled = false;
            dropZone.style.borderColor = 'var(--color-success)';
        } else {
            alert('Please drop a .csv file.');
        }
    });
}
</script>
JS;

require_once 'footer.php';
?>
