<?php
/* admin/first-timer.php — view one first-time visitor (form.php submission)
 * with their full details, contact buttons, previous/next navigation and the
 * Mark contacted / Reopen / Delete actions.
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

/* Whitelisted status values (mirrors first-timers.php). */
$ftStatuses = array('new', 'contacted');

/* Valid id: positive integer (prevents 0, negative, non-numeric and huge). */
$ftId = 0;
if (isset($_GET['id']) && is_string($_GET['id'])) {
    if (preg_match('/^[1-9][0-9]{0,9}$/', trim($_GET['id']))) {
        $ftId = (int)trim($_GET['id']);
    }
}

/* Load the visitor (if they exist). */
$ft = null;
if ($ftId > 0) {
    try {
        $stmt = $conn->prepare(
            'SELECT id, full_name, phone, email, visit_date, heard_from, prayer_need, status, contacted_at, created_at'
            . ' FROM first_timers WHERE id = ? LIMIT 1'
        );
        if ($stmt !== false) {
            $stmt->bind_param('i', $ftId);
            $stmt->execute();
            $res = $stmt->get_result();
            $ft = ($res !== false && $res->num_rows > 0) ? $res->fetch_assoc() : null;
            $stmt->close();
        }
    } catch (Throwable $ignored) {
        $ft = null;
    }
}

$ftStatus = 'new';
$ftContactedAt = null;
if (is_array($ft)) {
    if (in_array((string)$ft['status'], $ftStatuses, true)) {
        $ftStatus = (string)$ft['status'];
    }
    $ftContactedAt = ($ft['contacted_at'] !== null && $ft['contacted_at'] !== '') ? (string)$ft['contacted_at'] : null;
}

/* Previous/next navigation by adjacent id (never the same id). */
$prev = null;
$next = null;
if ($ftId > 0 && $ft !== null) {
    try {
        $stmt = $conn->prepare('SELECT id FROM first_timers WHERE id < ? ORDER BY id DESC LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $ftId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res !== false && $res->num_rows > 0) {
                $prev = (int)$res->fetch_assoc()['id'];
            }
            $stmt->close();
        }
    } catch (Throwable $ignored) {
    }
    try {
        $stmt = $conn->prepare('SELECT id FROM first_timers WHERE id > ? ORDER BY id ASC LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $ftId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res !== false && $res->num_rows > 0) {
                $next = (int)$res->fetch_assoc()['id'];
            }
            $stmt->close();
        }
    } catch (Throwable $ignored) {
    }
}

/* --- Contact helpers (mirrors first-timers.php) --- */
function ft_first_name($fullName) {
    $fullName = trim((string)$fullName);
    return ($fullName !== '') ? trim(strtok($fullName, ' ')) : 'there';
}

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

$returnTarget = 'first-timer.php?id=' . $ftId;
$backToList = 'first-timers.php';

$pageTitle = ($ft !== null) ? (string)$ft['full_name'] : 'First-Timer';
$activeNav = 'firsttimers';

/* Full dates for the title attributes. */
$visitFull = '';
$visitLabel = '';
$submittedFull = '';
$submittedRel = '';
$contactedRel = '';
$contactedFull = '';
if ($ft !== null) {
    $visitTs = ($ft['visit_date'] !== null && $ft['visit_date'] !== '') ? strtotime((string)$ft['visit_date']) : false;
    if ($visitTs !== false) {
        $visitLabel = date('d M Y', $visitTs);
        $visitFull = date('l, F j, Y', $visitTs);
    }
    $submitTs = strtotime((string)$ft['created_at']);
    $submittedFull = ($submitTs !== false) ? date('d M Y, g:ia', $submitTs) : (string)$ft['created_at'];
    $submittedRel = admin_relative_time($ft['created_at']);
    if ($ftContactedAt !== null) {
        $contactedTs = strtotime($ftContactedAt);
        $contactedFull = ($contactedTs !== false) ? date('d M Y, g:ia', $contactedTs) : $ftContactedAt;
        $contactedRel = admin_relative_time($ftContactedAt);
    }
}

include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<?php if ($ft === null): ?>
<div class="kci-serve__empty">
<div class="kci-serve__empty-card">
<i class="fa-solid fa-user-check kc-serve__empty-icon" aria-hidden="true"></i>
<h2>First-timer not found</h2>
<p>The visitor you are looking for does not exist or has been removed.</p>
<a class="kci-pillbtn kci-pillbtn--blue" href="<?php echo e($backToList); ?>">Back to first-timers</a>
</div>
</div>
<?php else: ?>
<?php
    $ftName = (string)$ft['full_name'];
    $ftEmail = (string)($ft['email'] !== null ? $ft['email'] : '');
    $ftPhone = (string)($ft['phone'] !== null ? $ft['phone'] : '');
    $ftHeard = (string)($ft['heard_from'] !== null ? $ft['heard_from'] : '');
    $ftPrayer = (string)($ft['prayer_need'] !== null ? $ft['prayer_need'] : '');
    $ftPhoneUsable = ft_phone_usable($ftPhone);
    $ftWaDigits = ft_wa_digits($ftPhoneUsable);
