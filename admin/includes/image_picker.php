<?php
/* admin/includes/image_picker.php — shared picture field for admin forms.
 *
 * admin_image_picker() renders a labelled field with a preview of the
 * current picture, a file input that the browser shrinks via the shared
 * admin.js shrink helper (data-kci-shrink), a hidden input holding the
 * current value, and a "Remove picture" checkbox.
 *
 * admin_resolve_image_field() is used on save: a newly uploaded file is
 * stored with admin_save_image_upload() ("media.php?id=N"); the remove box
 * returns the default; otherwise the posted current value is kept only when
 * it matches the whitelist, else the default. Upload failures are exposed
 * via $GLOBALS['kci_image_upload_error'] so the form can show them.
 */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

if (!function_exists('admin_image_picker_src')) {
    function admin_image_picker_src($value, $default) {
        $value = (string)$value;
        $candidate = ($value !== '') ? $value : (string)$default;
        if (preg_match('/^media\.php\?id=[0-9]+$/', $candidate)) {
            return '../' . $candidate;
        }
        if (preg_match('/^kci_image\/[A-Za-z0-9._-]+$/', $candidate)) {
            return '../' . $candidate;
        }
        return '../' . (string)$default;
    }
}

/* Labelled picture field. $name is the hidden/current field name; the file
 * input is "{$name}_file" and the remove box is "{$name}_remove". */
if (!function_exists('admin_image_picker')) {
    function admin_image_picker($name, $current, $label) {
        $name = (string)$name;
        if ($name === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $name = 'image';
        }
        $default = 'kci_image/im2.webp';
        $safeName = $name;
        $fileName = $safeName . '_file';
        $removeName = $safeName . '_remove';
        $previewId = 'event-image-preview';
        if ($safeName !== 'image') {
            $previewId = $safeName . '-preview';
        }
        $src = admin_image_picker_src((string)$current, $default);
        echo '<div class="kci-field kci-imagepick">';
        echo '<span>' . e((string)$label) . '</span>';
        echo '<img class="kci-imagepick__preview" id="' . e($previewId) . '" src="' . e($src) . '" alt="Current picture preview">';
        echo '<input type="file" name="' . e($fileName) . '" id="' . e($safeName) . '-file" accept="image/jpeg,image/png,image/webp" data-kci-shrink="1" data-kci-preview="' . e($previewId) . '">';
        echo '<input type="hidden" name="' . e($safeName) . '" value="' . e((string)$current) . '">';
        echo '<label class="kci-imagepick__remove"><input type="checkbox" name="' . e($removeName) . '" value="1"> Remove picture (use the default)</label>';
        echo '<p class="kci-muted">JPEG, PNG or WebP. Large pictures are shrunk automatically before upload.</p>';
        echo '</div>';
    }
}

/* Resolve the picture value on save. See file header for the contract. */
if (!function_exists('admin_resolve_image_field')) {
    function admin_resolve_image_field($conn, $adminId, $postName, $default) {
        $postName = (string)$postName;
        if ($postName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $postName)) {
            $postName = 'image';
        }
        $default = (string)$default;
        $GLOBALS['kci_image_upload_error'] = '';
        $fileKey = $postName . '_file';
        if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey]) && isset($_FILES[$fileKey]['error'])) {
            $err = (int)$_FILES[$fileKey]['error'];
            if ($err !== UPLOAD_ERR_NO_FILE) {
                if ($err !== UPLOAD_ERR_OK) {
                    $GLOBALS['kci_image_upload_error'] = 'That picture could not be uploaded. Please try a JPEG, PNG or WebP picture.';
                    return $default;
                }
                if (!function_exists('admin_save_image_upload')) {
                    $GLOBALS['kci_image_upload_error'] = 'Pictures cannot be processed on this server. Please try again later.';
                    return $default;
                }
                $res = admin_save_image_upload($conn, $_FILES[$fileKey], (int)$adminId, 1600);
                if (is_array($res) && !empty($res['ok']) && (int)$res['id'] > 0) {
                    return 'media.php?id=' . (int)$res['id'];
                }
                $GLOBALS['kci_image_upload_error'] = (is_array($res) && isset($res['error']) && $res['error'] !== '')
                    ? (string)$res['error']
                    : 'That picture could not be saved. Please try again.';
                return $default;
            }
        }
        $removeKey = $postName . '_remove';
        if (isset($_POST[$removeKey]) && (string)$_POST[$removeKey] === '1') {
            return $default;
        }
        $posted = isset($_POST[$postName]) && is_string($_POST[$postName]) ? trim($_POST[$postName]) : '';
        if (preg_match('/^(kci_image\/[A-Za-z0-9._-]+|media\.php\?id=[0-9]+)$/', $posted)) {
            return $posted;
        }
        return $default;
    }
}
