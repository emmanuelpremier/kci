<?php
/* admin/ministries.php — Ministries list: status tabs with live counts,
 * name search, category filter, pagination (15 per page). Mirrors the
 * Events module patterns (layout includes, filters, flash, confirm modal). */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';
$admin = require_admin($conn);

function ministry_get_string($key) {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) { return ''; }
    return (string)$_GET[$key];
}

function ministry_filter_query($tab, $search, $category, $page) {
    $q = array();
    if ($tab !== '' && $tab !== 'active') { $q['tab'] = $tab; }
    if ($search !== '') { $q['q'] = $search; }
    if ($category !== '') { $q['cat'] = $category; }
    if ($page > 1) { $q['page'] = $page; }
    return http_build_query($q);
}

function ministry_page_url($tab, $search, $category, $page) {
    $qs = ministry_filter_query($tab, $search, $category, $page);
    return 'ministries.php' . ($qs !== '' ? '?' . $qs : '');
}

/* Picture src for the list thumbnail; '' when the ministry has no usable
 * picture (the icon circle is rendered instead). */
function kci_admin_ministry_thumb_src($value) {
    $value = trim((string)$value);
    if (preg_match('/^media\.php\?id=[0-9]+$/', $value)) { return '../' . $value; }
    if (preg_match('/^kci_image\/[A-Za-z0-9._-]+$/', $value)) { return '../' . $value; }
    return '';
}

/* Safe Font Awesome class string for the icon circle. */
function kci_admin_ministry_icon($value) {
    $value = trim((string)$value);
    if ($value !== '' && preg_match('/^[A-Za-z0-9 -]{1,100}$/', $value)) { return $value; }
    return 'fa-solid fa-church';
}

/* Serve applications per ministry slug (one grouped query, whole table). */
function kci_ministry_app_counts($conn) {
    $counts = array();
    try {
        $stmt = $conn->prepare('SELECT ministry_slug, COUNT(*) AS c FROM serve_applications WHERE ministry_slug IS NOT NULL GROUP BY ministry_slug');
        if ($stmt === false) { return $counts; }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res !== false) {
            while ($row = $res->fetch_assoc()) {
                $counts[(string)$row['ministry_slug']] = (int)$row['c'];
            }
        }
        $stmt->close();
    } catch (Throwable $ignored) {
        $counts = array();
    }
    return $counts;
}

/* Distinct categories currently in use (filter select + whitelist). */
function kci_ministry_categories($conn) {
    $categories = array();
    try {
        $stmt = $conn->prepare('SELECT DISTINCT category FROM ministries ORDER BY category ASC');
        if ($stmt === false) { return $categories; }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res !== false) {
            while ($row = $res->fetch_assoc()) {
                $cat = trim((string)$row['category']);
                if ($cat !== '' && !in_array($cat, $categories, true)) { $categories[] = $cat; }
            }
        }
        $stmt->close();
    } catch (Throwable $ignored) {
        $categories = array();
    }
    return $categories;
}

$tab = strtolower(trim(ministry_get_string('tab')));
if (!in_array($tab, array('active', 'hidden', 'all'), true)) { $tab = 'active'; }
$search = trim(ministry_get_string('q'));
if (function_exists('mb_substr')) { $search = mb_substr($search, 0, 100, 'UTF-8'); }
else { $search = substr($search, 0, 100); }
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$perPage = 15;

$categories = kci_ministry_categories($conn);
$category = ministry_get_string('cat');
if (!in_array($category, $categories, true)) { $category = ''; }

$likePattern = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $search) . '%';
$hasSearch = ($search === '') ? '' : '1';
$hasCategory = ($category === '') ? '' : '1';

/* Tab counts follow the search + category filters (live counts). */
$tabCounts = array('active' => 0, 'hidden' => 0, 'all' => 0);
try {
    $cstmt = $conn->prepare('SELECT status FROM ministries WHERE (? = \'\' OR name LIKE ?) AND (? = \'\' OR category = ?)');
    if ($cstmt !== false) {
        $cstmt->bind_param('ssss', $hasSearch, $likePattern, $hasCategory, $category);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres !== false) {
            while ($crow = $cres->fetch_assoc()) {
                $st = (isset($crow['status']) && $crow['status'] === 'inactive') ? 'hidden' : 'active';
                $tabCounts[$st]++;
                $tabCounts['all']++;
            }
        }
        $cstmt->close();
    }
} catch (Throwable $ignored) {
}
$total = (int)$tabCounts[$tab];
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $perPage;

/* Rows for the current tab, sorted by id, 15 per page. The status literal
 * below is built only from the whitelisted tab value above. */
