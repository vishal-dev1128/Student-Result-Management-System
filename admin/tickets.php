<?php
$page_title = 'Support Tickets';
require_once '../includes/auth.php';
requireAdminAuth();

if (!hasAnyRole(['super_admin', 'admin'])) redirect('dashboard.php?error=unauthorized');

// Update status
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = sanitizeInteger($_GET['id']);
    $s  = sanitizeInput($_GET['status']);
    if (in_array($s, ['open','in_progress','resolved','closed'])) {
        executeQuery($conn, "UPDATE support_tickets SET status=?, updated_at=NOW() WHERE id=?", [$s, $id]);
        $_SESSION['flash_message'] = "Status updated to $s";
        $_SESSION['flash_type'] = 'success';
    }
    redirect('tickets.php');
}

// Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) redirect('tickets.php');
    $tid = sanitizeInteger($_POST['ticket_id']);
    $msg = sanitizeInput($_POST['reply'] ?? '');
    if (!empty($msg)) {
        try {
            executeQuery($conn, "INSERT INTO ticket_replies (ticket_id, admin_id, message, created_at) VALUES (?,?,?,NOW())", [$tid, $_SESSION['admin_id'], $msg]);
            executeQuery($conn, "UPDATE support_tickets SET status='in_progress', updated_at=NOW() WHERE id=?", [$tid]);
        } catch (Exception $e) {}
        $_SESSION['flash_message'] = 'Reply sent';
        $_SESSION['flash_type'] = 'success';
    }
    redirect("tickets.php?view=$tid");
}

// Filters
$sf = sanitizeInput($_GET['status_filter'] ?? '');
$pf = sanitizeInput($_GET['priority'] ?? '');
$q  = sanitizeInput($_GET['search'] ?? '');
$where = ['1=1']; $params = [];
if ($sf) { $where[] = 't.status=?'; $params[] = $sf; }
if ($pf) { $where[] = 't.priority=?'; $params[] = $pf; }
if ($q)  { $where[] = '(t.subject LIKE ? OR st.full_name LIKE ?)'; $p="%$q%"; $params[]=$p; $params[]=$p; }
$wc = implode(' AND ', $where);

