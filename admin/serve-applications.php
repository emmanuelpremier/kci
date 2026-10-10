<?php
/* admin/serve-applications.php — review the Serve With Us applications.
 *
 * Status tabs with live counts, name/email/phone search, a ministry filter,
 * newest-first ordering and 20-per-page pagination. Every row action posts
 * to serve-action.php with a CSRF token; Accept, Reject and Delete are
 * confirmed through the shared dialog in admin.js (data-confirm attributes).
 *
 * Every query is a prepared statement and every value is escaped on output.
 * The serve_applications and ministries tables already exist and are never
 * created or altered here. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';
if (file_exists(__DIR__ . '/../kci_validate.php')) {
    require_once __DIR__ . '/../kci_validate.php';
}

$admin = require_admin($conn);

/* --- Whitelisted statuses --- */
$serveStatuses = array('pending', 'accepted', 'rejected', 'contacted', 'completed');

/* Safe scalar getter for query-string values (arrays are rejected). */
function serve_get_string($key) {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) {
        return '';
    }
    return (string)$_GET[$key];
}

/* WhatsApp digits: normalised international digits via kci_normalize_phone()
 * when available (with the "+" stripped), otherwise digits only. */
function serve_wa_digits($phone) {
    $raw = (string)$phone;
    if (function_exists('kci_normalize_phone')) {
        try {
            $normalized = kci_normalize_phone($raw);
        } catch (Throwable $ignored) {
            $normalized = null;
        }
        if (is_string($normalized) && $normalized !== '') {
            return ltrim($normalized, '+');
        }
    }
    $digits = preg_replace('/\D+/', '', $raw);
    return is_string($digits) ? $digits : '';
}

/* Current filters as a query string (tabs, pagination and the return target). */
function serve_filter_query($statusFilter, $search, $ministryFilter, $page) {
    $q = array();
    if ($statusFilter !== '') {
        $q['status'] = $statusFilter;
    }
    if ($search !== '') {
        $q['q'] = $search;
    }
    if ($ministryFilter !== '') {
        $q['ministry'] = $ministryFilter;
    }
    if ($page > 1) {
        $q['page'] = $page;
    }
    return http_build_query($q);
}

function serve_page_url($statusFilter, $search, $ministryFilter, $page) {
    $qs = serve_filter_query($statusFilter, $search, $ministryFilter, $page);
    return 'serve-applications.php' . ($qs !== '' ? '?' . $qs : '');
}

/* First $length characters of a message, with an ellipsis when trimmed. */
function serve_excerpt($message, $length = 90) {
    $message = (string)$message;
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($message, 'UTF-8') <= $length) {
            return $message;
        }
        return mb_substr($message, 0, $length, 'UTF-8') . '…';
    }
    if (strlen($message) <= $length) {
        return $message;
    }
    return substr($message, 0, $length) . '…';
}

/* --- Filters (status and ministry are whitelisted) --- */
$statusFilter = strtolower(trim(serve_get_string('status')));
if ($statusFilter === 'all') {
    $statusFilter = '';
}
if ($statusFilter !== '' && !in_array($statusFilter, $serveStatuses, true)) {
    $statusFilter = '';
}

$search = trim(serve_get_string('q'));
if (function_exists('mb_substr')) {
    $search = mb_substr($search, 0, 100, 'UTF-8');
} else {
    $search = substr($search, 0, 100);
}

/* Ministries power the filter dropdown and whitelist its value. */
$ministries = array();
try {
    $mstmt = $conn->prepare('SELECT name, slug FROM ministries ORDER BY name ASC');
    if ($mstmt !== false) {
        $mstmt->execute();
        $mres = $mstmt->get_result();
        while ($mrow = $mres->fetch_assoc()) {
            $ministries[] = $mrow;
        }
        $mstmt->close();
    }
} catch (Throwable $ignored) {
    $ministries = array();
}
$ministrySlugs = array();
foreach ($ministries as $ministryRow) {
    $ministrySlugs[] = (string)$ministryRow['slug'];
}
$ministryFilter = trim(serve_get_string('ministry'));
if ($ministryFilter !== '' && !in_array($ministryFilter, $ministrySlugs, true)) {
    $ministryFilter = '';
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$perPage = 20;
$perPage = 20;

/* Shared WHERE fragments (bound flags keep the parameter list fixed, so one
 * bind_param call serves every filter combination). */
$whereBase = '(? = \'\' OR s.ministry_slug = ?)'
    . ' AND (? = \'\' OR s.full_name LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)';
$whereFull = '(? = \'\' OR s.status = ?) AND ' . $whereBase;

/* LIKE pattern with wildcard characters escaped (the prepared statement
 * already keeps quotes safe). */
$likePattern = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $search) . '%';
$hasStatus = ($statusFilter === '') ? '' : '1';
$hasMinistry = ($ministryFilter === '') ? '' : '1';
$hasSearch = ($search === '') ? '' : '1';

