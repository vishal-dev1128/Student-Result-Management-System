<?php
$page_title = 'Add Result';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('create', 'results')) {
    redirect('dashboard.php?error=unauthorized');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request';
    } else {
        $student_id = sanitizeInteger($_POST['student_id'] ?? '');
        $exam_id = sanitizeInteger($_POST['exam_id'] ?? '');
        $subject_marks = $_POST['marks'] ?? [];
        
        if (empty($student_id)) $errors[] = 'Please select a student';
        if (empty($exam_id)) $errors[] = 'Please select an exam';
        if (empty($subject_marks)) $errors[] = 'Please enter marks for at least one subject';
        
        // Check if result already exists
        if (!empty($student_id) && !empty($exam_id)) {
            $check_sql = "SELECT count(*) FROM result_summary WHERE student_id = ? AND exam_id = ?";
            if (getValue($conn, $check_sql, [$student_id, $exam_id]) > 0) {
                $errors[] = 'Result already exists for this student and exam';
            }
        }
        
        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $total_marks_obtained = 0;
                $total_marks = 0;
                $all_subjects_valid = true;
                
                // Validate and calculate totals
                foreach ($subject_marks as $subject_id => $marks) {
                    if ($marks === '') continue; // Skip if no marks entered
                    $subject_id = (int)$subject_id;
                    $marks = (int)$marks;
                    
                    // Get subject total marks
                    $subject_sql = "SELECT total_marks FROM subjects WHERE id = ?";
                    $subject = getRow($conn, $subject_sql, [$subject_id]);
                    
                    if ($subject) {
                        if ($marks > $subject['total_marks']) {
                            $errors[] = "Marks cannot exceed total marks for subject ID: $subject_id";
                            $all_subjects_valid = false;
                            break;
                        }
                        
                        $total_marks_obtained += $marks;
                        $total_marks += $subject['total_marks'];
                    }
                }
                
                if ($total_marks == 0) {
                    $errors[] = "Please enter marks for at least one subject correctly";
                    $all_subjects_valid = false;
                }
                
                if ($all_subjects_valid && empty($errors)) {
                    // Calculate percentage
                    $percentage = ($total_marks > 0) ? ($total_marks_obtained / $total_marks) * 100 : 0;
                    
                    $grade = calculateGrade($percentage, $conn);
                    $result_status = ($percentage >= 40) ? 'pass' : 'fail';
                    
                    // Insert main result summary record
                    $summary_sql = "INSERT INTO result_summary (student_id, exam_id, total_marks, max_marks, percentage, grade, result_status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    executeQuery($conn, $summary_sql, [$student_id, $exam_id, $total_marks_obtained, $total_marks, $percentage, $grade, $result_status]);
                    
                    // Insert subject-wise marks
                    $result_sql = "INSERT INTO results (student_id, exam_id, subject_id, total_marks_obtained, total_marks, percentage, grade, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    foreach ($subject_marks as $subject_id => $marks) {
                        if ($marks !== '') {
                            $subject_id = (int)$subject_id;
                            $marks_obtained = (int)$marks;
                            
                            $subject_info = getRow($conn, "SELECT total_marks FROM subjects WHERE id = ?", [$subject_id]);
                            $subj_total = $subject_info ? $subject_info['total_marks'] : 100;
                            
                            $subj_percentage = ($subj_total > 0) ? ($marks_obtained / $subj_total) * 100 : 0;
                            $subj_grade = calculateGrade($subj_percentage, $conn);
                            
                            executeQuery($conn, $result_sql, [$student_id, $exam_id, $subject_id, $marks_obtained, $subj_total, $subj_percentage, $subj_grade]);
                        }
                    }
                    
                    $conn->commit();
                    
                    logActivity($conn, $_SESSION['admin_id'], 'result_created', "Created result for student ID: $student_id, exam ID: $exam_id");
                    
                    $_SESSION['flash_message'] = 'Result added successfully';
                    $_SESSION['flash_type'] = 'success';
                    redirect('results.php');
                } else {
                    $conn->rollback();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = 'Failed to add result: ' . $e->getMessage();
            }
        }
    }
}

require_once 'header.php';

