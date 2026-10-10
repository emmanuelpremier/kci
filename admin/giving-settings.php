<?php
/* admin/giving-settings.php — editor for the Giving Account shown on the
 * public Giving page (giving.php). Saving requires the admin's CURRENT
 * PASSWORD in addition to the CSRF token; every value is validated on the
 * server and written to site_settings with INSERT ... ON DUPLICATE KEY
 * UPDATE, stamping updated_by with the logged-in admin's id.
 * The table already exists and is never created or altered here. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';
require __DIR__ . '/../kci_settings.php';

$admin = require_admin($conn);

/* The five supported card styles (kept in sync with kci_giving_details). */
$cardThemes = array('green', 'orange', 'blue', 'purple', 'black');

/* Common Nigerian banks offered as datalist suggestions. */
$bankSuggestions = array(
	'Opay', 'GTBank', 'Access Bank', 'First Bank', 'UBA', 'Zenith Bank',
	'Kuda', 'Moniepoint', 'PalmPay', 'Wema Bank', 'Fidelity Bank',
	'Stanbic IBTC', 'Sterling Bank', 'Union Bank', 'FCMB', 'Polaris Bank',
	'Keystone Bank',
);

/* Effective values currently shown on the public Giving page. */
$current = kci_giving_details($conn);

$errors = array();
$values = array(
	'bank'   => $current['bank'],
	'number' => $current['number'],
	'name'   => $current['name'],
	'theme'  => $current['theme'],
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	/* A POST without a valid CSRF token is rejected with 400. */
	if (!csrf_check()) {
		csrf_fail();
	}

	$values['bank'] = trim((string)($_POST['bank_name'] ?? ''));
	$values['number'] = trim((string)($_POST['account_number'] ?? ''));
	$values['name'] = trim((string)($_POST['account_name'] ?? ''));
	$values['theme'] = (string)($_POST['card_theme'] ?? '');
	$currentPassword = (string)($_POST['current_password'] ?? '');

	/* --- Server-side validation --- */
	if ($values['bank'] === '') {
		$errors[] = 'Please enter the bank name.';
	} elseif (mb_strlen($values['bank']) > 40) {
		$errors[] = 'Please keep the bank name to 40 characters or fewer.';
	} elseif (!preg_match('/^[\p{L}\p{N} &.,\'-]+$/u', $values['bank'])) {
		$errors[] = 'The bank name may only contain letters, numbers, spaces and the characters & . , \' -';
	}
	if (!preg_match('/^\d{10}$/', $values['number'])) {
		$errors[] = 'Please enter the account number exactly as 10 digits.';
	}
	if (!preg_match('/^[A-Za-z .\'-]{3,60}$/', $values['name'])) {
		$errors[] = 'The account name must be 3 to 60 characters using only letters, spaces, full stops, apostrophes and hyphens.';
	}
	if (!in_array($values['theme'], $cardThemes, true)) {
		$errors[] = 'Please choose one of the five card styles.';
	}
	if ($currentPassword === '') {
		$errors[] = 'Please enter your current password to confirm the change.';
	} elseif (!password_verify($currentPassword, (string)$admin['password_hash'])) {
		$errors[] = 'Your current password is incorrect.';
	}

	if (empty($errors)) {
		$saved = false;
		try {
			$stmt = $conn->prepare(
				'INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)'
				. ' ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),'
				. ' updated_at = CURRENT_TIMESTAMP, updated_by = VALUES(updated_by)'
			);
			if ($stmt !== false) {
				$adminId = (int)$admin['id'];
				$settingsToSave = array(
					'giving_bank_name'      => $values['bank'],
					'giving_account_number' => $values['number'],
					'giving_account_name'   => $values['name'],
					'giving_card_theme'     => $values['theme'],
				);
				$saved = true;
				foreach ($settingsToSave as $settingKey => $settingValue) {
					$stmt->bind_param('ssi', $settingKey, $settingValue, $adminId);
					if (!$stmt->execute()) {
						$saved = false;
						break;
					}
				}
				$stmt->close();
			}
		} catch (Throwable $ignored) {
			$saved = false;
		}
		if ($saved) {
			/* Log the bank name and ONLY the last 4 digits of the old and
			   new account number — never the full number. */
			admin_log_action(
				$conn,
				(int)$admin['id'],
				'giving_account_updated',
				'Bank: ' . $values['bank']
					. ' · account ' . admin_last4($current['number'])
					. ' → ' . admin_last4($values['number'])
			);
			flash_set('success', 'The Giving Account has been updated. It is live on the public Giving page now.');
			header('Location: giving-settings.php');
			exit;
		}
		$errors[] = 'The details could not be saved. Please try again.';
	}
}

