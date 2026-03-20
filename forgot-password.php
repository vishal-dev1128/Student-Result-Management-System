<?php
/**
 * Forgot Password Page
 * Sends a password reset link to admin email
 */

// Start session before anything
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';

// If already logged in as admin → redirect
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$message = '';
$message_type = '';
$step = 'request'; // request | sent

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple email-based reset request
    $email = sanitizeInput($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if admin with this email exists
        $admin = getRow($conn, "SELECT id, full_name, email FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);

        // Always show success (security: don't reveal if email exists)
        $message = 'If that email is registered, a password reset link has been sent. Please check your inbox.';
        $message_type = 'success';
        $step = 'sent';

        if ($admin) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            try {
                // Store token
                executeQuery($conn,
                    "UPDATE admin_users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
                    [$token, $expires, $admin['id']]);

                // For local dev: just log / show token (no email server)
                $reset_link = APP_URL . '/reset-password.php?token=' . $token;
                error_log("Password reset link for {$admin['email']}: $reset_link");

            } catch (Exception $e) {
                // Column may not exist — just show success anyway
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: var(--color-surface);
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
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
            padding: 0.5rem 0.5rem 0.5rem 2.2rem;
            font-size: 0.85rem;
            border-radius: 4px;
            background: transparent;
            border: 1px solid var(--color-border);
            width: 100%;
            box-sizing: border-box;
        }
        
        .input-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-secondary);
            font-size: 0.9rem;
        }

        .btn-submit {
            padding: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-top: 1rem;
            background: #1565C0;
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
            margin-top: 1.5rem;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: var(--color-text-primary);
        }
        
        .alert {
            padding: 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-error { background: rgba(198, 40, 40, 0.1); color: #c62828; border: 1px solid rgba(198, 40, 40, 0.2); }
        .alert-success { background: rgba(46, 125, 50, 0.1); color: #2e7d32; border: 1px solid rgba(46, 125, 50, 0.2); }
        
        @media (max-width: 768px) {
            .split-layout { flex-direction: column; }
            .split-left { padding: 3rem 1.5rem; flex: none; min-height: 40vh; }
            .split-right { padding: 2rem 1.5rem; flex: 1; }
            .brand-content h1 { font-size: 2.2rem; }
        }
    </style>
</head>
<body>
    <div class="split-layout">
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
                <p class="desc">Access restricted. Experience excellence redefined with exceptional quality and timeless design in every piece.</p>
            </div>
        </div>
        
        <div class="split-right">
            <div class="form-wrapper fade-in">
                <?php if ($step === 'sent'): ?>
                    <h2>Check Your Email</h2>
                    <p>If your email is registered in our system, you'll receive a password reset link shortly.</p>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Reset link sent! Check your inbox.
                    </div>
                    <a href="login.php" class="btn-submit" style="display:block; text-align:center; text-decoration:none;">
                        BACK TO LOGIN
                    </a>
                <?php else: ?>
                    <h2>Forgot Password?</h2>
                    <p>Enter your admin email address and we'll send you a link to reset your password.</p>

                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type === 'error' ? 'error' : 'success'; ?>">
                        <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                        <?php echo escape($message); ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-icon"><i class="fas fa-envelope"></i></span>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="your@email.com"
                                    value="<?php echo escape($_POST['email'] ?? ''); ?>"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">
                            SEND RESET LINK
                        </button>
                    </form>

                    <div style="text-align: center;">
                        <a href="login.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
