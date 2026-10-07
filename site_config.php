<?php
/**
 * site_config.php — Site-wide configuration for Kingdomite Church International.
 *
 * Include with include_once at the top of any page that needs:
 *   - Africa/Lagos timezone
 *   - CHURCH_NAME / CHURCH_ADDRESS / CHURCH_PHONE / CHURCH_EMAIL
 *   - CHURCH_WHATSAPP_URL (WhatsApp contact link)
 *   - CHURCH_FACEBOOK_URL (Facebook page link)
 */

// Set the site timezone to Africa/Lagos
date_default_timezone_set('Africa/Lagos');

// Church identity / contact
define('CHURCH_NAME', 'Kingdomite Church International');
define('CHURCH_ADDRESS', 'Beside Jumbo Close, off Ogboso Road, Obaema, Oyigbo, Rivers State, Nigeria');
define('CHURCH_PHONE', '+234 806 497 9241');
define('CHURCH_WHATSAPP_URL', 'https://wa.me/2348064979241');
define('CHURCH_EMAIL', 'dkcifamily@gmail.com');
define('CHURCH_FACEBOOK_URL', 'https://www.facebook.com/profile.php?id=100083099004068');
