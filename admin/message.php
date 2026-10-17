<?php
/* admin/message.php — view a single contact message from the public Contact
 * form with sender details, full message and previous/next navigation.
 * Opening a message marks it as read (single UPDATE, only when the id is valid)
 * so the unread side of the list reflects what the administrator has seen. */
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

/* Valid id: positive integer (prevents 0, negative, non-numeric and huge). */
$msgId = 0;
if (isset($_GET['id']) && is_string($_GET['id'])) {
    if (preg_match('/^[1-9][0-9]{0,9}$/', trim($_GET['id']))) {
        $msgId = (int)trim($_GET['id']);
    }
}

/* Load the message (if it exists). */
$msg = null;
if ($msgId > 0) {
    try {
        $stmt = $conn->prepare('SELECT id, full_name, email, phone, subject, message, is_read, created_at FROM contact_messages WHERE id = ? LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $msgId);
            $stmt->execute();
            $res = $stmt->get_result();
            $msg = ($res !== false && $res->num_rows > 0) ? $res->fetch_assoc() : null;
            $stmt->close();
        }
    } catch (Throwable $ignored) {
        $msg = null;
    }
}

/* Idempotent mark-as-read: only when the id is valid and the row is unread.
 * (string) cast: on PHP 8.1+ prepared statements return the TINYINT as int,
 * so a strict compare against '0' would never match. */
