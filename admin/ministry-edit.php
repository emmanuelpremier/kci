<?php
/* admin/ministry-edit.php — add + edit form for the Ministries module.
 * Two columns: form LEFT, live preview card RIGHT (stacked on phones).
 * Reuses the Events module patterns: image picker, slug flow, csrf,
 * flash messages, post-redirect-get. */
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

/* Curated Font Awesome icon choices — stored as the class string and
 * validated on the server against exactly this list. */
function kci_ministry_icons() {
    return array(
        'fa-solid fa-children',
        'fa-solid fa-fire',
        'fa-solid fa-book-bible',
        'fa-solid fa-heart',
        'fa-solid fa-music',
        'fa-solid fa-earth-africa',
        'fa-solid fa-hand-holding-heart',
        'fa-solid fa-hands-praying',
        'fa-solid fa-mug-hot',
        'fa-solid fa-users',
        'fa-solid fa-church',
        'fa-solid fa-cross',
        'fa-solid fa-microphone',
        'fa-solid fa-video',
        'fa-solid fa-camera',
        'fa-solid fa-graduation-cap',
        'fa-solid fa-seedling',
        'fa-solid fa-handshake',
        'fa-solid fa-shield-heart',
        'fa-solid fa-star',
        'fa-solid fa-lightbulb',
        'fa-solid fa-bullhorn',
        'fa-solid fa-baby',
        'fa-solid fa-person-praying',
    );
}

function kci_ministry_edit_id() {
    if (!isset($_GET['id']) || is_array($_GET['id'])) { return 0; }
    $raw = trim((string)$_GET['id']);
    if (!preg_match('/^[1-9][0-9]{0,9}$/', $raw)) { return 0; }
    return (int)$raw;
}

function kci_ministry_len($v) {
    if (function_exists('mb_strlen')) { return mb_strlen((string)$v, 'UTF-8'); }
    return strlen((string)$v);
}

/* Preview src for the stored picture; '' means "no picture, show icon". */
function kci_ministry_pic_src($value) {
    $value = trim((string)$value);
    if (preg_match('/^media\.php\?id=[0-9]+$/', $value)) { return '../' . $value; }
    if (preg_match('/^kci_image\/[A-Za-z0-9._-]+$/', $value)) { return '../' . $value; }
    return '';
}

/* Safe icon class for display/preview (whitelist + shape check). */
function kci_ministry_safe_icon($value, $fallback) {
    $value = trim((string)$value);
    if (in_array($value, kci_ministry_icons(), true)) { return $value; }
    if ($value !== '' && preg_match('/^[A-Za-z0-9 -]{1,100}$/', $value)) { return $value; }
    return $fallback;
}

