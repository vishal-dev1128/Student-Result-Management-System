<?php
require_once 'includes/config.php';

// Get latest notices
$notices_sql = "SELECT * FROM notices WHERE is_published = 1 AND (expiry_date IS NULL OR expiry_date > NOW()) ORDER BY priority DESC, created_at DESC LIMIT 3";
$notices = getRows($conn, $notices_sql);

// Get FAQs
$faqs_sql = "SELECT * FROM faqs WHERE is_published = 1 ORDER BY display_order ASC, id ASC LIMIT 5";
$faqs = getRows($conn, $faqs_sql);

// Get statistics
$stats_sql = "SELECT * FROM vw_dashboard_stats";
$stats = getRow($conn, $stats_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Result Management System - Check your exam results online">
    <title><?php echo APP_NAME; ?> - Home</title>
    
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
        /* Hero Section */
        .hero {
            background-image: url('images/hero-banner.png');
            background-size: cover;
            background-position: center;
            color: white;
            padding: var(--spacing-4xl) 0 var(--spacing-3xl);
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(26, 54, 126, 0.82) 0%, rgba(15, 30, 80, 0.88) 100%);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: var(--font-size-5xl);
            margin-bottom: var(--spacing-md);
            color: white;
        }
        
        .hero p {
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-xl);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .hero-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Feature Cards */
        .features {
            padding: var(--spacing-3xl) 0;
        }
        
        .feature-card {
            text-align: center;
            transition: transform var(--transition-base);
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto var(--spacing-lg);
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-3xl);
            color: white;
        }
        
        .feature-card {
            text-align: center;
        }
        
        .feature-card h3 {
            color: var(--color-text-primary);
            margin-bottom: var(--spacing-sm);
        }
        
        /* Notice Board */
        .notice-board {
            background: var(--color-surface-variant);
            padding: var(--spacing-3xl) 0;
        }
        
        .notice-item {
            border-left: 4px solid var(--color-primary);
        }
        
        .notice-priority-high {
            border-left-color: var(--color-error);
        }
        
        .notice-priority-urgent {
            border-left-color: var(--color-warning);
        }
        
        /* FAQ Section */
        .faq-item {
            margin-bottom: var(--spacing-md);
        }
        
        .faq-question {
            background: var(--color-surface);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color var(--transition-fast);
        }
        
        .faq-question:hover {
            background: var(--color-surface-variant);
        }
        
        .faq-question h4 {
            margin: 0;
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-medium);
        }
        
        .faq-answer {
            display: none;
            padding: var(--spacing-md) var(--spacing-lg);
            color: var(--color-text-secondary);
        }
        
        .faq-item.active .faq-answer {
            display: block;
        }
        
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        
        .faq-icon {
            transition: transform var(--transition-base);
        }
        
        /* Footer */
        .footer {
            background: var(--color-surface);
            padding: var(--spacing-2xl) 0 var(--spacing-lg);
            border-top: 1px solid var(--color-border);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }
        
        .footer h3 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-md);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: var(--spacing-sm);
        }
        
        .footer-links a {
            color: var(--color-text-secondary);
            transition: color var(--transition-fast);
        }
        
        .footer-links a:hover {
            color: var(--color-primary);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--color-border);
            color: var(--color-text-secondary);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: var(--font-size-3xl);
            }
            
            .hero p {
                font-size: var(--font-size-lg);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-graduation-cap"></i> SRMS
            </a>
            
            <button class="navbar-toggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-menu">
                <a href="index.php" class="navbar-link active">Home</a>
                <a href="result-search.php" class="navbar-link">Check Results</a>
                <a href="login.php" class="navbar-link">Admin Login</a>
                <a href="student/login.php" class="navbar-link">Student Portal</a>
                <button data-theme-toggle class="btn btn-outlined btn-sm">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content fade-in">
                <h1>Student Result Management System</h1>
                <p>Access your exam results instantly, anytime, anywhere. Fast, secure, and easy to use.</p>
                <div class="hero-actions">
                    <a href="result-search.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-search"></i> Check Your Results
                    </a>
                    <a href="student/login.php" class="btn btn-outlined btn-lg" style="border-color: white; color: white;">
                        <i class="fas fa-user"></i> Student Login
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="text-center mb-2xl">
                <h2>Why Choose Our System?</h2>
                <p class="text-secondary">Modern, secure, and user-friendly result management</p>
            </div>
            
            <div class="grid grid-cols-4 gap-lg">
                <div class="card feature-card stagger-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Instant Access</h3>
                    <p>Get your results immediately after publication. No waiting, no hassle.</p>
                </div>
                
                <div class="card feature-card stagger-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure & Private</h3>
                    <p>Your data is protected with industry-standard security measures.</p>
                </div>
                
                <div class="card feature-card stagger-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access from any device - desktop, tablet, or smartphone.</p>
                </div>
                
                <div class="card feature-card stagger-item">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3>PDF Download</h3>
                    <p>Download your results as PDF for printing and record keeping.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <?php if ($stats): ?>
    <section class="py-xl bg-surface-variant">
        <div class="container">
            <div class="grid grid-cols-4 gap-lg">
                <div class="stat-card slide-in-up">
                    <div class="stat-icon stat-icon-primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card slide-in-up">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Active Classes</div>
                        <div class="stat-value"><?php echo number_format($stats['total_classes']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card slide-in-up">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Exams Completed</div>
                        <div class="stat-value"><?php echo number_format($stats['completed_exams']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card slide-in-up">
                    <div class="stat-icon stat-icon-error">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Active Notices</div>
                        <div class="stat-value"><?php echo number_format($stats['active_notices']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Notice Board -->
    <?php if (!empty($notices)): ?>
    <section class="notice-board">
        <div class="container">
            <div class="text-center mb-2xl">
                <h2>Latest Notices</h2>
                <p class="text-secondary">Stay updated with important announcements</p>
            </div>
            
            <div class="grid grid-cols-1 gap-md" style="max-width: 900px; margin: 0 auto;">
                <?php foreach ($notices as $notice): ?>
                <div class="card notice-item notice-priority-<?php echo strtolower($notice['priority']); ?> fade-in">
                    <div class="card-header">
                        <div class="flex justify-between items-center">
                            <h3 class="card-title"><?php echo escape($notice['title']); ?></h3>
                            <span class="badge badge-<?php echo $notice['priority'] === 'high' || $notice['priority'] === 'urgent' ? 'error' : 'primary'; ?>">
                                <?php echo escape($notice['priority']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-secondary">
                            <i class="far fa-clock"></i> <?php echo formatDateTime($notice['publish_date']); ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <p><?php echo escape($notice['content']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ Section -->
    <?php if (!empty($faqs)): ?>
    <section class="py-3xl">
        <div class="container">
            <div class="text-center mb-2xl">
                <h2>Frequently Asked Questions</h2>
                <p class="text-secondary">Find answers to common questions</p>
            </div>
            
            <div style="max-width: 800px; margin: 0 auto;">
                <?php foreach ($faqs as $index => $faq): ?>
                <div class="faq-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h4><?php echo escape($faq['question']); ?></h4>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo escape($faq['answer']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <h3><i class="fas fa-graduation-cap"></i> SRMS</h3>
                    <p class="text-secondary">Modern student result management system for educational institutions.</p>
                </div>
                
                <div>
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="result-search.php">Check Results</a></li>
                        <li><a href="login.php">Admin Login</a></li>
                        <li><a href="student/login.php">Student Portal</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> <?php echo getSetting($conn, 'institute_email', 'info@srms.local'); ?></li>
                        <li><i class="fas fa-phone"></i> <?php echo getSetting($conn, 'institute_phone', '+91-1234567890'); ?></li>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo getSetting($conn, 'institute_address', 'Address not set'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting($conn, 'institute_name', APP_NAME); ?>. All rights reserved.</p>
                <p class="text-sm">Powered by SRMS v<?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </footer>

    <!-- Bottom Navigation (Mobile) -->
    <div class="bottom-nav">
        <div class="bottom-nav-container">
            <a href="index.php" class="bottom-nav-item active">
                <i class="fas fa-home bottom-nav-icon"></i>
                <span>Home</span>
            </a>
            <a href="result-search.php" class="bottom-nav-item">
                <i class="fas fa-search bottom-nav-icon"></i>
                <span>Results</span>
            </a>
            <a href="student/login.php" class="bottom-nav-item">
                <i class="fas fa-user bottom-nav-icon"></i>
                <span>Portal</span>
            </a>
            <a href="login.php" class="bottom-nav-item">
                <i class="fas fa-lock bottom-nav-icon"></i>
                <span>Admin</span>
            </a>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    <script>
        // FAQ Toggle
        function toggleFAQ(element) {
            const faqItem = element.closest('.faq-item');
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQs
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked FAQ if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }
    </script>
</body>
</html>