/* "Last updated by NAME on DATE" — from site_settings + admin_users. */
$lastUpdate = null;
try {
	$stmt = $conn->prepare(
		'SELECT s.updated_at, u.display_name'
		. ' FROM site_settings s'
		. ' LEFT JOIN admin_users u ON u.id = s.updated_by'
		. ' WHERE s.setting_key = ? LIMIT 1'
	);
	if ($stmt !== false) {
		$settingKey = 'giving_account_number';
		$stmt->bind_param('s', $settingKey);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
		$stmt->close();
		if ($row !== null && !empty($row['updated_at'])) {
			$lastUpdate = array(
				'name' => (string)($row['display_name'] ?? ''),
				'date' => date('d M Y', (int)strtotime((string)$row['updated_at'])),
			);
		}
	}
} catch (Throwable $ignored) {
	$lastUpdate = null;
}

$pageTitle = 'Giving Account';
$activeNav = 'giving';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<p class="kci-flash kci-flash--warning" role="alert">These details appear on the public Giving page immediately. Double-check the account number.</p>
<?php if (!empty($errors)): ?>
<p class="kci-flash kci-flash--error" role="alert"><?php echo e(implode(' ', $errors)); ?></p>
<?php endif; ?>
<div class="kci-give">
<div class="kci-card">
<h2>Giving Account details</h2>
<?php if ($lastUpdate !== null && $lastUpdate['name'] !== ''): ?>
<p class="kci-muted">Last updated by <?php echo e($lastUpdate['name']); ?> on <?php echo e($lastUpdate['date']); ?></p>
<?php elseif ($lastUpdate !== null): ?>
<p class="kci-muted">Last updated on <?php echo e($lastUpdate['date']); ?></p>
<?php else: ?>
<p class="kci-muted">Not saved yet — the public page is showing the defaults.</p>
<?php endif; ?>
<form method="post" action="giving-settings.php">
<?php csrf_field(); ?>
<label class="kci-field">
<span>Bank name</span>
<input type="text" name="bank_name" id="giving-bank" list="giving-bank-list" maxlength="40" required autocomplete="off" value="<?php echo e($values['bank']); ?>">
</label>
<datalist id="giving-bank-list">
<?php foreach ($bankSuggestions as $suggestedBank): ?>
<option value="<?php echo e($suggestedBank); ?>"></option>
<?php endforeach; ?>
</datalist>
<label class="kci-field">
<span>Account number</span>
<input type="text" name="account_number" id="giving-number" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" required autocomplete="off" value="<?php echo e($values['number']); ?>">
</label>
<label class="kci-field">
<span>Account name</span>
<input type="text" name="account_name" id="giving-name" minlength="3" maxlength="60" required autocomplete="off" value="<?php echo e($values['name']); ?>">
</label>
<fieldset class="kci-field kci-themes">
<legend>Card style</legend>
<div class="kci-swatches">
<?php foreach ($cardThemes as $cardThemeOption): ?>
<label class="kci-swatch kci-swatch--<?php echo e($cardThemeOption); ?>">
<input type="radio" name="card_theme" value="<?php echo e($cardThemeOption); ?>"<?php echo ($values['theme'] === $cardThemeOption) ? ' checked' : ''; ?>>
<span class="kci-swatch__dot" aria-hidden="true"></span>
<span class="kci-swatch__label"><?php echo e(ucfirst($cardThemeOption)); ?></span>
</label>
<?php endforeach; ?>
</div>
</fieldset>
<label class="kci-field">
<span>Current password</span>
<span class="kci-passwrap">
<input type="password" name="current_password" id="kci-password" autocomplete="current-password" required>
<button type="button" class="kci-showpass" id="kci-showpass" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
</span>
</label>
<p class="kci-muted">Type your current password to confirm the change. It is checked on the server and never stored.</p>
<div class="kci-actions">
<a class="kci-btn kci-btn--ghost" href="../giving.php" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View public Giving page</a>
<button type="submit" class="kci-btn kci-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
</div>
</form>
</div>
<aside class="kci-card kci-give__preview">
<h2>Live preview</h2>
<p class="kci-muted">This is exactly what visitors see on the Giving page.</p>
<div class="giving-preview giving-preview--<?php echo e($values['theme']); ?>" id="givingPreview">
<div class="giving-preview__top">
<span class="giving-preview__chip"></span>
<span class="giving-preview__bank" id="giving-preview-bank"><?php echo e($values['bank']); ?></span>
<i class="fa-solid fa-wifi giving-preview__wave" aria-hidden="true"></i>
</div>
<p class="giving-preview__number" id="giving-preview-number"><?php echo e(admin_group_number($values['number'])); ?></p>
<div class="giving-preview__bottom">
<div class="giving-preview__holder">
<span class="giving-preview__label">Account Name</span>
<strong class="giving-preview__name" id="giving-preview-name"><?php echo e(strtoupper($values['name'])); ?></strong>
</div>
<span class="giving-preview__tag">Giving</span>
</div>
</div>
</aside>
</div>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
