<?php
$page_title = 'View Student';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('read', 'students')) {
    redirect('dashboard.php?error=unauthorized');
}

$student_id = sanitizeInteger($_GET['id'] ?? 0);
if (!$student_id) redirect('students.php');

$student_sql = "SELECT s.*, c.name as class_name FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE s.id = ? AND s.status != 'deleted'";
$student = getRow($conn, $student_sql, [$student_id]);

if (!$student) {
    $_SESSION['flash_message'] = 'Student not found';
    $_SESSION['flash_type'] = 'error';
    redirect('students.php');
}

// Get results
$results_sql = "SELECT * FROM vw_student_results WHERE student_id = ? ORDER BY exam_date DESC";
$results = getRows($conn, $results_sql, [$student_id]);

require_once 'header.php';
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title"><?php echo escape($student['full_name']); ?></h1>
            <p class="page-subtitle">Roll Number: <?php echo escape($student['roll_number']); ?></p>
        </div>
        <div class="flex gap-md">
            <?php if (canPerformAction('update', 'students')): ?>
            <a href="edit-student.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <?php endif; ?>
            <a href="students.php" class="btn btn-outlined">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-lg">
    <!-- Student Info Card -->
    <div class="card">
        <div class="card-body text-center">
            <div class="admin-avatar" style="width: 150px; height: 150px; font-size: 48px; margin: 0 auto var(--spacing-md);">
                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
            </div>
            
            <h3><?php echo escape($student['full_name']); ?></h3>
            <p class="text-secondary"><?php echo escape($student['class_name'] ?? 'N/A'); ?></p>
            
            <span class="badge badge-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?> mt-md">
                <?php echo ucfirst($student['status']); ?>
            </span>
        </div>
    </div>
    
    <!-- Personal Details -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3 class="card-title">Personal Information</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="text-sm text-secondary">Email</label>
                    <p class="font-medium"><?php echo escape($student['email']); ?></p>
                </div>
                <div>
                    <label class="text-sm text-secondary">Phone</label>
                    <p class="font-medium"><?php echo escape($student['phone'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="text-sm text-secondary">Date of Birth</label>
                    <p class="font-medium"><?php echo formatDate($student['date_of_birth']); ?></p>
                </div>
                <div>
                    <label class="text-sm text-secondary">Gender</label>
                    <p class="font-medium"><?php echo ucfirst($student['gender'] ?? 'N/A'); ?></p>
                </div>
                <div style="grid-column: span 2;">
                    <label class="text-sm text-secondary">Address</label>
                    <p class="font-medium"><?php echo escape($student['address'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="text-sm text-secondary">Parent/Guardian</label>
                    <p class="font-medium"><?php echo escape($student['parent_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="text-sm text-secondary">Parent Phone</label>
                    <p class="font-medium"><?php echo escape($student['parent_phone'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Results History -->
<div class="card mt-lg">
    <div class="card-header">
        <h3 class="card-title">Results History</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Date</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-lg">No results found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td class="font-medium"><?php echo escape($result['exam_name']); ?></td>
                        <td><?php echo formatDate($result['exam_date']); ?></td>
                        <td><?php echo $result['total_marks_obtained']; ?>/<?php echo $result['total_marks']; ?></td>
                        <td><?php echo number_format($result['percentage'], 2); ?>%</td>
                        <td>
                            <span class="grade-badge grade-<?php echo strtolower($result['grade']); ?>">
                                <?php echo $result['grade']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="../result-search.php?roll=<?php echo $student['roll_number']; ?>&exam=<?php echo $result['exam_id']; ?>" class="btn btn-sm btn-outlined" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