/* --- Live counts per tab (respecting search + ministry, ignoring status) --- */
$tabCounts = array('all' => 0);
foreach ($serveStatuses as $tabStatus) {
    $tabCounts[$tabStatus] = 0;
}
try {
    $cstmt = $conn->prepare(
        'SELECT s.status AS st, COUNT(*) AS c FROM serve_applications s'
        . ' WHERE ' . $whereBase . ' GROUP BY s.status'
    );
    if ($cstmt !== false) {
        $cstmt->bind_param('ssssss', $hasMinistry, $ministryFilter, $hasSearch, $likePattern, $likePattern, $likePattern);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        while ($crow = $cres->fetch_assoc()) {
            if (in_array($crow['st'], $serveStatuses, true)) {
                $tabCounts[$crow['st']] = (int)$crow['c'];
                $tabCounts['all'] += (int)$crow['c'];
            }
        }
        $cstmt->close();
    }
} catch (Throwable $ignored) {
    /* Counts stay at 0; the list still renders. */
}

/* --- Total + page clamp --- */
$total = 0;
try {
    $tstmt = $conn->prepare('SELECT COUNT(*) AS c FROM serve_applications s WHERE ' . $whereFull);
    if ($tstmt !== false) {
        $tstmt->bind_param(
            'ssssssss',
            $hasStatus, $statusFilter,
            $hasMinistry, $ministryFilter,
            $hasSearch, $likePattern, $likePattern, $likePattern
        );
        $tstmt->execute();
        $trow = $tstmt->get_result()->fetch_assoc();
        if (is_array($trow) && isset($trow['c'])) {
            $total = (int)$trow['c'];
        }
        $tstmt->close();
    }
} catch (Throwable $ignored) {
    $total = 0;
}
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$offset = ($page - 1) * $perPage;

/* --- Newest-first page of rows (ministry NAME via slug join) --- */
$rows = array();
try {
    $lstmt = $conn->prepare(
        'SELECT s.id, s.full_name, s.email, s.phone, s.ministry_slug,'
        . ' s.availability, s.message, s.status, s.created_at,'
        . ' m.name AS ministry_name'
        . ' FROM serve_applications s'
        . ' LEFT JOIN ministries m ON m.slug = s.ministry_slug'
        . ' WHERE ' . $whereFull
        . ' ORDER BY s.created_at DESC, s.id DESC LIMIT ? OFFSET ?'
    );
    if ($lstmt !== false) {
        $lstmt->bind_param(
            'ssssssssii',
            $hasStatus, $statusFilter,
            $hasMinistry, $ministryFilter,
            $hasSearch, $likePattern, $likePattern, $likePattern,
            $perPage, $offset
        );
        $lstmt->execute();
        $lres = $lstmt->get_result();
        while ($lrow = $lres->fetch_assoc()) {
            $rows[] = $lrow;
        }
        $lstmt->close();
    }
} catch (Throwable $ignored) {
    $rows = array();
}

$returnTarget = serve_page_url($statusFilter, $search, $ministryFilter, $page);

$chipClasses = array(
    'pending'   => 'kci-chip--amber',
    'accepted'  => 'kci-chip--green',
    'rejected'  => 'kci-chip--red',
    'contacted' => 'kci-chip--blue',
    'completed' => 'kci-chip--grey',
);