$editId = kci_ministry_edit_id();
$isEdit = ($editId > 0);
$existing = null;
if ($isEdit) {
    try {
        $f = $conn->prepare('SELECT id, name, slug, category, icon, image, description, meeting, status FROM ministries WHERE id = ? LIMIT 1');
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

$values = array(
    'name' => '',
    'category' => '',
    'icon' => 'fa-solid fa-church',
    'desc' => '',
    'meeting' => '',
    'status' => 'active',
    'image' => '',
    'slug' => '',
);
$oldSlug = '';
if (is_array($existing)) {
    $values['name'] = (string)$existing['name'];
    $values['category'] = (string)$existing['category'];
    $values['icon'] = kci_ministry_safe_icon(isset($existing['icon']) ? $existing['icon'] : '', 'fa-solid fa-church');
    $values['desc'] = (string)$existing['description'];
    $values['meeting'] = (string)$existing['meeting'];
    $values['status'] = (isset($existing['status']) && $existing['status'] === 'inactive') ? 'inactive' : 'active';
    $values['image'] = trim((string)$existing['image']);
    $values['slug'] = (string)$existing['slug'];
    $oldSlug = (string)$existing['slug'];
}

/* Existing categories for the datalist (admin can pick or type a new one). */
/* Existing categories for the datalist (admin can pick or type a new one). */
$categoryOptions = array();
try {
    $cstmt = $conn->prepare('SELECT DISTINCT category FROM ministries ORDER BY category ASC');
    if ($cstmt !== false) {
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres !== false) {
            while ($crow = $cres->fetch_assoc()) {
                $cat = trim((string)$crow['category']);
                if ($cat !== '' && !in_array($cat, $categoryOptions, true)) { $categoryOptions[] = $cat; }
            }
        }
        $cstmt->close();
    }
} catch (Throwable $ignored) {
    $categoryOptions = array();
}

$newSlug = $oldSlug;
$slugChanged = false;
$appsUpdated = -1;
$errors = array();
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST' && !$notFound) {
    if (!csrf_check()) { csrf_fail(); }
    $pName = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $pCategory = isset($_POST['category']) && is_string($_POST['category']) ? trim($_POST['category']) : '';
    $pIcon = isset($_POST['icon']) && is_string($_POST['icon']) ? trim($_POST['icon']) : '';
    $pDesc = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
    $pMeeting = isset($_POST['meeting']) && is_string($_POST['meeting']) ? trim($_POST['meeting']) : '';
    $pStatus = isset($_POST['status']) && is_string($_POST['status']) ? strtolower(trim($_POST['status'])) : '';
    $pSlug = isset($_POST['slug']) && is_string($_POST['slug']) ? strtolower(trim($_POST['slug'])) : '';
    $values['name'] = $pName;
    $values['category'] = $pCategory;
    if (in_array($pIcon, kci_ministry_icons(), true)) { $values['icon'] = $pIcon; }
    $values['desc'] = $pDesc;
    $values['meeting'] = $pMeeting;
    $values['status'] = ($pStatus === 'inactive') ? 'inactive' : (($pStatus === 'active') ? 'active' : $values['status']);

    if ($pName === '') { $errors[] = 'Please enter the ministry name.'; }
    elseif (kci_ministry_len($pName) < 3 || kci_ministry_len($pName) > 60) { $errors[] = 'The ministry name must be 3 to 60 characters.'; }
    if ($pCategory === '') { $errors[] = 'Please enter the category.'; }
    elseif (kci_ministry_len($pCategory) > 40) { $errors[] = 'Please keep the category to 40 characters or fewer.'; }
    if (!in_array($pIcon, kci_ministry_icons(), true)) { $errors[] = 'Please choose an icon from the list.'; }
    if ($pDesc === '') { $errors[] = 'Please enter the short description.'; }
    elseif (kci_ministry_len($pDesc) < 20 || kci_ministry_len($pDesc) > 300) { $errors[] = 'The short description must be 20 to 300 characters.'; }
    if ($pMeeting === '') { $errors[] = 'Please enter the meeting time.'; }
    elseif (kci_ministry_len($pMeeting) > 60) { $errors[] = 'Please keep the meeting time to 60 characters or fewer.'; }
    if ($pStatus !== 'active' && $pStatus !== 'inactive') { $errors[] = 'Please choose a valid status.'; }

    if ($isEdit) {
        $wantsChange = isset($_POST['change_slug']) && (string)$_POST['change_slug'] === '1';
        if (!$wantsChange || $pSlug === $oldSlug) {
            $newSlug = $oldSlug;
        } else {
            $values['slug'] = $pSlug;
            if (!preg_match('/^[a-z0-9-]{3,60}$/', $pSlug)) {
                $errors[] = 'The page link may only use lowercase letters, digits and dashes (3 to 60 characters).';
            } else {
                $unique = kci_unique_slug($conn, $pSlug, $editId, 'ministries', 'slug', 'ministry');
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

    $imageValue = admin_resolve_image_field($conn, $adminId, 'image', '');
    $values['image'] = $imageValue;
    if (!empty($GLOBALS['kci_image_upload_error'])) { $errors[] = (string)$GLOBALS['kci_image_upload_error']; }

    if (empty($errors)) {
        $ok = false;
        $storeName = $pName;
        $storeCategory = $pCategory;
        $storeIcon = $pIcon;
        $storeDesc = $pDesc;
        $storeMeeting = $pMeeting;
        $storeStatus = $pStatus;
        $storeImage = $imageValue;
        try {
            if ($isEdit) {
                if ($slugChanged) {
                    /* Change the link and re-point serve applications in the
                     * same transaction so none are orphaned. (ministries may
                     * be MyISAM, where statements simply run in order; on
                     * InnoDB this is one real transaction.) */
                    $appsUpdated = 0;
                    $conn->begin_transaction();
                    $u = $conn->prepare('UPDATE ministries SET name = ?, slug = ?, category = ?, icon = ?, image = ?, description = ?, meeting = ?, status = ? WHERE id = ? LIMIT 1');
                    if ($u === false) { throw new Exception('update failed'); }
                    $u->bind_param('ssssssssi', $storeName, $newSlug, $storeCategory, $storeIcon, $storeImage, $storeDesc, $storeMeeting, $storeStatus, $editId);
                    $ok = $u->execute();
                    $u->close();
                    if (!$ok) { throw new Exception('update failed'); }
                    $a = $conn->prepare('UPDATE serve_applications SET ministry_slug = ? WHERE ministry_slug = ?');
                    if ($a === false) { throw new Exception('applications update failed'); }
                    $a->bind_param('ss', $newSlug, $oldSlug);
                    $a->execute();
                    $appsUpdated = (int)$a->affected_rows;
                    $a->close();
                    $conn->commit();
                } else {
                    $u = $conn->prepare('UPDATE ministries SET name = ?, category = ?, icon = ?, image = ?, description = ?, meeting = ?, status = ? WHERE id = ? LIMIT 1');
                    if ($u !== false) {
                        $u->bind_param('sssssssi', $storeName, $storeCategory, $storeIcon, $storeImage, $storeDesc, $storeMeeting, $storeStatus, $editId);
                        $ok = $u->execute();
                        $u->close();
                    }
                }
            } else {
                $genSlug = kci_unique_slug($conn, kci_slugify($pName, 'ministry'), 0, 'ministries', 'slug', 'ministry');
                $newSlug = $genSlug;
                $now = date('Y-m-d H:i:s');
                $ins = $conn->prepare('INSERT INTO ministries (name, slug, category, icon, image, description, meeting, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                if ($ins !== false) {
                    $ins->bind_param('sssssssss', $storeName, $genSlug, $storeCategory, $storeIcon, $storeImage, $storeDesc, $storeMeeting, $storeStatus, $now);
                    $ok = $ins->execute();
                    if ($ok) { $editId = (int)$conn->insert_id; }
                    $ins->close();
                }
            }
        } catch (Throwable $ignored) {
            $ok = false;
            try { $conn->rollback(); } catch (Throwable $ignoredRollback) { /* nothing to roll back */ }
        }
        if ($ok) {
            if ($isEdit) {
                admin_log_action($conn, $adminId, 'ministry_update', 'ministry #' . $editId . ' (' . $storeName . ')');
                $flashMsg = 'Ministry updated.';
                if ($slugChanged) {
                    admin_log_action($conn, $adminId, 'ministry_slug_change', 'ministry #' . $editId . ' (' . $storeName . ')');
                    $flashMsg .= ' ' . (int)$appsUpdated . ' serve application' . ((int)$appsUpdated === 1 ? '' : 's') . ' updated to the new link.';
                }
                flash_set('success', $flashMsg);
                header('Location: ministry-edit.php?id=' . $editId);
                exit;
            }
            admin_log_action($conn, $adminId, 'ministry_create', 'ministry #' . $editId . ' (' . $storeName . ')');
            flash_set('success', 'Ministry created.');
            header('Location: ministries.php');
            exit;
        }
        $errors[] = 'That ministry could not be saved. Please try again.';
    }
    if ($isEdit && $values['slug'] === '') { $values['slug'] = $newSlug; }
}
$previewIcon = kci_ministry_safe_icon($values['icon'], 'fa-solid fa-church');
$previewPic = kci_ministry_pic_src($values['image']);
$pageTitle = $isEdit ? 'Edit ministry' : 'Add ministry';
$activeNav = 'ministries';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<?php if ($notFound): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
<h2>Ministry not found</h2>
<p class="kci-muted">That ministry does not exist or was deleted.</p>
<a class="kci-btn kci-btn--primary kci-btn--sm" href="ministries.php"><i class="fa-solid fa-arrow-left"></i> Back to Ministries</a>
</div>
<?php else: ?>
<div class="kci-serve__head kci-min__head">
<div>
<h2 class="kci-serve__heading"><i class="fa-solid fa-people-group"></i> <?php echo $isEdit ? 'Edit ministry' : 'Add ministry'; ?></h2>
<p class="kci-muted"><?php echo $isEdit ? 'Update the details visitors see on the site.' : 'Create a new ministry for the church website.'; ?></p>
</div>
<div class="kci-actions">
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="ministries.php"><i class="fa-solid fa-arrow-left"></i> Back to Ministries</a>
<?php if ($isEdit): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="../ministry.php?slug=<?php echo urlencode((string)$oldSlug); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View on site</a>
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
<form method="post" action="<?php echo $isEdit ? 'ministry-edit.php?id=' . (int)$editId : 'ministry-edit.php'; ?>" enctype="multipart/form-data" id="kci-ministry-form">
<?php csrf_field(); ?>
<label class="kci-field">
<span>Name</span>
<input type="text" name="name" id="ministry-name" minlength="3" maxlength="60" required autocomplete="off" value="<?php echo e($values['name']); ?>">
</label>
<label class="kci-field">
<span>Category</span>
<input type="text" name="category" id="ministry-category" maxlength="40" required autocomplete="off" list="ministry-categories" value="<?php echo e($values['category']); ?>">
<datalist id="ministry-categories">
<?php foreach ($categoryOptions as $catOption): ?>
<option value="<?php echo e($catOption); ?>"></option>
<?php endforeach; ?>
</datalist>
<span class="kci-field__hint kci-muted">Pick an existing category or type a new one — new categories become their own filter on the site.</span>
</label>
<fieldset class="kci-field kci-iconpick">
<span>Icon</span>
<div class="kci-iconpick__grid">
<?php foreach (kci_ministry_icons() as $iconClass): ?>
<?php $iconChecked = ($values['icon'] === $iconClass); ?>
<label class="kci-iconopt<?php echo $iconChecked ? ' is-active' : ''; ?>" title="<?php echo e($iconClass); ?>">
<input type="radio" name="icon" value="<?php echo e($iconClass); ?>"<?php echo $iconChecked ? ' checked' : ''; ?>>
<i class="<?php echo e($iconClass); ?>" aria-hidden="true"></i>
<span class="kci-sr"><?php echo e($iconClass); ?></span>
</label>
<?php endforeach; ?>
</div>
<p class="kci-muted">Choose the icon shown when the ministry has no picture.</p>
</fieldset>
<label class="kci-field">
<span>Short description <span class="kci-muted">(<span id="ministry-desc-count"><?php echo (int)kci_ministry_len($values['desc']); ?></span>/300)</span></span>
<textarea name="description" id="ministry-description" rows="3" maxlength="300" required><?php echo e($values['desc']); ?></textarea>
</label>
<label class="kci-field">
<span>Meeting time</span>
<span class="kci-inputgroup">
<input type="text" name="meeting" id="ministry-meeting" maxlength="60" required autocomplete="off" value="<?php echo e($values['meeting']); ?>">
<button type="button" class="kci-pillbtn kci-pillbtn--outline kci-inputgroup__btn" id="ministry-meeting-dot" aria-label="Insert middle dot">&middot;</button>
</span>
<span class="kci-field__hint kci-muted">Example: Sundays &middot; 9:00 AM</span>
</label>
<div class="kci-field">
<span>Status</span>
<div class="kci-switch">
<label class="kci-switchopt<?php echo ($values['status'] === 'active') ? ' is-active' : ''; ?>">
<input type="radio" name="status" value="active"<?php echo ($values['status'] === 'active') ? ' checked' : ''; ?>>
<i class="fa-solid fa-eye" aria-hidden="true"></i> Active
</label>
<label class="kci-switchopt<?php echo ($values['status'] === 'inactive') ? ' is-active' : ''; ?>">
<input type="radio" name="status" value="inactive"<?php echo ($values['status'] === 'inactive') ? ' checked' : ''; ?>>
<i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Hidden
</label>
</div>
</div>
<?php admin_image_picker('image', $values['image'], 'Picture'); ?>
<p class="kci-muted kci-field__hint">Optional — without a picture the public pages show the icon instead.</p>
<?php if ($isEdit): ?>
<div class="kci-field">
<span>Page link (slug)</span>
<p class="kci-muted" id="ministry-slug-locked">ministry.php?slug=<?php echo e($oldSlug); ?></p>
<div id="ministry-slug-edit" hidden>
<div class="kci-warnbox" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Old links to this ministry will stop working</div>
<input type="text" name="slug" id="ministry-slug" maxlength="60" autocomplete="off" value="<?php echo e($values['slug'] !== '' ? $values['slug'] : $oldSlug); ?>">
<input type="hidden" name="change_slug" id="ministry-change-slug" value="0">
</div>
<button type="button" class="kci-pillbtn kci-pillbtn--outline" id="ministry-slug-toggle"><i class="fa-solid fa-link" aria-hidden="true"></i> Change link</button>
</div>
<?php else: ?>
<div class="kci-field">
<span>Page link preview</span>
<p class="kci-muted" id="ministry-slug-preview">ministry.php?slug=<span id="ministry-slug-preview-value">ministry</span></p>
</div>
<?php endif; ?>
<div class="kci-actions">
<button type="submit" class="kci-btn kci-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Save ministry</button>
</div>
</form>
</div>
<aside class="kci-card kci-give__preview">
<h2>Live preview</h2>
<p class="kci-muted">This is how the ministry card looks on the site.</p>
<article class="kci-minprev" id="ministryPreview">
<div class="kci-minprev__media">
<span class="kci-minprev__icon" id="ministryPreviewIconWrap"><i id="ministryPreviewIcon" class="<?php echo e($previewIcon); ?>" aria-hidden="true"></i></span>
<img id="ministryPreviewImg"<?php echo ($previewPic !== '') ? ' src="' . e($previewPic) . '"' : ' hidden'; ?> alt="Ministry picture preview">
</div>
<div class="kci-minprev__body">
<span class="kci-minprev__cat" id="ministryPreviewCategory"><?php echo e($values['category'] !== '' ? $values['category'] : 'Category'); ?></span>
<h3 id="ministryPreviewName"><?php echo e($values['name'] !== '' ? $values['name'] : 'Your ministry name'); ?></h3>
<p id="ministryPreviewDesc"><?php echo e($values['desc'] !== '' ? $values['desc'] : 'Your short description appears here.'); ?></p>
<p class="kci-minprev__meeting"><i class="fa-regular fa-clock" aria-hidden="true"></i> <span id="ministryPreviewMeeting"><?php echo e($values['meeting'] !== '' ? $values['meeting'] : 'Meeting time'); ?></span></p>
</div>
</article>
</aside>
</div>
<?php endif; ?>
</section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>