$statusSql = '';
if ($tab === 'active') { $statusSql = ' AND status = \'active\''; }
elseif ($tab === 'hidden') { $statusSql = ' AND status = \'inactive\''; }
$rows = array();
try {
    $lstmt = $conn->prepare('SELECT id, name, slug, category, icon, image, meeting, status FROM ministries'
        . ' WHERE (? = \'\' OR name LIKE ?) AND (? = \'\' OR category = ?)' . $statusSql
        . ' ORDER BY id ASC LIMIT ?, ?');
    if ($lstmt !== false) {
        $lstmt->bind_param('ssssii', $hasSearch, $likePattern, $hasCategory, $category, $offset, $perPage);
        $lstmt->execute();
        $lres = $lstmt->get_result();
        if ($lres !== false) {
            while ($lrow = $lres->fetch_assoc()) { $rows[] = $lrow; }
        }
        $lstmt->close();
    }
} catch (Throwable $ignored) {
    $rows = array();
}

$appCounts = kci_ministry_app_counts($conn);
$qsNow = ministry_filter_query($tab, $search, $category, $page);
$returnTarget = 'ministries.php' . ($qsNow !== '' ? '?' . $qsNow : '');
$tabLabels = array('active' => 'Active', 'hidden' => 'Hidden', 'all' => 'All');
$pageTitle = 'Ministries';
$activeNav = 'ministries';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page kci-serve">
<div class="kci-card kci-serve__toolbar">
<div class="kci-serve__head">
<div>
<h2 class="kci-serve__heading"><i class="fa-solid fa-people-group"></i> Ministries</h2>
<p class="kci-muted">Manage the ministry teams visitors see across the site.</p>
</div>
<div class="kci-actions">
<a class="kci-btn kci-btn--primary" href="ministry-edit.php"><i class="fa-solid fa-plus"></i> Add ministry</a>
</div>
</div>
<nav class="kci-tabs" aria-label="Ministry status">
<?php foreach ($tabLabels as $tabValue => $tabLabel): ?>
<?php $tabActive = ($tab === $tabValue); ?>
<a class="kci-tabs__item<?php echo $tabActive ? ' is-active' : ''; ?>"<?php echo $tabActive ? ' aria-current="page"' : ''; ?> href="<?php echo e(ministry_page_url($tabValue, $search, $category, 1)); ?>"><?php echo e($tabLabel); ?> <span class="kci-tabs__count"><?php echo (int)$tabCounts[$tabValue]; ?></span></a>
<?php endforeach; ?>
</nav>
<form method="get" action="ministries.php" class="kci-serve__filters" role="search">
<input type="hidden" name="tab" value="<?php echo e($tab); ?>">
<label class="kci-serve__search">
<span class="kci-serve__search-label">Search ministries</span>
<span class="kci-serve__search-box">
<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
<input type="search" name="q" maxlength="100" autocomplete="off" placeholder="Ministry name" value="<?php echo e($search); ?>">
</span>
</label>
<label class="kci-serve__ministry">
<span class="kci-serve__search-label">Category</span>
<select name="cat">
<option value="">All categories</option>
<?php foreach ($categories as $catOption): ?>
<option value="<?php echo e($catOption); ?>"<?php echo ($category === $catOption) ? ' selected' : ''; ?>><?php echo e($catOption); ?></option>
<?php endforeach; ?>
</select>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
<?php if ($search !== '' || $category !== ''): ?>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="<?php echo e(ministry_page_url($tab, '', '', 1)); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
<?php endif; ?>
</form>
</div>
<?php if (empty($rows)): ?>
<div class="kci-card kci-serve__empty">
<span class="kci-serve__empty-icon" aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
<h2><?php echo ($search !== '' || $category !== '') ? 'No ministries found' : 'No ministries yet'; ?></h2>
<p class="kci-muted"><?php echo ($search !== '' || $category !== '') ? 'There is nothing here under these filters yet.' : 'Add your first ministry and it will appear on the site right away.'; ?></p>
<div class="kci-actions kci-serve__emptyactions">
<a class="kci-btn kci-btn--primary kci-btn--sm" href="ministry-edit.php"><i class="fa-solid fa-plus"></i> Add ministry</a>
<a class="kci-btn kci-btn--ghost kci-btn--sm" href="ministries.php"><i class="fa-solid fa-xmark"></i> Clear all filters</a>
</div>
</div>
<?php else: ?>
<div class="kci-tablewrap kci-serve__wrap kci-min__wrap">
<table class="kci-table kci-serve__table kci-min__table">
<thead>
<tr>
<th scope="col">Ministry</th>
<th scope="col">Category</th>
<th scope="col">Meeting</th>
<th scope="col">Status</th>
<th scope="col">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
<?php $mid = (int)$row['id']; ?>
<?php $mSlug = (string)$row['slug']; ?>
<?php $thumb = kci_admin_ministry_thumb_src(isset($row['image']) ? $row['image'] : ''); ?>
<?php $iconClass = kci_admin_ministry_icon(isset($row['icon']) ? $row['icon'] : ''); ?>
<?php $isActive = (isset($row['status']) && $row['status'] === 'active'); ?>
<?php $chip = $isActive ? 'kci-chip--green' : 'kci-chip--grey'; ?>
<?php $appCount = isset($appCounts[$mSlug]) ? (int)$appCounts[$mSlug] : 0; ?>
<?php $appsLabel = ($appCount === 1) ? '1 application' : $appCount . ' applications'; ?>
<?php $vurl = '../ministry.php?slug=' . urlencode($mSlug); ?>
<tr>
<td data-label="Ministry">
<span class="kci-min__cell">
<?php if ($thumb !== ''): ?>
<img class="kci-min__thumb" src="<?php echo e($thumb); ?>" alt="" loading="lazy">
<?php else: ?>
<span class="kci-min__iconcircle" aria-hidden="true"><i class="<?php echo e($iconClass); ?>"></i></span>
<?php endif; ?>
<span class="kci-min__meta">
<span class="kci-min__title" title="<?php echo e($row['name']); ?>"><?php echo e($row['name']); ?></span>
<span class="kci-muted kci-min__apps"><?php echo e($appsLabel); ?></span>
</span>
</span>
</td>
<td data-label="Category"><span class="kci-min__cat"><?php echo e($row['category']); ?></span></td>
<td data-label="Meeting"><span class="kci-min__meeting" title="<?php echo e($row['meeting']); ?>"><?php echo e($row['meeting']); ?></span></td>
<td data-label="Status"><span class="kci-chip <?php echo e($chip); ?>"><?php echo $isActive ? 'Active' : 'Hidden'; ?></span></td>
<td data-label="Actions">
<div class="kci-serve__rowactions kci-min__actions">
<a class="kci-pillbtn kci-pillbtn--blue" href="<?php echo e($vurl); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> View</a>
<a class="kci-pillbtn kci-pillbtn--amber" href="ministry-edit.php?id=<?php echo (int)$mid; ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
<form method="post" action="ministry-action.php" class="kci-serve__form">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="<?php echo $isActive ? 'hide' : 'show'; ?>">
<input type="hidden" name="id" value="<?php echo (int)$mid; ?>">
<input type="hidden" name="return" value="<?php echo e($returnTarget); ?>">
<button type="submit" class="kci-pillbtn <?php echo $isActive ? 'kci-pillbtn--outline' : 'kci-pillbtn--green'; ?>">
<i class="fa-solid <?php echo $isActive ? 'fa-eye-slash' : 'fa-eye'; ?>" aria-hidden="true"></i> <?php echo $isActive ? 'Hide' : 'Show'; ?>
</button>
</form>
<form method="post" action="ministry-action.php" class="kci-serve__form" data-confirm-title="Delete this ministry?" data-confirm="This removes the ministry and its page. <?php echo (int)$appCount; ?> application<?php echo ($appCount === 1) ? '' : 's'; ?> mention<?php echo ($appCount === 1) ? 's' : ''; ?> it and will keep their record but show the ministry as removed. Consider Hide instead.">
<?php csrf_field(); ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo (int)$mid; ?>">
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
<nav class="kci-pagination" aria-label="Ministries pages">
<?php if ($page > 1): ?>
<a class="kci-pagination__item" href="<?php echo e(ministry_page_url($tab, $search, $category, $page - 1)); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php endif; ?>
<?php $w0 = max(1, $page - 2); ?>
<?php $w1 = min($pages, $page + 2); ?>
<?php for ($pg = $w0; $pg <= $w1; $pg++): ?>
<?php if ($pg === $page): ?>
<span class="kci-pagination__item is-current" aria-current="page"><?php echo (int)$pg; ?></span>
<?php else: ?>
<a class="kci-pagination__item" href="<?php echo e(ministry_page_url($tab, $search, $category, $pg)); ?>"><?php echo (int)$pg; ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $pages): ?>
<a class="kci-pagination__item" href="<?php echo e(ministry_page_url($tab, $search, $category, $page + 1)); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<?php endif; ?>
</nav>
<?php endif; ?>
</div>
<?php endif; ?>
</section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>