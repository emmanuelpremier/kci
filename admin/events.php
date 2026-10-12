<?php
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';
$admin = require_admin($conn);
$today = date('Y-m-d');
function event_get_string($key) {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) { return ''; }
    return (string)$_GET[$key];
}
function event_filter_query($tab, $search, $page) {
    $q = array();
    if ($tab !== '' && $tab !== 'upcoming') { $q['tab'] = $tab; }
    if ($search !== '') { $q['q'] = $search; }
    if ($page > 1) { $q['page'] = $page; }
    return http_build_query($q);
}
function event_page_url($tab, $search, $page) {
    $qs = event_filter_query($tab, $search, $page);
    return 'events.php' . ($qs !== '' ? '?' . $qs : '');
}
function event_day_status($stored, $today) {
    $day = substr(trim((string)$stored), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
        $ts = strtotime((string)$stored);
        if ($ts === false) { return 'past'; }
        $day = date('Y-m-d', $ts);
    }
    return ($day >= $today) ? 'upcoming' : 'past';
}
function kci_admin_event_thumb_src($value) {
    $value = trim((string)$value);
    if (preg_match('/^media\.php\?id=[0-9]+$/', $value)) { return '../' . $value; }
    if (preg_match('/^kci_image\/[A-Za-z0-9._-]+$/', $value)) { return '../' . $value; }
    return '../kci_image/im2.webp';
}
$tab = strtolower(trim(event_get_string('tab')));
if ($tab === '') { $tab = 'upcoming'; }
if (!in_array($tab, array('upcoming', 'past', 'all'), true)) { $tab = 'upcoming'; }
$search = trim(event_get_string('q'));
if (function_exists('mb_substr')) { $search = mb_substr($search, 0, 100, 'UTF-8'); }
else { $search = substr($search, 0, 100); }
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$perPage = 15;
$likePattern = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $search) . '%';
$hasSearch = ($search === '') ? '' : '1';
$tabCounts = array('upcoming' => 0, 'past' => 0, 'all' => 0);
try {
    $cstmt = $conn->prepare('SELECT date FROM events WHERE (? = \'\' OR title LIKE ?)');
    if ($cstmt !== false) {
        $cstmt->bind_param('ss', $hasSearch, $likePattern);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        while ($crow = $cres->fetch_assoc()) {
            $st = event_day_status(isset($crow['date']) ? $crow['date'] : '', $today);
            $tabCounts[$st]++;
            $tabCounts['all']++;
        }
        $cstmt->close();
    }
} catch (Throwable $ignored) {
}
$total = 0;
try {
    if ($tab === 'all') {
        $tstmt = $conn->prepare('SELECT COUNT(*) AS c FROM events WHERE (? = \'\' OR title LIKE ?)');
        if ($tstmt !== false) {
            $tstmt->bind_param('ss', $hasSearch, $likePattern);
            $tstmt->execute();
            $trow = $tstmt->get_result()->fetch_assoc();
            if (is_array($trow) && isset($trow['c'])) { $total = (int)$trow['c']; }
            $tstmt->close();
        }
    } else {
        $tstmt = $conn->prepare('SELECT date FROM events WHERE (? = \'\' OR title LIKE ?)');
        if ($tstmt !== false) {
            $tstmt->bind_param('ss', $hasSearch, $likePattern);
            $tstmt->execute();
            $tres = $tstmt->get_result();
            while ($trow = $tres->fetch_assoc()) {
                if (event_day_status(isset($trow['date']) ? $trow['date'] : '', $today) === $tab) { $total++; }
            }
            $tstmt->close();
        }
    }
} catch (Throwable $ignored) {
    $total = 0;
}
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) { $page = $pages; }
$rows = array();
try {
    $lstmt = $conn->prepare('SELECT id, title, slug, image, date, location, description FROM events WHERE (? = \'\' OR title LIKE ?) ORDER BY date DESC, id DESC LIMIT 2000');
    if ($lstmt !== false) {
        $lstmt->bind_param('ss', $hasSearch, $likePattern);
        $lstmt->execute();
        $lres = $lstmt->get_result();
        $all = array();
        while ($lrow = $lres->fetch_assoc()) { $all[] = $lrow; }
        $lstmt->close();
        if ($tab !== 'all') {
            $kept = array();
            foreach ($all as $candidate) {
                if (event_day_status(isset($candidate['date']) ? $candidate['date'] : '', $today) === $tab) { $kept[] = $candidate; }
            }
            $all = $kept;
        }
        if ($tab === 'upcoming') {
            usort($all, function ($a, $b) {
                $ta = strtotime((string)$a['date']);
                $tb = strtotime((string)$b['date']);
                if ($ta === false) { $ta = 0; }
                if ($tb === false) { $tb = 0; }
                if ($ta === $tb) { return (int)$a['id'] - (int)$b['id']; }
                return ($ta < $tb) ? -1 : 1;
            });
        } else {
            usort($all, function ($a, $b) {
                $ta = strtotime((string)$a['date']);
                $tb = strtotime((string)$b['date']);
                if ($ta === false) { $ta = 0; }
                if ($tb === false) { $tb = 0; }
                if ($ta === $tb) { return (int)$b['id'] - (int)$a['id']; }
                return ($ta > $tb) ? -1 : 1;
            });
        }
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($all, $offset, $perPage);
    }
} catch (Throwable $ignored) {
    $rows = array();
}
$offset = ($page - 1) * $perPage;
$qsNow = event_filter_query($tab, $search, $page);
$returnTarget = 'events.php' . ($qsNow !== '' ? '?' . $qsNow : '');
$tabLabels = array('upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All');
$pageTitle = 'Events';
$activeNav = 'events';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page kci-serve">
<div class="kci-card kci-serve__toolbar">
<div class="kci-serve__head">
<div>
<h2 class="kci-serve__heading"><i class="fa-solid fa-calendar-days"></i> Events</h2>
<p class="kci-muted">Share services, conferences and gatherings.</p>
</div>
<div class="kci-actions">
<a class="kci-btn kci-btn--primary" href="event-edit.php"><i class="fa-solid fa-plus"></i> Add event</a>
</div>
</div>
<nav class="kci-tabs" aria-label="Event status">
<?php foreach ($tabLabels as $tabValue => $tabLabel): ?>
<?php $tabActive = ($tab === $tabValue); ?>
<a class="kci-tabs__item<?php echo $tabActive ? ' is-active' : ''; ?>"<?php echo $tabActive ? ' aria-current="page"' : ''; ?> href="<?php echo e(event_page_url($tabValue, $search, 1)); ?>"><?php echo e($tabLabel); ?> <span class="kci-tabs__count"><?php echo (int)$tabCounts[$tabValue]; ?></span></a>
<?php endforeach; ?>
</nav>
<form method="get" action="events.php" class="kci-serve__filters" role="search">
<input type="hidden" name="tab" value="<?php echo e($tab); ?>">
<label class="kci-serve__search">
<span class="kci-serve__search-label">Search events</span>
<span class="kci-serve__search-box">
<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
<input type="search" name="q" maxlength="100" autocomplete="off" placeholder="Event title" value="<?php echo e($search); ?>">
</span>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
<?php if ($search !== ''): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="<?php echo e(event_page_url($tab, '', 1)); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
<?php endif; ?>
</form>
</div>
<?php if (empty($rows)): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
<h2>No events found</h2>
<p class="kci-muted">There is nothing here under these filters yet.</p>
<div class="kci-actions" style="justify-content:center">
<a class="kci-btn kci-btn--primary kci-btn--sm" href="event-edit.php"><i class="fa-solid fa-plus"></i> Add event</a>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="events.php"><i class="fa-solid fa-xmark"></i> Clear all filters</a>
</div>
</div>
<?php else: ?>
<div class="kci-tablewrap kci-serve__wrap kci-event__wrap">
<table class="kci-table kci-serve__table kci-event__table">
<thead>
<tr>
<th scope="col">Event</th>
<th scope="col">Date</th>
<th scope="col">Location</th>
<th scope="col">Status</th>
<th scope="col">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
<?php $eventId = (int)$row['id']; ?>
<?php $st = event_day_status(isset($row['date']) ? $row['date'] : '', $today); ?>
<?php $dts = strtotime((string)$row['date']); ?>
<?php $dlabel = ($dts !== false) ? date('d M Y', $dts) : (string)$row['date']; ?>
<?php $dtitle = ($dts !== false) ? date('l, F j, Y', $dts) : (string)$row['date']; ?>
<?php $thumb = kci_admin_event_thumb_src(isset($row['image']) ? $row['image'] : ''); ?>
<?php $chip = ($st === 'upcoming') ? 'kci-chip--green' : 'kci-chip--grey'; ?>
<?php $vurl = '../event.php?slug=' . urlencode((string)$row['slug']); ?>
<tr>
<td data-label="Event"><span class="kci-event__cell"><img class="kci-event__thumb" src="<?php echo e($thumb); ?>" alt="" loading="lazy"><span class="kci-event__title" title="<?php echo e($row['title']); ?>"><?php echo e($row['title']); ?></span></span></td>
<td data-label="Date"><span title="<?php echo e($dtitle); ?>"><?php echo e($dlabel); ?></span></td>
<td data-label="Location"><span class="kci-event__loc" title="<?php echo e($row['location']); ?>"><?php echo e($row['location']); ?></span></td>
<td data-label="Status"><span class="kci-chip <?php echo e($chip); ?>"><?php echo e(ucfirst($st)); ?></span></td>
<td data-label="Actions">
<div class="kci-serve__rowactions kci-event__actions">
<a class="kci-pillbtn kci-pillbtn--blue" href="<?php echo e($vurl); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> View</a>
<a class="kci-pillbtn kci-pillbtn--amber" href="event-edit.php?id=<?php echo (int)$eventId; ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
<form method="post" action="event-action.php" class="kci-serve__form" data-confirm-title="Delete this event?" data-confirm="This will also remove the event's page. This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo (int)$eventId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--red"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
</form>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php $from = ($total === 0) ? 0 : $offset + 1; ?>
<?php $to = min($offset + $perPage, $total); ?>
<div class="kci-serve__pager">
<p class="kci-muted">Showing <?php echo (int)$from; ?>–<?php echo (int)$to; ?> of <?php echo (int)$total; ?></p>
<?php if ($pages > 1): ?>
<nav class="kci-pagination" aria-label="Events pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="<?php echo e(event_page_url($tab, $search, $page - 1)); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php $w0 = max(1, $page - 2); ?>
<?php $w1 = min($pages, $page + 2); ?>
<?php for ($pg = $w0; $pg <= $w1; $pg++): ?>
<?php if ($pg === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pg; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="<?php echo e(event_page_url($tab, $search, $pg)); ?>"><?php echo (int)$pg; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="<?php echo e(event_page_url($tab, $search, $page + 1)); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>

