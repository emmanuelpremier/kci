<?php
/* admin/messages.php — list the contact messages sent from the public
 * Contact form.
 *
 * Read-status tabs with live counts, name/email/subject/message search,
 * newest-first ordering and 20-per-page pagination. Row actions post to
 * message-action.php with a CSRF token (Mark read / Mark unread / Delete);
 * Delete and "Mark all as read" are confirmed through the shared dialog in
 * admin.js (data-confirm attributes). Contact buttons offer a prefilled
 * mailto reply, a tel link and a wa.me link; the phone links are hidden
 * when the stored phone is missing or invalid (kci_normalize_phone()).
 *
 * "Export CSV" streams the whole filtered list (not just the current page)
 * as UTF-8 with a BOM, neutralising spreadsheet formula injection.
 *
 * Every query is a prepared statement and every value is escaped on output.
 * The contact_messages table already exists and is never created or altered. */
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

/* Whitelisted read-status filters (mirrors the serve/giving status tabs). */
$msgStatuses = array('unread', 'read');

/* Safe scalar getter for query-string values (arrays are rejected). */
function messages_get_string($key) {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) {
        return '';
    }
    return (string)$_GET[$key];
}

/* Normalised E.164 phone ("" when missing or invalid) — used to hide the
 * tel/WhatsApp buttons for unusable numbers. */