if ($msgId > 0 && $msg !== null && (string)$msg['is_read'] === '0') {
    try {
        $stmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ? AND is_read = 0');
        if ($stmt !== false) {
            $stmt->bind_param('i', $msgId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $ignored) {
    }
}

/* Refresh the loaded row with the current (possibly now-read) state. */
if ($msgId > 0 && $msg !== null) {
    try {
        $stmt = $conn->prepare('SELECT id, full_name, email, phone, subject, message, is_read, created_at FROM contact_messages WHERE id = ? LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $msgId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res !== false && $res->num_rows > 0) {
                $msg = $res->fetch_assoc();
            }
            $stmt->close();
        }
    } catch (Throwable $ignored) {
        $msg = null;
    }
}

$msgIsRead = ($msg !== null && (string)$msg['is_read'] === '1');

/* Previous/next navigation by adjacent id (never the same id). */
$prev = null;
$next = null;
if ($msgId > 0 && $msg !== null) {
    try {
        $stmt = $conn->prepare('SELECT id FROM contact_messages WHERE id < ? ORDER BY id DESC LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $msgId);
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
        $stmt = $conn->prepare('SELECT id FROM contact_messages WHERE id > ? ORDER BY id ASC LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $msgId);
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

$returnTarget = 'message.php?id=' . $msgId;
$backToUnread = 'messages.php?status=unread';
$backToAll = 'messages.php?status=all';

/* --- Helper functions (defined locally in this file) --- */

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

function messages_reply_url($email, $subject, $fullName) {
    $subject = (string)$subject;
    $fullName = (string)$fullName;
    $firstName = $fullName !== '' ? trim(strtok($fullName, ' ')) : 'there';
    $subject = 'Re: ' . $subject;
    $body = 'Hello ' . $firstName . ",\n\nThank you for getting in touch with Kingdomite Church International. We have received your message and our team will reply as soon as possible.\n\nWarm regards,\nKingdomite Church International";
    return 'mailto:' . $email . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
}

function messages_wa_reply_url($digits, $fullName) {
    $fullName = (string)$fullName;
    $firstName = $fullName !== '' ? trim(strtok($fullName, ' ')) : 'there';
    $text = 'Hello ' . $firstName . ', this is Kingdomite Church International. Thank you for contacting us — we will get back to you shortly.';
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}


/* Page title and nav. */
$pageTitle = ($msg !== null && trim((string)$msg['subject']) !== '') ? trim((string)$msg['subject']) : 'Message';
$activeNav = 'messages';

/* Full received date for the title attribute (mirrors serve-applications). */
$receivedFull = '';
$receivedRel = '';
if ($msg !== null) {
    $ts = strtotime((string)$msg['created_at']);
    $receivedFull = ($ts !== false) ? date('d M Y, g:ia', $ts) : (string)$msg['created_at'];
    $receivedRel = admin_relative_time($msg['created_at']);
}

include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
    <?php if ($msg === null): ?>
    <div class="kci-serve__empty">
        <div class="kci-serve__empty-card">
            <i class="fa-solid fa-envelope-open-text kc-serve__empty-icon" aria-hidden="true"></i>
            <h2>Message not found</h2>
            <p>The message you are looking for does not exist or has been removed.</p>
            <a class="kci-pillbtn kci-pillbtn--blue" href="<?php echo e($backToUnread); ?>">Back to unread messages</a>
        </div>
    </div>
    <?php else: ?>
    <div class="kci-msgdetail kci-card">
        <div class="kci-msgdetail__head">
            <span class="kci-pillbtn kci-pillbtn--<?php echo $msgIsRead ? 'gray' : 'violet'; ?>">
                <?php echo $msgIsRead ? 'Read' : 'Unread'; ?>
            </span>
            <h2>From <?php echo e($msg['full_name']); ?></h2>
            <span class="kci-msgdetail__meta-label">Received</span>
            <span class="kci-msgdetail__meta-value" title="<?php echo e($receivedFull); ?>"><?php echo e($receivedRel); ?></span>
        </div>

        <div class="kci-msgdetail__body">
            <p class="kci-msgdetail__label">Subject</p>
            <p class="kci-msgdetail__value"><?php echo e($msg['subject']); ?></p>

            <p class="kci-msgdetail__label">Email</p>
            <p class="kci-msgdetail__value"><?php echo e($msg['email']); ?></p>

            <p class="kci-msgdetail__label">Phone</p>
            <p class="kci-msgdetail__value"><?php echo e($msg['phone']); ?></p>

            <p class="kci-msgdetail__label">Message</p>
            <div class="kci-msgdetail__body-text"><?php echo nl2br(e($msg['message'])); ?></div>
        </div>

        <div class="kci-msgdetail__contact">
            <?php if ($msg['email'] !== ''): ?>
            <a class="kci-btn kci-btn--outline" href="<?php echo e(messages_reply_url($msg['email'], $msg['subject'], $msg['full_name'])); ?>"><i class="fa-solid fa-reply" aria-hidden="true"></i> Reply by email</a>
            <?php endif; ?>
            <?php
            $phoneUsable = messages_phone_usable($msg['phone']);
            if ($phoneUsable !== ''):
            ?>
            <a class="kci-btn kci-btn--outline" href="tel:<?php echo e($phoneUsable); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call</a>
            <a class="kci-btn kci-btn--outline" href="<?php echo e(messages_wa_reply_url(messages_wa_digits($msg['phone']), $msg['full_name'])); ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
            <?php endif; ?>
        </div>

        <div class="kci-msgdetail__nav">
            <?php if ($prev !== null): ?>
            <a class="kci-pillbtn kci-pillbtn--outline" href="message.php?id=<?php echo $prev; ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?php echo e($prev); ?></a>
            <?php endif; ?>
            <span class="kci-msgdetail__nav-spacer"></span>
            <?php if ($next !== null): ?>
            <a class="kci-pillbtn kci-pillbtn--outline" href="message.php?id=<?php echo $next; ?>"><?php echo e($next); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <?php endif; ?>
        </div>

        <div class="kci-msgdetail__actions">
            <form method="post" action="message-action.php" class="kci-serve__form" data-confirm-title="Mark as unread?" data-confirm="Mark the message from <?php echo e($msg['full_name']); ?> as unread? This cannot be undone.">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="unread">
                <input type="hidden" name="id" value="<?php echo (int)$msgId; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
                <button type="submit" class="kci-pillbtn kci-pillbtn--outline"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Mark unread</button>
            </form>
            <form method="post" action="message-action.php" class="kci-serve__form" data-confirm-title="Delete this message?" data-confirm="Permanently delete the message from <?php echo e($msg['full_name']); ?>? This cannot be undone.">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$msgId; ?>">
                <input type="hidden" name="return" value="<?php echo e($backToUnread); ?>">
                <button type="submit" class="kci-pillbtn kci-pillbtn--red"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete</button>
            </form>
        </div>
    </div>

    <div class="kci-msgdetail__back">
        <a class="kci-pillbtn kci-pillbtn--outline" href="<?php echo e($backToUnread); ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to unread messages</a>
        <a class="kci-pillbtn kci-pillbtn--outline" href="<?php echo e($backToAll); ?>">Full messages list <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
</body>
</html>