?>
<div class="kci-msgdetail kci-card">
<div class="kci-msgdetail__head">
<span class="kci-pillbtn kci-pillbtn--<?php echo ($ftStatus === 'contacted') ? 'blue' : 'amber'; ?>">
<?php echo ($ftStatus === 'contacted') ? 'Contacted' : 'New'; ?>
</span>
<h2>From <?php echo e($ftName); ?></h2>
<span class="kci-msgdetail__meta-label">Submitted</span>
<span class="kci-msgdetail__meta-value" title="<?php echo e($submittedFull); ?>"><?php echo e($submittedRel); ?></span>
</div>

<div class="kci-msgdetail__body">
<p class="kci-msgdetail__label">Visit date</p>
<p class="kci-msgdetail__value"><?php echo ($visitLabel !== '') ? e($visitLabel) : '<span class="kci-muted">No visit date given</span>'; ?><?php if ($visitLabel !== ''): ?> <span class="kci-chip <?php echo ((date('Y-m-d', $visitTs) >= date('Y-m-d')) ? 'kci-chip--green' : 'kci-chip--grey'); ?>"><?php echo ((date('Y-m-d', $visitTs) >= date('Y-m-d')) ? 'Upcoming' : 'Past'); ?></span><?php endif; ?></p>

<p class="kci-msgdetail__label">Phone</p>
<p class="kci-msgdetail__value"><?php echo ($ftPhone !== '') ? e($ftPhone) : '<span class="kci-muted">—</span>'; ?></p>

<p class="kci-msgdetail__label">Email</p>
<p class="kci-msgdetail__value"><?php echo ($ftEmail !== '') ? e($ftEmail) : '<span class="kci-muted">—</span>'; ?></p>

<p class="kci-msgdetail__label">How they heard about us</p>
<p class="kci-msgdetail__value"><?php echo ($ftHeard !== '') ? e($ftHeard) : '<span class="kci-muted">—</span>'; ?></p>

<p class="kci-msgdetail__label">Prayer need</p>
<?php if ($ftPrayer !== ''): ?>
<div class="kci-msgdetail__body-text"><?php echo nl2br(e($ftPrayer)); ?></div>
<?php else: ?>
<p class="kci-msgdetail__value"><span class="kci-muted">—</span></p>
<?php endif; ?>

<p class="kci-msgdetail__label">Contacted</p>
<p class="kci-msgdetail__value"><?php if ($contactedRel !== ''): ?><span title="<?php echo e($contactedFull); ?>"><?php echo e($contactedRel); ?></span><?php else: ?><span class="kci-muted">Not contacted yet</span><?php endif; ?></p>
</div>

<div class="kci-msgdetail__contact">
<?php if ($ftEmail !== ''): ?>
<a class="kci-btn kci-btn--outline" href="<?php echo e(ft_mail_url($ftEmail, $ftName)); ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email</a>
<?php endif; ?>
<?php if ($ftPhoneUsable !== ''): ?>
<a class="kci-btn kci-btn--outline" href="tel:<?php echo e($ftPhoneUsable); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call</a>
<?php endif; ?>
<?php if ($ftPhoneUsable !== '' && $ftWaDigits !== ''): ?>
<a class="kci-btn kci-btn--outline" href="<?php echo e(ft_wa_url($ftWaDigits, $ftName)); ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
<?php endif; ?>
<?php if ($ftEmail === '' && $ftPhoneUsable === ''): ?>
<span class="kci-muted">No contact details on file.</span>
<?php endif; ?>
</div>

<div class="kci-msgdetail__nav">
<?php if ($prev !== null): ?>
<a class="kci-pillbtn kci-pillbtn--outline" href="first-timer.php?id=<?php echo $prev; ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?php echo e($prev); ?></a>
<?php endif; ?>
<span class="kci-msgdetail__nav-spacer"></span>
<?php if ($next !== null): ?>
<a class="kci-pillbtn kci-pillbtn--outline" href="first-timer.php?id=<?php echo $next; ?>"><?php echo e($next); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
<?php endif; ?>
</div>

<div class="kci-msgdetail__actions">
<?php if ($ftStatus === 'new'): ?>
<form method="post" action="first-timer-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="contacted">
<input type="hidden" name="id" value="<?php echo (int)$ftId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--green"><i class="fa-solid fa-headset" aria-hidden="true"></i> Mark contacted</button>
</form>
<?php else: ?>
<form method="post" action="first-timer-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="reopen">
<input type="hidden" name="id" value="<?php echo (int)$ftId; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--amber"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reopen</button>
</form>
<?php endif; ?>
<form method="post" action="first-timer-action.php" class="kci-serve__form" data-confirm-title="Delete this visitor?" data-confirm="Permanently delete the first-timer record from <?php echo e($ftName); ?>? This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo (int)$ftId; ?>">
<input type="hidden" name="return" value="<?php echo e($backToList); ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--red"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete</button>
</form>
</div>
</div>

<div class="kci-msgdetail__back">
<a class="kci-pillbtn kci-pillbtn--outline" href="<?php echo e($backToList); ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to first-timers</a>
</div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>