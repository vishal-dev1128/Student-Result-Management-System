<?php
$page_title = 'Import Results';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('create', 'results')) {
    redirect('dashboard.php?error=unauthorized');
}

// ─── Download CSV Template ────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="results_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Roll Number*',
        'Student Name (Optional)',
        'Exam Name*',
        'Subject Code*',
        'Marks Obtained*'
    ]);
    // Sample rows
    fputcsv($out, ['2025001', 'Rahul Sharma', 'First Term Examination 2025', 'MATH101', '85']);
    fputcsv($out, ['2025001', 'Rahul Sharma', 'First Term Examination 2025', 'ENG101', '78']);
    fputcsv($out, ['2025002', 'Priya Patel', 'First Term Examination 2025', 'MATH101', '92']);
    fclose($out);
    exit;
}

$imported    = 0;
$skipped     = [];
$upload_err  = null;
$show_result = false;
$csrf_token  = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $upload_err = 'Invalid request. Please try again.';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_err = 'Please select a CSV file to upload.';
    } else {
        $file     = $_FILES['csv_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 10 * 1024 * 1024; // 10 MB

        if ($ext !== 'csv') {
            $upload_err = 'Only CSV files are allowed.';
        } elseif ($file['size'] > $max_size) {
            $upload_err = 'File size exceeds the 10 MB limit.';
        } else {
            // 1. Data Mapping
            $students_res = getRows($conn, "SELECT id, roll_number FROM students");
            $student_map  = [];
            foreach ($students_res as $s) $student_map[$s['roll_number']] = $s['id'];

            function getExamMap($conn) {
                $exams_res = getRows($conn, "SELECT id, exam_name FROM exams");
                $map = [];
                foreach ($exams_res as $e) $map[strtolower(trim($e['exam_name']))] = $e['id'];
                return $map;
            }
            $exam_map = getExamMap($conn);

            $subjects_res = getRows($conn, "SELECT id, subject_code, total_marks FROM subjects");
            $subject_map  = [];
            foreach ($subjects_res as $sub) $subject_map[strtolower(trim($sub['subject_code']))] = $sub;

            $new_exams_created = [];

            // 2. Parse CSV and group by Student + Exam
            $handle  = fopen($file['tmp_name'], 'r');
            $row_num = 0;
            $data_groups = [];

            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;
                if ($row_num === 1 || empty(array_filter($row))) continue;

                $col_count = count($row);
                
                // Flexible mapping based on column count
                if ($col_count >= 8) {
                    // Format: Roll, Name, Class, Exam, SubCode, SubName, Total, Obtained
                    $roll_number    = trim($row[0] ?? '');
                    $exam_name      = trim($row[3] ?? '');
                    $subject_code   = trim($row[4] ?? '');
                    $marks_obtained = trim($row[7] ?? '');
                } elseif ($col_count >= 5) {
                    // Format: Roll, Name, Exam, Subject, Marks
                    $roll_number    = trim($row[0] ?? '');
                    $exam_name      = trim($row[2] ?? '');
                    $subject_code   = trim($row[3] ?? '');
                    $marks_obtained = trim($row[4] ?? '');
                } else {
                    // Original Format: Roll, Exam, Subject, Marks
                    $roll_number    = trim($row[0] ?? '');
                    $exam_name      = trim($row[1] ?? '');
                    $subject_code   = trim($row[2] ?? '');
                    $marks_obtained = trim($row[3] ?? '');
                }

                $row_label = "Row $row_num ($roll_number)";

                if (!isset($student_map[$roll_number])) {
                    $skipped[] = "$row_label: Student with roll number '$roll_number' not found.";
                    continue;
                }

                $exam_name_lower = strtolower(trim($exam_name));
                if (empty($exam_name_lower)) {
                    $skipped[] = "$row_label: Exam name is empty.";
                    continue;
                }

                // Auto-create exam if missing
                if (!isset($exam_map[$exam_name_lower])) {
                    $academic_year = date('Y') . '-' . (date('Y') + 1);
                    $exam_date = date('Y-m-d');
                    $sql_create = "INSERT INTO exams (exam_name, exam_type, academic_year, exam_date, status, created_at) VALUES (?, 'other', ?, ?, 'active', NOW())";
                    if (executeQuery($conn, $sql_create, [$exam_name, $academic_year, $exam_date])) {
                        $new_id = $conn->insert_id;
                        $exam_map[$exam_name_lower] = $new_id;
                        $new_exams_created[] = $exam_name;
                        logActivity($conn, $_SESSION['admin_id'], 'exam_auto_created', "Auto-created exam '$exam_name' during import");
                    } else {
                        $skipped[] = "$row_label: Failed to auto-create exam '$exam_name'.";
                        continue;
                    }
                }

                $sub_code_lower = strtolower(trim($subject_code));
                if (!isset($subject_map[$sub_code_lower])) {
                    $skipped[] = "$row_label: Subject code '$subject_code' not found.";
                    continue;
                }

                if (!is_numeric($marks_obtained)) {
                    $skipped[] = "$row_label: Marks must be a number.";
                    continue;
                }

                $student_id = $student_map[$roll_number];
                $exam_id    = $exam_map[$exam_name_lower];
                $group_key  = $student_id . '_' . $exam_id;

                if (!isset($data_groups[$group_key])) {
                    $data_groups[$group_key] = [
                        'student_id' => $student_id,
                        'exam_id'    => $exam_id,
                        'subjects'   => []
                    ];
                }

                $subject_info = $subject_map[$sub_code_lower];
                if ((float)$marks_obtained > $subject_info['total_marks']) {
                    $skipped[] = "$row_label: Marks ($marks_obtained) exceed total marks (" . $subject_info['total_marks'] . ") for $subject_code.";
                    continue;
                }

                $data_groups[$group_key]['subjects'][] = [
                    'id'             => $subject_info['id'],
                    'marks_obtained' => (float)$marks_obtained,
                    'total_marks'    => (int)$subject_info['total_marks']
                ];
            }
            fclose($handle);

            // 3. Process groups within a transaction
            if (!empty($data_groups)) {
                $conn->begin_transaction();
                try {
                    foreach ($data_groups as $group) {
                        $sid = $group['student_id'];
                        $eid = $group['exam_id'];
                        
                        $group_obtained = 0;
                        $group_total    = 0;

                        foreach ($group['subjects'] as $sub) {
                            $sub_id   = $sub['id'];
                            $sub_obt  = $sub['marks_obtained'];
                            $sub_tot  = $sub['total_marks'];
                            $sub_perc = ($sub_tot > 0) ? ($sub_obt / $sub_tot) * 100 : 0;
                            $sub_grade = calculateGrade($sub_perc, $conn);

                            // Upsert individual result
                            $sql_sub = "INSERT INTO results (student_id, exam_id, subject_id, total_marks_obtained, total_marks, percentage, grade, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) 
                                        ON DUPLICATE KEY UPDATE total_marks_obtained = VALUES(total_marks_obtained), percentage = VALUES(percentage), grade = VALUES(grade), updated_at = NOW()";
                            executeQuery($conn, $sql_sub, [$sid, $eid, $sub_id, $sub_obt, $sub_tot, $sub_perc, $sub_grade]);
                            
                            $group_obtained += $sub_obt;
                            $group_total    += $sub_tot;
                        }

                        // Calculate overall summary
                        $overall_perc  = ($group_total > 0) ? ($group_obtained / $group_total) * 100 : 0;
                        $overall_grade = calculateGrade($overall_perc, $conn);
                        $status        = ($overall_perc >= 40) ? 'pass' : 'fail';

                        // Upsert summary
                        $sql_sum = "INSERT INTO result_summary (student_id, exam_id, total_marks, max_marks, percentage, grade, result_status, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                                    ON DUPLICATE KEY UPDATE total_marks = VALUES(total_marks), max_marks = VALUES(max_marks), percentage = VALUES(percentage), grade = VALUES(grade), result_status = VALUES(result_status), updated_at = NOW()";
                        executeQuery($conn, $sql_sum, [$sid, $eid, $group_obtained, $group_total, $overall_perc, $overall_grade, $status]);
                        
                        $imported++;
                    }
                    $conn->commit();
                    logActivity($conn, $_SESSION['admin_id'], 'result_bulk_import', "Bulk imported results for $imported student-exam sets.");
                } catch (Exception $e) {
                    $conn->rollback();
                    $upload_err = "Database error during import: " . $e->getMessage();
                }
            }
            $show_result = true;
        }
    }
}

