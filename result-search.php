<?php
require_once 'includes/config.php';

// Initialize variables
$result = null;
$error = null;
$search_performed = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_number = sanitizeInput($_POST['roll_number'] ?? '');
    $class_id = sanitizeInteger($_POST['class_id'] ?? '');
    $exam_id = sanitizeInteger($_POST['exam_id'] ?? '');
    
    if (empty($roll_number) || empty($class_id) || empty($exam_id)) {
        $error = 'Please fill in all fields';
    } else {
        $search_performed = true;
        
        // Search for student
        $student_sql = "SELECT * FROM students WHERE roll_number = ? AND class_id = ? AND status = 'active'";
        $student = getRow($conn, $student_sql, [$roll_number, $class_id]);
        
        if ($student) {
            // Get result
            $result_sql = "SELECT * FROM vw_student_results WHERE student_id = ? AND exam_id = ?";
            $result = getRow($conn, $result_sql, [$student['id'], $exam_id]);
            
            if (!$result) {
                $error = 'No results found for the given criteria';
            }
        } else {
            $error = 'Student not found with the given roll number and class';
        }
    }
}

// Get classes for dropdown
$classes_sql = "SELECT id, name as class_name FROM classes WHERE status = 'active' ORDER BY name ASC";
$classes = getRows($conn, $classes_sql);

