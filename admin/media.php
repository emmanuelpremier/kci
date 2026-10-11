<?php
/* admin/media.php — Media library (database-backed pictures). */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/media_helpers.php';
require __DIR__ . '/../kci_db.php';

$admin = require_admin($conn);
$adminId = (int)$admin['id'];

/* Shared usage check: exact text media.php?id=N in image columns. */
function media_in_use($conn, $id) {
    $needle = 'media.php?id=' . (int)$id;
    foreach (array('events', 'ministries') as $table) {
        try {
            $desc = $conn->query('SHOW COLUMNS FROM `' . $table . '`');
            if ($desc === false) { continue; }
            $cols = array();
            while ($crow = $desc->fetch_assoc()) {
                if (isset($crow['Field']) && strtolower((string)$crow['Field']) === 'image') {
                    $cols[] = $crow['Field'];
                }
            }
            if (empty($cols)) { continue; }
            foreach ($cols as $col) {
                $stmt = $conn->prepare('SELECT id FROM `' . $table . '` WHERE `' . $col . '` LIKE ? LIMIT 1');
                if ($stmt === false) { continue; }
                $like = '%' . $needle . '%';
                $stmt->bind_param('s', $like);
                $stmt->execute();
                $res = $stmt->get_result();
                $found = ($res !== false && $res->fetch_assoc() !== null);
                $stmt->close();
                if ($found) { return true; }
            }
        } catch (Throwable $ignored) { continue; }
    }
    return false;
}

/* Upload handling (POST to self, then redirect). */
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
    if (!csrf_check()) { csrf_fail(); }
    $queue = array();
    if (isset($_FILES['pictures']) && is_array($_FILES['pictures']) && isset($_FILES['pictures']['error']) && is_array($_FILES['pictures']['error'])) {
        $bucket = $_FILES['pictures'];
        $count = count($bucket['error']);
        for ($i = 0; $i < $count; $i++) {
            $one = array(
                'name'     => isset($bucket['name'][$i]) ? $bucket['name'][$i] : '',
                'type'     => isset($bucket['type'][$i]) ? $bucket['type'][$i] : '',
                'tmp_name' => isset($bucket['tmp_name'][$i]) ? $bucket['tmp_name'][$i] : '',
                'error'    => (int)$bucket['error'][$i],
                'size'     => isset($bucket['size'][$i]) ? (int)$bucket['size'][$i] : 0,
            );
            if ((int)$one['error'] === UPLOAD_ERR_NO_FILE && $one['tmp_name'] === '') { continue; }
            $queue[] = $one;
        }
    }
    if (empty($queue)) {
        flash_set('error', 'Please choose at least one picture to upload.');
        header('Location: media.php');
        exit;
    }
    if (count($queue) > 10) {
        flash_set('error', 'You can upload at most 10 pictures at a time.');
        header('Location: media.php');
        exit;
    }
    $okCount = 0;
    $errors = array();
    $newIds = array();
    $okNames = array();
    foreach ($queue as $one) {
        $label = (isset($one['name']) && is_string($one['name']) && trim($one['name']) !== '') ? trim($one['name']) : 'picture';
        $res = admin_save_image_upload($conn, $one, $adminId, 1600);
        if (is_array($res) && !empty($res['ok'])) {
            $okCount++;
            $newIds[] = (int)$res['id'];
            $okNames[] = $label;
        } else {
            $errors[] = $label . ': ' . (is_array($res) ? (string)$res['error'] : 'That upload failed.');
        }
    }
    if ($okCount > 0) {
        $detail = 'uploaded ' . $okCount . ' picture' . ($okCount === 1 ? '' : 's');
        $detail .= ' (ids ' . implode(',', $newIds) . ')';
        if (!empty($okNames)) {
            $names = array_slice($okNames, 0, 5);
            $detail .= ': ' . implode(', ', $names);
            if (count($okNames) > 5) { $detail .= ', ...'; }
        }
        admin_log_action($conn, $adminId, 'media_upload', $detail);
    }
    if ($okCount > 0 && empty($errors)) {
        flash_set('success', 'Uploaded ' . $okCount . ' picture' . ($okCount === 1 ? '' : 's') . '.');
    } elseif ($okCount > 0) {
        flash_set('success', 'Uploaded ' . $okCount . ' picture' . ($okCount === 1 ? '' : 's') . '. ' . count($errors) . ' failed: ' . implode(' ', array_slice($errors, 0, 3)));
        $_SESSION['kci_media_errors'] = array_slice($errors, 0, 10);
    } else {
        flash_set('error', 'No pictures were uploaded. ' . implode(' ', array_slice($errors, 0, 3)));
        $_SESSION['kci_media_errors'] = array_slice($errors, 0, 10);
    }
    header('Location: media.php');
    exit;
}

$mediaErrors = array();
if (isset($_SESSION['kci_media_errors']) && is_array($_SESSION['kci_media_errors'])) {
    $mediaErrors = $_SESSION['kci_media_errors'];
    unset($_SESSION['kci_media_errors']);
}

