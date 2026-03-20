<?php
require_once 'includes/config.php';

$student_id = sanitizeInteger($_GET['id'] ?? 0);
$exam_id    = sanitizeInteger($_GET['exam'] ?? 0);

if (!$student_id || !$exam_id) {
    die('Invalid request.');
}

// Get student & exam info (first row covers header data)
$info_sql = "SELECT DISTINCT student_id, exam_id, roll_number, student_name,
                    class_name, exam_name, exam_type, exam_date,
                    overall_percentage, overall_grade, result_status
             FROM vw_student_results
             WHERE student_id = ? AND exam_id = ?
             LIMIT 1";
$info = getRow($conn, $info_sql, [$student_id, $exam_id]);

if (!$info) {
    die('Result not found or not yet published.');
}

// Get per-subject rows
$subjects_sql = "SELECT subject_name, total_marks_obtained, total_marks, percentage, grade
                 FROM vw_student_results
                 WHERE student_id = ? AND exam_id = ?
                 ORDER BY subject_name ASC";
$subjects = getRows($conn, $subjects_sql, [$student_id, $exam_id]);

// Aggregate totals
$grand_obtained = array_sum(array_column($subjects, 'total_marks_obtained'));
$grand_total    = array_sum(array_column($subjects, 'total_marks'));
$overall_pct    = $grand_total > 0 ? round(($grand_obtained / $grand_total) * 100, 2) : 0;
$overall_grade  = calculateGrade($overall_pct, $conn);

$institute_name = getSetting($conn, 'institute_name', APP_NAME);
$institute_email= getSetting($conn, 'institute_email', '');
$institute_phone= getSetting($conn, 'institute_phone', '');

