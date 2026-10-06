<?php
/**
 * kci_validate.php — shared validation helpers for the Kingdomite
 * Church International website (PHP + mysqli).
 *
 * Included with include_once by contact.php, serve.php, form.php and
 * giving.php so every form on the site applies exactly the same phone,
 * email and date rules and stores the same normalised values.
 *
 * All helpers are pure: no database access, no DNS or network lookups,
 * nothing logged, nothing stored anywhere.
 */

/**
 * Normalise and validate a visitor-typed phone number.
 *
 * Spaces, dashes, dots and parentheses are removed first. Anything
 * other than digits and ONE leading "+" is rejected.
 *
 * Accepted Nigerian formats (result is always "+234" + the 10 digits):
 *   - 08031234567            11 digits with a leading 0
 *   - 2348031234567          234 + 10 digits (with or without "+")
 *   - +2348031234567
 *   - +234 0803 123 4567     "+234" + trunk 0 + 10 digits (0 dropped)
 * After the country code or leading 0, the 10-digit national number
 * must start with 7, 8 or 9.
 *
 * Any other international number written with a leading "+" is kept
 * as "+" + digits when the digit count is 8-15 (E.164 range).
 *
 * @param string $raw Raw value typed by the visitor.
 * @return string|null Normalised number, or null when invalid.
 */
function kci_normalize_phone(string $raw): ?string {
	$phone = trim($raw);

	/* Remove the separators people love to type. */
	$phone = str_replace([' ', '-', '.', '(', ')'], '', $phone);

	/* Only digits and at most one leading "+" may remain. */
	if (!preg_match('/^\+?[0-9]+$/', $phone)) {
		return null;
	}

	$hasPlus = ($phone[0] === '+');
	$digits  = substr($phone, $hasPlus ? 1 : 0);
	$len     = strlen($digits);

	/* --- Nigerian numbers (country code 234) ------------------- */
	if (substr($digits, 0, 3) === '234') {
		$national = substr($digits, 3);
		/* "+23408031234567" — drop the extra trunk 0. */
		if ($hasPlus && strlen($national) === 11 && $national[0] === '0') {
			$national = substr($national, 1);
		}
		/* 234 must be followed by exactly 10 digits. */
		if (strlen($national) !== 10) {
			return null;
		}
		/* The national number must start with 7, 8 or 9. */
		if ($national[0] !== '7' && $national[0] !== '8' && $national[0] !== '9') {
			return null;
		}
		return '+234' . $national;
	}

	/* --- "0XXXXXXXXX": 11 digits with a leading 0 -------------- */
	if (!$hasPlus) {
		if ($len === 11 && $digits[0] === '0') {
			$national = substr($digits, 1);
			if ($national[0] !== '7' && $national[0] !== '8' && $national[0] !== '9') {
				return null;
			}
			return '+234' . $national;
		}
		/* Anything else without a "+" is not valid. */
		return null;
	}

	/* --- Other international numbers --------------------------- */
	if ($len < 8 || $len > 15) {
		return null;
	}
	return '+' . $digits;
}

/**
 * Check that a value is a real calendar date in YYYY-MM-DD form that
 * is not in the future and not older than $maxDaysBack days.
 *
 * The round-trip comparison (parsed value must reproduce the input
 * exactly) rejects impossible dates such as 2026-02-31, which
 * DateTimeImmutable would otherwise roll over to 2026-03-03.
 *
 * @param string $raw         Raw value typed by the visitor.
 * @param int    $maxDaysBack How far back the date may be (default 90).
 * @return bool
 */
function kci_valid_recent_date(string $raw, int $maxDaysBack = 90): bool {
	$raw = trim($raw);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
		return false;
	}
	/* "!" zeroes the time, so all comparisons happen at midnight. */
	$date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
	if ($date === false || $date->format('Y-m-d') !== $raw) {
		return false;
	}
	$today = new DateTimeImmutable('today');
	if ($date > $today) {
		return false;
	}
	$oldest = $today->modify('-' . (int)$maxDaysBack . ' days');
	return $date >= $oldest;
}

/**
 * Check an email address with the shared site rules.
 *
 * The address is trimmed and lower-cased first, so the same input
 * always stores the same value. Nothing is looked up on the network —
 * the typo map below is a plain in-memory array kept at the top of the
 * function so new typos can be added in one line.
 *
 * Rejected: empty (when required), longer than 254 characters, anything
 * filter_var() refuses, a domain without a dot, a domain with empty
 * labels (consecutive dots), a final label shorter than two letters,
 * and the known provider typos (which get a "did you mean" message).
 *
 * @param string $raw      Raw value typed by the visitor.
 * @param bool   $required Whether the field may be left empty.
 * @return array{ok: bool, email: string, error: string}
 *         'email' is the normalised (lower-case) address; 'error' is
 *         empty when 'ok' is true.
 */
