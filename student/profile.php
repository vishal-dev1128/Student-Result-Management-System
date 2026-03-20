<?php
$page_title = 'My Profile';
require_once 'header.php';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request';
    } else {
        $email = sanitizeEmail($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        
        if (empty($email)) $errors[] = 'Email is required';
        
        // Check if email already exists (excluding current student)
        if (!empty($email) && $email !== $current_student['email']) {
            $check_sql = "SELECT count(*) FROM students WHERE email = ? AND id != ? AND status != 'deleted'";
            if (getValue($conn, $check_sql, [$email, $_SESSION['student_id']]) > 0) {
                $errors[] = 'Email already exists';
            }
        }
        

        
        if (empty($errors)) {
            $sql = "UPDATE students SET email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
            if (executeQuery($conn, $sql, [$email, $phone, $address, $_SESSION['student_id']])) {
                logActivity($conn, $_SESSION['student_id'], 'profile_updated', 'Student updated profile', 'student');
                $success = true;
                
                // Refresh current student data
                $current_student = getCurrentStudent($conn);
            } else {
                $errors[] = 'Failed to update profile';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<style>
/* Premium Profile Styles */
.profile-sidebar-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    padding: var(--spacing-2xl) var(--spacing-xl);
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-sidebar-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.profile-avatar-wrap {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto var(--spacing-lg);
}

.profile-avatar-lg {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    font-weight: 700;
    box-shadow: 0 12px 24px rgba(79, 70, 229, 0.25);
    border: 4px solid var(--color-surface);
}

.profile-status-indicator {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--color-success);
    border: 4px solid var(--color-surface);
    box-shadow: var(--shadow-sm);
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-primary);
    margin-bottom: var(--spacing-xs);
    font-family: 'Poppins', sans-serif;
}

.profile-role {
    font-size: 0.95rem;
    color: var(--color-text-secondary);
    font-weight: 500;
    margin-bottom: var(--spacing-sm);
}

.profile-class {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: 6px 16px;
    background: var(--color-surface-variant);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-text-primary);
}

/* Premium Form Card */
.form-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.form-card-header {
    padding: var(--spacing-xl) var(--spacing-2xl);
    border-bottom: 1px solid var(--color-border);
    background: linear-gradient(to right, rgba(79, 70, 229, 0.03), transparent);
}

.form-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text-primary);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.form-card-title i {
    color: var(--color-primary);
}

.form-card-body {
    padding: var(--spacing-2xl);
}

.premium-input-wrap {
    position: relative;
    border-radius: var(--radius-lg);
    transition: all 0.3s ease;
}

.premium-input-wrap:focus-within {
    transform: translateY(-2px);
}

.form-control-premium {
    width: 100%;
    padding: 14px 16px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-lg);
    background: var(--color-surface);
    color: var(--color-text-primary);
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.form-control-premium:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    outline: none;
}

.form-control-premium:disabled {
    background: var(--color-surface-variant);
    opacity: 0.7;
    cursor: not-allowed;
}

.form-label-premium {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xs);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-card-footer {
    padding: var(--spacing-lg) var(--spacing-2xl);
    background: var(--color-surface-variant);
    border-top: 1px solid var(--color-border);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
}