function messages_phone_usable($phone) {
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
function messages_wa_digits($phone) {
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
function messages_first_name($fullName) {
    $fullName = trim((string)$fullName);
    return ($fullName !== '') ? trim(strtok($fullName, ' ')) : 'there';
}

/* Prefilled reply: subject "Re: <subject>", body greeting the sender by
 * first name and signing off "Kingdomite Church International".
 * rawurlencode() keeps spaces as %20 (never "+"). */
function messages_reply_url($email, $subject, $fullName) {
    $replySubject = 'Re: ' . (string)$subject;
    $body = 'Hello ' . messages_first_name($fullName) . ",\n\nThank you for getting in touch with Kingdomite Church International. We have received your message and our team will reply as soon as possible.\n\nWarm regards,\nKingdomite Church International";
    return 'mailto:' . $email . '?subject=' . rawurlencode($replySubject) . '&body=' . rawurlencode($body);
}

/* Prefilled WhatsApp message to the sender. */
function messages_wa_url($digits, $fullName) {
    $text = 'Hello ' . messages_first_name($fullName) . ', this is Kingdomite Church International. Thank you for contacting us — we will get back to you shortly.';
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}

/* Current filters as a query string (tabs, pagination and the return target). */
function messages_filter_query($statusFilter, $search, $page) {
    $q = array();
    if ($statusFilter !== '') {
        $q['status'] = $statusFilter;
    }
    if ($search !== '') {
        $q['q'] = $search;
    }
    if ($page > 1) {
        $q['page'] = $page;
    }
    return http_build_query($q);
}

function messages_page_url($statusFilter, $search, $page) {
    $qs = messages_filter_query($statusFilter, $search, $page);
    return 'messages.php' . ($qs !== '' ? '?' . $qs : '');
}

/* First $length characters of the message, with an ellipsis when trimmed. */
function messages_excerpt($message, $length = 90) {
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

/* Spreadsheet formula injection guard: any cell starting with =, +, - or @
 * gets a leading single quote so Excel/Sheets treat it as plain text. */
function messages_csv_cell($value) {
    $value = (string)$value;
    if ($value !== '' && strpos($value, '=') === 0) {
        return "'" . $value;
    }
    if ($value !== '' && strpos($value, '+') === 0) {
        return "'" . $value;
    }
    if ($value !== '' && strpos($value, '-') === 0) {
        return "'" . $value;
    }
    if ($value !== '' && strpos($value, '@') === 0) {
        return "'" . $value;
    }
    return $value;
}

/* --- Filters (status is whitelisted, search is length-capped) --- */
$statusFilter = strtolower(trim(messages_get_string('status')));
if ($statusFilter === 'all') {
    $statusFilter = '';
}
if ($statusFilter !== '' && !in_array($statusFilter, $msgStatuses, true)) {
    $statusFilter = '';
}

$search = trim(messages_get_string('q'));
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
$whereBase = '(? = \'\' OR m.full_name LIKE ? OR m.email LIKE ? OR m.subject LIKE ? OR m.message LIKE ?)';
$whereFull = '(? = \'\' OR m.is_read = ?) AND ' . $whereBase;

/* LIKE pattern with wildcard characters escaped (the prepared statement
 * already keeps quotes safe). */
$likePattern = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $search) . '%';
$hasStatus = ($statusFilter === '') ? '' : '1';
$hasSearch = ($search === '') ? '' : '1';
$statusValue = ($statusFilter === '') ? '' : (($statusFilter === 'unread') ? '0' : '1');

/* --- CSV export: the whole filtered list, streamed before any HTML --- */
$exportRequested = (messages_get_string('export') === 'csv');
if ($exportRequested) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="kingdomite-messages-' . date('Ymd') . '.csv"');
    $csvOut = fopen('php://output', 'w');
    if ($csvOut !== false) {
        fwrite($csvOut, "\xEF\xBB\xBF"); /* UTF-8 BOM for Excel. */
        fputcsv($csvOut, array('ID', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Received', 'Read'), ',', '"', '\\');
        try {
            $xstmt = $conn->prepare(
                'SELECT m.id, m.full_name, m.email, m.phone, m.subject, m.message, m.is_read, m.created_at'
                . ' FROM contact_messages m'
                . ' WHERE ' . $whereFull
                . ' ORDER BY m.created_at DESC, m.id DESC'
            );
            if ($xstmt !== false) {
                $xstmt->bind_param('sssssss', $hasStatus, $statusValue, $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern);
                $xstmt->execute();
                $xres = $xstmt->get_result();
                if ($xres !== false) {
                    while ($xrow = $xres->fetch_assoc()) {
                        $xTs = strtotime((string)$xrow['created_at']);
                        fputcsv($csvOut, array(
                            messages_csv_cell((string)(int)$xrow['id']),
                            messages_csv_cell((string)$xrow['full_name']),
                            messages_csv_cell((string)$xrow['email']),
                            messages_csv_cell((string)($xrow['phone'] !== null ? $xrow['phone'] : '')),
                            messages_csv_cell((string)$xrow['subject']),
                            messages_csv_cell((string)$xrow['message']),
                            messages_csv_cell(($xTs !== false) ? date('Y-m-d H:i:s', $xTs) : (string)$xrow['created_at']),
                            messages_csv_cell(((string)$xrow['is_read'] === '1') ? 'Yes' : 'No'),
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

/* --- Live counts per tab (respecting search, ignoring status) --- */
$tabCounts = array('all' => 0, 'unread' => 0, 'read' => 0);
try {
    $cstmt = $conn->prepare(
        'SELECT m.is_read AS st, COUNT(*) AS c FROM contact_messages m'
        . ' WHERE ' . $whereBase
        . ' GROUP BY m.is_read'
    );
    if ($cstmt !== false) {
        $cstmt->bind_param('sssss', $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres !== false) {
            while ($crow = $cres->fetch_assoc()) {
                $crowKey = ((string)$crow['st'] === '1') ? 'read' : 'unread';
                $tabCounts[$crowKey] = (int)$crow['c'];
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
    $tstmt = $conn->prepare('SELECT COUNT(*) AS c FROM contact_messages m WHERE ' . $whereFull);
    if ($tstmt !== false) {
        $tstmt->bind_param(
            'sssssss',
            $hasStatus, $statusValue,
            $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern
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

/* --- Newest-first page of rows --- */
$rows = array();
try {
    $lstmt = $conn->prepare(
        'SELECT m.id, m.full_name, m.email, m.phone, m.subject, m.message, m.is_read, m.created_at'
        . ' FROM contact_messages m'
        . ' WHERE ' . $whereFull
        . ' ORDER BY m.created_at DESC, m.id DESC LIMIT ? OFFSET ?'
    );
    if ($lstmt !== false) {
        $lstmt->bind_param(
            'sssssssii',
            $hasStatus, $statusValue,
            $hasSearch, $likePattern, $likePattern, $likePattern, $likePattern,
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

$returnTarget = messages_page_url($statusFilter, $search, $page);

/* Export link keeps the current filters (the export ignores the page). */
$csvQuery = array('export' => 'csv');
if ($statusFilter !== '') {
    $csvQuery['status'] = $statusFilter;
}
if ($search !== '') {
    $csvQuery['q'] = $search;
}
$csvUrl = 'messages.php?' . http_build_query($csvQuery);

$pageTitle = 'Messages';
$activeNav = 'messages';
include __DIR__ . '/includes/layout_top.php';
?>
<style>
/* Messages list only (scoped by .kci-msgtable): keep the sender name and its
   "New" badge on one line, and give the Sender column a little more width so
   longer names plus the badge fit without wrapping. */
.kci-msgtable th:first-child,
.kci-msgtable td[data-label="Sender"] { min-width: 210px; }
.kci-msgtable td[data-label="Sender"] .kci-serve__name {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 8px;
}
.kci-msgtable td[data-label="Sender"] .kci-serve__name > .kci-msgtable__nm {
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kci-msgtable td[data-label="Sender"] .kci-serve__name > .kci-chip { flex: none; }
/* Mobile card mode (mirrors the shared 900px stacked-card breakpoint):
   long sender names wrap safely instead of clipping with an ellipsis. */
@media (max-width: 900px) {
    .kci-msgtable td[data-label="Sender"] .kci-serve__name { flex-wrap: wrap; }
    .kci-msgtable td[data-label="Sender"] .kci-serve__name > .kci-msgtable__nm {
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
    }
}
</style>
<section class="kci-page kci-serve">
<div class="kci-card">
<div class="kci-serve__head">
<div>
<h2 class="kci-serve__heading"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact messages</h2>
<p class="kci-muted"><?php echo (int)$total; ?> message<?php echo ((int)$total === 1) ? '' : 's'; ?> found</p>
</div>
<div class="kci-actions">
<form method="post" action="message-action.php" class="kci-serve__form" data-confirm-title="Mark all as read?" data-confirm="Mark every unread message as read? You can still mark them unread afterwards.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="read_all">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--violet"><i class="fa-solid fa-check-double" aria-hidden="true"></i> Mark all as read</button>
</form>
<a class="kci-pillbtn kci-pillbtn--outline" href="<?php echo e($csvUrl); ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
</div>
</div>
<nav class="kci-tabs" aria-label="Filter by read status">
<?php $tabDefs = array('all' => 'All', 'unread' => 'Unread', 'read' => 'Read'); ?>
<?php foreach ($tabDefs as $tabValue => $tabLabel): ?>
<?php
    $tabStatus = ($tabValue === 'all') ? '' : $tabValue;
    $tabUrl = messages_page_url($tabStatus, $search, 1);
    $tabActive = ($statusFilter === $tabStatus);
?>
<a class="kci-tabs__item<?php echo $tabActive ? ' is-active' : ''; ?>"<?php echo $tabActive ? ' aria-current="page"' : ''; ?> href="<?php echo e($tabUrl); ?>"><?php echo e($tabLabel); ?> <span class="kci-tabs__count"><?php echo (int)$tabCounts[$tabValue]; ?></span></a>
<?php endforeach; ?>
</nav>
<form method="get" action="messages.php" class="kci-serve__filters" role="search">
<?php if ($statusFilter !== ''): ?>
<input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">
<?php endif; ?>
<label class="kci-serve__search">
<span class="kci-serve__search-label">Search messages</span>
<span class="kci-serve__search-box">
<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
<input type="search" name="q" maxlength="100" autocomplete="off" placeholder="Name, email, subject or message" value="<?php echo e($search); ?>">
</span>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
<?php if ($search !== ''): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="<?php echo e(messages_page_url($statusFilter, '', 1)); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
<?php endif; ?>
</form>
</div>

<?php if (empty($rows)): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></span>
<h2>No messages found</h2>
<p class="kci-muted"><?php echo ($tabCounts['all'] === 0) ? 'No one has written to you yet. Messages sent through the Contact form on the website will appear here.' : 'There is nothing here under these filters yet. Try a different search or clear the filters to see everything.'; ?></p>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="messages.php"><i class="fa-solid fa-xmark"></i> Clear all filters</a>
</div>
<?php else: ?>
<div class="kci-tablewrap kci-serve__wrap">
<table class="kci-table kci-serve__table kci-msgtable">
<thead>
<tr>
<th scope="col">Sender</th>
<th scope="col">Email</th>
<th scope="col">Message</th>
<th scope="col">Received</th>
<th scope="col">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
<?php
    $msgId = (int)$row['id'];
    $rowUnread = ((string)$row['is_read'] === '0');
    $rowName = (string)$row['full_name'];
    $rowEmail = (string)$row['email'];
    $rowPhone = (string)($row['phone'] !== null ? $row['phone'] : '');
    $rowSubject = (string)$row['subject'];
    $rowMessage = (string)$row['message'];
    $preview = messages_excerpt($rowMessage, 90);
    $phoneUsable = messages_phone_usable($rowPhone);
    $waDigits = messages_wa_digits($phoneUsable);
    $rowTs = strtotime((string)$row['created_at']);
    $rowFull = ($rowTs !== false) ? date('d M Y, g:ia', $rowTs) : (string)$row['created_at'];
    $rowRel = admin_relative_time($row['created_at']);
?>
<tr>
<td data-label="Sender">
<span class="kci-serve__name"><span class="kci-msgtable__nm"><?php echo e($rowName); ?></span><?php if ($rowUnread): ?> <span class="kci-chip kci-chip--blue">New</span><?php endif; ?></span>
<span class="kci-serve__contact">
<?php if ($rowEmail !== ''): ?>
<a class="kci-iconbtn" href="<?php echo e(messages_reply_url($rowEmail, $rowSubject, $rowName)); ?>" title="Reply by email to <?php echo e($rowEmail); ?>" aria-label="Reply by email to <?php echo e($rowName); ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($phoneUsable !== ''): ?>
<a class="kci-iconbtn" href="tel:<?php echo e($phoneUsable); ?>" title="Call <?php echo e($phoneUsable); ?>" aria-label="Call <?php echo e($rowName); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($phoneUsable !== '' && $waDigits !== ''): ?>
<a class="kci-iconbtn" href="<?php echo e(messages_wa_url($waDigits, $rowName)); ?>" target="_blank" rel="noopener" title="WhatsApp <?php echo e($phoneUsable); ?>" aria-label="WhatsApp <?php echo e($rowName); ?>"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($rowEmail === '' && $phoneUsable === ''): ?>
<span class="kci-muted">—</span>
<?php endif; ?>
</span>
</td>
<td data-label="Email"><span class="kci-serve__message" title="<?php echo e($rowEmail); ?>"><?php echo e($rowEmail !== '' ? $rowEmail : '—'); ?></span></td>
<td data-label="Message">
<span class="kci-serve__name"><?php echo e($rowSubject !== '' ? $rowSubject : '(no subject)'); ?></span>
<span class="kci-muted"><?php echo e($preview !== '' ? $preview : '—'); ?></span>
</td>
<td data-label="Received"><span title="<?php echo e($rowFull); ?>"><?php echo e($rowRel); ?></span></td>
<td data-label="Actions">
<div class="kci-serve__rowactions">
<a class="kci-pillbtn kci-pillbtn--blue" href="message.php?id=<?php echo $msgId; ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a>
<?php if ($rowUnread): ?>
<form method="post" action="message-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="read">
<input type="hidden" name="id" value="<?php echo $msgId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--green"><i class="fa-solid fa-envelope-open" aria-hidden="true"></i> Mark read</button>
</form>
<?php else: ?>
<form method="post" action="message-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="unread">
<input type="hidden" name="id" value="<?php echo $msgId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--amber"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Mark unread</button>
</form>
<?php endif; ?>
<form method="post" action="message-action.php" class="kci-serve__form" data-confirm-title="Delete this message?" data-confirm="Permanently delete the message from <?php echo e($rowName); ?>? This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo $msgId; ?>">
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
<?php
    $from = ($total === 0) ? 0 : $offset + 1;
    $to = min($offset + $perPage, $total);
?>
<div class="kci-serve__pager">
<p class="kci-muted">Showing <?php echo (int)$from; ?>–<?php echo (int)$to; ?> of <?php echo (int)$total; ?></p>
<?php if ($pages > 1): ?>
<nav class="kci-pagination" aria-label="Messages pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="<?php echo e(messages_page_url($statusFilter, $search, $page - 1)); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php
    $winStart = max(1, $page - 2);
    $winEnd = min($pages, $page + 2);
    for ($pager = $winStart; $pager <= $winEnd; $pager++):
?>
<?php if ($pager === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pager; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="<?php echo e(messages_page_url($statusFilter, $search, $pager)); ?>"><?php echo (int)$pager; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="<?php echo e(messages_page_url($statusFilter, $search, $page + 1)); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>