require_once 'header.php';
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Import Results</h1>
            <p class="page-subtitle">Upload CSV to bulk import student marks</p>
        </div>
        <a href="results.php" class="btn btn-outlined">
            <i class="fas fa-arrow-left"></i> Back to Results
        </a>
    </div>
</div>

<?php if ($show_result): ?>
    <div class="card mb-lg">
        <div class="card-body">
            <div class="flex items-center gap-md mb-md">
                <div class="admin-avatar bg-success-light text-success">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h3 class="mb-xs">Import Completed</h3>
                    <p class="text-secondary">Summary of your bulk upload</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-md mb-lg">
                <div class="bg-surface-variant p-md rounded-lg text-center">
                    <div class="text-2xl font-bold text-success"><?php echo $imported; ?></div>
                    <div class="text-sm text-secondary">Student Records Updated</div>
                </div>
                <div class="bg-surface-variant p-md rounded-lg text-center">
                    <div class="text-2xl font-bold text-error"><?php echo count($skipped); ?></div>
                    <div class="text-sm text-secondary">Rows Skipped/Errors</div>
                </div>
            </div>

            <?php if (!empty($new_exams_created)): ?>
                <div class="alert alert-success mb-lg">
                    <div class="flex items-center gap-sm">
                        <i class="fas fa-plus-circle"></i>
                        <div>
                            <strong class="block mb-xs">New Exams Created:</strong>
                            <div class="flex flex-wrap gap-xs">
                                <?php foreach (array_unique($new_exams_created) as $exam): ?>
                                    <span class="badge badge-success"><?php echo escape($exam); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($skipped)): ?>
                <h4 class="mb-sm text-error">Errors & Warnings</h4>
                <div class="table-container" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--color-border);">
                    <table class="table table-sm">
                        <thead style="position: sticky; top: 0; background: var(--color-surface-variant); z-index: 1;">
                            <tr><th>Issue Description</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($skipped as $msg): ?>
                            <tr><td class="text-error" style="font-size: 0.85rem;"><?php echo escape($msg); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-lg flex gap-md">
                <a href="import-results.php" class="btn btn-primary">Import More</a>
                <a href="results.php" class="btn btn-outlined">View Results</a>
            </div>
        </div>
    </div>
