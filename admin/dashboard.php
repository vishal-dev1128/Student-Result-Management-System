<?php
$page_title = 'Dashboard';
require_once 'header.php';

// ── Stats ──────────────────────────────────────────────
$stats = [];
try { $stats = getRow($conn, "SELECT * FROM vw_dashboard_stats") ?: []; } catch(Exception $e){}

// ── Recent Students ────────────────────────────────────
$recent_students = [];
try {
    $recent_students = getRows($conn,
        "SELECT s.*, c.name as class_name FROM students s
         LEFT JOIN classes c ON s.class_id = c.id
         WHERE s.status != 'deleted'
         ORDER BY s.created_at DESC LIMIT 5") ?: [];
} catch(Exception $e){}

// ── Recent Activity ────────────────────────────────────
$recent_activity = [];
try {
    $recent_activity = getRows($conn,
        "SELECT al.*,
                CASE al.user_type
                    WHEN 'admin'   THEN au.full_name
                    WHEN 'student' THEN st.full_name
                    ELSE 'System'
                END as actor_name
         FROM activity_logs al
         LEFT JOIN admin_users au ON al.user_type='admin'   AND al.user_id=au.id
         LEFT JOIN students   st  ON al.user_type='student' AND al.user_id=st.id
         ORDER BY al.created_at DESC LIMIT 10") ?: [];
} catch(Exception $e){}

// ── Pending Tickets ────────────────────────────────────
$pending_tickets = 0;
try { $pending_tickets = (int)(getValue($conn, "SELECT COUNT(*) FROM support_tickets WHERE status='open'") ?? 0); } catch(Exception $e){}

// ── Class-wise Student Count ───────────────────────────
$class_stats = [];
try {
    $class_stats = getRows($conn,
        "SELECT c.name as class_name, COUNT(s.id) as student_count
         FROM classes c
         LEFT JOIN students s ON c.id = s.class_id AND s.status = 'active'
         WHERE c.status = 'active'
         GROUP BY c.id, c.name ORDER BY c.name") ?: [];
} catch(Exception $e){}

// ── Pass / Fail Stats ──────────────────────────────────
$pass_count = 0; $fail_count = 0;
try {
    $pf = getRow($conn,
        "SELECT
            SUM(CASE WHEN percentage >= 40 THEN 1 ELSE 0 END) as pass_count,
            SUM(CASE WHEN percentage <  40 THEN 1 ELSE 0 END) as fail_count
         FROM results WHERE percentage IS NOT NULL");
    $pass_count = (int)($pf['pass_count'] ?? 0);
    $fail_count = (int)($pf['fail_count'] ?? 0);
} catch(Exception $e){}

// ── Monthly student growth (last 6 months) ─────────────
$monthly_labels = []; $monthly_counts = [];
try {
    $months = getRows($conn,
        "SELECT DATE_FORMAT(created_at,'%b') as month_label,
                DATE_FORMAT(created_at,'%Y-%m') as month_key,
                COUNT(*) as cnt
         FROM students
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
         GROUP BY month_key, month_label ORDER BY month_key ASC");
    foreach ($months ?: [] as $m) {
        $monthly_labels[] = $m['month_label'];
        $monthly_counts[] = (int)$m['cnt'];
    }
} catch(Exception $e){}

// ── Subject performance ────────────────────────────────
$subj_labels = []; $subj_avgs = [];
try {
    $subjs = getRows($conn,
        "SELECT s.name, ROUND(AVG(r.percentage),1) as avg_pct
         FROM results r JOIN subjects s ON r.subject_id = s.id
         WHERE r.percentage IS NOT NULL
         GROUP BY r.subject_id, s.name LIMIT 8");
    foreach ($subjs ?: [] as $row) {
        $subj_labels[] = $row['name'];
        $subj_avgs[]   = (float)$row['avg_pct'];
    }
} catch(Exception $e){}

// ── Upcoming exams ─────────────────────────────────────
$upcoming_exams = [];
try {
    $upcoming_exams = getRows($conn,
        "SELECT name, exam_date FROM exams
         WHERE exam_date >= CURDATE()
         ORDER BY exam_date ASC LIMIT 10") ?: [];
} catch(Exception $e){}

// ── Metrics ────────────────────────────────────────────
$total_students  = (int)($stats['total_students']  ?? 0);
$total_classes   = (int)($stats['total_classes']   ?? 0);
$total_subjects  = (int)($stats['total_subjects']  ?? 0);
$completed_exams = (int)($stats['completed_exams'] ?? 0);
$total_results   = $pass_count + $fail_count;
$pass_rate       = $total_results > 0 ? round($pass_count / $total_results * 100) : 0;

$extra_css = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
?>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
    <div>
        <h1 class="page-title">Executive Dashboard</h1>
        <p class="page-subtitle"><?php echo date('l, d F Y'); ?> &nbsp;·&nbsp; Welcome back, <strong><?php echo escape($current_admin['full_name']); ?></strong></p>
    </div>
    <button onclick="location.reload()" class="btn btn-outlined btn-sm"><i class="fas fa-sync-alt"></i> Refresh</button>
</div>

<!-- ══ KPI CARDS ══ -->
<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);">

    <div class="kpi-card" style="--kpi-accent:linear-gradient(135deg,#6366f1,#8b5cf6);--kpi-glow:0 12px 30px rgba(99,102,241,.2);">
        <div class="kpi-card-header">
            <div class="kpi-icon" style="background:rgba(99,102,241,.12);color:#6366f1;"><i class="fas fa-user-graduate"></i></div>
            <div class="kpi-trend up"><i class="fas fa-arrow-up"></i> Active</div>
        </div>
        <div class="kpi-value" data-target="<?php echo $total_students; ?>">0</div>
        <div class="kpi-label">Total Students</div>
        <div class="kpi-sparkline"><svg viewBox="0 0 100 40" preserveAspectRatio="none" style="width:100%;height:100%;"><polyline points="0,35 20,28 40,32 60,18 80,22 100,10" fill="none" stroke="rgba(99,102,241,.4)" stroke-width="2"/><polyline points="0,35 20,28 40,32 60,18 80,22 100,10 100,40 0,40" fill="rgba(99,102,241,.08)" stroke="none"/></svg></div>
    </div>

    <div class="kpi-card" style="--kpi-accent:linear-gradient(135deg,#0ea5e9,#06b6d4);--kpi-glow:0 12px 30px rgba(14,165,233,.2);">
        <div class="kpi-card-header">
            <div class="kpi-icon" style="background:rgba(14,165,233,.12);color:#0ea5e9;"><i class="fas fa-school"></i></div>
            <div class="kpi-trend flat"><i class="fas fa-minus"></i> <?php echo $total_subjects; ?> subjects</div>
        </div>
        <div class="kpi-value" data-target="<?php echo $total_classes; ?>">0</div>
        <div class="kpi-label">Active Classes</div>
        <div class="kpi-sparkline"><svg viewBox="0 0 100 40" preserveAspectRatio="none" style="width:100%;height:100%;"><polyline points="0,30 25,25 50,28 75,20 100,18" fill="none" stroke="rgba(14,165,233,.4)" stroke-width="2"/><polyline points="0,30 25,25 50,28 75,20 100,18 100,40 0,40" fill="rgba(14,165,233,.08)" stroke="none"/></svg></div>
    </div>

    <div class="kpi-card" style="--kpi-accent:linear-gradient(135deg,#f59e0b,#ef4444);--kpi-glow:0 12px 30px rgba(245,158,11,.2);">
        <div class="kpi-card-header">
            <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:#f59e0b;"><i class="fas fa-file-alt"></i></div>
            <div class="kpi-trend up"><i class="fas fa-check"></i> Done</div>
        </div>
        <div class="kpi-value" data-target="<?php echo $completed_exams; ?>">0</div>
        <div class="kpi-label">Exams Completed</div>
        <div class="kpi-sparkline"><svg viewBox="0 0 100 40" preserveAspectRatio="none" style="width:100%;height:100%;"><polyline points="0,38 20,34 40,28 60,22 80,16 100,8" fill="none" stroke="rgba(245,158,11,.4)" stroke-width="2"/><polyline points="0,38 20,34 40,28 60,22 80,16 100,8 100,40 0,40" fill="rgba(245,158,11,.08)" stroke="none"/></svg></div>
    </div>

    <div class="kpi-card" style="--kpi-accent:linear-gradient(135deg,#10b981,#059669);--kpi-glow:0 12px 30px rgba(16,185,129,.2);">
        <div class="kpi-card-header">
            <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:#10b981;"><i class="fas fa-chart-pie"></i></div>
            <div class="kpi-trend <?php echo $pass_rate>=70?'up':($pass_rate>=50?'flat':'down'); ?>"><i class="fas fa-<?php echo $pass_rate>=70?'arrow-up':($pass_rate>=50?'minus':'arrow-down'); ?>"></i> <?php echo $pass_rate>=70?'Excellent':($pass_rate>=50?'Average':'Needs work'); ?></div>
        </div>
        <div class="kpi-value" data-target="<?php echo $pass_rate; ?>" data-suffix="%">0%</div>
        <div class="kpi-label">Overall Pass Rate</div>
        <div style="height:6px;background:var(--color-border);border-radius:10px;margin-top:14px;"><div style="height:100%;width:<?php echo $pass_rate; ?>%;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;transition:width 1.2s ease;"></div></div>
    </div>

    <div class="kpi-card" style="--kpi-accent:linear-gradient(135deg,#f43f5e,#e11d48);--kpi-glow:0 12px 30px rgba(244,63,94,.2);">
        <div class="kpi-card-header">
            <div class="kpi-icon" style="background:rgba(244,63,94,.12);color:#f43f5e;"><i class="fas fa-ticket-alt"></i></div>
            <?php if($pending_tickets>0): ?><span style="font-size:10px;font-weight:700;background:rgba(244,63,94,.12);color:#f43f5e;padding:3px 8px;border-radius:20px;">URGENT</span><?php endif; ?>
        </div>
        <div class="kpi-value" data-target="<?php echo $pending_tickets; ?>">0</div>
        <div class="kpi-label">Pending Tickets</div>
        <?php if($pending_tickets>0): ?>
        <a href="tickets.php" style="display:inline-flex;align-items:center;gap:5px;margin-top:12px;font-size:12px;color:#f43f5e;text-decoration:none;font-weight:600;"><i class="fas fa-arrow-right"></i> Handle now</a>
        <?php else: ?><div style="margin-top:12px;font-size:12px;color:var(--color-success);">&#10003; All resolved</div><?php endif; ?>
    </div>

    <div class="kpi-card" style="--kpi-accent:linear-gradient(135deg,#8b5cf6,#7c3aed);--kpi-glow:0 12px 30px rgba(139,92,246,.2);">
        <div class="kpi-card-header">
            <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><i class="fas fa-poll"></i></div>
            <div class="kpi-trend up"><i class="fas fa-database"></i> Records</div>
        </div>
        <div class="kpi-value" data-target="<?php echo $total_results; ?>">0</div>
        <div class="kpi-label">Results in Database</div>
        <div style="font-size:12px;color:var(--color-text-secondary);margin-top:12px;">
            <span style="color:#10b981;font-weight:600;"><?php echo $pass_count; ?> pass</span> &middot;
            <span style="color:#f43f5e;font-weight:600;"><?php echo $fail_count; ?> fail</span>
        </div>
    </div>

</div>

<!-- ══ CHARTS 2x2 ══ -->
<div class="chart-grid" style="margin-bottom:24px;">

    <div class="chart-card">
        <div class="chart-card-header"><div class="chart-card-title"><i class="fas fa-users"></i> Students by Class</div><span class="chart-card-badge"><?php echo count($class_stats); ?> classes</span></div>
        <div class="chart-card-body">
            <?php if(empty($class_stats)): ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-secondary);"><i class="fas fa-chart-bar" style="font-size:40px;opacity:.2;"></i><p style="margin-top:12px;">No class data</p></div>
            <?php else: ?><div style="height:220px;"><canvas id="classChart"></canvas></div><?php endif; ?>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-card-header"><div class="chart-card-title"><i class="fas fa-circle-notch"></i> Pass vs Fail</div><span class="chart-card-badge"><?php echo $total_results; ?> results</span></div>
        <div class="chart-card-body" style="display:flex;align-items:center;gap:24px;">
            <?php if($total_results===0): ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-secondary);flex:1;"><i class="fas fa-chart-pie" style="font-size:40px;opacity:.2;"></i><p style="margin-top:12px;">No results</p></div>
            <?php else: ?>
            <div style="width:180px;height:180px;flex-shrink:0;"><canvas id="donutChart"></canvas></div>
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;"><div style="width:12px;height:12px;border-radius:50%;background:#10b981;"></div><span style="font-size:13px;font-weight:600;">Passed</span><span style="margin-left:auto;font-size:18px;font-weight:800;color:#10b981;"><?php echo $pass_count; ?></span></div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;"><div style="width:12px;height:12px;border-radius:50%;background:#f43f5e;"></div><span style="font-size:13px;font-weight:600;">Failed</span><span style="margin-left:auto;font-size:18px;font-weight:800;color:#f43f5e;"><?php echo $fail_count; ?></span></div>
                <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--color-border);"><div style="font-size:28px;font-weight:800;"><?php echo $pass_rate; ?>%</div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--color-text-secondary);">Pass Rate</div></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-card-header"><div class="chart-card-title"><i class="fas fa-chart-line"></i> Student Growth</div><span class="chart-card-badge">Last 6 months</span></div>
        <div class="chart-card-body">
            <?php if(empty($monthly_labels)): ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-secondary);"><i class="fas fa-chart-line" style="font-size:40px;opacity:.2;"></i><p style="margin-top:12px;">No growth data</p></div>
            <?php else: ?><div style="height:220px;"><canvas id="growthChart"></canvas></div><?php endif; ?>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-card-header"><div class="chart-card-title"><i class="fas fa-star"></i> Subject Performance</div><span class="chart-card-badge">Average %</span></div>
        <div class="chart-card-body">
            <?php if(empty($subj_avgs)): ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-secondary);"><i class="fas fa-spider" style="font-size:40px;opacity:.2;"></i><p style="margin-top:12px;">No subject scores</p></div>
            <?php else: ?><div style="height:220px;"><canvas id="radarChart"></canvas></div><?php endif; ?>
        </div>
    </div>

</div>

<!-- ══ BOTTOM ROW ══ -->
<div class="dashboard-bottom">

    <!-- Activity Feed -->
    <div class="activity-feed-card">
        <div class="activity-feed-header"><span><i class="fas fa-history" style="color:var(--color-primary);margin-right:6px;"></i> Recent Activity</span><a href="activity-logs.php" style="font-size:12px;color:var(--color-primary);text-decoration:none;">View all &rarr;</a></div>
        <?php if(empty($recent_activity)): ?>
        <div style="padding:40px;text-align:center;color:var(--color-text-secondary);"><i class="fas fa-clock" style="font-size:36px;opacity:.2;display:block;margin-bottom:10px;"></i>No recent activity</div>
        <?php else:
        $actIcons=['admin_login'=>['sign-in-alt','rgba(99,102,241,.12)','#6366f1'],'admin_logout'=>['sign-out-alt','rgba(148,163,184,.12)','#94a3b8'],'student_login'=>['user','rgba(14,165,233,.12)','#0ea5e9'],'student_created'=>['user-plus','rgba(16,185,129,.12)','#10b981'],'student_updated'=>['user-edit','rgba(245,158,11,.12)','#f59e0b'],'student_deleted'=>['user-minus','rgba(244,63,94,.12)','#f43f5e'],'result_created'=>['plus-circle','rgba(16,185,129,.12)','#10b981'],'result_updated'=>['edit','rgba(245,158,11,.12)','#f59e0b'],'result_deleted'=>['trash','rgba(244,63,94,.12)','#f43f5e'],'notice_created'=>['bell','rgba(245,158,11,.12)','#f59e0b'],'faq_created'=>['question-circle','rgba(139,92,246,.12)','#8b5cf6'],'failed_login'=>['ban','rgba(244,63,94,.12)','#f43f5e']];
        foreach($recent_activity as $i=>$act):
            $info=$actIcons[$act['action']]??['circle','rgba(148,163,184,.12)','#94a3b8']; ?>
        <div class="activity-entry" style="animation-delay:<?php echo $i*50; ?>ms;">
            <div class="activity-icon" style="background:<?php echo $info[1]; ?>;color:<?php echo $info[2]; ?>;"><i class="fas fa-<?php echo $info[0]; ?>"></i></div>
            <div class="activity-body">
                <div class="activity-desc"><?php echo escape($act['description']); ?></div>
                <div class="activity-meta"><?php if(!empty($act['actor_name'])): ?><strong><?php echo escape($act['actor_name']); ?></strong> &middot; <?php endif; ?><?php echo timeAgo($act['created_at']); ?></div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Right Column -->
    <div class="dashboard-right-col">

        <!-- Calendar Widget -->
        <div class="calendar-widget">
            <div class="calendar-header"><span id="calMonthLabel">Loading&hellip;</span><div style="display:flex;gap:4px;"><button class="calendar-nav-btn" id="calPrev"><i class="fas fa-chevron-left"></i></button><button class="calendar-nav-btn" id="calNext"><i class="fas fa-chevron-right"></i></button></div></div>
            <div class="calendar-grid">
                <div class="cal-weekdays"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                <div class="cal-days" id="calDays"></div>
            </div>
            <?php if(!empty($upcoming_exams)): ?>
            <div style="padding:0 16px 14px;border-top:1px solid var(--color-border);margin-top:8px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--color-text-secondary);margin-bottom:8px;padding-top:10px;">Upcoming Exams</div>
                <?php foreach(array_slice($upcoming_exams,0,3) as $ex): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:12.5px;"><span style="color:var(--color-text-primary);font-weight:500;"><?php echo escape($ex['name']); ?></span><span style="color:#f59e0b;font-size:11px;font-weight:600;"><?php echo date('d M',strtotime($ex['exam_date'])); ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Alerts Widget -->
        <div class="alerts-widget">
            <div class="alerts-widget-header"><i class="fas fa-bell" style="color:var(--color-primary);margin-right:6px;"></i> System Alerts</div>
            <?php if($pending_tickets>0): ?>
            <div class="alert-entry"><div class="alert-pill" style="background:#f43f5e;"></div><div class="alert-text"><?php echo $pending_tickets; ?> open ticket<?php echo $pending_tickets>1?'s':'';?> need attention</div><a href="tickets.php" class="alert-link">Handle &rarr;</a></div>
            <?php endif;
            $unpublished=0; try{$unpublished=(int)(getValue($conn,"SELECT COUNT(*) FROM notices WHERE is_published=0")??0);}catch(Exception $e){}
            if($unpublished>0): ?>
            <div class="alert-entry"><div class="alert-pill" style="background:#f59e0b;"></div><div class="alert-text"><?php echo $unpublished; ?> notice<?php echo $unpublished>1?'s':'';?> in draft</div><a href="notices.php" class="alert-link">Publish &rarr;</a></div>
            <?php endif;
            $nxt=!empty($upcoming_exams)?$upcoming_exams[0]:null;
            if($nxt): $days=(int)((strtotime($nxt['exam_date'])-time())/86400); ?>
            <div class="alert-entry"><div class="alert-pill" style="background:#6366f1;"></div><div class="alert-text">Next exam in <strong><?php echo $days; ?></strong> day<?php echo $days!==1?'s':'';?></div><a href="exams.php" class="alert-link">View &rarr;</a></div>
            <?php endif;
            if($pending_tickets===0 && $unpublished===0 && !$nxt): ?>
            <div class="alert-entry"><div class="alert-pill" style="background:#10b981;"></div><div class="alert-text" style="color:var(--color-text-secondary);">All systems normal</div></div>
            <?php endif; ?>
        </div>

        <!-- Recent Students Widget -->
        <div class="alerts-widget">
            <div class="alerts-widget-header"><i class="fas fa-user-plus" style="color:var(--color-primary);margin-right:6px;"></i> New Students</div>
            <?php if(empty($recent_students)): ?>
            <div style="padding:20px;text-align:center;color:var(--color-text-secondary);font-size:13px;">No students yet</div>
            <?php else: foreach($recent_students as $s): ?>
            <div class="alert-entry" style="align-items:center;">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;"><?php echo strtoupper(substr($s['full_name'],0,1)); ?></div>
                <div class="alert-text" style="flex:1;"><div style="font-weight:600;font-size:13px;"><?php echo escape($s['full_name']); ?></div><div style="font-size:11px;color:var(--color-text-secondary);"><?php echo escape($s['class_name']??'No class'); ?></div></div>
                <div style="font-size:11px;color:var(--color-text-secondary);"><?php echo timeAgo($s['created_at']); ?></div>
            </div>
            <?php endforeach; endif; ?>
            <div style="padding:10px 18px;border-top:1px solid var(--color-border);text-align:center;"><a href="students.php" style="font-size:12px;color:var(--color-primary);text-decoration:none;font-weight:600;">View all students &rarr;</a></div>
        </div>

    </div>
</div>

<?php
// ── Prepare JS data ────────────────────────────────────
$classLabels = json_encode(array_column($class_stats, 'class_name'));
$classCounts = json_encode(array_map('intval', array_column($class_stats, 'student_count')));
$monthLabels = json_encode($monthly_labels);
$monthCounts = json_encode($monthly_counts);
$subjLabels  = json_encode($subj_labels);
$subjAvgs    = json_encode($subj_avgs);
$examDatesJS = json_encode(array_map(function($e){ return substr($e['exam_date'],0,10); }, $upcoming_exams));

ob_start();
?>
<script>
/* ══ DASHBOARD CHARTS — Chart.js 4 ══ */
const isDark     = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor  = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
const textColor  = () => isDark() ? '#94a3b8' : '#64748b';
const surfColor  = () => isDark() ? '#111118' : '#ffffff';
const chartDef   = { maintainAspectRatio:false, responsive:true, animation:{duration:900,easing:'easeOutQuart'}, plugins:{legend:{display:false}} };

// 1. Students by Class
const cCtx = document.getElementById('classChart');
if(cCtx) new Chart(cCtx,{ type:'bar', data:{ labels:<?php echo $classLabels; ?>, datasets:[{ data:<?php echo $classCounts; ?>, backgroundColor:'rgba(99,102,241,0.75)', borderRadius:8, borderSkipped:false }] }, options:{ ...chartDef, scales:{ x:{grid:{color:gridColor()},ticks:{color:textColor()}}, y:{beginAtZero:true,grid:{color:gridColor()},ticks:{color:textColor(),stepSize:1}} } } });

// 2. Pass vs Fail
const dCtx = document.getElementById('donutChart');
if(dCtx) new Chart(dCtx,{ type:'doughnut', data:{ labels:['Passed','Failed'], datasets:[{ data:[<?php echo $pass_count; ?>,<?php echo $fail_count; ?>], backgroundColor:['#10b981','#f43f5e'], borderColor:surfColor(), borderWidth:4, hoverOffset:6 }] }, options:{ ...chartDef, cutout:'70%' } });

// 3. Growth
const gCtx = document.getElementById('growthChart');
if(gCtx) new Chart(gCtx,{ type:'line', data:{ labels:<?php echo $monthLabels; ?>, datasets:[{ data:<?php echo $monthCounts; ?>, borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,0.1)', borderWidth:2.5, tension:0.4, fill:true, pointBackgroundColor:'#6366f1', pointRadius:4 }] }, options:{ ...chartDef, scales:{ x:{grid:{color:gridColor()},ticks:{color:textColor()}}, y:{beginAtZero:true,grid:{color:gridColor()},ticks:{color:textColor(),stepSize:1}} } } });

// 4. Radar
const rCtx = document.getElementById('radarChart');
if(rCtx) new Chart(rCtx,{ type:'radar', data:{ labels:<?php echo $subjLabels; ?>, datasets:[{ data:<?php echo $subjAvgs; ?>, borderColor:'#8b5cf6', backgroundColor:'rgba(139,92,246,0.15)', borderWidth:2, pointBackgroundColor:'#8b5cf6', pointRadius:4 }] }, options:{ ...chartDef, scales:{ r:{ beginAtZero:true, max:100, grid:{color:gridColor()}, pointLabels:{color:textColor(),font:{size:11}}, ticks:{display:false} } } } });

// ── Smart Calendar ──
const examDates = <?php echo $examDatesJS; ?>;
let calDate = new Date();
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function renderCalendar() {
    const yr = calDate.getFullYear(), mo = calDate.getMonth(), today = new Date();
    document.getElementById('calMonthLabel').textContent = monthNames[mo] + ' ' + yr;
    const firstDay = new Date(yr,mo,1).getDay(), dim = new Date(yr,mo+1,0).getDate(), prev = new Date(yr,mo,0).getDate();
    let html = '';
    for(let i=firstDay-1;i>=0;i--) html+=`<div class="cal-day other-month">${prev-i}</div>`;
    for(let d=1;d<=dim;d++){
        const ds=`${yr}-${String(mo+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isToday=d===today.getDate()&&mo===today.getMonth()&&yr===today.getFullYear();
        const hasExam=examDates.includes(ds);
        html+=`<div class="cal-day ${isToday?'today':''} ${hasExam?'has-exam':''}" title="${hasExam?'Exam scheduled':''}">${d}</div>`;
    }
    const rem=(firstDay+dim)%7; if(rem) for(let i=1;i<=7-rem;i++) html+=`<div class="cal-day other-month">${i}</div>`;
    document.getElementById('calDays').innerHTML=html;
}

document.getElementById('calPrev')?.addEventListener('click',()=>{calDate.setMonth(calDate.getMonth()-1);renderCalendar();});
document.getElementById('calNext')?.addEventListener('click',()=>{calDate.setMonth(calDate.getMonth()+1);renderCalendar();});
renderCalendar();
</script>
<?php
$extra_js = ob_get_clean();
require_once 'footer.php';
?>