/* Grid: newest first, 24 per page. */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$perPage = 24;
$total = 0;
try {
    $tstmt = $conn->prepare('SELECT COUNT(*) AS c FROM media');
    if ($tstmt !== false) {
        $tstmt->execute();
        $trow = $tstmt->get_result()->fetch_assoc();
        if (is_array($trow) && isset($trow['c'])) { $total = (int)$trow['c']; }
        $tstmt->close();
    }
} catch (Throwable $ignored) { $total = 0; }
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $perPage;
$rows = array();
try {
    $lstmt = $conn->prepare('SELECT id, filename, mime, width, height, size_bytes, created_at FROM media ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
    if ($lstmt !== false) {
        $lstmt->bind_param('ii', $perPage, $offset);
        $lstmt->execute();
        $lres = $lstmt->get_result();
        while ($lrow = $lres->fetch_assoc()) { $rows[] = $lrow; }
        $lstmt->close();
    }
} catch (Throwable $ignored) { $rows = array(); }

$pageTitle = 'Media';
$activeNav = 'media';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page kci-media">
<div class="kci-card kci-media__upload">
<h2><i class="fa-solid fa-cloud-arrow-up"></i> Upload pictures</h2>
<p class="kci-muted">JPEG, PNG or WebP only. Up to 10 files at once, each under 8 MB. Stored in the database.</p>
<?php if (!empty($mediaErrors)): ?>
<ul class="kci-media__errors" role="alert">
<?php foreach ($mediaErrors as $err): ?>
<li><?php echo e($err); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<form method="post" action="media.php" enctype="multipart/form-data" id="kci-media-form">
<?php csrf_field(); ?>
<div class="kci-media__drop" id="kci-media-drop" role="button" tabindex="0" aria-label="Drop pictures here or press Enter to choose files">
<i class="fa-solid fa-images kci-media__dropicon" aria-hidden="true"></i>
<p><strong>Drag and drop pictures here</strong> or <span class="kci-media__browse">browse files</span></p>
<p class="kci-muted kci-media__hint">A preview appears below before you upload.</p>
<p class="kci-muted kci-media__serverlimit">Server limit per file: <?php echo e(admin_media_format_limit_mb(admin_media_server_limit_bytes())); ?> MB. Large pictures are shrunk automatically before upload.</p>
<input type="file" name="pictures[]" id="kci-media-input" accept="image/jpeg,image/png,image/webp" multiple>
</div>
<div class="kci-media__preview" id="kci-media-preview" aria-live="polite"></div>
<div class="kci-actions">
<button type="submit" class="kci-btn kci-btn--primary"><i class="fa-solid fa-upload"></i> Upload pictures</button>
</div>
</form>
</div>
<div class="kci-card kci-media__library">
<h2><i class="fa-solid fa-images"></i> Library</h2>
<p class="kci-muted"><?php echo (int)$total; ?> picture<?php echo ((int)$total === 1) ? '' : 's'; ?> stored</p>
<?php if (empty($rows)): ?>
<p class="kci-muted">No pictures yet. Upload your first picture above.</p>
<?php else: ?>
<div class="kci-media__grid">
<?php foreach ($rows as $r): ?>
<?php $mid = (int)$r['id']; $murl = 'media.php?id=' . $mid; ?>
<?php $mdate = (string)$r['created_at']; ?>
<?php try { $mdate = (new DateTimeImmutable((string)$r['created_at']))->format('d M Y'); } catch (Throwable $ignored) { /* keep raw */ } ?>
<?php $kb = max(1, (int)round((int)$r['size_bytes'] / 1024)); ?>
<article class="kci-media__card">
<img class="kci-media__thumb" src="../<?php echo e($murl); ?>" alt="<?php echo e($r['filename']); ?>" loading="lazy">
<div class="kci-media__body">
<p class="kci-media__name" title="<?php echo e($r['filename']); ?>"><?php echo e($r['filename']); ?></p>
<p class="kci-media__meta"><?php echo (int)$kb; ?> KB · <?php echo (int)$r['width']; ?> x <?php echo (int)$r['height']; ?> · <?php echo e($mdate); ?></p>
<p class="kci-media__url"><code><?php echo e($murl); ?></code></p>
<div class="kci-media__row">
<button type="button" class="kci-pillbtn kci-pillbtn--amber" data-copy-link="<?php echo e($murl); ?>"><i class="fa-solid fa-link" aria-hidden="true"></i> Copy link</button>
<form method="post" action="media-action.php" data-confirm-title="Delete this picture?" data-confirm="Permanently delete this picture? This cannot be undone.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo (int)$mid; ?>">
<input type="hidden" name="return" value="media.php<?php echo ($page > 1) ? '?page=' . (int)$page : ''; ?>">
<button type="submit" class="kci-pillbtn kci-pillbtn--red"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
</form>
</div>
</div>
</article>
<?php endforeach; ?>
</div>
<?php $from = ($total === 0) ? 0 : $offset + 1; ?>
<?php $to = min($offset + $perPage, $total); ?>
<div class="kci-serve__pager">
<p class="kci-muted">Showing <?php echo (int)$from; ?> to <?php echo (int)$to; ?> of <?php echo (int)$total; ?></p>
<?php if ($pages > 1): ?>
<nav class="kci-pagination" aria-label="Media pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="media.php?page=<?php echo (int)($page - 1); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php $winStart = max(1, $page - 2); ?>
<?php $winEnd = min($pages, $page + 2); ?>
<?php for ($pager = $winStart; $pager <= $winEnd; $pager++): ?>
<?php if ($pager === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pager; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="media.php?page=<?php echo (int)$pager; ?>"><?php echo (int)$pager; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="media.php?page=<?php echo (int)($page + 1); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
<div class="kci-toast" id="kci-toast" role="status" hidden></div>
</section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>