$pageTitle = 'Serve Applications';
$activeNav = 'serve';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page kci-serve">
<div class="kci-card">
<h2 class="kci-serve__heading">Serve applications</h2>
<p class="kci-muted"><?php echo (int)$total; ?> application<?php echo ((int)$total === 1) ? '' : 's'; ?> found</p>
<nav class="kci-tabs" aria-label="Filter by status">
<?php $tabDefs = array('all' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'contacted' => 'Contacted', 'completed' => 'Completed'); ?>
<?php foreach ($tabDefs as $tabValue => $tabLabel): ?>
<?php
    $tabStatus = ($tabValue === 'all') ? '' : $tabValue;
    $tabUrl = serve_page_url($tabStatus, $search, $ministryFilter, 1);
    $tabActive = ($statusFilter === $tabStatus);
?>
<a class="kci-tabs__item<?php echo $tabActive ? ' is-active' : ''; ?>"<?php echo $tabActive ? ' aria-current="page"' : ''; ?> href="<?php echo e($tabUrl); ?>"><?php echo e($tabLabel); ?> <span class="kci-tabs__count"><?php echo (int)$tabCounts[$tabValue]; ?></span></a>
<?php endforeach; ?>
</nav>
<form method="get" action="serve-applications.php" class="kci-serve__filters" role="search">
<?php if ($statusFilter !== ''): ?>
<input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">
<?php endif; ?>
<label class="kci-serve__search">
<span class="kci-serve__search-label">Search applications</span>
<span class="kci-serve__search-box">
<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
<input type="search" name="q" maxlength="100" autocomplete="off" placeholder="Name, email or phone" value="<?php echo e($search); ?>">
</span>
</label>
<label class="kci-serve__ministry">
<span class="kci-serve__search-label">Ministry</span>
<select name="ministry">
<option value="">All ministries</option>
<?php foreach ($ministries as $ministryOption): ?>
<option value="<?php echo e($ministryOption['slug']); ?>"<?php echo ($ministryFilter === (string)$ministryOption['slug']) ? ' selected' : ''; ?>><?php echo e($ministryOption['name']); ?></option>
<?php endforeach; ?>
</select>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
<?php if ($search !== '' || $ministryFilter !== ''): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="<?php echo e(serve_page_url($statusFilter, '', '', 1)); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
<?php endif; ?>
</form>
</div>

<?php if (empty($rows)): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-inbox"></i></span>
<h2>No applications found</h2>
<p class="kci-muted">There is nothing here under these filters yet. Try a different search, pick another ministry, or clear the filters to see everything.</p>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="serve-applications.php"><i class="fa-solid fa-xmark"></i> Clear all filters</a>
</div>
<?php else: ?>
<div class="kci-tablewrap kci-serve__wrap">
<table class="kci-table kci-serve__table">
<thead>
<tr>
<th scope="col">Applicant</th>
<th scope="col">Ministry</th>
<th scope="col">Availability</th>
<th scope="col">Message</th>
<th scope="col">Submitted</th>
<th scope="col">Status</th>
<th scope="col">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
<?php
    $appId = (int)$row['id'];
    $appStatus = (string)$row['status'];
    $ministryName = (isset($row['ministry_name']) && $row['ministry_name'] !== null && $row['ministry_name'] !== '')
        ? (string)$row['ministry_name']
        : (string)$row['ministry_slug'];
    $availability = ($row['availability'] !== null && $row['availability'] !== '') ? (string)$row['availability'] : '—';
    $message = (string)($row['message'] ?? '');
    $messageShort = ($message === '') ? '—' : serve_excerpt($message, 60);
    $email = (string)$row['email'];
    $phone = (string)$row['phone'];
    $waDigits = serve_wa_digits($phone);
    $submittedTs = strtotime((string)$row['created_at']);
    $submittedFull = ($submittedTs !== false) ? date('d M Y, g:ia', $submittedTs) : (string)$row['created_at'];
    $chipClass = isset($chipClasses[$appStatus]) ? $chipClasses[$appStatus] : 'kci-chip--grey';
