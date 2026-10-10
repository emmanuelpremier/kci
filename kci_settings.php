<?php
/* kci_settings.php — read-only helpers for the site_settings table.
 * The Giving Account editor (admin/giving-settings.php) saves the bank
 * name, account number, account name and card theme into site_settings;
 * the public Giving page reads them back through kci_giving_details().
 * All rows are loaded once per request into a static cache; if the query
 * fails the cache simply stays empty and every call falls back to its
 * default, so a database problem can never break a page.
 * The table already exists and is never created or altered here.
 * This file outputs nothing — include it before any HTML. */

/* Return one stored setting, or $default when the row is missing, the
 * stored value is empty, or the query failed. */
function kci_setting(mysqli $conn, string $key, string $default = ''): string {
	static $settings = null;
	if ($settings === null) {
		$settings = array();
		try {
			$stmt = $conn->prepare('SELECT setting_key, setting_value FROM site_settings');
			if ($stmt !== false) {
				if ($stmt->execute()) {
					$result = $stmt->get_result();
					if ($result !== false) {
						while ($row = $result->fetch_assoc()) {
							$settings[(string)$row['setting_key']] = (string)$row['setting_value'];
						}
					}
				}
				$stmt->close();
			}
		} catch (Throwable $ignored) {
			$settings = array(); /* query failed — every key falls back to its default */
		}
	}
	$value = isset($settings[$key]) ? trim($settings[$key]) : '';
	return ($value === '') ? $default : $value;
}

/* Giving Account details for the public Giving page.
 * Defaults keep the page working before anything has ever been saved;
 * a stored number that is not exactly 10 digits or a theme outside the
 * five supported styles is ignored in favour of the default. */
function kci_giving_details($conn): array {
	$defaults = array(
		'bank'   => 'Opay',
		'number' => '9013194092',
		'name'   => 'Lucy David Vincent',
		'theme'  => 'green',
	);
	$themes = array('green', 'orange', 'blue', 'purple', 'black');

	$bank = $defaults['bank'];
	$number = $defaults['number'];
	$name = $defaults['name'];
	$theme = $defaults['theme'];
	if ($conn instanceof mysqli) {
		$bank = kci_setting($conn, 'giving_bank_name', $defaults['bank']);
		$number = kci_setting($conn, 'giving_account_number', $defaults['number']);
		$name = kci_setting($conn, 'giving_account_name', $defaults['name']);
		$theme = kci_setting($conn, 'giving_card_theme', $defaults['theme']);
	}
	if (!preg_match('/^\d{10}$/', $number)) {
		$number = $defaults['number'];
	}
	if (!in_array($theme, $themes, true)) {
		$theme = $defaults['theme'];
	}
	return array(
		'bank'   => $bank,
		'number' => $number,
		'name'   => $name,
		'theme'  => $theme,
	);
}
