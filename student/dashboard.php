<?php
$page_title = 'Dashboard';
require_once 'header.php';

// Get student results (Grouped by exam to prevent duplications)
$results_sql = "SELECT student_id, exam_id, exam_name, exam_date, 
                       overall_percentage as percentage, overall_grade as grade 
                FROM vw_student_results 
                WHERE student_id = ? 
                GROUP BY exam_id, student_id, exam_name, exam_date, overall_percentage, overall_grade 
                ORDER BY exam_date DESC LIMIT 5";
$recent_results = getRows($conn, $results_sql, [$_SESSION['student_id']]);

// Get notices
$notices_sql = "SELECT * FROM notices WHERE is_published = 1 AND (expiry_date IS NULL OR expiry_date > NOW()) ORDER BY priority DESC, created_at DESC LIMIT 3";
$notices = getRows($conn, $notices_sql);

// Get statistics
$stats_sql = "SELECT 
                COUNT(DISTINCT exam_id) as total_exams,
                AVG(percentage) as avg_percentage,
                MAX(percentage) as best_percentage
              FROM vw_student_results 
              WHERE student_id = ?";
$stats = getRow($conn, $stats_sql, [$_SESSION['student_id']]);
?>

<style>
/* Premium Dashboard Overrides */
.page-header {
    background: linear-gradient(135deg, rgba(21,101,192,0.02) 0%, rgba(21,101,192,0.06) 100%);
    padding: var(--spacing-2xl) var(--spacing-3xl);
    border-radius: 24px;
    margin-bottom: var(--spacing-xl);
    border: 1px solid rgba(21,101,192,0.1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
}
.page-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--color-primary);
}
.stat-card {
    background: var(--color-surface);
    border: none;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: var(--spacing-xl) var(--spacing-lg);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(21,101,192,0.1);
}
.stat-icon {
    width: 64px; height: 64px;
    font-size: 28px;
    border-radius: 16px;
    margin-bottom: var(--spacing-md);
    display: flex; align-items: center; justify-content: center;
}
.stat-icon-primary { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); color: #1565c0; }
.stat-icon-success { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #2e7d32; }
.stat-icon-warning { background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); color: #f57f17; }
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.03);
    overflow: hidden;
    transition: all 0.3s ease;
}
.card:hover { box-shadow: 0 12px 32px rgba(0,0,0,0.06); }
.card-header {
    background: var(--color-surface-variant);
    border-bottom: 1px solid var(--color-border);
    padding: var(--spacing-lg) var(--spacing-xl);
}
.card-title { font-weight: 700; font-size: 18px; color: var(--color-text-primary); }
.btn-primary.gradient-btn {
    background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(21,101,192,0.3);
    transition: all 0.2s ease;
}
.btn-primary.gradient-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,101,192,0.4);
}
.notice-pill {
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: 12px;
    border-left: 4px solid var(--color-primary);
    background: var(--color-surface-variant);
}
</style>

<div class="page-header">
    <h1 class="page-title">Welcome, <?php echo escape(explode(' ', $current_student['full_name'] ?? 'Student')[0]); ?>!</h1>
    <p class="page-subtitle text-secondary mt-xs" style="font-size: 16px;"><?php echo escape($current_student['class_name'] ?? 'Unknown Class'); ?> &nbsp;•&nbsp; Roll No: <?php echo escape($current_student['roll_number'] ?? 'N/A'); ?></p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-3 gap-lg mb-xl">
    <div class="stat-card slide-in-up">
        <div class="stat-icon stat-icon-primary">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Exams</div>
            <div class="stat-value"><?php echo $stats['total_exams'] ?? 0; ?></div>
        </div>
    </div>
    
    <div class="stat-card slide-in-up">
        <div class="stat-icon stat-icon-success">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Average %</div>
            <div class="stat-value"><?php echo number_format($stats['avg_percentage'] ?? 0, 1); ?>%</div>
        </div>
    </div>
    
    <div class="stat-card slide-in-up">
        <div class="stat-icon stat-icon-warning">
            <i class="fas fa-trophy"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Best Score</div>
            <div class="stat-value"><?php echo number_format($stats['best_percentage'] ?? 0, 1); ?>%</div>
        </div>
    </div>
</div>

<!-- Recent Results -->
<div class="grid grid-cols-2 gap-lg mb-xl">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Results</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_results)): ?>
                <p class="text-secondary text-center py-lg">No results available yet</p>
            <?php else: ?>
                <div class="space-y-md">
                    <?php foreach ($recent_results as $result): ?>
                    <div class="flex justify-between items-center p-sm hover:bg-surface-variant rounded transition">
                        <div>
                            <div class="font-medium"><?php echo escape($result['exam_name']); ?></div>
                            <div class="text-sm text-secondary"><?php echo formatDate($result['exam_date']); ?></div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium"><?php echo number_format($result['percentage'], 2); ?>%</div>
                            <span class="grade-badge grade-<?php echo strtolower($result['grade']); ?>">
                                <?php echo $result['grade']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($recent_results)): ?>
        <div class="card-footer">
            <a href="results.php" class="text-primary text-sm">View all results →</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Notices -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Latest Notices</h3>
        </div>
        <div class="card-body">
            <?php if (empty($notices)): ?>
                <p class="text-secondary text-center py-lg">No notices available</p>
            <?php else: ?>
                <div class="space-y-md">
                    <?php foreach ($notices as $notice): ?>
                    <div class="p-sm hover:bg-surface-variant rounded transition">
                        <div class="flex justify-between items-start mb-xs">
                            <div class="font-medium"><?php echo escape($notice['title']); ?></div>
                            <?php if ($notice['priority'] === 'high' || $notice['priority'] === 'urgent'): ?>
                            <span class="badge badge-error">Important</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-secondary mt-xs" style="line-height: 1.5;"><?php echo escape(substr($notice['content'], 0, 100)); ?>...</div>
                        <div class="text-xs mt-sm text-secondary" style="font-weight: 500;"><i class="fas fa-clock mr-xs"></i> <?php echo timeAgo($notice['created_at']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="flex gap-md flex-wrap">
            <a href="results.php" class="btn btn-primary gradient-btn" style="border-radius: 999px; padding: 12px 24px;">
                <i class="fas fa-chart-line"></i> View All Results
            </a>
            <a href="profile.php" class="btn btn-outlined" style="border-radius: 999px; padding: 12px 24px;">
                <i class="fas fa-user"></i> Update Profile
            </a>
            <a href="change-password.php" class="btn btn-outlined" style="border-radius: 999px; padding: 12px 24px;">
                <i class="fas fa-key"></i> Change Password
            </a>
            <a href="../result-search.php" class="btn btn-outlined" target="_blank" style="border-radius: 999px; padding: 12px 24px;">
                <i class="fas fa-search"></i> Public Result Search
            </a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