<?php else: ?>

    <?php if ($upload_err): ?>
        <div class="alert alert-error mb-lg">
            <i class="fas fa-exclamation-circle"></i> <?php echo escape($upload_err); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md-grid-cols-3 gap-lg mb-xl">
        <div class="md-col-span-1">
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-md">Instructions</h3>
                    <ul class="text-secondary" style="padding-left: 1.25rem; font-size: 0.9rem;">
                        <li class="mb-xs">Download the template provided.</li>
                        <li class="mb-xs">Ensure <strong>Roll Numbers</strong> match exactly with records in the system.</li>
                        <li class="mb-xs"><strong>Exam Name</strong> must exist in the system (case-insensitive).</li>
                        <li class="mb-xs"><strong>Subject Code</strong> must exist (e.g., MATH101).</li>
                        <li class="mb-xs">If a result already exists, it will be <strong>updated</strong> with new marks.</li>
                    </ul>
                    <a href="?action=template" class="btn btn-outlined btn-block mt-lg">
                        <i class="fas fa-download"></i> Download CSV Template
                    </a>
                </div>
            </div>
        </div>

        <div class="md-col-span-2">
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-md">Upload CSV File</h3>
                    <form method="POST" enctype="multipart/form-data" id="importForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div id="dropZone" style="
                            border: 2px dashed var(--color-border);
                            border-radius: 12px;
                            padding: 3.5rem 1rem;
                            text-align: center;
                            cursor: pointer;
                            transition: all 0.2s;
                            background: var(--color-surface-variant);
                        " onclick="document.getElementById('csv_file').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-md text-secondary"></i>
                            <p class="font-bold mb-xs">Browse or drag & drop CSV file</p>
                            <p class="text-secondary text-sm">Max size: 10MB</p>
                            <div id="fileName" class="mt-md font-medium text-primary" style="display:none;"></div>
                        </div>

                        <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display:none;" onchange="handleFileSelect(this)">

                        <div class="mt-xl flex justify-end">
                            <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                                <i class="fas fa-upload"></i> Start Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
$extra_js = <<<'JS'
<script>
function handleFileSelect(input) {
    const fileName = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitBtn');
    const dropZone = document.getElementById('dropZone');
    
    if (input.files.length > 0) {
        fileName.textContent = "Selected: " + input.files[0].name;
        fileName.style.display = "block";
        submitBtn.disabled = false;
        dropZone.style.borderColor = "var(--color-primary)";
        dropZone.style.background = "rgba(21, 101, 192, 0.05)";
    } else {
        fileName.style.display = "none";
        submitBtn.disabled = true;
    }
}

// Drag and drop handlers
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    dropZone.addEventListener('dragover', () => {
        dropZone.style.borderColor = "var(--color-primary)";
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.style.borderColor = "var(--color-border)";
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        const input = document.getElementById('csv_file');
        input.files = files;
        handleFileSelect(input);
    });
}
</script>
JS;

require_once 'footer.php';
?>
