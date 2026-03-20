<?php
$page_title = 'Edit Student';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('update', 'students')) {
    redirect('dashboard.php?error=unauthorized');
}

// Get student ID
$student_id = sanitizeInteger($_GET['id'] ?? 0);
if (!$student_id) {
    redirect('students.php');
}

// Get student data
$student_sql = "SELECT * FROM students WHERE id = ? AND status != 'deleted'";
$student = getRow($conn, $student_sql, [$student_id]);

if (!$student) {
    $_SESSION['flash_message'] = 'Student not found';
    $_SESSION['flash_type'] = 'error';
    redirect('students.php');
}

// Initialize variables
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $roll_number = sanitizeInput($_POST['roll_number'] ?? '');
        $class_id = sanitizeInteger($_POST['class_id'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $date_of_birth = sanitizeInput($_POST['date_of_birth'] ?? '');
        $gender = sanitizeInput($_POST['gender'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $parent_name = sanitizeInput($_POST['parent_name'] ?? '');
        $parent_phone = sanitizeInput($_POST['parent_phone'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'active');
        
        // Validate
        if (empty($full_name)) $errors[] = 'Full name is required';
        if (empty($roll_number)) $errors[] = 'Roll number is required';
        if (empty($class_id)) $errors[] = 'Class is required';
        if (empty($email)) $errors[] = 'Email is required';
        
        // Check duplicates (excluding current student)
        if (!empty($roll_number) && $roll_number !== $student['roll_number']) {
            $check_sql = "SELECT count(*) FROM students WHERE roll_number = ? AND id != ? AND status != 'deleted'";
            if (getValue($conn, $check_sql, [$roll_number, $student_id]) > 0) {
                $errors[] = 'Roll number already exists';
            }
        }
        
        if (!empty($email) && $email !== $student['email']) {
            $check_sql = "SELECT count(*) FROM students WHERE email = ? AND id != ? AND status != 'deleted'";
            if (getValue($conn, $check_sql, [$email, $student_id]) > 0) {
                $errors[] = 'Email already exists';
            }
        }
        

        
        // Handle password reset
        if (isset($_POST['reset_password']) && $_POST['reset_password'] === '1') {
            $dob = new DateTime($date_of_birth);
            $default_password = $dob->format('dmY');
            $hashed_password = hashPassword($default_password);
            
            $update_sql = "UPDATE students SET password = ? WHERE id = ?";
            executeQuery($conn, $update_sql, [$hashed_password, $student_id]);
            
            $_SESSION['flash_message'] = "Password reset to: $default_password";
            $_SESSION['flash_type'] = 'info';
        }
        
        if (empty($errors)) {
            $sql = "UPDATE students SET 
                        full_name = ?, roll_number = ?, class_id = ?, email = ?, phone = ?,
                        date_of_birth = ?, gender = ?, address = ?, parent_name = ?, parent_phone = ?,
                        status = ?, updated_at = NOW()
                    WHERE id = ?";
            
            $params = [
                $full_name, $roll_number, $class_id, $email, $phone,
                $date_of_birth, $gender, $address, $parent_name, $parent_phone,
                $status, $student_id
            ];
            
            if (executeQuery($conn, $sql, $params)) {
                logActivity($conn, $_SESSION['admin_id'], 'student_updated', "Updated student: $full_name (ID: $student_id)");
                
                $_SESSION['flash_message'] = 'Student updated successfully';
                $_SESSION['flash_type'] = 'success';
                redirect('students.php');
            } else {
                $errors[] = 'Failed to update student';
            }
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $student;
}

require_once 'header.php';

$classes = getRows($conn, "SELECT * FROM classes WHERE status = 'active' ORDER BY name");
$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h1 class="page-title">Edit Student</h1>
    <p class="page-subtitle">Update student information</p>
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
    <form method="POST" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="card-body">
            <div class="grid grid-cols-2 gap-md">
                <div class="form-group">
                    <label for="full_name" class="form-label required">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo escape($_POST['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="roll_number" class="form-label required">Roll Number</label>
                    <input type="text" id="roll_number" name="roll_number" class="form-control" value="<?php echo escape($_POST['roll_number'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="class_id" class="form-label required">Class</label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo ($_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($class['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth" class="form-label required">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?php echo escape($_POST['date_of_birth'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                

                
                <div class="form-group">
                    <label for="email" class="form-label required">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo escape($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo escape($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo escape($_POST['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                    <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?php echo escape($_POST['parent_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="parent_phone" class="form-label">Parent/Guardian Phone</label>
                    <input type="tel" id="parent_phone" name="parent_phone" class="form-control" value="<?php echo escape($_POST['parent_phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo ($_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="reset_password" value="1" class="form-checkbox">
                        <span>Reset password to default (DOB)</span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="flex justify-between">
                <a href="students.php" class="btn btn-outlined">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Student
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