$tickets = [];
try {
    $tickets = getRows($conn,
        "SELECT t.*, st.full_name as student_name, st.roll_number FROM support_tickets t
         LEFT JOIN students st ON t.student_id=st.id WHERE $wc
         ORDER BY FIELD(t.status,'open','in_progress','resolved','closed'), t.created_at DESC", $params) ?: [];
} catch (Exception $e) {}

// View single
$viewing = null; $replies = [];
if (isset($_GET['view'])) {
    $vid = sanitizeInteger($_GET['view']);
    try {
        $viewing = getRow($conn, "SELECT t.*, st.full_name as student_name, st.email FROM support_tickets t LEFT JOIN students st ON t.student_id=st.id WHERE t.id=?", [$vid]);
        $replies = getRows($conn, "SELECT tr.*, au.full_name as admin_name FROM ticket_replies tr LEFT JOIN admin_users au ON tr.admin_id=au.id WHERE tr.ticket_id=? ORDER BY tr.created_at ASC", [$vid]) ?: [];
    } catch (Exception $e) {}
}

require_once 'header.php';
$csrf = generateCSRFToken();

function tbadge($s) { $c=['open'=>'error','in_progress'=>'warning','resolved'=>'success','closed'=>'secondary']; return $c[$s]??'secondary'; }
?>

<style>
.ticket-row { cursor:pointer; transition:background .12s; }
.ticket-row:hover td { background:var(--color-surface-variant); }
.pdot { display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px; }
.bubble { padding:12px 16px;border-radius:12px;font-size:13px;line-height:1.55;margin-bottom:10px; }
.ba { background:var(--color-primary);color:#fff;margin-left:30px; }
.bs { background:var(--color-surface-variant);margin-right:30px; }
</style>

<div class="page-header">
    <h1 class="page-title">Support Tickets</h1>
    <p class="page-subtitle"><?php echo count($tickets); ?> ticket(s)</p>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type']??'info'; ?> mb-lg"><?php echo escape($_SESSION['flash_message']); ?></div>
<?php unset($_SESSION['flash_message'],$_SESSION['flash_type']); endif; ?>

<?php if ($viewing): ?>
<div class="card mb-lg">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="card-title"><?php echo escape($viewing['subject']); ?></h3>
                <div class="text-sm text-secondary">From: <strong><?php echo escape($viewing['student_name']??'Unknown'); ?></strong> &bull; <?php echo timeAgo($viewing['created_at']); ?></div>
            </div>
            <div class="flex gap-sm">
                <a href="tickets.php?id=<?php echo $viewing['id']; ?>&status=resolved" class="btn btn-success btn-sm">Mark Resolved</a>
                <a href="tickets.php?id=<?php echo $viewing['id']; ?>&status=closed" class="btn btn-outlined btn-sm">Close</a>
                <a href="tickets.php" class="btn btn-outlined btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="bubble bs"><div style="font-size:11px;opacity:.7;margin-bottom:4px;"><?php echo escape($viewing['student_name']??'Student'); ?> &bull; <?php echo date('d M Y H:i',strtotime($viewing['created_at'])); ?></div><?php echo nl2br(escape($viewing['message'])); ?></div>
        <?php foreach ($replies as $r): ?>
        <div class="bubble <?php echo $r['admin_id'] ? 'ba' : 'bs'; ?>">
            <div style="font-size:11px;opacity:.8;margin-bottom:4px;"><?php echo $r['admin_id'] ? escape($r['admin_name']??'Admin') : 'Student'; ?> &bull; <?php echo date('d M Y H:i',strtotime($r['created_at'])); ?></div>
            <?php echo nl2br(escape($r['message'])); ?>
        </div>
        <?php endforeach; ?>
        <?php if ($viewing['status'] !== 'closed'): ?>
        <form method="POST" class="mt-lg">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="ticket_id" value="<?php echo $viewing['id']; ?>">
            <div class="form-group"><label class="form-label">Your Reply</label><textarea name="reply" class="form-control" rows="3" required></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-reply"></i> Send Reply</button>
        </form>
        <?php else: ?>
        <div class="alert alert-info mt-lg"><i class="fas fa-lock"></i> This ticket is closed.</div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card mb-lg">
    <div class="card-body">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div class="form-group" style="margin:0;"><input type="text" name="search" class="form-control" placeholder="Search…" value="<?php echo escape($q); ?>"></div>
            <div class="form-group" style="margin:0;">
                <select name="status_filter" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach(['open','in_progress','resolved','closed'] as $so): ?>
                    <option value="<?php echo $so; ?>" <?php echo $sf===$so?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$so)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <select name="priority" class="form-control">
                    <option value="">All Priorities</option>
                    <?php foreach(['urgent','high','normal','low'] as $po): ?>
                    <option value="<?php echo $po; ?>" <?php echo $pf===$po?'selected':''; ?>><?php echo ucfirst($po); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            <a href="tickets.php" class="btn btn-outlined"><i class="fas fa-redo"></i> Reset</a>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Subject</th><th>Student</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if(empty($tickets)): ?><tr><td colspan="7" class="text-center text-secondary py-xl">No tickets found</td></tr>
                <?php else: foreach($tickets as $t): ?>
                <tr class="ticket-row" onclick="location='tickets.php?view=<?php echo $t['id']; ?>'">
                    <td class="text-secondary text-sm">#<?php echo $t['id']; ?></td>
                    <td><div class="font-medium"><?php echo escape($t['subject']); ?></div></td>
                    <td><div><?php echo escape($t['student_name']??'—'); ?></div><div class="text-sm text-secondary"><?php echo escape($t['roll_number']??''); ?></div></td>
                    <td><span class="pdot" style="background:<?php $pc=['urgent'=>'#dc2626','high'=>'#d97706','normal'=>'#2563eb','low'=>'#9ca3af']; echo $pc[$t['priority']]??'#9ca3af'; ?>;"></span><?php echo ucfirst($t['priority']??'normal'); ?></td>
                    <td><span class="badge badge-<?php echo tbadge($t['status']); ?>"><?php echo ucfirst(str_replace('_',' ',$t['status'])); ?></span></td>
                    <td class="text-sm text-secondary"><?php echo timeAgo($t['created_at']); ?></td>
                    <td onclick="event.stopPropagation()">
                        <div class="flex gap-sm">
                            <a href="tickets.php?view=<?php echo $t['id']; ?>" class="btn btn-sm btn-outlined"><i class="fas fa-eye"></i></a>
                            <?php if($t['status']!=='resolved'): ?><a href="tickets.php?id=<?php echo $t['id']; ?>&status=resolved" class="btn btn-sm btn-success" title="Resolve"><i class="fas fa-check"></i></a><?php endif; ?>
                            <?php if($t['status']!=='closed'): ?><a href="tickets.php?id=<?php echo $t['id']; ?>&status=closed" class="btn btn-sm btn-outlined" title="Close"><i class="fas fa-times"></i></a><?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
