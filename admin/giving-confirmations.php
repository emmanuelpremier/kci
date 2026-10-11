<?php
/* admin/giving-confirmations.php — review giving confirmations.
 *
 * Status tabs with live counts, name/phone/email/reference search, a
 * giving-type filter, newest-first ordering and 20-per-page pagination.
 * Every row action posts to giving-action.php with a CSRF token; Confirm,
 * Reject and Delete are confirmed through the shared dialog in admin.js
 * (data-confirm attributes). Confirm and Reject offer an optional note.
 *
 * Every query is a prepared statement and every value is escaped on output.
 * The giving_proofs table already exists and is never created or altered. */
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

/* Whitelisted statuses + giving types (public giving.php values). */
$giveStatuses = array('pending', 'confirmed', 'rejected');
$giveTypes = array('Tithe', 'Offering', 'Seed', 'Building and Projects', 'Missions and Outreach', 'Other');

/* Safe scalar getter for query-string values (arrays are rejected). */
function giving_get_string($key) {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) {
        return '';
    }
    return (string)$_GET[$key];
}

/* WhatsApp digits: normalised international digits via kci_normalize_phone()
 * when available (with the "+" stripped), otherwise digits only. */
function giving_wa_digits($phone) {
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

/* Public reference code: "KCI-" plus the id padded to 6 digits. */
function giving_code($id) {
    return 'KCI-' . str_pad((string)(int)$id, 6, '0', STR_PAD_LEFT);
}

/* Current filters as a query string (tabs, pagination, return target). */
function giving_filter_query($statusFilter, $search, $typeFilter, $page) {
    $q = array();
    if ($statusFilter !== '') {
        $q['status'] = $statusFilter;
    }
    if ($search !== '') {
        $q['q'] = $search;
    }
    if ($typeFilter !== '') {
        $q['type'] = $typeFilter;
    }
    if ($page > 1) {
        $q['page'] = $page;
    }
    return http_build_query($q);
}

function giving_page_url($statusFilter, $search, $typeFilter, $page) {
    $qs = giving_filter_query($statusFilter, $search, $typeFilter, $page);
    return 'giving-confirmations.php' . ($qs !== '' ? '?' . $qs : '');
}

/* First $length characters of a value, with an ellipsis when trimmed. */
function giving_excerpt($value, $length = 24) {
    $value = (string)$value;
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $length) {
            return $value;
        }
        return mb_substr($value, 0, $length, 'UTF-8') . '…';
    }
    if (strlen($value) <= $length) {
        return $value;
    }
    return substr($value, 0, $length) . '…';
}

/* Filters (status and type are whitelisted). */
$statusFilter = strtolower(trim(giving_get_string('status')));
if ($statusFilter === 'all') {
    $statusFilter = '';
}
if ($statusFilter !== '' && !in_array($statusFilter, $giveStatuses, true)) {
    $statusFilter = '';
}

$search = trim(giving_get_string('q'));
if (function_exists('mb_substr')) {
    $search = mb_substr($search, 0, 100, 'UTF-8');
} else {
    $search = substr($search, 0, 100);
}

$typeFilter = trim(giving_get_string('type'));
if ($typeFilter !== '' && !in_array($typeFilter, $giveTypes, true)) {
    $typeFilter = '';
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$perPage = 20;

/* Shared WHERE fragments (bound flags keep the parameter list fixed, so one
 * bind_param call serves every filter combination). */
$whereBase = '(? = \'\' OR g.giving_type = ?)'
    . ' AND (? = \'\' OR g.full_name LIKE ? OR g.phone LIKE ? OR g.email LIKE ? OR g.reference LIKE ?)';
$whereFull = '(? = \'\' OR g.status = ?) AND ' . $whereBase;

/* LIKE pattern with wildcard characters escaped (the prepared statement
 * already keeps quotes safe). */
$likePattern = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $search) . '%';
$hasStatus = ($statusFilter === '') ? '' : '1';
$hasType = ($typeFilter === '') ? '' : '1';
$hasSearch = ($search === '') ? '' : '1';

