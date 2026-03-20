<?php
$page_title = 'Add Student';
require_once '../includes/auth.php';
requireAdminAuth();

if (!canPerformAction('create', 'students')) {
    redirect('dashboard.php?error=unauthorized');
}

// Initialize variables
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize inputs
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
        
        // Validate required fields
        if (empty($full_name)) $errors[] = 'Full name is required';
        if (empty($roll_number)) $errors[] = 'Roll number is required';
        if (empty($class_id)) $errors[] = 'Class is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($date_of_birth)) $errors[] = 'Date of birth is required';
        
        // Check if roll number already exists
        if (!empty($roll_number)) {
            $check_sql = "SELECT count(*) FROM students WHERE roll_number = ? AND status != 'deleted'";
            if (getValue($conn, $check_sql, [$roll_number]) > 0) {
                $errors[] = 'Roll number already exists';
            }
        }
        
        // Check if email already exists
        if (!empty($email)) {
            $check_sql = "SELECT count(*) FROM students WHERE email = ? AND status != 'deleted'";
            if (getValue($conn, $check_sql, [$email]) > 0) {
                $errors[] = 'Email already exists';
            }
        }
        

        
        // If no errors, insert student
        if (empty($errors)) {
            // Default password = "password"
            $hashed_password = hashPassword('password');
            
            $sql = "INSERT INTO students (
                        full_name, roll_number, class_id, email, phone, 
                        date_of_birth, gender, address, parent_name, parent_phone,
                        password, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $full_name, $roll_number, $class_id, $email, $phone,
                $date_of_birth, $gender, $address, $parent_name, $parent_phone,
                $hashed_password, $status
            ];
            
            if (executeQuery($conn, $sql, $params)) {
                $student_id = $conn->insert_id;
                logActivity($conn, $_SESSION['admin_id'], 'student_created', "Created student: $full_name (ID: $student_id)");
                
                $_SESSION['flash_message'] = "Student added successfully. Default password is: password";
                $_SESSION['flash_type'] = 'success';
                redirect('students.php');
            } else {
                $errors[] = 'Failed to add student. Please try again.';
            }
        }
    }
}

require_once 'header.php';

// Get classes
$classes = getRows($conn, "SELECT * FROM classes WHERE status = 'active' ORDER BY name");

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h1 class="page-title">Add New Student</h1>
    <p class="page-subtitle">Fill in the student information below</p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error mb-lg">
    <i class="fas fa-exclamation-circle"></i>
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
            <h3 class="mb-md">Personal Information</h3>
            
            <div class="grid grid-cols-2 gap-md">
                <div class="form-group">
                    <label for="full_name" class="form-label required">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        value="<?php echo escape($_POST['full_name'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="roll_number" class="form-label required">Roll Number</label>
                    <input 
                        type="text" 
                        id="roll_number" 
                        name="roll_number" 
                        class="form-control" 
                        value="<?php echo escape($_POST['roll_number'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="class_id" class="form-label required">Class</label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($class['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth" class="form-label required">Date of Birth</label>
                    <input 
                        type="date" 
                        id="date_of_birth" 
                        name="date_of_birth" 
                        class="form-control" 
                        value="<?php echo escape($_POST['date_of_birth'] ?? ''); ?>"
                        required
                    >
                    <small class="text-secondary">Default login password will be: <strong>password</strong></small>
                </div>
                
                <div class="form-group">
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                

            </div>
            
            <hr class="my-lg">
            
            <h3 class="mb-md">Contact Information</h3>
            
            <div class="grid grid-cols-2 gap-md">
                <div class="form-group">
                    <label for="email" class="form-label required">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?php echo escape($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-control" 
                        value="<?php echo escape($_POST['phone'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label for="address" class="form-label">Address</label>
                    <textarea 
                        id="address" 
                        name="address" 
                        class="form-control" 
                        rows="3"
                    ><?php echo escape($_POST['address'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <hr class="my-lg">
            
            <h3 class="mb-md">Parent/Guardian Information</h3>
            
            <div class="grid grid-cols-2 gap-md">
                <div class="form-group">
                    <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                    <input 
                        type="text" 
                        id="parent_name" 
                        name="parent_name" 
                        class="form-control" 
                        value="<?php echo escape($_POST['parent_name'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="parent_phone" class="form-label">Parent/Guardian Phone</label>
                    <input 
                        type="tel" 
                        id="parent_phone" 
                        name="parent_phone" 
                        class="form-control" 
                        value="<?php echo escape($_POST['parent_phone'] ?? ''); ?>"
                    >
                </div>
            </div>
            
            <hr class="my-lg">
            
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="flex justify-between">
                <a href="students.php" class="btn btn-outlined">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Student
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