// Grade CSS
function gradeColor($g) {
    $map = [
        'A+' => ['bg' => '#1b5e20', 'light' => '#e8f5e9'],
        'A'  => ['bg' => '#2e7d32', 'light' => '#e8f5e9'],
        'B+' => ['bg' => '#0d47a1', 'light' => '#e3f2fd'],
        'B'  => ['bg' => '#1565c0', 'light' => '#e3f2fd'],
        'C'  => ['bg' => '#e65100', 'light' => '#fff3e0'],
        'D'  => ['bg' => '#bf360c', 'light' => '#fbe9e7'],
        'F'  => ['bg' => '#b71c1c', 'light' => '#ffebee'],
    ];
    return $map[$g] ?? ['bg' => '#546e7a', 'light' => '#eceff1'];
}
$overallColor = gradeColor($overall_grade);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - <?php echo escape($info['student_name']); ?> | <?php echo escape($info['exam_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            color: #1a1a2e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 16px 40px;
        }

        /* ── Top Controls (hidden on print) ── */
        .controls {
            width: 100%;
            max-width: 780px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 2px solid #1565C0;
            color: #1565C0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-back:hover { background: #1565C0; color: white; }

        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: linear-gradient(135deg, #1565C0, #0D47A1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(21,101,192,0.35);
            transition: all 0.2s;
        }
        .btn-pdf:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(21,101,192,0.45); }

        /* ── Result Card ── */
        .result-card {
            width: 100%;
            max-width: 780px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        /* Header */
        .card-header {
            background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
            color: white;
            padding: 32px 36px 64px;
            text-align: center;
            position: relative;
        }
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 40px;
            background: white;
            clip-path: ellipse(55% 100% at 50% 100%);
        }
        .header-badge {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
        }
        .card-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            color: white;
        }
        .card-header .subtitle {
            font-size: 13px;
            opacity: 0.85;
            color: white;
        }
        .exam-tag {
            display: inline-block;
            margin-top: 10px;
            background: rgba(255,255,255,0.2);
            padding: 4px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        /* Student Info */
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            margin-top: 32px;
            border-top: 1px solid #e8edf2;
            border-bottom: 1px solid #e8edf2;
        }
        .info-cell {
            padding: 14px 24px;
            border-right: 1px solid #e8edf2;
        }
        .info-cell:nth-child(even) { border-right: none; }
        .info-cell:nth-child(n+3) { border-top: 1px solid #e8edf2; }
        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #90a4ae;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a237e;
        }

        /* Section Title */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1565C0;
            padding: 20px 24px 10px;
        }

        /* Marks Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            background: #f0f4f8;
        }
        th {
            padding: 11px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #607d8b;
            text-align: left;
        }
        th:not(:first-child) { text-align: center; }

        td {
            padding: 13px 16px;
            font-size: 13px;
            color: #37474f;
            border-bottom: 1px solid #f0f4f8;
        }
        td:not(:first-child) { text-align: center; }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafbff; }

        .subject-name { font-weight: 600; color: #1a237e; }

        /* Grade Pill */
        .grade-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #fff;
        }

        /* Progress bar */
        .pct-bar-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pct-bar {
            flex: 1;
            height: 6px;
            background: #e0e7ef;
            border-radius: 4px;
            overflow: hidden;
        }
        .pct-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #1565C0, #42a5f5);
        }
        .pct-text { font-size: 12px; font-weight: 600; min-width: 42px; color: #37474f; }

        /* Summary Row */
        .summary-strip {
            background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            text-align: center;
            padding: 20px 0 18px;
            margin-top: 6px;
        }
        .summary-item { padding: 0 12px; }
        .summary-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.75); margin-bottom: 5px; }
        .summary-value { font-size: 20px; font-weight: 700; color: #fff; }

        /* Overall Grade Box */
        .overall-grade-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px; height: 44px;
            border-radius: 50%;
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }

        /* Status Badge */
        .status-pass { color: #2e7d32; background: #e8f5e9; padding: 2px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-fail { color: #b71c1c; background: #ffebee; padding: 2px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }

        /* Footer */
        .card-footer {
            padding: 18px 24px;
            border-top: 1px solid #e8edf2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafbff;
        }
        .footer-text { font-size: 11px; color: #90a4ae; }
        .generated-on { font-size: 10px; color: #b0bec5; }

        /* ── Print Styles ── */
        @media print {
            body { background: white !important; padding: 0; }
            .controls { display: none !important; }
            .result-card { box-shadow: none; border-radius: 0; max-width: 100%; }
            .card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .summary-strip { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .grade-pill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .pct-fill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- Controls (hidden on print) -->
<div class="controls">
    <a href="javascript:history.back()" class="btn-back">
        &#8592; Back to Results
    </a>
    <button class="btn-pdf" onclick="window.print()">
        &#8659; Save as PDF / Print
    </button>
</div>

<!-- Result Card -->
<div class="result-card">

    <!-- Header -->
    <div class="card-header">
        <div class="header-badge">🎓</div>
        <h1><?php echo escape($institute_name); ?></h1>
        <div class="subtitle">Official Examination Result</div>
        <div class="exam-tag"><?php echo escape($info['exam_name']); ?></div>
    </div>

    <!-- Student Info -->
    <div class="student-info">
        <div class="info-cell">
            <div class="info-label">Student Name</div>
            <div class="info-value"><?php echo escape($info['student_name']); ?></div>
        </div>
        <div class="info-cell">
            <div class="info-label">Roll Number</div>
            <div class="info-value"><?php echo escape($info['roll_number']); ?></div>
        </div>
        <div class="info-cell">
            <div class="info-label">Class</div>
            <div class="info-value"><?php echo escape($info['class_name']); ?></div>
        </div>
        <div class="info-cell">
            <div class="info-label">Exam Date</div>
            <div class="info-value"><?php echo formatDate($info['exam_date']); ?></div>
        </div>
    </div>

    <!-- Marks Table -->
    <div class="section-title">&#128203; Subject-wise Performance</div>
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Marks Obtained</th>
                <th>Total Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $s):
                $pct = (float)$s['percentage'];
                $gc  = gradeColor($s['grade']);
                $barW = min(100, $pct);
            ?>
            <tr>
                <td class="subject-name"><?php echo escape($s['subject_name']); ?></td>
                <td><strong><?php echo formatNumber($s['total_marks_obtained'], 0); ?></strong></td>
                <td><?php echo formatNumber($s['total_marks'], 0); ?></td>
                <td>
                    <div class="pct-bar-wrap">
                        <div class="pct-bar">
                            <div class="pct-fill" style="width:<?php echo $barW; ?>%"></div>
                        </div>
                        <span class="pct-text"><?php echo number_format($pct, 1); ?>%</span>
                    </div>
                </td>
                <td>
                    <span class="grade-pill" style="background:<?php echo $gc['bg']; ?>">
                        <?php echo escape($s['grade']); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary Strip -->
    <div class="summary-strip">
        <div class="summary-item">
            <div class="summary-label">Total Marks</div>
            <div class="summary-value">
                <?php echo formatNumber($grand_obtained, 0); ?> / <?php echo formatNumber($grand_total, 0); ?>
            </div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Overall Percentage</div>
            <div class="summary-value"><?php echo number_format($overall_pct, 2); ?>%</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Overall Grade</div>
            <div class="summary-value" style="display:flex;align-items:center;justify-content:center;gap:10px;">
                <?php $ogc = gradeColor($overall_grade); ?>
                <span class="overall-grade-badge" style="background:<?php echo $ogc['bg']; ?>">
                    <?php echo escape($overall_grade); ?>
                </span>
                <span class="<?php echo strtolower($info['result_status']) === 'pass' ? 'status-pass' : 'status-fail'; ?>" style="font-size:12px;">
                    <?php echo strtoupper($info['result_status']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer">
        <div class="footer-text">
            <?php if ($institute_email): ?>&#9993; <?php echo escape($institute_email); ?>&nbsp;&nbsp;<?php endif; ?>
            <?php if ($institute_phone): ?>&#9990; <?php echo escape($institute_phone); ?><?php endif; ?>
        </div>
        <div class="generated-on">
            Generated on <?php echo date('d M Y, h:i A'); ?> &nbsp;|&nbsp; <?php echo escape(APP_NAME); ?> v<?php echo APP_VERSION; ?>
        </div>
    </div>

</div>

<script>
    // Auto-open print dialog after page loads (for direct PDF download flow)
    window.addEventListener('load', function() {
        // Small delay so styles render before print dialog
        setTimeout(function() {
            window.print();
        }, 600);
    });
</script>
</body>
</html>
