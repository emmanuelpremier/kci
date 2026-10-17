<?php
/* admin/home-page.php — editor for the Home Page (index.php): the lead
 * pastor's picture and introduction, the four "Recent Quotes" cards and the
 * three testimonial quotes with their authors.
 *
 * Saving follows the Giving Account editor (giving-settings.php): CSRF token
 * plus the admin's CURRENT PASSWORD, server-side validation of every field,
 * pictures through the shared image picker (uploaded to the media table) and
 * values written to site_settings with INSERT ... ON DUPLICATE KEY UPDATE,
 * stamping updated_by with the logged-in admin's id. The public page reads
 * them back through kci_home_content() in kci_settings.php and falls back to
 * the built-in defaults, so the home page never breaks.
 * The table already exists and is never created or altered here. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/media_helpers.php';
require __DIR__ . '/includes/image_picker.php';
require __DIR__ . '/../kci_db.php';
require __DIR__ . '/../kci_settings.php';

$admin = require_admin($conn);
$adminId = (int)$admin['id'];

/* Field limits. */
$hpBioMax = 700;
$hpQuoteMax = 1200;
$hpAuthorMax = 80;

function hp_len($value) {
    $value = (string)$value;
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }
    return strlen($value);
}

function hp_cut($value, $max) {
    $value = (string)$value;
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, (int)$max, 'UTF-8');
    }
    return substr($value, 0, (int)$max);
}

/* Built-in Home Page defaults, matching the public page (index.php). These
 * are only shown until something is saved from this editor. */
function kci_home_defaults() {
	return array(
		'pastor_image'   => 'kci_image/im8.webp',
		'pastor_bio'     => "I'm David Vincent, Lead Pastor of Kingdomite Church International. I personally invite you to our official website, I would love to hear from and pray with you. Get in touch with me via any of personal social media handles to stay connected with me, Thank you and remain blessed.",
		'quote_images'   => array(
			'kci_image/im4.webp',
			'kci_image/im7.webp',
			'kci_image/im6.webp',
			'kci_image/im5.webp',
		),
		'quote_texts'    => array(
			'During the embers of glory program, i wrote my prayer points as my pastor requested. all together i ended up writing four, my last request was very urgent being the fourth one. i asked GOD to provide money for me so i will be able to pay my childrens school fees which was already an disturbing issue because the term will soon start. So during the program i recieve a call that i least expected from and it solve the disturing issue that was on ground, before the embers of glory program end. So i just want to thank God for what he has done may his name be glorified in Jesus Name Amen.',
			'Praise the Lord Hallelujah........ My elder sister has this particular pain in her stomach and it has been for a very long time, recently finding out that it is fibroid so anytime i make a video call with her she is always crying, then i meet my pastor because she was about to have an operation the following day and she was scared so i told my pator to pray and my pastor said she will come out of the operation room without any harm and i say amen. The following morning my elder sister was the first person to call me how am i doing, i was suprised because this is the same person that anytime we talk on phone she is always crying, and she said the operation was successful.',
			'I have been asking God for to make my daughter pass her exams that she wont rewrite her jamb exam twice, and she will also pass her post utme exam and to God be the glory he answered the prayer. I am so happy because i have been struggling to pay her fees right from waec and God has been so faithful may his name be praised forever in Jesus name Amen.',
		),
		'quote_authors'  => array(
			'Pst. Mrs. Lucy David',
			'Bro. Prince',
			'Mrs. Emmanuel',
		),
	);
}

/* Current Home Page content: the defaults overlaid with anything saved in
 * site_settings by this editor. Reads use the shared kci_setting() helper
 * (loaded from kci_settings.php); a missing or empty row falls back to its
 * default, so this editor never breaks. */
function kci_home_content($conn) {
	$defaults = kci_home_defaults();
	$content  = $defaults;
	if ($conn instanceof mysqli) {
		$content['pastor_image'] = kci_setting($conn, 'home_pastor_image', $defaults['pastor_image']);
		$content['pastor_bio']   = kci_setting($conn, 'home_pastor_bio', $defaults['pastor_bio']);
		for ($i = 1; $i <= 4; $i++) {
			$content['quote_images'][$i - 1] = kci_setting($conn, 'home_quote_image_' . $i, $defaults['quote_images'][$i - 1]);
		}
		for ($i = 1; $i <= 3; $i++) {
			$content['quote_texts'][$i - 1]   = kci_setting($conn, 'home_quote_text_' . $i, $defaults['quote_texts'][$i - 1]);
			$content['quote_authors'][$i - 1] = kci_setting($conn, 'home_quote_author_' . $i, $defaults['quote_authors'][$i - 1]);
		}
	}
	return $content;
}