// Get exams for dropdown
$exams_sql = "SELECT * FROM exams WHERE is_published = 1 AND status = 'active' ORDER BY exam_date DESC";
$exams = getRows($conn, $exams_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Check your exam results online - Student Result Management System">
    <title>Check Results - <?php echo APP_NAME; ?></title>
    
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
        .search-container {
            height: calc(100vh - 80px); /* Adjust based on navbar height */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md) 0;
        }
        
        .search-card {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-header {
            text-align: center;
            margin-bottom: var(--spacing-sm); /* Reduced margin */
        }
        
        .search-icon {
            width: 50px; /* Reduced from 80px */
            height: 50px;
            margin: 0 auto var(--spacing-sm); /* Reduced margin */
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem; /* Reduced font size */
            color: white;
        }
        
        .result-display {
            max-width: 900px;
            margin: var(--spacing-md) auto;
            max-height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
        }
        
        .result-display .card {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Result Header ── */
        .result-header {
            text-align: center;
            padding: var(--spacing-sm) var(--spacing-md);
            background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
            color: #ffffff !important;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .result-header h1,
        .result-header h2,
        .result-header h3,
        .result-header p,
        .result-header span {
            color: #ffffff !important;
        }

        .result-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
            letter-spacing: 0.3px;
        }

        .result-header p {
            font-size: 0.8rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 500;
        }
        
        .result-top-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 0;
            background: var(--color-surface-variant);
            border-bottom: 1px solid var(--color-border);
        }

        .result-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px; /* Reduced gap */
            padding: var(--spacing-sm) var(--spacing-md); /* Reduced padding */
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.7rem; /* Reduced */
            color: var(--color-text-secondary);
            margin-bottom: 0px;
        }
        
        .info-value {
            font-size: 0.8rem; /* Reduced */
            font-weight: var(--font-weight-medium);
            color: var(--color-text-primary);
        }
        
        .result-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
            padding: var(--spacing-xs);
            align-content: center;
        }
        
        .summary-item {
            text-align: center;
            padding: var(--spacing-xs); /* Reduced */
            background: transparent;
            border-radius: 0;
            border-left: 1px solid var(--color-border);
        }
        
        .summary-item:first-child {
            border-left: none;
        }
        
        .summary-label {
            font-size: 0.65rem; /* Reduced */
            color: var(--color-text-secondary);
            margin-bottom: 0px;
        }
        
        .summary-value {
            font-size: 1rem; /* Reduced */
            font-weight: var(--font-weight-bold);
            color: var(--color-primary);
        }
        
        .card-body {
            padding: var(--spacing-sm) var(--spacing-md); /* Reduced padding */
            overflow-y: auto;
            flex-grow: 1;
        }
        
        .table {
            font-size: 0.75rem; /* Reduced font size */
            margin-bottom: 0;
        }
        
        .table th, .table td {
            padding: 0.25rem 0.5rem; /* Reduced padding */
        }
        
        .result-actions {
            display: flex;
            gap: var(--spacing-sm);
            justify-content: center;
            padding: 8px; /* Reduced padding */
            border-top: 1px solid var(--color-border);
            background: var(--color-surface);
        }

        /* ── Rich Grade Badges ── */
        .grade-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px; /* Reduced */
            padding: 3px 8px; /* Reduced */
            border-radius: 999px;
            font-size: 0.7rem; /* Reduced */
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #fff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.18);
        }

        .grade-badge.grade-a\+ { background: linear-gradient(135deg, #1b5e20, #2e7d32); }
        .grade-badge.grade-a   { background: linear-gradient(135deg, #2e7d32, #388e3c); }
        .grade-badge.grade-b\+ { background: linear-gradient(135deg, #0d47a1, #1565c0); }
        .grade-badge.grade-b   { background: linear-gradient(135deg, #1565c0, #1976d2); }
        .grade-badge.grade-c   { background: linear-gradient(135deg, #e65100, #f57c00); }
        .grade-badge.grade-d   { background: linear-gradient(135deg, #bf360c, #e64a19); }
        .grade-badge.grade-f   { background: linear-gradient(135deg, #b71c1c, #c62828); }
        
        @media (max-width: 768px) {
            .result-info {
                grid-template-columns: 1fr;
            }
            
            .result-summary {
                grid-template-columns: 1fr;
            }
        }
        
        @media print {
            .navbar, .result-actions, .bottom-nav {
                display: none !important;
            }
            .result-display {
                max-height: none;
                margin: 0;
            }
            body {
                overflow: visible !important;
            }
        }
        
        body {
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
    </style>

</head>
<body class="no-scroll">
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
                <a href="index.php" class="navbar-link">Home</a>
                <a href="result-search.php" class="navbar-link active">Check Results</a>
                <a href="login.php" class="navbar-link">Admin Login</a>
                <a href="student/login.php" class="navbar-link">Student Portal</a>
                <button data-theme-toggle class="btn btn-outlined btn-sm">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </div>
        </div>
    </nav>

    <?php if (!$result): ?>
    <!-- Search Form -->
    <div class="search-container">
        <div class="container" style="width: 100%;">
            <div class="card search-card fade-in">
                <div class="search-header" style="margin-bottom: var(--spacing-md);">
                    <div class="search-icon" style="width: 60px; height: 60px; font-size: 2rem; margin-bottom: var(--spacing-sm);">
                        <i class="fas fa-search"></i>
                    </div>
                    <h1 style="font-size: 1.5rem;">Check Your Results</h1>
                    <p class="text-secondary" style="font-size: 0.9rem;">Enter your details to view your exam results</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="form">
                    <div class="form-group" style="margin-bottom: var(--spacing-sm);">
                        <label for="roll_number" class="form-label required" style="font-size: 0.8rem; margin-bottom: 2px;">Roll Number</label>
                        <input 
                            type="text" 
                            id="roll_number" 
                            name="roll_number" 
                            class="form-control" 
                            placeholder="Enter your roll number"
                            value="<?php echo escape($_POST['roll_number'] ?? ''); ?>"
                            required
                            style="padding: 0.4rem; font-size: 0.85rem;"
                        >
                    </div>
                    
                    <div class="form-group" style="margin-bottom: var(--spacing-sm);">
                        <label for="class_id" class="form-label required" style="font-size: 0.8rem; margin-bottom: 2px;">Class</label>
                        <select id="class_id" name="class_id" class="form-control" required style="padding: 0.4rem; font-size: 0.85rem;">
                            <option value="">Select your class</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($class['class_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: var(--spacing-md);">
                        <label for="exam_id" class="form-label required" style="font-size: 0.8rem; margin-bottom: 2px;">Exam</label>
                        <select id="exam_id" name="exam_id" class="form-control" required style="padding: 0.4rem; font-size: 0.85rem;">
                            <option value="">Select exam</option>
                            <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo $exam['id']; ?>" <?php echo (isset($_POST['exam_id']) && $_POST['exam_id'] == $exam['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($exam['exam_name']); ?> (<?php echo formatDate($exam['exam_date']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 0.4rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-search"></i> Search Results
                    </button>
                </form>
                
                <div class="text-center mt-sm">
                    <p class="text-secondary" style="font-size: 0.8rem;">
                        <i class="fas fa-info-circle"></i> 
                        Already have an account? 
                        <a href="student/login.php" class="text-primary">Login to Student Portal</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Result Display -->
    <div class="result-display fade-in">
        <div class="card">
            <div class="result-header">
                <h1><?php echo getSetting($conn, 'institute_name', APP_NAME); ?></h1>
                <p><?php echo escape($result['exam_name']); ?></p>
            </div>
            
            <div class="result-top-section">
                <div class="result-info">
                    <div class="info-item">
                        <span class="info-label">Student Name</span>
                        <span class="info-value"><?php echo escape($result['student_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Roll Number</span>
                        <span class="info-value"><?php echo escape($result['roll_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Class</span>
                        <span class="info-value"><?php echo escape($result['class_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Exam Date</span>
                        <span class="info-value"><?php echo formatDate($result['exam_date']); ?></span>
                    </div>
                </div>
                
                <div class="result-summary">
                    <div class="summary-item">
                        <div class="summary-label">Marks</div>
                        <div class="summary-value" style="font-size: 0.9rem;"><?php echo formatNumber($result['total_marks_obtained']); ?>/<?php echo formatNumber($result['total_marks']); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Percentage</div>
                        <div class="summary-value" style="font-size: 0.9rem;"><?php echo formatNumber($result['percentage'], 2); ?>%</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Grade</div>
                        <div class="summary-value">
                            <?php $overallGradeClass = str_replace('+', 'plus', strtolower($result['grade'])); ?>
                            <span class="grade-badge grade-<?php echo $overallGradeClass; ?>" style="font-size: 0.8rem; padding: 2px 8px;">
                                <?php echo escape($result['grade']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <h3 class="mb-xs" style="font-size: 0.9rem; margin-bottom: 4px;">Subject-wise Marks</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th class="text-center">Obtained</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">%</th>
                                <th class="text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get individual subject results
                            $subjects_sql = "SELECT r.*, s.subject_name 
                                           FROM results r 
                                           JOIN subjects s ON r.subject_id = s.id 
                                           WHERE r.student_id = ? AND r.exam_id = ?
                                           ORDER BY s.subject_name ASC";
                            $subjects = getRows($conn, $subjects_sql, [$result['student_id'], $result['exam_id']]);
                            
                            foreach ($subjects as $subject):
                                $marks_obtained = $subject['total_marks_obtained'] ?? 0;
                                $total_marks = $subject['total_marks'] ?? 100;
                                $percentage = ($total_marks > 0) ? (($marks_obtained / $total_marks) * 100) : 0;
                                $grade = calculateGrade($percentage, $conn);
                            ?>
                            <tr>
                                <td><?php echo escape($subject['subject_name']); ?></td>
                                <td class="text-center"><?php echo formatNumber($marks_obtained); ?></td>
                                <td class="text-center"><?php echo formatNumber($total_marks); ?></td>
                                <td class="text-center"><?php echo formatNumber($percentage, 2); ?>%</td>
                                <td class="text-center">
                                    <?php $gradeClass = str_replace('+', 'plus', strtolower($grade)); ?>
                                    <span class="grade-badge grade-<?php echo $gradeClass; ?>">
                                        <?php echo $grade; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="result-actions">
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="download-result.php?id=<?php echo $result['student_id']; ?>&exam=<?php echo $result['exam_id']; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> PDF
                </a>
                <a href="result-search.php" class="btn btn-outlined btn-sm">
                    <i class="fas fa-search"></i> Back
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bottom Navigation (Mobile) -->
    <div class="bottom-nav">
        <div class="bottom-nav-container">
            <a href="index.php" class="bottom-nav-item">
                <i class="fas fa-home bottom-nav-icon"></i>
                <span>Home</span>
            </a>
            <a href="result-search.php" class="bottom-nav-item active">
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
</body>
</html>
