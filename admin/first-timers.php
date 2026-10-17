<?php
/* admin/first-timers.php — list the first-time visitors who filled in the
 * public First-Timer Form (form.php).
 *
 * Status tabs with live counts (All / New / Contacted), a visit-date filter
 * (Upcoming / Past / All), name/phone/email search, pagination and a CSV
 * export — the same shape as messages.php and serve-applications.php. When
 * the Upcoming filter is chosen the soonest visits are listed first.
 *
 * Every row offers a tel link, a wa.me link prefilled with a welcome message
 * that greets the visitor by first name, a mailto link, the visit date, how
 * they heard about us, a prayer-need preview, the relative submitted time and
 * a status chip. Row actions post to first-timer-action.php with a CSRF token
 * (Mark contacted / Reopen / Delete); Delete is confirmed through the shared
 * dialog in admin.js (data-confirm attributes).
 *
 * "Export CSV" streams the whole filtered list (not just the current page)
 * as UTF-8 with a BOM, neutralising spreadsheet formula injection.
 *
 * Every query is a prepared statement and every value is escaped on output.
 * The first_timers table already exists and is never created or altered. */
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

/* Whitelisted status tabs and visit-date filters. */
$ftStatuses = array('new', 'contacted');
$ftVisits = array('upcoming', 'past', 'all');

/* Safe scalar getter for query-string values (arrays are rejected). */
function ft_get_string($key) {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) {
        return '';
    }
    return (string)$_GET[$key];
}

/* Normalised E.164 phone ("" when missing or invalid) — used to hide the
 * tel/WhatsApp buttons for unusable numbers. */
function ft_phone_usable($phone) {
    $raw = (string)$phone;
    if (function_exists('kci_normalize_phone')) {
        try {
            $normalized = kci_normalize_phone($raw);
        } catch (Throwable $ignored) {
            $normalized = null;
        }
        if (is_string($normalized) && $normalized !== '') {
            return $normalized;
        }
    }
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits !== '' && strlen($digits) >= 8) {
        return '+' . $digits;
    }
    return '';
}

/* WhatsApp digits: normalised international digits via kci_normalize_phone()
 * when available (with the "+" stripped), otherwise digits only. */
function ft_wa_digits($phone) {
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

/* First word of a display name ("Mary Jane" -> "Mary") for greetings. */
function ft_first_name($fullName) {
    $fullName = trim((string)$fullName);
    return ($fullName !== '') ? trim(strtok($fullName, ' ')) : 'there';
}

/* Prefilled WhatsApp welcome message that greets the visitor by first name. */
function ft_wa_url($digits, $fullName) {
    $text = 'Hello ' . ft_first_name($fullName)
        . ', welcome to Kingdomite Church International! Thank you for filling in the first-timer form — we are so glad you are coming. Our welcome team will reach out to you shortly. See you in church!';
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}

/* Prefilled welcome email. rawurlencode() keeps spaces as %20 (never "+"). */
function ft_mail_url($email, $fullName) {
    $subject = 'Welcome to Kingdomite Church International';
    $body = 'Hello ' . ft_first_name($fullName) . ",\n\nThank you for filling in the first-timer form. We have received your details and our welcome team is already getting ready to receive you.\n\nSee you in church!\n\nWarm regards,\nKingdomite Church International";
    return 'mailto:' . $email . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
}

/* First $length characters of a text, with an ellipsis when trimmed. */
function ft_excerpt($text, $length = 90) {
    $text = (string)$text;
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length, 'UTF-8') . '…';
    }
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '…';
}

/* Spreadsheet formula injection guard: any cell starting with =, +, - or @
 * gets a leading single quote so Excel/Sheets treat it as plain text. */
function ft_csv_cell($value) {
    $value = (string)$value;
    if ($value !== '' && strpos($value, '=') === 0) { return "'" . $value; }
    if ($value !== '' && strpos($value, '+') === 0) { return "'" . $value; }
    if ($value !== '' && strpos($value, '-') === 0) { return "'" . $value; }
    if ($value !== '' && strpos($value, '@') === 0) { return "'" . $value; }
    return $value;
}

/* Current filters as a query string (tabs, visit filter, pagination). */
function ft_filter_query($statusFilter, $visitFilter, $search, $page) {
    $q = array();
    if ($statusFilter !== '') {
        $q['status'] = $statusFilter;
    }
    if ($visitFilter !== 'all') {
        $q['visit'] = $visitFilter;
    }
    if ($search !== '') {
        $q['q'] = $search;
    }
    if ($page > 1) {
        $q['page'] = $page;
    }
    return http_build_query($q);
}

