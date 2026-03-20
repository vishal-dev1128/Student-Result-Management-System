<?php
$page_title = 'Change Password';
require_once 'header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password)) $errors[] = 'Current password is required';
        if (empty($new_password)) $errors[] = 'New password is required';
        if (empty($confirm_password)) $errors[] = 'Please confirm your new password';
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long';
        }
        
        if (empty($errors)) {
            // Check if current password is correct
            $student_id = $_SESSION['student_id'];
            $sql = "SELECT password FROM students WHERE id = ?";
            $db_password = getValue($conn, $sql, [$student_id]);
            
            if (password_verify($current_password, $db_password)) {
                // Update password
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE students SET password = ?, updated_at = NOW() WHERE id = ?";
                
                if (executeQuery($conn, $update_sql, [$new_hashed_password, $student_id])) {
                    logActivity($conn, $student_id, 'password_changed', 'Student changed their password', 'student');
                    $success = true;
                } else {
                    $errors[] = 'Failed to update password. Please try again later.';
                }
            } else {
                $errors[] = 'Incorrect current password';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<style>
/* Premium Form Card */
.form-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    max-width: 600px;
    margin: 0 auto;
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
    margin-bottom: var(--spacing-lg);
}

.premium-input-wrap:focus-within {
    transform: translateY(-2px);
}

.form-control-premium {
    width: 100%;
    padding: 14px 16px 14px 44px; /* Space for icon */
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
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.input-icon-left {
    position: absolute;
    left: 16px;
    top: 38px;
    color: var(--color-text-secondary);
}

/* Toggle Password Visibility */
.pwd-toggle {
    position: absolute;
    right: 14px;
    top: 38px;
    background: none;
    border: none;
    color: var(--color-text-secondary);
    cursor: pointer;
    padding: 4px;
    font-size: 15px;
    transition: color 0.2s;
    line-height: 1;
}
.pwd-toggle:hover { color: var(--color-primary); }
</style>

<div class="page-header mb-xl text-center">
    <h1 class="page-title mb-xs" style="font-family: 'Playfair Display', serif; font-size: 2.5rem; letter-spacing: -0.5px;">Change Password</h1>
    <p class="page-subtitle text-secondary" style="font-size: 1.1rem;">Secure your account by regularly updating your password</p>
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
    <span class="font-medium">Password changed successfully! You can now use your new password next time you log in.</span>
</div>
<?php else: ?>

<div class="form-card slide-in-up">
    <div class="form-card-header">
        <h3 class="form-card-title">
            <i class="fas fa-key"></i> Security Settings
        </h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-card-body">
            
            <div class="premium-input-wrap">
                <label for="current_password" class="form-label-premium required">Current Password</label>
                <div class="relative">
                    <i class="fas fa-lock input-icon-left"></i>
                    <input type="password" id="current_password" name="current_password" class="form-control-premium" required>
                    <button type="button" class="pwd-toggle" onclick="togglePassword('current_password', 'current_icon')" aria-label="Toggle visibility">
                        <i class="fas fa-eye" id="current_icon"></i>
                    </button>
                </div>
            </div>
            
            <hr style="border:0; border-top:1px dashed var(--color-border); margin: var(--spacing-xl) 0;">
            
            <div class="premium-input-wrap">
                <label for="new_password" class="form-label-premium required">New Password</label>
                <div class="relative">
                    <i class="fas fa-shield-alt input-icon-left"></i>
                    <input type="password" id="new_password" name="new_password" class="form-control-premium" required>
                    <button type="button" class="pwd-toggle" onclick="togglePassword('new_password', 'new_icon')" aria-label="Toggle visibility">
                        <i class="fas fa-eye" id="new_icon"></i>
                    </button>
                </div>
                <small class="text-secondary" style="display:block; margin-top:8px;">Must be at least 6 characters long.</small>
            </div>
            
            <div class="premium-input-wrap mb-0">
                <label for="confirm_password" class="form-label-premium required">Confirm New Password</label>
                <div class="relative">
                    <i class="fas fa-check-double input-icon-left"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control-premium" required>
                    <button type="button" class="pwd-toggle" onclick="togglePassword('confirm_password', 'confirm_icon')" aria-label="Toggle visibility">
                        <i class="fas fa-eye" id="confirm_icon"></i>
                    </button>
                </div>
            </div>
            
        </div>
        
        <div class="form-card-footer">
            <a href="profile.php" class="btn btn-outlined">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary shadow-md">
                <i class="fas fa-save"></i> Change Password
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once 'footer.php'; ?>