function kci_check_email(string $raw, bool $required): array {
	/* Common provider typos => the correct domain. Easy to extend. */
	$knownTypos = [
		/* Gmail */
		'gmail.co'     => 'gmail.com',
		'gmail.con'    => 'gmail.com',
		'gmail.cm'     => 'gmail.com',
		'gmail.om'     => 'gmail.com',
		'gmial.com'    => 'gmail.com',
		'gmai.com'     => 'gmail.com',
		'gmal.com'     => 'gmail.com',
		'gnail.com'    => 'gmail.com',
		'gmil.com'     => 'gmail.com',
		'gmaill.com'   => 'gmail.com',
		'gamil.com'    => 'gmail.com',
		/* Yahoo */
		'yahoo.co'     => 'yahoo.com',
		'yahoo.con'    => 'yahoo.com',
		'yaho.com'     => 'yahoo.com',
		'yahooo.com'   => 'yahoo.com',
		'yahho.com'    => 'yahoo.com',
		/* Hotmail */
		'hotmail.co'   => 'hotmail.com',
		'hotmail.con'  => 'hotmail.com',
		'hotmial.com'  => 'hotmail.com',
		'hotmal.com'   => 'hotmail.com',
		'hotmai.com'   => 'hotmail.com',
		/* Outlook */
		'outlook.con'  => 'outlook.com',
		'outlook.co'   => 'outlook.com',
		'outlok.com'   => 'outlook.com',
		'outlookk.com' => 'outlook.com',
		/* iCloud */
		'icloud.con'   => 'icloud.com',
		'icloud.co'    => 'icloud.com',
		'iclould.com'  => 'icloud.com',
	];

	$email = strtolower(trim($raw));

	if ($email === '') {
		if ($required) {
			return ['ok' => false, 'email' => '', 'error' => 'Please enter your email address.'];
		}
		return ['ok' => true, 'email' => '', 'error' => ''];
	}

	$invalid = 'Please enter a valid email address.';

	if (strlen($email) > 254) {
		return ['ok' => false, 'email' => $email, 'error' => $invalid];
	}
	if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
		return ['ok' => false, 'email' => $email, 'error' => $invalid];
	}

	$atPos  = strrpos($email, '@');
	$local  = substr($email, 0, $atPos);
	$domain = substr($email, $atPos + 1);

	/* Domain must contain a dot (so "vicky@gmail" fails) … */
	if (strpos($domain, '.') === false) {
		return ['ok' => false, 'email' => $email, 'error' => $invalid];
	}
	$labels = explode('.', $domain);
	/* … have no empty labels ("gmail..com", ".com") … */
	if (in_array('', $labels, true)) {
		return ['ok' => false, 'email' => $email, 'error' => $invalid];
	}
	/* … and end in a real-looking TLD of at least two letters. */
	$lastLabel = (string)end($labels);
	if (strlen($lastLabel) < 2) {
		return ['ok' => false, 'email' => $email, 'error' => $invalid];
	}

	/* Known provider typo — suggest the corrected address using the
	   visitor's own name part, e.g. "vicky@gmail.co" below. */
	if (isset($knownTypos[$domain])) {
		return [
			'ok'    => false,
			'email' => $email,
			'error' => 'That email looks mistyped. Did you mean ' . $local . '@' . $knownTypos[$domain] . '?',
		];
	}

	return ['ok' => true, 'email' => $email, 'error' => ''];
}

/**
 * Check that a value is a real calendar date in YYYY-MM-DD form that
 * is not before today and not more than $maxDaysAhead days away —
 * the mirror image of kci_valid_recent_date() for future dates such
 * as a planned visit. The round-trip comparison rejects impossible
 * dates like 2026-02-31.
 *
 * @param string $raw          Raw value typed by the visitor.
 * @param int    $maxDaysAhead How far ahead the date may be (default 180).
 * @return bool
 */
function kci_valid_upcoming_date(string $raw, int $maxDaysAhead = 180): bool {
	$raw = trim($raw);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
		return false;
	}
	/* "!" zeroes the time, so all comparisons happen at midnight. */
	$date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
	if ($date === false || $date->format('Y-m-d') !== $raw) {
		return false;
	}
	$today = new DateTimeImmutable('today');
	if ($date < $today) {
		return false;
	}
	$latest = $today->modify('+' . (int)$maxDaysAhead . ' days');
	return $date <= $latest;
}