function ft_page_url($statusFilter, $visitFilter, $search, $page) {
    $qs = ft_filter_query($statusFilter, $visitFilter, $search, $page);
    return 'first-timers.php' . ($qs !== '' ? '?' . $qs : '');
}

/* --- Filters (status and visit are whitelisted, search is length-capped) --- */
$statusFilter = strtolower(trim(ft_get_string('status')));
if ($statusFilter === 'all') {
    $statusFilter = '';
}
if ($statusFilter !== '' && !in_array($statusFilter, $ftStatuses, true)) {
    $statusFilter = '';
}

$visitFilter = strtolower(trim(ft_get_string('visit')));
if (!in_array($visitFilter, $ftVisits, true)) {
    $visitFilter = 'all';
}

$search = trim(ft_get_string('q'));
if (function_exists('mb_substr')) {
    $search = mb_substr($search, 0, 100, 'UTF-8');
} else {
    $search = substr($search, 0, 100);
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$perPage = 20;

/* Shared WHERE fragments (bound flags keep the parameter list fixed, so one
 * bind_param call serves every filter combination). */
$whereSearch = '(? = \'\' OR f.full_name LIKE ? OR f.phone LIKE ? OR f.email LIKE ?)';
$whereVisit = '(? = \'1\' OR (f.visit_date IS NOT NULL'
    . ' AND ((? = \'1\' AND f.visit_date >= CURDATE())'
    . ' OR (? = \'1\' AND f.visit_date < CURDATE()))))';
$whereBase = $whereVisit . ' AND ' . $whereSearch;
$whereFull = '(? = \'\' OR f.status = ?) AND ' . $whereBase;

/* LIKE pattern with wildcard characters escaped (the prepared statement
 * already keeps quotes safe). */
$likePattern = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $search) . '%';
$hasStatus = ($statusFilter === '') ? '' : '1';
$hasSearch = ($search === '') ? '' : '1';
$visitAll = ($visitFilter === 'all') ? '1' : '';
$visitUpcoming = ($visitFilter === 'upcoming') ? '1' : '';
$visitPast = ($visitFilter === 'past') ? '1' : '';

/* Ordering: upcoming visits soonest-first (when that filter is chosen), past
 * visits most-recent-first, otherwise newest submission first. Built only
 * from whitelisted values, so it is safe to concatenate. */
if ($visitFilter === 'upcoming') {
    $orderBy = 'f.visit_date ASC, f.created_at DESC, f.id DESC';
} elseif ($visitFilter === 'past') {
    $orderBy = 'f.visit_date DESC, f.created_at DESC, f.id DESC';
} else {
    $orderBy = 'f.created_at DESC, f.id DESC';
}

/* --- CSV export: the whole filtered list, streamed before any HTML --- */
$exportRequested = (ft_get_string('export') === 'csv');
if ($exportRequested) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="kingdomite-first-timers-' . date('Ymd') . '.csv"');
    $csvOut = fopen('php://output', 'w');
    if ($csvOut !== false) {
        fwrite($csvOut, "\xEF\xBB\xBF"); /* UTF-8 BOM for Excel. */
        fputcsv($csvOut, array('ID', 'Name', 'Phone', 'Email', 'Visit date', 'Heard from', 'Prayer need', 'Status', 'Contacted at', 'Submitted'), ',', '"', '\\');
        try {
            $xstmt = $conn->prepare(
                'SELECT f.id, f.full_name, f.phone, f.email, f.visit_date, f.heard_from, f.prayer_need, f.status, f.contacted_at, f.created_at'
                . ' FROM first_timers f'
                . ' WHERE ' . $whereFull
                . ' ORDER BY ' . $orderBy
            );
            if ($xstmt !== false) {
                $xstmt->bind_param(
                    'sssssssss',
                    $hasStatus, $statusFilter,
                    $visitAll, $visitUpcoming, $visitPast,
                    $hasSearch, $likePattern, $likePattern, $likePattern
                );
                $xstmt->execute();
                $xres = $xstmt->get_result();
                if ($xres !== false) {
                    while ($xrow = $xres->fetch_assoc()) {
                        $xTs = strtotime((string)$xrow['created_at']);
                        $xContactedTs = ($xrow['contacted_at'] !== null && $xrow['contacted_at'] !== '')
                            ? strtotime((string)$xrow['contacted_at'])
                            : false;
                        fputcsv($csvOut, array(
                            ft_csv_cell((string)(int)$xrow['id']),
                            ft_csv_cell((string)$xrow['full_name']),
                            ft_csv_cell((string)($xrow['phone'] !== null ? $xrow['phone'] : '')),
                            ft_csv_cell((string)($xrow['email'] !== null ? $xrow['email'] : '')),
                            ft_csv_cell((string)($xrow['visit_date'] !== null ? $xrow['visit_date'] : '')),
                            ft_csv_cell((string)($xrow['heard_from'] !== null ? $xrow['heard_from'] : '')),
                            ft_csv_cell((string)($xrow['prayer_need'] !== null ? $xrow['prayer_need'] : '')),
                            ft_csv_cell((string)$xrow['status']),
                            ft_csv_cell(($xContactedTs !== false) ? date('Y-m-d H:i:s', $xContactedTs) : ''),
                            ft_csv_cell(($xTs !== false) ? date('Y-m-d H:i:s', $xTs) : (string)$xrow['created_at']),
                        ), ',', '"', '\\');
                    }
                }
                $xstmt->close();
            }
        } catch (Throwable $ignored) {
            /* Partial file already streamed; nothing else to do. */
        }
    }
    fclose($csvOut);
    exit;
}

