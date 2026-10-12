<?php
/* admin/event-edit.php — add + edit form (part 1). */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/media_helpers.php';
require __DIR__ . '/includes/image_picker.php';
require __DIR__ . '/../kci_db.php';
$admin = require_admin($conn);
$adminId = (int)$admin['id'];
$defaultImage = 'kci_image/im2.webp';
function kci_event_edit_id() {
    if (!isset($_GET['id']) || is_array($_GET['id'])) { return 0; }
    $raw = trim((string)$_GET['id']);
    if (!preg_match('/^[1-9][0-9]{0,9}$/', $raw)) { return 0; }
    return (int)$raw;
}
function kci_event_day_part($stored) {
    $day = substr(trim((string)$stored), 0, 10);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) { return $day; }
    $ts = strtotime((string)$stored);
    if ($ts === false) { return ''; }
    return date('Y-m-d', $ts);
}
function kci_event_len($v) {
    if (function_exists('mb_strlen')) { return mb_strlen((string)$v, 'UTF-8'); }
    return strlen((string)$v);
}
function kci_event_thumb($value, $defaultImage) {
    $value = trim((string)$value);
    if (preg_match('/^media\.php\?id=[0-9]+$/', $value)) { return '../' . $value; }
    if (preg_match('/^kci_image\/[A-Za-z0-9._-]+$/', $value)) { return '../' . $value; }
    return '../' . $defaultImage;
}
$editId = kci_event_edit_id();
$isEdit = ($editId > 0);
$existing = null;
if ($isEdit) {
    try {
        $f = $conn->prepare('SELECT id, title, slug, image, date, location, description, content FROM events WHERE id = ? LIMIT 1');
        if ($f !== false) {
            $f->bind_param('i', $editId);
            $f->execute();
            $r = $f->get_result();
            $existing = ($r !== false) ? $r->fetch_assoc() : null;
            $f->close();
        }
    } catch (Throwable $ignored) { $existing = null; }
}
$notFound = ($isEdit && !is_array($existing));
$values = array('title' => '', 'date' => '', 'loc' => 'Kingdomite Church International', 'desc' => '', 'body' => '', 'image' => $defaultImage, 'slug' => '');
$oldSlug = '';
if (is_array($existing)) {
    $values['title'] = (string)$existing['title'];
    $values['date'] = kci_event_day_part(isset($existing['date']) ? $existing['date'] : '');
    $values['loc'] = (string)$existing['location'];
    $values['desc'] = (string)$existing['description'];
    $values['body'] = (string)$existing['content'];
    $tmpImg = trim((string)$existing['image']);
    $values['image'] = ($tmpImg !== '') ? $tmpImg : $defaultImage;
    $values['slug'] = (string)$existing['slug'];
    $oldSlug = (string)$existing['slug'];
}
$newSlug = $oldSlug;
$slugChanged = false;
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST' && !$notFound) {
    if (!csrf_check()) { csrf_fail(); }
    $pTitle = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
    $pDate = isset($_POST['event_date']) && is_string($_POST['event_date']) ? trim($_POST['event_date']) : '';
    $pLoc = isset($_POST['location']) && is_string($_POST['location']) ? trim($_POST['location']) : '';
    $pDesc = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
    $pBody = isset($_POST['content']) && is_string($_POST['content']) ? trim($_POST['content']) : '';
    $pSlug = isset($_POST['slug']) && is_string($_POST['slug']) ? strtolower(trim($_POST['slug'])) : '';
    $values['title'] = $pTitle;
    $values['date'] = $pDate;
    $values['loc'] = $pLoc;
    $values['desc'] = $pDesc;
    $values['body'] = $pBody;
    $tlen = kci_event_len($pTitle);
    if ($pTitle === '') { $errors[] = 'Please enter the event title.'; }
    elseif ($tlen < 3 || $tlen > 120) { $errors[] = 'The title must be 3 to 120 characters.'; }
    if ($pDate === '') { $errors[] = 'Please choose the event date.'; }
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pDate)) { $errors[] = 'The date must use the calendar picker (YYYY-MM-DD).'; }
    else {
        $parts = explode('-', $pDate);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) { $errors[] = 'That date is not a real calendar date.'; }
    }
    if ($pLoc === '') { $errors[] = 'Please enter the location.'; }
    elseif (kci_event_len($pLoc) > 150) { $errors[] = 'Please keep the location to 150 characters or fewer.'; }
    if ($pDesc === '') { $errors[] = 'Please enter the short description.'; }
    elseif (kci_event_len($pDesc) > 300) { $errors[] = 'Please keep the short description to 300 characters or fewer.'; }
    if (kci_event_len($pBody) > 1000) { $errors[] = 'Please keep the full details to 1000 characters or fewer.'; }
    if ($isEdit) {
        $wantsChange = isset($_POST['change_slug']) && (string)$_POST['change_slug'] === '1';
        if (!$wantsChange || $pSlug === $oldSlug) {
            $newSlug = $oldSlug;
        } else {
            $values['slug'] = $pSlug;
            if (!preg_match('/^[a-z0-9-]{3,60}$/', $pSlug)) {
                $errors[] = 'The page link may only use lowercase letters, digits and dashes (3 to 60 characters).';
            } else {
                $unique = kci_unique_slug($conn, $pSlug, $editId);
                if ($unique !== $pSlug) {
                    $errors[] = 'That page link is already in use. Please choose another.';
                } else {
                    $newSlug = $pSlug;
                    $slugChanged = true;
                }
            }
        }
        if ($values['slug'] === '') { $values['slug'] = $oldSlug; }
    }
    $imageValue = admin_resolve_image_field($conn, $adminId, 'image', $defaultImage);
    $values['image'] = $imageValue;
    if (!empty($GLOBALS['kci_image_upload_error'])) { $errors[] = (string)$GLOBALS['kci_image_upload_error']; }
    if (empty($errors)) {
        $storeDate = $pDate . ' 12:00:00';
        $ok = false;
        try {
            if ($isEdit) {
                $u = $conn->prepare('UPDATE events SET title = ?, slug = ?, image = ?, date = ?, location = ?, description = ?, content = ? WHERE id = ? LIMIT 1');
                if ($u !== false) {
                    $u->bind_param('sssssssi', $pTitle, $newSlug, $imageValue, $storeDate, $pLoc, $pDesc, $pBody, $editId);
                    $ok = $u->execute();
                    $u->close();
                }
            } else {
                $genSlug = kci_unique_slug($conn, kci_slugify($pTitle), 0);
                $newSlug = $genSlug;
                $ins = $conn->prepare('INSERT INTO events (title, slug, image, date, location, description, content) VALUES (?, ?, ?, ?, ?, ?, ?)');
                if ($ins !== false) {
                    $ins->bind_param('sssssss', $pTitle, $genSlug, $imageValue, $storeDate, $pLoc, $pDesc, $pBody);
                    $ok = $ins->execute();
                    if ($ok) { $editId = (int)$conn->insert_id; }
                    $ins->close();
                }
            }
        } catch (Throwable $ignored) { $ok = false; }
        if ($ok) {
            if ($isEdit) {
                admin_log_action($conn, $adminId, 'event_update', 'updated event #' . $editId . ' (' . $pTitle . ')');
                if ($slugChanged) {
                    admin_log_action($conn, $adminId, 'event_slug_change', 'event #' . $editId . ' link changed to ' . $newSlug);
                }
                flash_set('success', 'Event updated.');
                header('Location: event-edit.php?id=' . $editId);
                exit;
            }
            admin_log_action($conn, $adminId, 'event_create', 'created event #' . $editId . ' (' . $pTitle . ')');
            $addAnother = isset($_POST['add_another']) && (string)$_POST['add_another'] === '1';
            if ($addAnother) {
                flash_set('success', 'Event saved. Add another below.');
                header('Location: event-edit.php');
                exit;
            }
            flash_set('success', 'Event created.');
            header('Location: events.php');
            exit;
        }
        $errors[] = 'That event could not be saved. Please try again.';
    }
    if ($isEdit && $values['slug'] === '') { $values['slug'] = $newSlug; }
}
$pageTitle = $isEdit ? 'Edit event' : 'Add event';
$activeNav = 'events';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<?php if ($notFound): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
<h2>Event not found</h2>
<p class="kci-muted">That event does not exist or was deleted.</p>
<a class="kci-btn kci-btn--primary kci-btn--sm" href="events.php"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>
</div>
<?php else: ?>
<div class="kci-serve__head" style="margin-bottom:14px">
<div>
<h2 class="kci-serve__heading"><i class="fa-solid fa-calendar-days"></i> <?php echo $isEdit ? 'Edit event' : 'Add event'; ?></h2>
<p class="kci-muted"><?php echo $isEdit ? 'Update the details visitors see on the site.' : 'Create a new event for the church website.'; ?></p>
</div>
<div class="kci-actions">
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="events.php"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>
<?php if ($isEdit): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="../event.php?slug=<?php echo urlencode((string)$oldSlug); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View on site</a>
<?php endif; ?>
</div>
</div>
<?php if (!empty($errors)): ?>
<ul class="kci-media__errors" role="alert">
<?php foreach ($errors as $err): ?>
<li><?php echo e($err); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<div class="kci-give">
<div class="kci-card">
<form method="post" action="<?php echo $isEdit ? 'event-edit.php?id=' . (int)$editId : 'event-edit.php'; ?>" enctype="multipart/form-data" id="kci-event-form">
<?php csrf_field(); ?>
<label class="kci-field">
<span>Title</span>
<input type="text" name="title" id="event-title" minlength="3" maxlength="120" required autocomplete="off" value="<?php echo e($values['title']); ?>">
</label>
<label class="kci-field">
<span>Date</span>
<input type="date" name="event_date" id="event-date" required value="<?php echo e($values['date']); ?>">
</label>
<label class="kci-field">
<span>Location</span>
<input type="text" name="location" id="event-location" maxlength="150" required autocomplete="off" value="<?php echo e($values['loc']); ?>">
</label>
<label class="kci-field">
<span>Short description <span class="kci-muted">(<span id="event-desc-count"><?php echo (int)kci_event_len($values['desc']); ?></span>/300)</span></span>
<textarea name="description" id="event-description" rows="3" maxlength="300" required><?php echo e($values['desc']); ?></textarea>
</label>
<label class="kci-field">
<span>Full details (optional) <span class="kci-muted">(<span id="event-content-count"><?php echo (int)kci_event_len($values['body']); ?></span>/1000)</span></span>
<textarea name="content" id="event-content" rows="5" maxlength="1000"><?php echo e($values['body']); ?></textarea>
</label>
<?php admin_image_picker('image', $values['image'], 'Picture'); ?>
<?php if ($isEdit): ?>
<div class="kci-field">
<span>Page link (slug)</span>
<p class="kci-muted" id="event-slug-locked">event.php?slug=<?php echo e($oldSlug); ?></p>
<div id="event-slug-edit" hidden>
<div class="kci-warnbox" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Old links to this event will stop working</div>
<input type="text" name="slug" id="event-slug" maxlength="60" autocomplete="off" value="<?php echo e($values['slug'] !== '' ? $values['slug'] : $oldSlug); ?>">
<input type="hidden" name="change_slug" id="event-change-slug" value="0">
</div>
<button type="button" class="kci-pillbtn kci-pillbtn--outline" id="event-slug-toggle"><i class="fa-solid fa-link" aria-hidden="true"></i> Change link</button>
</div>
<?php else: ?>
<div class="kci-field">
<span>Page link preview</span>
<p class="kci-muted" id="event-slug-preview">event.php?slug=<span id="event-slug-preview-value">event</span></p>
</div>
<?php endif; ?>
<div class="kci-actions">
<button type="submit" class="kci-btn kci-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Save event</button>
<?php if (!$isEdit): ?>
<button type="submit" name="add_another" value="1" class="kci-btn kci-btn--ghost"><i class="fa-solid fa-plus"></i> Save and add another</button>
<?php endif; ?>
</div>
</form>
</div>
<aside class="kci-card kci-give__preview">
<h2>Live preview</h2>
<p class="kci-muted">This is how the event card looks on the site.</p>
<article class="kci-eventprev" id="eventPreview">
<div class="kci-eventprev__media">
<img id="eventPreviewImg" src="<?php echo e(kci_event_thumb($values['image'], $defaultImage)); ?>" alt="Event picture preview">
<span class="kci-eventprev__loc" id="eventPreviewLoc"><?php echo e($values['loc'] !== '' ? $values['loc'] : 'Kingdomite Church International'); ?></span>
</div>
<div class="kci-eventprev__body">
<h3 id="eventPreviewTitle"><?php echo e($values['title'] !== '' ? $values['title'] : 'Your event title'); ?></h3>
<p class="kci-eventprev__date" id="eventPreviewDate">Pick a date</p>
<p id="eventPreviewDesc"><?php echo e($values['desc'] !== '' ? $values['desc'] : 'Your short description appears here.'); ?></p>
</div>
</article>
</aside>
</div>
<?php endif; ?>
</section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
