<?php
/* admin/index.php — dashboard. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';

$admin = require_admin($conn);
$display = $admin['display_name'];
$initials = admin_initials($display);

$h = (int)date('G');
$greet = ($h < 12)
    ? 'Good morning'
    : (($h < 17) ? 'Good afternoon' : 'Good evening');
/**
 * Run a SELECT COUNT(*) query (must be a prepared statement) and return the
 * integer count. Fails closed to 0 so a bad query never breaks the page.
 */
function kci_count($conn, $sql, $param = null, $value = null) {
    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return 0;
        }
        if ($param !== null && $value !== null) {
            $stmt->bind_param($param, $value);
        }
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($r && array_key_exists('c', $r)) ? (int)$r['c'] : 0;
    } catch (Throwable $ignored) {
        return 0;
    }
}

$stats = array(
    'serve'    => array(
        'label' => 'Serve applications',
        'hint'  => 'Pending approval',
        'icon'  => 'fa-hand-holding-heart',
        'color' => 'violet',
        'sql'   => 'SELECT COUNT(*) AS c FROM serve_applications WHERE status = ?',
        'p'     => 's',
        'v'     => 'pending',
        'href'  => 'serve-applications.php?status=pending',
    ),
    'giving'   => array(
        'label' => 'Giving confirmations',
        'hint'  => 'Awaiting review',
        'icon'  => 'fa-hand-holding-usd',
        'color' => 'amber',
        'sql'   => 'SELECT COUNT(*) AS c FROM giving_proofs WHERE status = ?',
        'p'     => 's',
        'v'     => 'pending',
    ),
    'contact'  => array(
        'label' => 'Contact messages',
        'hint'  => 'Last 7 days',
        'icon'  => 'fa-envelope',
        'color' => 'blue',
        'sql'   => 'SELECT COUNT(*) AS c FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
    ),
    'first'    => array(
        'label' => 'First-timers',
        'hint'  => 'Last 30 days',
        'icon'  => 'fa-user-check',
        'color' => 'green',
        'sql'   => 'SELECT COUNT(*) AS c FROM first_timers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
    ),
    'events'   => array(
        'label' => 'Upcoming events',
        'hint'  => 'On or after today',
        'icon'  => 'fa-calendar-plus',
        'color' => 'rose',
        'sql'   => 'SELECT COUNT(*) AS c FROM events WHERE date >= CURDATE()',
    ),
    'ministries' => array(
        'label' => 'Active ministries',
        'hint'  => 'Currently running',
        'icon'  => 'fa-people-group',
        'color' => 'cyan',
        'sql'   => 'SELECT COUNT(*) AS c FROM ministries WHERE status = ?',
        'p'     => 's',
        'v'     => 'active',
    ),
);


/* Resolve every stat count (one prepared query per stat). */
foreach ($stats as $key => $stat) {
    $param = isset($stat['p']) ? $stat['p'] : null;
    $value = isset($stat['v']) ? $stat['v'] : null;
    $stats[$key]['count'] = kci_count($conn, $stat['sql'], $param, $value);
}
/* Last 8 admin log entries for the activity feed. */
$activity = array();
try {
    $stmt = $conn->prepare(
        'SELECT l.action, l.details, l.created_at, u.display_name'
        . ' FROM admin_log l'
        . ' LEFT JOIN admin_users u ON u.id = l.admin_id'
        . ' ORDER BY l.created_at DESC LIMIT 8'
    );
    if ($stmt !== false) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $activity[] = $row;
        }
        $stmt->close();
    }
} catch (Throwable $ignored) {
    $activity = array();
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<div class="kci-card kci-welcome">
<div>
<h2><?php echo e($greet . ', ' . $display); ?></h2>
<p>Here is what is happening across the church today.</p>
</div>
<div class="kci-actions">
<a class="kci-btn kci-btn--sm kci-btn--ghost" href="password.php"><i class="fa-solid fa-key"></i> Change password</a>
<a class="kci-btn kci-btn--sm kci-btn--primary" href="giving-settings.php"><i class="fa-solid fa-building-columns"></i> Giving account</a>
</div>
</div>
<div class="kci-grid kci-grid--3">
<?php foreach ($stats as $stat): ?>
<?php if (isset($stat['href'])): ?>
<a class="kci-stat kci-stat--link" href="<?php echo e($stat['href']); ?>" aria-label="<?php echo e($stat['label'] . ': ' . (int)$stat['count'] . ' — ' . $stat['hint'] . '. View pending serve applications.'); ?>">
<p class="kci-stat__label"><i class="fa-solid <?php echo e($stat['icon']); ?>"></i> <?php echo e($stat['label']); ?></p>
<p class="kci-stat__value"><?php echo (int)$stat['count']; ?></p>
<p class="kci-stat__hint"><?php echo e($stat['hint']); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></p>
</a>
<?php else: ?>
<article class="kci-stat">
<p class="kci-stat__label"><i class="fa-solid <?php echo e($stat['icon']); ?>"></i> <?php echo e($stat['label']); ?></p>
<p class="kci-stat__value"><?php echo (int)$stat['count']; ?></p>
<p class="kci-stat__hint"><?php echo e($stat['hint']); ?></p>
</article>
<?php endif; ?>
<?php endforeach; ?>
</div>
<div class="kci-grid kci-grid--2">
<article class="kci-card">
<h2>Recent activity</h2>
<?php if (empty($activity)): ?>
<p class="kci-muted">No activity has been recorded yet.</p>
<?php else: ?>
<ul class="kci-feed">
<?php foreach ($activity as $item): ?>
<li class="kci-feed__item">
<span class="kci-feed__who"><?php echo e(($item['display_name'] !== null && $item['display_name'] !== '') ? $item['display_name'] : 'System'); ?></span>
<span class="kci-feed__what"><?php echo e($item['action']); ?><?php echo (!empty($item['details'])) ? ' &middot; ' . e($item['details']) : ''; ?></span>
<span class="kci-feed__when"><?php echo e(admin_relative_time($item['created_at'])); ?></span>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</article>
<article class="kci-card">
<h2>Your account</h2>
<p><?php echo e($admin['username']); ?> &middot; <?php echo e($admin['role']); ?></p>
<p class="kci-muted">Keep your sign-in details current. You will be asked to choose a new password whenever yours has been reset.</p>
<div class="kci-actions">
<a class="kci-btn kci-btn--primary" href="password.php"><i class="fa-solid fa-key"></i> Change password</a>
</div>
</article>
</div>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