/* --- Live counts per tab (respecting search + visit filter) --- */
$tabCounts = array('all' => 0, 'new' => 0, 'contacted' => 0);
try {
    $cstmt = $conn->prepare(
        'SELECT f.status AS st, COUNT(*) AS c FROM first_timers f'
        . ' WHERE ' . $whereBase
        . ' GROUP BY f.status'
    );
    if ($cstmt !== false) {
        $cstmt->bind_param('sssssss', $visitAll, $visitUpcoming, $visitPast, $hasSearch, $likePattern, $likePattern, $likePattern);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres !== false) {
            while ($crow = $cres->fetch_assoc()) {
                if (in_array((string)$crow['st'], $ftStatuses, true)) {
                    $tabCounts[(string)$crow['st']] = (int)$crow['c'];
                    $tabCounts['all'] += (int)$crow['c'];
                }
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
    $tstmt = $conn->prepare('SELECT COUNT(*) AS c FROM first_timers f WHERE ' . $whereFull);
    if ($tstmt !== false) {
        $tstmt->bind_param(
            'sssssssss',
            $hasStatus, $statusFilter,
            $visitAll, $visitUpcoming, $visitPast,
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

/* --- Page of rows (upcoming visits soonest-first when that filter is on) --- */
$rows = array();
try {
    $lstmt = $conn->prepare(
        'SELECT f.id, f.full_name, f.phone, f.email, f.visit_date, f.heard_from, f.prayer_need, f.status, f.contacted_at, f.created_at'
        . ' FROM first_timers f'
        . ' WHERE ' . $whereFull
        . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?'
    );
    if ($lstmt !== false) {
        $lstmt->bind_param(
            'sssssssssii',
            $hasStatus, $statusFilter,
            $visitAll, $visitUpcoming, $visitPast,
            $hasSearch, $likePattern, $likePattern, $likePattern,
            $perPage, $offset
        );
        $lstmt->execute();
        $lres = $lstmt->get_result();
        if ($lres !== false) {
            while ($lrow = $lres->fetch_assoc()) {
                $rows[] = $lrow;
            }
        }
        $lstmt->close();
    }
} catch (Throwable $ignored) {
    $rows = array();
}

$returnTarget = ft_page_url($statusFilter, $visitFilter, $search, $page);
$chipClasses = array('new' => 'kci-chip--amber', 'contacted' => 'kci-chip--blue');

/* Export link keeps the current filters (the export ignores the page). */
$csvQuery = array('export' => 'csv');
if ($statusFilter !== '') {
    $csvQuery['status'] = $statusFilter;
}
if ($visitFilter !== 'all') {
    $csvQuery['visit'] = $visitFilter;
}
if ($search !== '') {
    $csvQuery['q'] = $search;
}
$csvUrl = 'first-timers.php?' . http_build_query($csvQuery);

$pageTitle = 'First-Timers';
$activeNav = 'firsttimers';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page kci-ftlist">
<div class="kci-card">
<div class="kci-msglist__head">
<div>
<h2 class="kci-serve__heading"><i class="fa-solid fa-user-check" aria-hidden="true"></i> First-time visitors</h2>
<p class="kci-muted"><?php echo (int)$total; ?> visitor<?php echo ((int)$total === 1) ? '' : 's'; ?> found</p>
</div>
<div class="kci-msglist__toolbar">
<a class="kci-pillbtn kci-pillbtn--outline" href="<?php echo e($csvUrl); ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
</div>
</div>
<nav class="kci-tabs" aria-label="Filter by contact status">
<?php $tabDefs = array('all' => 'All', 'new' => 'New', 'contacted' => 'Contacted'); ?>
<?php foreach ($tabDefs as $tabValue => $tabLabel): ?>
<?php
    $tabStatus = ($tabValue === 'all') ? '' : $tabValue;
    $tabUrl = ft_page_url($tabStatus, $visitFilter, $search, 1);
    $tabActive = ($statusFilter === $tabStatus);
?>
<a class="kci-tabs__item<?php echo $tabActive ? ' is-active' : ''; ?>"<?php echo $tabActive ? ' aria-current="page"' : ''; ?> href="<?php echo e($tabUrl); ?>"><?php echo e($tabLabel); ?> <span class="kci-tabs__count"><?php echo (int)$tabCounts[$tabValue]; ?></span></a>
<?php endforeach; ?>
</nav>
<form method="get" action="first-timers.php" class="kci-serve__filters" role="search">
<?php if ($statusFilter !== ''): ?>
<input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">
<?php endif; ?>
<label class="kci-serve__search">
<span class="kci-serve__search-label">Search visitors</span>
<span class="kci-serve__search-box">
<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
<input type="search" name="q" maxlength="100" autocomplete="off" placeholder="Name, phone or email" value="<?php echo e($search); ?>">
</span>
</label>
<label class="kci-serve__ministry">
<span class="kci-serve__search-label">Visit date</span>
<select name="visit">
<option value="all"<?php echo ($visitFilter === 'all') ? ' selected' : ''; ?>>All visits</option>
<option value="upcoming"<?php echo ($visitFilter === 'upcoming') ? ' selected' : ''; ?>>Upcoming</option>
<option value="past"<?php echo ($visitFilter === 'past') ? ' selected' : ''; ?>>Past</option>
</select>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
<?php if ($search !== '' || $visitFilter !== 'all'): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="<?php echo e(ft_page_url($statusFilter, '', '', 1)); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
<?php endif; ?>
</form>
</div>

<?php if (empty($rows)): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span>
<h2>No first-timers found</h2>
<p class="kci-muted"><?php echo ($tabCounts['all'] === 0) ? 'No one has filled in the First-Timer Form yet. Submissions from the website will appear here.' : 'There is nothing here under these filters yet. Try a different search, pick another visit window, or clear the filters to see everything.'; ?></p>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="first-timers.php"><i class="fa-solid fa-xmark"></i> Clear all filters</a>
</div>
<?php else: ?>
<div class="kci-tablewrap kci-serve__wrap kci-ftlist__wrap">
<table class="kci-table kci-serve__table kci-ftlist__table">
<thead>
<tr>
<th scope="col">Visitor</th>
<th scope="col">Visit date</th>
<th scope="col">Heard from</th>
<th scope="col">Prayer need</th>
<th scope="col">Submitted</th>
<th scope="col">Status</th>
<th scope="col">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
<?php
    $ftId = (int)$row['id'];
    $rowName = (string)$row['full_name'];
    $rowEmail = (string)($row['email'] !== null ? $row['email'] : '');
    $rowPhone = (string)($row['phone'] !== null ? $row['phone'] : '');
    $rowStatus = (string)$row['status'];
    if (!in_array($rowStatus, $ftStatuses, true)) {
        $rowStatus = 'new';
    }
    $phoneUsable = ft_phone_usable($rowPhone);
    $waDigits = ft_wa_digits($phoneUsable);
    $visitDate = (string)($row['visit_date'] !== null ? $row['visit_date'] : '');
    $visitTs = ($visitDate !== '') ? strtotime($visitDate) : false;
    $visitLabel = ($visitTs !== false) ? date('d M Y', $visitTs) : '—';
    $visitTitle = ($visitTs !== false) ? date('l, F j, Y', $visitTs) : 'No visit date given';
    $visitDay = ($visitTs !== false) ? date('Y-m-d', $visitTs) : '';
    $isUpcoming = ($visitDay !== '' && $visitDay >= date('Y-m-d'));
    $visitChip = $isUpcoming ? 'kci-chip--green' : 'kci-chip--grey';
    $heardFrom = (string)($row['heard_from'] !== null ? $row['heard_from'] : '');
    $prayerNeed = (string)($row['prayer_need'] !== null ? $row['prayer_need'] : '');
    $prayerPreview = ($prayerNeed === '') ? '—' : ft_excerpt($prayerNeed, 80);
    $rowTs = strtotime((string)$row['created_at']);
    $rowFull = ($rowTs !== false) ? date('d M Y, g:ia', $rowTs) : (string)$row['created_at'];
    $rowRel = admin_relative_time($row['created_at']);
    $chipClass = isset($chipClasses[$rowStatus]) ? $chipClasses[$rowStatus] : 'kci-chip--grey';
?>
<tr>
<td data-label="Visitor">
<span class="kci-serve__name"><?php echo e($rowName); ?></span>
<span class="kci-msglist__email" title="<?php echo e($rowEmail); ?>"><?php echo e($rowEmail !== '' ? $rowEmail : (($rowPhone !== '') ? $rowPhone : '—')); ?></span>
<span class="kci-serve__contact">
<?php if ($rowEmail !== ''): ?>
<a class="kci-iconbtn" href="<?php echo e(ft_mail_url($rowEmail, $rowName)); ?>" title="Email <?php echo e($rowEmail); ?>" aria-label="Email <?php echo e($rowName); ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($phoneUsable !== ''): ?>
<a class="kci-iconbtn" href="tel:<?php echo e($phoneUsable); ?>" title="Call <?php echo e($rowPhone); ?>" aria-label="Call <?php echo e($rowName); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($phoneUsable !== '' && $waDigits !== ''): ?>
<a class="kci-iconbtn" href="<?php echo e(ft_wa_url($waDigits, $rowName)); ?>" target="_blank" rel="noopener" title="WhatsApp <?php echo e($rowPhone); ?>" aria-label="WhatsApp <?php echo e($rowName); ?>"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
<?php endif; ?>
</span>
</td>
<td data-label="Visit date"><span title="<?php echo e($visitTitle); ?>"><?php echo e($visitLabel); ?><?php if ($visitDay !== ''): ?> <span class="kci-chip <?php echo e($visitChip); ?>"><?php echo $isUpcoming ? 'Upcoming' : 'Past'; ?></span><?php endif; ?></span></td>
<td data-label="Heard from"><span class="kci-ftlist__heard" title="<?php echo e($heardFrom); ?>"><?php echo e(($heardFrom !== '') ? $heardFrom : '—'); ?></span></td>
<td data-label="Prayer need" class="kci-ftlist__prayer"><span class="kci-msglist__preview" title="<?php echo e($prayerNeed); ?>"><?php echo e($prayerPreview); ?></span></td>
<td data-label="Submitted" class="kci-msglist__time" title="<?php echo e($rowFull); ?>"><?php echo e($rowRel); ?></td>
<td data-label="Status"><span class="kci-chip <?php echo e($chipClass); ?>"><?php echo ($rowStatus === 'new') ? 'New' : 'Contacted'; ?></span></td>
<td data-label="Actions">
<div class="kci-serve__rowactions">
<a class="kci-pillbtn kci-pillbtn--blue" href="first-timer.php?id=<?php echo $ftId; ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a>
<?php if ($rowStatus === 'new'): ?>
<form method="post" action="first-timer-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="contacted">
<input type="hidden" name="id" value="<?php echo $ftId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--blue"><i class="fa-solid fa-headset" aria-hidden="true"></i> Mark contacted</button>
</form>
<?php else: ?>
<form method="post" action="first-timer-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="reopen">
<input type="hidden" name="id" value="<?php echo $ftId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--amber"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reopen</button>
</form>
<?php endif; ?>
<form method="post" action="first-timer-action.php" class="kci-serve__form" data-confirm-title="Delete this visitor?" data-confirm="Permanently delete the first-timer record from <?php echo e($rowName); ?>? This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo $ftId; ?>">
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
<nav class="kci-pagination" aria-label="First-timers pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="<?php echo e(ft_page_url($statusFilter, $visitFilter, $search, $page - 1)); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php
    $winStart = max(1, $page - 2);
    $winEnd = min($pages, $page + 2);
    for ($pager = $winStart; $pager <= $winEnd; $pager++):
?>
<?php if ($pager === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pager; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="<?php echo e(ft_page_url($statusFilter, $visitFilter, $search, $pager)); ?>"><?php echo (int)$pager; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="<?php echo e(ft_page_url($statusFilter, $visitFilter, $search, $page + 1)); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>