/* Alert Styling */
.premium-alert {
    border-radius: var(--radius-lg);
    padding: var(--spacing-md) var(--spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="page-header mb-xl">
    <h1 class="page-title mb-xs" style="font-family: 'Playfair Display', serif; font-size: 2.5rem; letter-spacing: -0.5px;">My Profile</h1>
    <p class="page-subtitle text-secondary" style="font-size: 1.1rem;">Manage your personal information and account settings</p>
</div>

<?php if (!empty($errors)): ?>
<div class="premium-alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;">
    <i class="fas fa-exclamation-circle text-xl"></i>
    <ul class="mb-0" style="list-style: none; padding: 0;">
        <?php foreach ($errors as $error): ?>
        <li><?php echo escape($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="premium-alert" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981;">
    <i class="fas fa-check-circle text-xl"></i>
    <span class="font-medium">Profile updated successfully!</span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-xl">
    <!-- Left Column: Profile Overview -->
    <div class="md:col-span-1">
        <div class="profile-sidebar-card slide-in-up">
            <div class="profile-avatar-wrap">
                <div class="profile-avatar-lg">
                    <?php echo strtoupper(substr($current_student['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-status-indicator" title="Active Account"></div>
            </div>
            
            <h3 class="profile-name"><?php echo escape($current_student['full_name']); ?></h3>
            <p class="profile-role"><i class="fas fa-id-badge mr-xs"></i> <?php echo escape($current_student['roll_number']); ?></p>
            
            <div class="profile-class mt-md">
                <i class="fas fa-graduation-cap text-primary"></i> 
                <span>Class <?php echo escape($current_student['class_name']); ?></span>
            </div>
            
            <div class="mt-xl pt-lg border-t text-sm text-secondary">
                <i class="fas fa-calendar-alt mr-xs"></i> Member since <?php echo date('M Y', strtotime($current_student['created_at'])); ?>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Edit Form -->
    <div class="md:col-span-2">
        <div class="form-card slide-in-up" style="animation-delay: 0.1s;">
            <div class="form-card-header">
                <h3 class="form-card-title">
                    <i class="fas fa-user-edit"></i> Personal Information
                </h3>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                        
                        <!-- Read-only fields group -->
                        <div class="premium-input-wrap">
                            <label class="form-label-premium">Full Name</label>
                            <input type="text" class="form-control-premium" value="<?php echo escape($current_student['full_name']); ?>" disabled>
                        </div>
                        
                        <div class="premium-input-wrap">
                            <label class="form-label-premium">Roll Number</label>
                            <input type="text" class="form-control-premium" value="<?php echo escape($current_student['roll_number']); ?>" disabled>
                        </div>
                        
                        <div class="premium-input-wrap">
                            <label class="form-label-premium">Class</label>
                            <input type="text" class="form-control-premium" value="<?php echo escape($current_student['class_name']); ?>" disabled>
                        </div>
                        
                        <div class="premium-input-wrap">
                            <label class="form-label-premium">Date of Birth</label>
                            <input type="text" class="form-control-premium" value="<?php echo formatDate($current_student['date_of_birth']); ?>" disabled>
                        </div>
                        
                        <!-- Divider -->
                        <div class="col-span-1 md:col-span-2">
                            <hr style="border:0; border-top:1px dashed var(--color-border); margin: var(--spacing-sm) 0;">
                        </div>
                        
                        <!-- Editable fields group -->
                        <div class="premium-input-wrap">
                            <label for="email" class="form-label-premium required">Email Address</label>
                            <div class="relative">
                                <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-secondary"></i>
                                <input type="email" id="email" name="email" class="form-control-premium pl-10" value="<?php echo escape($current_student['email']); ?>" style="padding-left: 40px;" required>
                            </div>
                        </div>
                        
                        <div class="premium-input-wrap">
                            <label for="phone" class="form-label-premium">Phone Number</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-4 top-1/2 transform -translate-y-1/2 text-secondary"></i>
                                <input type="tel" id="phone" name="phone" class="form-control-premium pl-10" value="<?php echo escape($current_student['phone'] ?? ''); ?>" style="padding-left: 40px;">
                            </div>
                        </div>
                        
                        <div class="premium-input-wrap col-span-1 md:col-span-2">
                            <label for="address" class="form-label-premium">Residential Address</label>
                            <textarea id="address" name="address" class="form-control-premium" rows="3" style="resize: vertical; min-height: 80px;"><?php echo escape($current_student['address'] ?? ''); ?></textarea>
                        </div>
                        
                    </div>
                </div>
                
                <div class="form-card-footer">
                    <a href="change-password.php" class="btn btn-outlined">
                        <i class="fas fa-key"></i> Security Settings
                    </a>
                    <button type="submit" class="btn btn-primary shadow-md">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