?>
<tr>
<td data-label="Applicant">
<span class="kci-serve__name"><?php echo e($row['full_name']); ?></span>
<span class="kci-serve__contact">
<?php if ($email !== ''): ?>
<a class="kci-iconbtn" href="mailto:<?php echo e($email); ?>" title="Email <?php echo e($email); ?>" aria-label="Email <?php echo e($row['full_name']); ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($phone !== ''): ?>
<a class="kci-iconbtn" href="tel:<?php echo e($phone); ?>" title="Call <?php echo e($phone); ?>" aria-label="Call <?php echo e($row['full_name']); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($waDigits !== ''): ?>
<a class="kci-iconbtn" href="https://wa.me/<?php echo e($waDigits); ?>" target="_blank" rel="noopener" title="WhatsApp <?php echo e($phone); ?>" aria-label="WhatsApp <?php echo e($row['full_name']); ?>"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
<?php endif; ?>
</span>
</td>
<td data-label="Ministry"><?php echo e($ministryName); ?></td>
<td data-label="Availability"><?php echo e($availability); ?></td>
<td data-label="Message"><span class="kci-serve__message"<?php echo ($message === '') ? '' : ' title="' . e($message) . '"'; ?>><?php echo e($messageShort); ?></span></td>
<td data-label="Submitted"><span title="<?php echo e($submittedFull); ?>"><?php echo e(admin_relative_time($row['created_at'])); ?></span></td>
<td data-label="Status"><span class="kci-chip <?php echo e($chipClass); ?>"><?php echo e(ucfirst($appStatus)); ?></span></td>
<td data-label="Actions">
<div class="kci-serve__rowactions">
<?php if ($appStatus === 'pending'): ?>
<form method="post" action="serve-action.php" class="kci-serve__form" data-confirm-title="Accept this application?" data-confirm="Accept <?php echo e($row['full_name']); ?> to serve? You can add an optional note below." data-confirm-note="1">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="accept">
<input type="hidden" name="id" value="<?php echo $appId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--green"><i class="fa-solid fa-check" aria-hidden="true"></i> Accept</button>
</form>
<form method="post" action="serve-action.php" class="kci-serve__form" data-confirm-title="Reject this application?" data-confirm="Reject the application from <?php echo e($row['full_name']); ?>? You can add an optional reason below." data-confirm-note="1">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="reject">
<input type="hidden" name="id" value="<?php echo $appId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--red"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Reject</button>
</form>
<?php elseif ($appStatus === 'accepted'): ?>
<form method="post" action="serve-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="contacted">
<input type="hidden" name="id" value="<?php echo $appId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--blue"><i class="fa-solid fa-headset" aria-hidden="true"></i> Mark contacted</button>
</form>
<?php elseif ($appStatus === 'contacted'): ?>
<form method="post" action="serve-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="completed">
<input type="hidden" name="id" value="<?php echo $appId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--violet"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Mark completed</button>
</form>
<?php elseif ($appStatus === 'rejected'): ?>
<form method="post" action="serve-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="reopen">
<input type="hidden" name="id" value="<?php echo $appId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--amber"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reopen</button>
</form>
<?php endif; ?>
<form method="post" action="serve-action.php" class="kci-serve__form" data-confirm-title="Delete this application?" data-confirm="Permanently delete the application from <?php echo e($row['full_name']); ?>? This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo $appId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--outline"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
</form>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php
    $from = ($total === 0) ? 0 : $offset + 1;
    $to = min($offset + $perPage, $total);
?>
<div class="kci-serve__pager">
<p class="kci-muted">Showing <?php echo (int)$from; ?>–<?php echo (int)$to; ?> of <?php echo (int)$total; ?></p>
<?php if ($pages > 1): ?>
<nav class="kci-pagination" aria-label="Applications pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="<?php echo e(serve_page_url($statusFilter, $search, $ministryFilter, $page - 1)); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php
    $winStart = max(1, $page - 2);
    $winEnd = min($pages, $page + 2);
    for ($pager = $winStart; $pager <= $winEnd; $pager++):
?>
<?php if ($pager === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pager; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="<?php echo e(serve_page_url($statusFilter, $search, $ministryFilter, $pager)); ?>"><?php echo (int)$pager; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="<?php echo e(serve_page_url($statusFilter, $search, $ministryFilter, $page + 1)); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