/* Summary cards (global totals, independent of the list filters). */
$sumPending = 0;
$sumMonth = '0';
$sumAll = '0';
try {
    $sstmt = $conn->prepare('SELECT COUNT(*) AS c FROM giving_proofs WHERE status = ?');
    if ($sstmt !== false) {
        $wantPending = 'pending';
        $sstmt->bind_param('s', $wantPending);
        $sstmt->execute();
        $srow = $sstmt->get_result()->fetch_assoc();
        if (is_array($srow) && isset($srow['c'])) {
            $sumPending = (int)$srow['c'];
        }
        $sstmt->close();
    }
} catch (Throwable $ignored) {
    $sumPending = 0;
}
try {
    $mstmt = $conn->prepare('SELECT COALESCE(SUM(amount), 0) AS t FROM giving_proofs WHERE status = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())');
    if ($mstmt !== false) {
        $wantConfirmed = 'confirmed';
        $mstmt->bind_param('s', $wantConfirmed);
        $mstmt->execute();
        $mrow = $mstmt->get_result()->fetch_assoc();
        if (is_array($mrow) && isset($mrow['t'])) {
            $sumMonth = (string)$mrow['t'];
        }
        $mstmt->close();
    }
} catch (Throwable $ignored) {
    $sumMonth = '0';
}
try {
    $astmt = $conn->prepare('SELECT COALESCE(SUM(amount), 0) AS t FROM giving_proofs WHERE status = ?');
    if ($astmt !== false) {
        $wantConfirmed2 = 'confirmed';
        $astmt->bind_param('s', $wantConfirmed2);
        $astmt->execute();
        $arow = $astmt->get_result()->fetch_assoc();
        if (is_array($arow) && isset($arow['t'])) {
            $sumAll = (string)$arow['t'];
        }
        $astmt->close();
    }
} catch (Throwable $ignored) {
    $sumAll = '0';
}
/* Live counts per tab (respecting search + type, ignoring status). */
$tabCounts = array('all' => 0, 'pending' => 0, 'confirmed' => 0, 'rejected' => 0);
try {
    $cstmt = $conn->prepare('SELECT g.status AS st, COUNT(*) AS c FROM giving_proofs g WHERE ' . $whereBase . ' GROUP BY g.status');
    if ($cstmt !== false) {
        $cstmt->bind_param('sssssss', $hasType, $typeFilter, $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        while ($crow = $cres->fetch_assoc()) {
            if (in_array($crow['st'], $giveStatuses, true)) {
                $tabCounts[$crow['st']] = (int)$crow['c'];
                $tabCounts['all'] += (int)$crow['c'];
            }
        }
        $cstmt->close();
    }
} catch (Throwable $ignored) {
    /* Counts stay at 0; the list still renders. */
}
/* Total + page clamp. */
$total = 0;
try {
    $tstmt = $conn->prepare('SELECT COUNT(*) AS c FROM giving_proofs g WHERE ' . $whereFull);
    if ($tstmt !== false) {
        $tstmt->bind_param('sssssssss', $hasStatus, $statusFilter, $hasType, $typeFilter, $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern);
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

/* Newest-first page of rows. */
$rows = array();
try {
    $lstmt = $conn->prepare('SELECT g.id, g.full_name, g.phone, g.email, g.amount, g.giving_type, g.give_date, g.sender_account_name, g.reference, g.note, g.status, g.created_at FROM giving_proofs g WHERE ' . $whereFull . ' ORDER BY g.created_at DESC, g.id DESC LIMIT ? OFFSET ?');
    if ($lstmt !== false) {
        $lstmt->bind_param('sssssssssii', $hasStatus, $statusFilter, $hasType, $typeFilter, $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern, $perPage, $offset);
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
$returnTarget = giving_page_url($statusFilter, $search, $typeFilter, $page);
$chipClasses = array('pending' => 'kci-chip--amber', 'confirmed' => 'kci-chip--green', 'rejected' => 'kci-chip--red');
$pageTitle = 'Giving Confirmations';
$activeNav = 'confirmations';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page kci-giveconf">
<div class="kci-grid kci-grid--3 kci-giveconf__summary">
<article class="kci-card kci-giveconf__sum">
<p class="kci-stat__label"><i class="fa-solid fa-hourglass-half"></i> Pending review</p>
<p class="kci-stat__value"><?php echo (int)$sumPending; ?></p>
<p class="kci-stat__hint">Awaiting confirmation</p>
</article>
<article class="kci-card kci-giveconf__sum">
<p class="kci-stat__label"><i class="fa-solid fa-calendar-check"></i> Confirmed this month</p>
<p class="kci-stat__value"><?php echo e(kci_naira($sumMonth)); ?></p>
<p class="kci-stat__hint">Confirmed gifts this month</p>
</article>
<article class="kci-card kci-giveconf__sum">
<p class="kci-stat__label"><i class="fa-solid fa-hand-holding-dollar"></i> Confirmed all time</p>
<p class="kci-stat__value"><?php echo e(kci_naira($sumAll)); ?></p>
<p class="kci-stat__hint">Every confirmed gift</p>
</article>
</div>
<div class="kci-card">
<h2 class="kci-serve__heading">Giving confirmations</h2>


<p class="kci-muted"><?php echo (int)$total; ?> confirmation<?php echo ((int)$total === 1) ? '' : 's'; ?> found</p>
<nav class="kci-tabs" aria-label="Filter by status">
<?php $tabDefs = array('all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected'); ?>
<?php foreach ($tabDefs as $tabValue => $tabLabel): ?>
<?php $tabStatus = ($tabValue === 'all') ? '' : $tabValue; ?>
<?php $tabUrl = giving_page_url($tabStatus, $search, $typeFilter, 1); ?>
<?php $tabActive = ($statusFilter === $tabStatus); ?>
<a class="kci-tabs__item<?php echo $tabActive ? ' is-active' : ''; ?>"<?php echo $tabActive ? ' aria-current="page"' : ''; ?> href="<?php echo e($tabUrl); ?>"><?php echo e($tabLabel); ?> <span class="kci-tabs__count"><?php echo (int)$tabCounts[$tabValue]; ?></span></a>
<?php endforeach; ?>
</nav>
<form method="get" action="giving-confirmations.php" class="kci-serve__filters kci-giveconf__filters" role="search">
<?php if ($statusFilter !== ''): ?>
<input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">
<?php endif; ?>
<label class="kci-serve__search">
<span class="kci-serve__search-label">Search confirmations</span>
<span class="kci-serve__search-box">
<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
<input type="search" name="q" maxlength="100" autocomplete="off" placeholder="Name, phone, email or reference" value="<?php echo e($search); ?>">
</span>
</label>
<label class="kci-serve__ministry">
<span class="kci-serve__search-label">Giving type</span>
<select name="type">
<option value="">All types</option>
<?php foreach ($giveTypes as $typeOption): ?>
<option value="<?php echo e($typeOption); ?>"<?php echo ($typeFilter === $typeOption) ? ' selected' : ''; ?>><?php echo e($typeOption); ?></option>
<?php endforeach; ?>
</select>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
<?php if ($search !== '' || $typeFilter !== ''): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="<?php echo e(giving_page_url($statusFilter, '', '', 1)); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
<?php endif; ?>
</form>
</div>
<?php if (empty($rows)): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-dollar"></i></span>
<h2>No confirmations found</h2>
<p class="kci-muted">There is nothing here under these filters yet. Try a different search, pick another giving type, or clear the filters to see everything.</p>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="giving-confirmations.php"><i class="fa-solid fa-xmark"></i> Clear all filters</a>
</div>
<?php else: ?>
<div class="kci-tablewrap kci-serve__wrap kci-giveconf__wrap">
<table class="kci-table kci-serve__table kci-giveconf__table">
<thead>
<tr>
<th scope="col">Reference</th>
<th scope="col">Giver</th>
<th scope="col">Amount</th>
<th scope="col">Type</th>
<th scope="col">Give date</th>
<th scope="col">Sender</th>
<th scope="col">Txn ref</th>
<th scope="col">Submitted</th>
<th scope="col">Status</th>
<th scope="col">Actions</th>
</tr>
</thead>
<?php foreach ($rows as $row): ?>
<?php $giveId = (int)$row['id']; ?>
<?php $giveStatus = (string)$row['status']; ?>
<?php $giveCode = giving_code($giveId); ?>
<?php $email = (string)($row['email'] ?? ''); ?>
<?php $phone = (string)($row['phone'] ?? ''); ?>
<?php $waDigits = giving_wa_digits($phone); ?>
<?php $senderName = ($row['sender_account_name'] !== null && $row['sender_account_name'] !== '') ? (string)$row['sender_account_name'] : '—'; ?>
<?php $senderShort = ($senderName === '—') ? '—' : giving_excerpt($senderName, 18); ?>
<?php $txnRef = (string)($row['reference'] ?? ''); ?>
<?php $txnShort = ($txnRef === '') ? '—' : giving_excerpt($txnRef, 16); ?>
<?php $giveDateRaw = (string)($row['give_date'] ?? ''); ?>
<?php $giveDateTs = ($giveDateRaw !== '') ? strtotime($giveDateRaw) : false; ?>
<?php $giveDateShown = ($giveDateTs !== false && $giveDateTs !== 0) ? date('d M Y', $giveDateTs) : (($giveDateRaw !== '') ? $giveDateRaw : '—'); ?>
<?php $submittedTs = strtotime((string)$row['created_at']); ?>
<?php $submittedFull = ($submittedTs !== false) ? date('d M Y, g:ia', $submittedTs) : (string)$row['created_at']; ?>
<?php $chipClass = isset($chipClasses[$giveStatus]) ? $chipClasses[$giveStatus] : 'kci-chip--grey'; ?>
<tr>
<td data-label="Reference"><span class="kci-giveconf__code" title="<?php echo e($giveCode); ?>"><?php echo e($giveCode); ?></span></td>
<td data-label="Giver">
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
<td data-label="Amount"><span class="kci-giveconf__amount"><?php echo e(kci_naira($row['amount'])); ?></span></td>
<td data-label="Type"><?php echo e($row['giving_type']); ?></td>
<td data-label="Sender"><span class="kci-giveconf__trunc"<?php echo ($senderName === '—') ? '' : ' title="' . e($senderName) . '"'; ?>><?php echo e($senderShort); ?></span></td>
<td data-label="Txn ref"><span class="kci-giveconf__trunc"<?php echo ($txnRef === '') ? '' : ' title="' . e($txnRef) . '"'; ?>><?php echo e($txnShort); ?></span></td>
<td data-label="Submitted"><span title="<?php echo e($submittedFull); ?>"><?php echo e(admin_relative_time($row['created_at'])); ?></span></td>
<td data-label="Status"><span class="kci-chip <?php echo e($chipClass); ?>"><?php echo e(ucfirst($giveStatus)); ?></span></td>
<td data-label="Actions">
<div class="kci-serve__rowactions kci-giveconf__rowactions">
<?php if ($giveStatus === 'pending'): ?>
<form method="post" action="giving-action.php" class="kci-serve__form" data-confirm-title="Confirm this giving?" data-confirm="Confirm this giving? You can add an optional note below." data-confirm-note="1">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="confirm">
<input type="hidden" name="id" value="<?php echo $giveId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--green"><i class="fa-solid fa-check" aria-hidden="true"></i> Confirm</button>
</form>
<form method="post" action="giving-action.php" class="kci-serve__form" data-confirm-title="Reject this giving?" data-confirm="Reject this giving? You can add an optional reason below." data-confirm-note="1">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="reject">
<input type="hidden" name="id" value="<?php echo $giveId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--red"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Reject</button>
</form>
<?php elseif ($giveStatus === 'confirmed' || $giveStatus === 'rejected'): ?>
<form method="post" action="giving-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="reopen">
<input type="hidden" name="id" value="<?php echo $giveId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--amber"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reopen</button>
</form>
<?php endif; ?>
<form method="post" action="giving-action.php" class="kci-serve__form" data-confirm-title="Delete this confirmation?" data-confirm="Permanently delete this confirmation? This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo $giveId; ?>">
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
<?php $from = ($total === 0) ? 0 : $offset + 1; ?>
<?php $to = min($offset + $perPage, $total); ?>
<div class="kci-serve__pager">
<p class="kci-muted">Showing <?php echo (int)$from; ?>–<?php echo (int)$to; ?> of <?php echo (int)$total; ?></p>
<?php if ($pages > 1): ?>
<nav class="kci-pagination" aria-label="Confirmations pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="<?php echo e(giving_page_url($statusFilter, $search, $typeFilter, $page - 1)); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php $winStart = max(1, $page - 2); ?>
<?php $winEnd = min($pages, $page + 2); ?>
<?php for ($pager = $winStart; $pager <= $winEnd; $pager++): ?>
<?php if ($pager === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pager; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="<?php echo e(giving_page_url($statusFilter, $search, $typeFilter, $pager)); ?>"><?php echo (int)$pager; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="<?php echo e(giving_page_url($statusFilter, $search, $typeFilter, $page + 1)); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>

