<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

// Initialize variables
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } else {
            // Check rate limiting
            $ip_address = $_SERVER['REMOTE_ADDR'];
            if (!checkRateLimit($conn, $ip_address, 'login')) {
                $error = 'Too many login attempts. Please try again later.';
            } else {
                // Get admin user
                $sql = "SELECT * FROM admin_users WHERE username = ? AND status = 'active'";
                $admin = getRow($conn, $sql, [$username]);
                
                if ($admin && verifyPassword($password, $admin['password'])) {
                    // Update last login
                    $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
                    executeQuery($conn, $update_sql, [$admin['id']]);
                    
                    // Set session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    // Set session fingerprint
                    setSessionFingerprint();
                    
                    // Set remember me cookie
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                        
                        // Store token in database (in production, hash this token)
                        $update_sql = "UPDATE admin_users SET remember_token = ? WHERE id = ?";
                        executeQuery($conn, $update_sql, [$token, $admin['id']]);
                    }
                    
                    // Log activity
                    logActivity($conn, $admin['id'], 'admin_login', 'Admin logged in successfully');
                    
                    // Reset rate limiting on success
                    resetLoginAttempts($ip_address, 'login');
                    
                    // Redirect to dashboard
                    redirect('admin/dashboard.php');
                } else {
                    $error = 'Invalid username or password';
                    
                    // Record failed attempt for rate limiting
                    recordFailedLogin($ip_address, 'login');
                    
                    // Log failed attempt
                    if ($admin) {
                        logActivity($conn, $admin['id'], 'failed_login', 'Failed login attempt');
                    }
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Login - Student Result Management System">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/animations.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: var(--color-surface);
            overflow-x: hidden;
        }
        
        .split-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        
        .split-left {
            flex: 1.2;
            background: linear-gradient(135deg, #0D47A1 0%, #1565C0 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
        }
        
        .split-right {
            flex: 1;
            background: var(--color-surface);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        
        .brand-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            letter-spacing: 4px;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            color: #ffffff;
            line-height: 1.2;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .brand-content .desc {
            max-width: 380px;
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin: 0 auto;
        }
        
        .form-wrapper {
            width: 100%;
            max-width: 360px;
        }
        
        .form-wrapper h2 {
            font-size: 1.6rem;
            margin-bottom: 0.2rem;
            font-weight: 700;
            color: var(--color-text-primary);
        }
        
        .form-wrapper > p {
            color: var(--color-text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }
        
        .form-wrapper p a {
            font-weight: 600;
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-size: 0.8rem;
            color: var(--color-text-secondary);
            margin-bottom: 0.3rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .form-control {
            padding: 0.5rem 0.5rem 0.5rem 2.2rem; /* Compact padding */
            font-size: 0.85rem;
            border-radius: 4px;
            background: transparent;
            border: 1px solid var(--color-border);
        }
        
        .input-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-secondary);
            font-size: 0.9rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-text-secondary);
            cursor: pointer;
            padding: 0;
            font-size: 0.9rem;
        }
        
        .password-toggle:hover {
            color: var(--color-primary);
        }
        
        .btn-submit {
            padding: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-top: 1rem;
            background: #1565C0; /* Theme button color */
            color: #fff;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-submit:hover {
            background: #0D47A1;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--color-text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            margin-top: 2rem;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: var(--color-text-primary);
        }
        
        .theme-toggle-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            color: var(--color-text-secondary);
            cursor: pointer;
            font-size: 1.2rem;
        }

        .checkbox-label {
            font-size: 0.8rem;
            color: var(--color-text-secondary);
        }
        
        /* Dark mode overrides if needed */
        [data-theme="dark"] .split-right {
            background: #121212;
        }
        [data-theme="dark"] .btn-submit {
            background: #fff;
            color: #000;
        }
        [data-theme="dark"] .btn-submit:hover {
            background: #eee;
        }
        
        @media (max-width: 768px) {
            .split-layout {
                flex-direction: column;
            }
            .split-left {
                padding: 3rem 1.5rem;
                flex: none;
                min-height: 40vh;
            }
            .split-right {
                padding: 2rem 1.5rem;
                flex: 1;
            }
            .brand-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <!-- Left Side: Branding -->
        <div class="split-left">
            <div class="brand-content fade-in">
                <div class="brand-logo" style="margin-bottom:1.5rem;">
                    <span style="
                        display:inline-flex;align-items:center;gap:0.6rem;
                        background:rgba(255,255,255,0.15);
                        border:1.5px solid rgba(255,255,255,0.3);
                        border-radius:50px;
                        padding:0.45rem 1.1rem;
                        font-size:1.15rem;font-weight:700;
                        color:#fff;letter-spacing:0.5px;
                        backdrop-filter:blur(6px);
                    ">
                        <i class="fas fa-graduation-cap" style="font-size:1.3rem;"></i> SRMS
                    </span>
                </div>
                <h1>STUDENT RESULT<br>MANAGEMENT SYSTEM</h1>
                <p class="desc">Join our exclusive community. Experience excellence redefined with exceptional quality and timeless design in every piece.</p>
            </div>
        </div>
        
        <!-- Right Side: Login Form -->
        <div class="split-right">
            <button data-theme-toggle class="theme-toggle-btn" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
            
            <div class="form-wrapper fade-in">
                <h2>Admin Login</h2>
                <p>For student login, visit the <a href="student/login.php" class="text-primary">Student Portal</a></p>
                
                <?php if ($error): ?>
                <div class="alert alert-error" style="padding: 10px; font-size: 0.85rem; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success" style="padding: 10px; font-size: 0.85rem; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo escape($success); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-control" 
                                value="<?php echo escape($_POST['username'] ?? ''); ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center" style="margin-top: 0.5rem;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" class="form-checkbox" style="margin-right: 5px;">
                            <span>Remember me</span>
                        </label>
                        <!-- Optional Forgot Password link below
                        <a href="forgot-password.php" style="font-size: 0.8rem; color: var(--color-primary); text-decoration: none;">Forgot password?</a>
                        -->
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        LOGIN TO ACCOUNT
                    </button>
                </form>
                
                <div style="text-align: center;">
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                showNotification('Please fill in all fields', 'error');
            }
        });
    </script>
</body>
</html>