// Get students
$students = getRows($conn, "SELECT id, roll_number, full_name, class_id FROM students WHERE status = 'active' ORDER BY roll_number");

// Get exams
$exams = getRows($conn, "SELECT id, exam_name, exam_date FROM exams WHERE status = 'active' ORDER BY exam_date DESC");

// Get subjects
$subjects = getRows($conn, "SELECT id, subject_name, subject_code, total_marks FROM subjects WHERE status = 'active' ORDER BY subject_name");

$csrf_token = generateCSRFToken();

?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Add Result</h1>
            <p class="page-subtitle">Enter marks for student</p>
        </div>
        <div class="flex gap-sm">
            <a href="import-results.php" class="btn btn-success">
                <i class="fas fa-file-import"></i> Import Result
            </a>
            <a href="results.php" class="btn btn-outlined">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error mb-lg">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo escape($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <form method="POST" id="resultForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="card-body">
            <div class="grid grid-cols-2 gap-md mb-lg">
                <div class="form-group">
                    <label for="student_id" class="form-label required">Student</label>
                    <select id="student_id" name="student_id" class="form-control" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($student['roll_number'] . ' - ' . $student['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="exam_id" class="form-label required">Exam</label>
                    <select id="exam_id" name="exam_id" class="form-control" required>
                        <option value="">Select Exam</option>
                        <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>" <?php echo (isset($_POST['exam_id']) && $_POST['exam_id'] == $exam['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($exam['exam_name'] . ' - ' . formatDate($exam['exam_date'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <hr class="my-lg">
            
            <h3 class="mb-md">Subject-wise Marks</h3>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Subject Code</th>
                            <th>Total Marks</th>
                            <th>Marks Obtained</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td class="font-medium"><?php echo escape($subject['subject_name']); ?></td>
                            <td><?php echo escape($subject['subject_code'] ?? 'N/A'); ?></td>
                            <td><?php echo $subject['total_marks']; ?></td>
                            <td>
                                <input 
                                    type="number" 
                                    name="marks[<?php echo $subject['id']; ?>]" 
                                    class="form-control subject-marks" 
                                    min="0" 
                                    max="<?php echo $subject['total_marks']; ?>"
                                    data-max="<?php echo $subject['total_marks']; ?>"
                                    value="<?php echo $_POST['marks'][$subject['id']] ?? ''; ?>"
                                    placeholder="Enter marks"
                                >
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="2">Total</td>
                            <td id="totalMarks">0</td>
                            <td id="totalObtained">0</td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="3">Percentage</td>
                            <td id="percentage">0.00%</td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="3">Grade</td>
                            <td id="grade">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="flex justify-between">
                <a href="results.php" class="btn btn-outlined">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Result
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$extra_js = <<<'JS'
<script>
// Auto-calculate totals
document.querySelectorAll('.subject-marks').forEach(input => {
    input.addEventListener('input', calculateTotals);
});

function calculateTotals() {
    let totalMarks = 0;
    let totalObtained = 0;
    
    document.querySelectorAll('.subject-marks').forEach(input => {
        const max = parseInt(input.dataset.max) || 0;
        const value = parseInt(input.value) || 0;
        
        // Validate
        if (value > max) {
            input.value = max;
        }
        
        if (input.value) {
            totalMarks += max;
            totalObtained += parseInt(input.value) || 0;
        }
    });
    
    const percentage = totalMarks > 0 ? (totalObtained / totalMarks) * 100 : 0;
    const grade = getGrade(percentage);
    
    document.getElementById('totalMarks').textContent = totalMarks;
    document.getElementById('totalObtained').textContent = totalObtained;
    document.getElementById('percentage').textContent = percentage.toFixed(2) + '%';
    document.getElementById('grade').textContent = grade;
}

function getGrade(percentage) {
    if (percentage >= 90) return 'A+';
    if (percentage >= 80) return 'A';
    if (percentage >= 70) return 'B+';
    if (percentage >= 60) return 'B';
    if (percentage >= 50) return 'C';
    if (percentage >= 40) return 'D';
    return 'F';
}

// Initial calculation
calculateTotals();
</script>
JS;

require_once 'footer.php';
?>