/* Current content on the public page (defaults overlaid with saved values). */
$values = kci_home_content($conn);
$defaults = kci_home_defaults();

$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* A POST without a valid CSRF token is rejected with 400. */
    if (!csrf_check()) {
        csrf_fail();
    }

    $values['pastor_bio'] = trim((string)($_POST['pastor_bio'] ?? ''));
    for ($i = 1; $i <= 3; $i++) {
        $values['quote_texts'][$i - 1] = trim((string)($_POST['quote_text_' . $i] ?? ''));
        $values['quote_authors'][$i - 1] = trim((string)($_POST['quote_author_' . $i] ?? ''));
    }

    /* --- Server-side validation --- */
    if ($values['pastor_bio'] === '') {
        $errors[] = 'Please write the introduction for the lead pastor.';
    } elseif (hp_len($values['pastor_bio']) > $hpBioMax) {
        $errors[] = 'Please keep the pastor introduction to ' . $hpBioMax . ' characters or fewer.';
    }
    for ($i = 1; $i <= 3; $i++) {
        $text = $values['quote_texts'][$i - 1];
        $author = $values['quote_authors'][$i - 1];
        if ($text === '') {
            $errors[] = 'Please write the text for quote ' . $i . '.';
        } elseif (hp_len($text) > $hpQuoteMax) {
            $errors[] = 'Quote ' . $i . ' must be ' . $hpQuoteMax . ' characters or fewer.';
        }
        if ($author === '') {
            $errors[] = 'Please write the author for quote ' . $i . '.';
        } elseif (hp_len($author) > $hpAuthorMax) {
            $errors[] = 'The author of quote ' . $i . ' must be ' . $hpAuthorMax . ' characters or fewer.';
        }
    }
    $currentPassword = (string)($_POST['current_password'] ?? '');
    if ($currentPassword === '') {
        $errors[] = 'Please enter your current password to confirm the change.';
    } elseif (!password_verify($currentPassword, (string)$admin['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }

    if (empty($errors)) {
        /* Pictures: new upload, "Remove" back to the default, or keep current. */
        $values['pastor_image'] = admin_resolve_image_field($conn, $adminId, 'pastor_image', 'kci_image/im8.webp');
        if (!empty($GLOBALS['kci_image_upload_error'])) {
            $errors[] = (string)$GLOBALS['kci_image_upload_error'];
        }
        for ($i = 1; $i <= 4; $i++) {
            $resolved = admin_resolve_image_field($conn, $adminId, 'quote_image_' . $i, $defaults['quote_images'][$i - 1]);
            if (!empty($GLOBALS['kci_image_upload_error'])) {
                $errors[] = (string)$GLOBALS['kci_image_upload_error'];
            }
            $values['quote_images'][$i - 1] = $resolved;
        }
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
                $settingsToSave = array(
                    'home_pastor_image' => $values['pastor_image'],
                    'home_pastor_bio'   => $values['pastor_bio'],
                );
                for ($i = 1; $i <= 4; $i++) {
                    $settingsToSave['home_quote_image_' . $i] = $values['quote_images'][$i - 1];
                }
                for ($i = 1; $i <= 3; $i++) {
                    $settingsToSave['home_quote_text_' . $i] = $values['quote_texts'][$i - 1];
                    $settingsToSave['home_quote_author_' . $i] = $values['quote_authors'][$i - 1];
                }
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
            /* Only the fact that the page changed is logged — never the text. */
            admin_log_action($conn, $adminId, 'home_page_updated', 'Pastor picture/intro, 4 quote cards and 3 quotes saved');
            flash_set('success', 'The Home Page has been updated. It is live on the public page now.');
            header('Location: home-page.php');
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
        $settingKey = 'home_pastor_bio';
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

$pageTitle = 'Home Page';
$activeNav = 'home';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<p class="kci-flash kci-flash--warning" role="alert">These details appear on the public Home Page immediately. Saving requires your current password.</p>
<?php if (!empty($errors)): ?>
<p class="kci-flash kci-flash--error" role="alert"><?php echo e(implode(' ', $errors)); ?></p>
<?php endif; ?>
<div class="kci-give">
<div class="kci-card">
<h2>Home Page content</h2>
<?php if ($lastUpdate !== null && $lastUpdate['name'] !== ''): ?>
<p class="kci-muted">Last updated by <?php echo e($lastUpdate['name']); ?> on <?php echo e($lastUpdate['date']); ?></p>
<?php elseif ($lastUpdate !== null): ?>
<p class="kci-muted">Last updated on <?php echo e($lastUpdate['date']); ?></p>
<?php else: ?>
<p class="kci-muted">Not saved yet — the public page is showing the defaults.</p>
<?php endif; ?>
<form method="post" action="home-page.php" enctype="multipart/form-data">
<?php csrf_field(); ?>

<h3 class="kci-home__section">Lead pastor</h3>
<?php admin_image_picker('pastor_image', $values['pastor_image'], 'Pastor picture'); ?>
<label class="kci-field">
<span>Pastor introduction</span>
<textarea name="pastor_bio" rows="4" maxlength="<?php echo (int)$hpBioMax; ?>" required><?php echo e($values['pastor_bio']); ?></textarea>
</label>
<p class="kci-muted">The paragraph under &ldquo;OUR LEAD PASTOR&rdquo;. Up to <?php echo (int)$hpBioMax; ?> characters.</p>

<h3 class="kci-home__section">Recent quote cards</h3>
<p class="kci-muted">The four picture cards in the &ldquo;RECENT QUOTES&rdquo; section, in order.</p>
<?php for ($i = 1; $i <= 4; $i++): ?>
<?php admin_image_picker('quote_image_' . $i, $values['quote_images'][$i - 1], 'Quote card ' . $i); ?>
<?php endfor; ?>

<h3 class="kci-home__section">Testimonial quotes</h3>
<p class="kci-muted">The three quotes that rotate under &ldquo;CONNECT WITH US&rdquo;.</p>
<?php for ($i = 1; $i <= 3; $i++): ?>
<label class="kci-field">
<span>Quote <?php echo (int)$i; ?> text</span>
<textarea name="quote_text_<?php echo (int)$i; ?>" rows="5" maxlength="<?php echo (int)$hpQuoteMax; ?>" required><?php echo e($values['quote_texts'][$i - 1]); ?></textarea>
</label>
<label class="kci-field">
<span>Quote <?php echo (int)$i; ?> author</span>
<input type="text" name="quote_author_<?php echo (int)$i; ?>" maxlength="<?php echo (int)$hpAuthorMax; ?>" required value="<?php echo e($values['quote_authors'][$i - 1]); ?>">
</label>
<?php endfor; ?>

<label class="kci-field">
<span>Current password</span>
<span class="kci-passwrap">
<input type="password" name="current_password" id="kci-password" autocomplete="current-password" required>
<button type="button" class="kci-showpass" id="kci-showpass" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
</span>
</label>
<p class="kci-muted">Type your current password to confirm the change. It is checked on the server and never stored.</p>
<div class="kci-actions">
<a class="kci-btn kci-btn--ghost" href="../index.php" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View public Home Page</a>
<button type="submit" class="kci-btn kci-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
</div>
</form>
</div>
<aside class="kci-card kci-give__preview">
<h2>Preview</h2>
<p class="kci-muted">This is exactly what visitors see on the Home Page.</p>
<div class="kci-home__preview">
<img class="kci-home__pastor" src="<?php echo e(admin_image_picker_src($values['pastor_image'], 'kci_image/im8.webp')); ?>" alt="Lead pastor picture">
<p class="kci-home__bio"><?php echo e($values['pastor_bio']); ?></p>
<div class="kci-home__cards">
<?php for ($i = 1; $i <= 4; $i++): ?>
<img src="<?php echo e(admin_image_picker_src($values['quote_images'][$i - 1], $defaults['quote_images'][$i - 1])); ?>" alt="Quote card <?php echo (int)$i; ?>">
<?php endfor; ?>
</div>
<div class="kci-home__quotes">
<?php for ($i = 1; $i <= 3; $i++): ?>
<p class="kci-home__quote"><?php echo e($values['quote_texts'][$i - 1]); ?><span class="kci-home__author"><?php echo e($values['quote_authors'][$i - 1]); ?></span></p>
<?php endfor; ?>
</div>
</div>
</aside>
</div>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>