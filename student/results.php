<?php
$page_title = 'My Results';
require_once 'header.php';

// Get filter
$exam_filter = sanitizeInteger($_GET['exam'] ?? '');

// Build query
$where = ["student_id = ?"];
$params = [$_SESSION['student_id']];

if (!empty($exam_filter)) {
    $where[] = "exam_id = ?";
    $params[] = $exam_filter;
}

$where_clause = implode(' AND ', $where);

// Get results (Grouped by exam so we get 1 card per exam, not 1 per subject)
$sql = "SELECT student_id, exam_id, exam_name, exam_date, 
               overall_percentage as percentage, overall_grade as grade, 
               SUM(total_marks_obtained) as total_marks_obtained, 
               SUM(total_marks) as total_marks 
        FROM vw_student_results 
        WHERE $where_clause 
        GROUP BY exam_id, student_id, exam_name, exam_date, overall_percentage, overall_grade 
        ORDER BY exam_date DESC";
$results = getRows($conn, $sql, $params);

// Get exams for filter
$exams_sql = "SELECT DISTINCT e.id, e.exam_name, e.exam_date 
               FROM exams e
               INNER JOIN results r ON e.id = r.exam_id
               WHERE r.student_id = ?
               ORDER BY e.exam_date DESC";
$exams = getRows($conn, $exams_sql, [$_SESSION['student_id']]);
?>

<style>
/* Premium Results Styles */
.results-filter-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: var(--spacing-xl);
}

.result-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: var(--spacing-2xl);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.result-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.result-card-header {
    padding: var(--spacing-xl) var(--spacing-2xl);
    background: linear-gradient(to right, rgba(79, 70, 229, 0.04), transparent);
    border-bottom: 1px solid var(--color-border);
}

.exam-title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--color-text-primary);
    margin-bottom: 4px;
}

.grade-badge-premium {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 800;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    color: white;
}

