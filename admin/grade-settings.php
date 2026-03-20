<?php
$page_title = 'Grade Settings';
require_once '../includes/auth.php';
requireAdminAuth();

if (!hasAnyRole(['super_admin', 'admin'])) {
    redirect('dashboard.php?error=unauthorized');
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request';
    } else {
        $grades = $_POST['grades'] ?? [];
        
        if (empty($grades)) {
            $errors[] = 'Please configure at least one grade';
        }
        
        if (empty($errors)) {
            // Delete existing grades
            executeQuery($conn, "DELETE FROM grade_settings");
            
            // Insert new grades
            $sql = "INSERT INTO grade_settings (grade, min_percentage, max_percentage, created_at) VALUES (?, ?, ?, NOW())";
            
            foreach ($grades as $grade_data) {
                $grade = sanitizeInput($grade_data['grade'] ?? '');
                $min = (float)($grade_data['min'] ?? 0);
                $max = (float)($grade_data['max'] ?? 0);
                
                if (!empty($grade) && $min >= 0 && $max <= 100 && $min <= $max) {
                    executeQuery($conn, $sql, [$grade, $min, $max]);
                }
            }
            
            logActivity($conn, $_SESSION['admin_id'], 'grade_settings_updated', 'Updated grade settings');
            
            $_SESSION['flash_message'] = 'Grade settings updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('grade-settings.php');
        }
    }
}

require_once 'header.php';

// Get current grade settings
$grades = getRows($conn, "SELECT * FROM grade_settings ORDER BY min_percentage DESC");

// If no grades exist, set defaults
if (empty($grades)) {
    $grades = [
        ['grade' => 'A+', 'min_percentage' => 90, 'max_percentage' => 100],
        ['grade' => 'A', 'min_percentage' => 80, 'max_percentage' => 89],
        ['grade' => 'B+', 'min_percentage' => 70, 'max_percentage' => 79],
        ['grade' => 'B', 'min_percentage' => 60, 'max_percentage' => 69],
        ['grade' => 'C', 'min_percentage' => 50, 'max_percentage' => 59],
        ['grade' => 'D', 'min_percentage' => 40, 'max_percentage' => 49],
        ['grade' => 'F', 'min_percentage' => 0, 'max_percentage' => 39],
    ];
}

$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h1 class="page-title">Grade Configuration</h1>
    <p class="page-subtitle">Configure grade thresholds based on percentage</p>
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

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> mb-lg">
    <?php echo escape($_SESSION['flash_message']); ?>
</div>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>

<div class="card">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="card-body">
            <div class="alert alert-info mb-lg">
                <i class="fas fa-info-circle"></i>
                Configure the percentage ranges for each grade. Ensure there are no gaps or overlaps.
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Min Percentage</th>
                            <th>Max Percentage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTable">
                        <?php foreach ($grades as $index => $grade): ?>
                        <tr>
                            <td>
                                <input 
                                    type="text" 
                                    name="grades[<?php echo $index; ?>][grade]" 
                                    class="form-control" 
                                    value="<?php echo escape($grade['grade']); ?>"
                                    required
                                    style="max-width: 100px;"
                                >
                            </td>
                            <td>
                                <input 
                                    type="number" 
                                    name="grades[<?php echo $index; ?>][min]" 
                                    class="form-control" 
                                    value="<?php echo $grade['min_percentage']; ?>"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    style="max-width: 150px;"
                                >
                            </td>
                            <td>
                                <input 
                                    type="number" 
                                    name="grades[<?php echo $index; ?>][max]" 
                                    class="form-control" 
                                    value="<?php echo $grade['max_percentage']; ?>"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    style="max-width: 150px;"
                                >
                            </td>
                            <td>
                                <button type="button" onclick="removeGrade(this)" class="btn btn-sm btn-error">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="button" onclick="addGrade()" class="btn btn-outlined">
                <i class="fas fa-plus"></i> Add Grade
            </button>
        </div>
        
        <div class="card-footer">
            <div class="flex justify-between">
                <a href="dashboard.php" class="btn btn-outlined">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Grade Preview -->
<div class="card mt-lg">
    <div class="card-header">
        <h3 class="card-title">Grade Preview</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-4 gap-md">
            <?php foreach ($grades as $grade): ?>
            <div class="p-md rounded" style="background: var(--color-surface-variant);">
                <div class="text-center">
                    <div class="grade-badge grade-<?php echo strtolower($grade['grade']); ?>" style="font-size: 24px; padding: 12px 24px;">
                        <?php echo escape($grade['grade']); ?>
                    </div>
                    <div class="text-sm text-secondary mt-sm">
                        <?php echo $grade['min_percentage']; ?>% - <?php echo $grade['max_percentage']; ?>%
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
let gradeIndex = <?php echo count($grades); ?>;

function addGrade() {
    const tbody = document.getElementById('gradesTable');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" name="grades[${gradeIndex}][grade]" class="form-control" required style="max-width: 100px;">
        </td>
        <td>
            <input type="number" name="grades[${gradeIndex}][min]" class="form-control" min="0" max="100" step="0.01" required style="max-width: 150px;">
        </td>
        <td>
            <input type="number" name="grades[${gradeIndex}][max]" class="form-control" min="0" max="100" step="0.01" required style="max-width: 150px;">
        </td>
        <td>
            <button type="button" onclick="removeGrade(this)" class="btn btn-sm btn-error">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    gradeIndex++;
}

function removeGrade(button) {
    var row = button.closest('tr');
    confirmDialog(
        'Remove this grade row? You can add it back if needed.',
        function() { row.remove(); },
        null,
        { title: 'Remove Grade', confirmText: 'Remove', danger: false }
    );
}
</script>
JS;

require_once 'footer.php';
?>
