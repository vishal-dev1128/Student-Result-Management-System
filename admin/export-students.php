<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'students')) {
    redirect('dashboard.php?error=unauthorized');
}

$action = sanitizeInput($_GET['action'] ?? '');

// ─── Export: Students CSV ─────────────────────────────────────────────────────
if ($action === 'students') {
    $search       = sanitizeInput($_GET['search'] ?? '');
    $class_filter = sanitizeInteger($_GET['class'] ?? '');
    $status_filter = sanitizeInput($_GET['status'] ?? 'active');

    $where  = ["s.status != 'deleted'"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(s.full_name LIKE ? OR s.roll_number LIKE ? OR s.email LIKE ?)";
        $p = "%$search%";
        $params[] = $p; $params[] = $p; $params[] = $p;
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
    $sql = "SELECT s.*, c.name as class_name
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE $where_clause
            ORDER BY s.roll_number ASC";
    $students = getRows($conn, $sql, $params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fputs($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Roll Number', 'Full Name', 'Class', 'Email', 'Phone',
        'Date of Birth', 'Gender', 'Address',
        'Parent Name', 'Parent Phone', 'Status', 'Created At'
    ]);

    foreach ($students as $s) {
        fputcsv($out, [
            $s['roll_number'], $s['full_name'], $s['class_name'] ?? '',
            $s['email'], $s['phone'] ?? '', $s['date_of_birth'],
            $s['gender'] ?? '', $s['address'] ?? '',
            $s['parent_name'] ?? '', $s['parent_phone'] ?? '',
            $s['status'], $s['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

// ─── Export: Results CSV ──────────────────────────────────────────────────────
if ($action === 'results') {
    $exam_filter  = sanitizeInteger($_GET['exam'] ?? '');
    $class_filter = sanitizeInteger($_GET['class'] ?? '');

    $where  = ["1=1"];
    $params = [];

    if (!empty($exam_filter)) {
        $where[] = "r.exam_id = ?";
        $params[] = $exam_filter;
    }
    if (!empty($class_filter)) {
        $where[] = "s.class_id = ?";
        $params[] = $class_filter;
    }

    $where_clause = implode(' AND ', $where);

    // Detailed subject-level results
    $sql = "SELECT
                s.roll_number, s.full_name, c.name AS class_name,
                e.exam_name, e.exam_type, e.academic_year,
                sub.subject_name,
                res.total_marks_obtained AS marks_obtained,
                res.total_marks, res.percentage, res.grade,
                rs.result_status, rs.rank
            FROM results res
            JOIN students s   ON res.student_id  = s.id
            JOIN exams e      ON res.exam_id      = e.id
            JOIN subjects sub ON res.subject_id   = sub.id
            JOIN classes c    ON s.class_id       = c.id
            LEFT JOIN result_summary rs ON rs.student_id = s.id AND rs.exam_id = e.id
            WHERE $where_clause
            ORDER BY s.roll_number ASC, sub.subject_name ASC";

    $results = getRows($conn, $sql, $params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="results_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Roll Number', 'Student Name', 'Class',
        'Exam', 'Exam Type', 'Academic Year',
        'Subject Name',
        'Marks Obtained', 'Total Marks', 'Percentage', 'Grade',
        'Result Status', 'Rank'
    ]);

    foreach ($results as $r) {
        fputcsv($out, [
            $r['roll_number'], $r['full_name'], $r['class_name'],
            $r['exam_name'], $r['exam_type'], $r['academic_year'],
            $r['subject_name'],
            $r['marks_obtained'], $r['total_marks'], $r['percentage'], $r['grade'],
            $r['result_status'] ?? '', $r['rank'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ─── UI Page ──────────────────────────────────────────────────────────────────
$page_title = 'Export Data';
require_once 'header.php';

// Stats for display
$total_students = getValue($conn, "SELECT COUNT(*) FROM students WHERE status != 'deleted'");
$total_results  = getValue($conn, "SELECT COUNT(*) FROM result_summary");
$total_classes  = getValue($conn, "SELECT COUNT(*) FROM classes WHERE status = 'active'");

$classes = getRows($conn, "SELECT id, name FROM classes WHERE status = 'active' ORDER BY name");
$exams   = getRows($conn, "SELECT id, exam_name FROM exams WHERE status = 'active' ORDER BY exam_date DESC");
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Export Data</h1>
            <p class="page-subtitle">Download student and result data as CSV</p>
        </div>
        <a href="students.php" class="btn btn-outlined">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-3 gap-md mb-lg">
    <div class="card">
        <div class="card-body text-center" style="padding:1.2rem;">
            <div style="font-size:2rem;font-weight:700;color:var(--color-primary);"><?php echo number_format($total_students); ?></div>
            <div class="text-secondary" style="font-size:0.85rem;">Total Students</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center" style="padding:1.2rem;">
            <div style="font-size:2rem;font-weight:700;color:var(--color-success);"><?php echo number_format($total_results); ?></div>
            <div class="text-secondary" style="font-size:0.85rem;">Result Records</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center" style="padding:1.2rem;">
            <div style="font-size:2rem;font-weight:700;color:var(--color-warning);"><?php echo number_format($total_classes); ?></div>
            <div class="text-secondary" style="font-size:0.85rem;">Active Classes</div>
        </div>
    </div>
</div>

<div class="grid gap-lg" style="grid-template-columns: 1fr 1fr;">

    <!-- ── Export Students ─────────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-md mb-md">
                <div style="
                    width:48px;height:48px;border-radius:12px;
                    background:linear-gradient(135deg,#1565C0,#1e88e5);
                    display:flex;align-items:center;justify-content:center;
                    flex-shrink:0;">
                    <i class="fas fa-users" style="color:#fff;font-size:1.2rem;"></i>
                </div>
                <div>
                    <h3 style="margin:0;">Export Students</h3>
                    <p class="text-secondary" style="font-size:0.82rem;margin:0;">Download the student list as CSV</p>
                </div>
            </div>

            <form method="GET" action="export-students.php">
                <input type="hidden" name="action" value="students">

                <div class="form-group">
                    <label class="form-label">Class (optional)</label>
                    <select name="class" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo escape($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="all">All Statuses</option>
                        <option value="active" selected>Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Search by Name / Roll / Email (optional)</label>
                    <input type="text" name="search" class="form-control" placeholder="Leave empty to export all">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-file-csv"></i> Download Students CSV
                </button>
            </form>
        </div>
        <div class="card-footer text-secondary" style="font-size:0.8rem;">
            <i class="fas fa-info-circle"></i>
            Exports: Roll No, Name, Class, Email, Phone, DOB, Gender, Address, Parent Info, Status
        </div>
    </div>

    <!-- ── Export Results ─────────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-md mb-md">
                <div style="
                    width:48px;height:48px;border-radius:12px;
                    background:linear-gradient(135deg,#2e7d32,#43a047);
                    display:flex;align-items:center;justify-content:center;
                    flex-shrink:0;">
                    <i class="fas fa-chart-bar" style="color:#fff;font-size:1.2rem;"></i>
                </div>
                <div>
                    <h3 style="margin:0;">Export Results</h3>
                    <p class="text-secondary" style="font-size:0.82rem;margin:0;">Download subject-wise result data as CSV</p>
                </div>
            </div>

            <form method="GET" action="export-students.php">
                <input type="hidden" name="action" value="results">

                <div class="form-group">
                    <label class="form-label">Exam (optional)</label>
                    <select name="exam" class="form-control">
                        <option value="">All Exams</option>
                        <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>"><?php echo escape($exam['exam_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Class (optional)</label>
                    <select name="class" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo escape($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="height:62px;"></div><!-- spacer to align buttons -->

                <button type="submit" class="btn btn-success" style="width:100%;">
                    <i class="fas fa-file-csv"></i> Download Results CSV
                </button>
            </form>
        </div>
        <div class="card-footer text-secondary" style="font-size:0.8rem;">
            <i class="fas fa-info-circle"></i>
            Exports: Roll No, Name, Class, Exam, Subject, Marks, Grade, Status, Rank
        </div>
    </div>

</div>

<!-- Quick Links -->
<div class="card mt-lg">
    <div class="card-body">
        <h4 class="mb-md"><i class="fas fa-bolt"></i> Quick Exports</h4>
        <div class="flex gap-md flex-wrap">
            <a href="export-students.php?action=students&status=active" class="btn btn-outlined btn-sm">
                <i class="fas fa-users"></i> All Active Students
            </a>
            <a href="export-students.php?action=students&status=all" class="btn btn-outlined btn-sm">
                <i class="fas fa-users"></i> All Students (All Statuses)
            </a>
            <a href="export-students.php?action=results" class="btn btn-outlined btn-sm">
                <i class="fas fa-chart-bar"></i> All Results
            </a>
            <a href="import-students.php" class="btn btn-success btn-sm">
                <i class="fas fa-file-import"></i> Import Students
            </a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