.grade-a-plus { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.grade-a { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); }
.grade-b-plus { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.grade-b { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); }
.grade-c { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.grade-d { background: linear-gradient(135deg, #fca5a5 0%, #ef4444 100%); }
.grade-f { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); }

.summary-stat-box {
    text-align: center;
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    background: var(--color-surface-variant);
    border: 1px solid var(--color-border);
}

.stat-box-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.stat-box-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.subject-table th {
    background: transparent;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1px;
    color: var(--color-text-secondary);
    padding-bottom: 12px;
}

.premium-progress {
    height: 8px;
    background: var(--color-border);
    border-radius: 4px;
    overflow: hidden;
}

.premium-progress-fill {
    height: 100%;
    background: linear-gradient(to right, var(--color-primary), var(--color-primary-light));
    border-radius: 4px;
}

.result-card-footer {
    padding: var(--spacing-lg) var(--spacing-2xl);
    background: var(--color-surface-variant);
    border-top: 1px solid var(--color-border);
}
</style>

<div class="page-header mb-xl">
    <h1 class="page-title mb-xs" style="font-family: 'Playfair Display', serif; font-size: 2.5rem; letter-spacing: -0.5px;">My Results</h1>
    <p class="page-subtitle text-secondary" style="font-size: 1.1rem;">Detailed overview of your academic performance</p>
</div>

<!-- Filter -->
<?php if (!empty($exams)): ?>
<div class="results-filter-card slide-in-up">
    <form method="GET" class="flex flex-col md:flex-row gap-md items-end">
        <div class="form-group mb-0 w-full" style="flex: 1;">
            <label for="exam" class="form-label-premium">Filter by Examination</label>
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-secondary" style="left: 14px;"></i>
                <select name="exam" id="exam" class="form-control-premium" style="padding-left: 40px; appearance: none; -webkit-appearance: none; background: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23757575%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.4-12.8z%22%2F%3E%3C%2Fsvg%3E') no-repeat right 14px center; background-size: 12px; background-color: var(--color-surface);">
                    <option value="">All Examinations</option>
                    <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo $exam['id']; ?>" <?php echo $exam_filter == $exam['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($exam['exam_name'] . ' - ' . formatDate($exam['exam_date'])); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-md w-full md:w-auto">
            <button type="submit" class="btn btn-primary shadow-md flex-1 md:flex-none">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
            <a href="results.php" class="btn btn-outlined flex-1 md:flex-none">
                <i class="fas fa-redo"></i> Reset
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Results -->
<?php if (empty($results)): ?>
<div class="card slide-in-up">
    <div class="card-body text-center py-2xl">
        <div class="mb-lg" style="opacity: 0.2;">
            <i class="fas fa-file-invoice" style="font-size: 80px;"></i>
        </div>
        <h3 class="mb-sm">No Results Found</h3>
        <p class="text-secondary mx-auto" style="max-width: 400px;">We couldn't find any published exam results for your account. Please check back later or contact the administration.</p>
    </div>
</div>
<?php else: ?>
    <?php foreach ($results as $result): ?>
    <div class="result-card slide-in-up">
        <div class="result-card-header">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="exam-title"><?php echo escape($result['exam_name']); ?></h3>
                    <div class="flex items-center gap-sm text-secondary text-sm">
                        <i class="fas fa-calendar-alt"></i> <?php echo formatDate($result['exam_date']); ?>
                    </div>
                </div>
                <div class="grade-badge-premium grade-<?php echo strtolower(str_replace('+', '', $result['grade'])); ?>">
                    <?php echo $result['grade']; ?>
                </div>
            </div>
        </div>
        
        <div class="card-body" style="padding: var(--spacing-2xl);">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
                <div class="summary-stat-box">
                    <div class="stat-box-label">Marks Obtained</div>
                    <div class="stat-box-value">
                        <span class="text-primary"><?php echo number_format($result['total_marks_obtained'], 0); ?></span><span class="text-sm text-secondary"> / <?php echo $result['total_marks']; ?></span>
                    </div>
                </div>
                <div class="summary-stat-box">
                    <div class="stat-box-label">Success Rate</div>
                    <div class="stat-box-value text-success"><?php echo number_format($result['percentage'], 2); ?>%</div>
                </div>
                <div class="summary-stat-box">
                    <div class="stat-box-label">Academic Grade</div>
                    <div class="stat-box-value"><?php echo $result['grade']; ?></div>
                </div>
            </div>
            
            <!-- Subject-wise Marks -->
            <h4 class="mb-lg flex items-center gap-sm" style="font-family: 'Poppins', sans-serif; font-weight: 600;">
                <i class="fas fa-list-ul text-primary"></i> Subject-wise Performance
            </h4>
            <div class="table-responsive">
                <table class="table subject-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Subject</th>
                            <th>Score</th>
                            <th>Total</th>
                            <th style="width: 200px;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get subject-wise marks
                        $subjects_sql = "SELECT subject_name, total_marks_obtained as marks_obtained, total_marks as subject_total_marks
                                        FROM vw_student_results
                                        WHERE student_id = ? AND exam_id = ?
                                        ORDER BY subject_name";
                        $subjects = getRows($conn, $subjects_sql, [$result['student_id'], $result['exam_id']]);
                        
                        foreach ($subjects as $subject):
                            $subject_percentage = ($subject['subject_total_marks'] > 0) 
                                ? ($subject['marks_obtained'] / $subject['subject_total_marks']) * 100 
                                : 0;
                        ?>
                        <tr>
                            <td class="font-semibold text-primary"><?php echo escape($subject['subject_name']); ?></td>
                            <td class="font-medium"><?php echo $subject['marks_obtained']; ?></td>
                            <td class="text-secondary"><?php echo $subject['subject_total_marks']; ?></td>
                            <td>
                                <div class="flex items-center gap-md">
                                    <div class="premium-progress flex-1">
                                        <div class="premium-progress-fill" style="width: <?php echo $subject_percentage; ?>%; background: <?php echo $subject_percentage >= 40 ? 'var(--color-primary)' : 'var(--color-error)'; ?>;"></div>
                                    </div>
                                    <span class="text-sm font-bold text-secondary" style="width: 45px;"><?php echo number_format($subject_percentage, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="result-card-footer">
            <div class="flex flex-col md:flex-row justify-between items-center gap-md">
                <div class="text-sm text-secondary flex items-center gap-sm bg-surface p-xs px-sm rounded-lg border">
                    <i class="fas fa-info-circle text-primary"></i> 
                    <span>Result generated on <?php echo date('M d, Y'); ?></span>
                </div>
                <a href="../download-result.php?id=<?php echo $result['student_id']; ?>&exam=<?php echo $result['exam_id']; ?>" class="btn btn-primary shadow-md w-full md:w-auto" target="_blank">
                    <i class="fas fa-cloud-download-alt"></i> Download Report Card
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
