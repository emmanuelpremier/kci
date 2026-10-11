<?php
/* admin/includes/media_helpers.php — database-backed picture uploads.
 * Stored IN `media` (disk is wiped on redeploy). Every upload is
 * re-encoded with GD so metadata and hidden payloads are stripped.
 * Never trusts the client MIME type or extension. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

/* Public URL for a stored picture (served from the project root). */
function admin_image_url($id) {
    return 'media.php?id=' . (int)$id;
}

/* Rotate/flip a GD image per its EXIF orientation tag. */
function admin_media_fix_orientation($im, $orientation) {
    $orientation = (int)$orientation;
    if ($orientation < 2 || $orientation > 8) { return $im; }
    $hasFlip = function_exists('imageflip');
    $hasRotate = function_exists('imagerotate');
    try {
        if ($orientation === 2) {
            if ($hasFlip) { imageflip($im, IMG_FLIP_HORIZONTAL); }
        } elseif ($orientation === 3) {
            if ($hasRotate) { $r = imagerotate($im, 180, 0); if ($r !== false) { $im = $r; } }
        } elseif ($orientation === 4) {
            if ($hasFlip) { imageflip($im, IMG_FLIP_VERTICAL); }
        } elseif ($orientation === 5) {
            if ($hasRotate) { $r = imagerotate($im, 270, 0); if ($r !== false) { $im = $r; } }
            if ($hasFlip) { imageflip($im, IMG_FLIP_HORIZONTAL); }
        } elseif ($orientation === 6) {
            if ($hasRotate) { $r = imagerotate($im, 270, 0); if ($r !== false) { $im = $r; } }
        } elseif ($orientation === 7) {
            if ($hasRotate) { $r = imagerotate($im, 90, 0); if ($r !== false) { $im = $r; } }
            if ($hasFlip) { imageflip($im, IMG_FLIP_HORIZONTAL); }
        } elseif ($orientation === 8) {
            if ($hasRotate) { $r = imagerotate($im, 90, 0); if ($r !== false) { $im = $r; } }
        }
    } catch (Throwable $ignored) { /* keep unrotated */ }
    return $im;
}

/* Clean original name: letters, digits, dash, underscore, max 80 chars. */
function admin_media_clean_name($originalName, $extension) {
    $base = basename(str_replace('\\', '/', (string)$originalName));
    $dot = strrpos($base, '.');
    $stem = ($dot !== false) ? substr($base, 0, $dot) : $base;
    $stem = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$stem);
    $stem = trim((string)$stem, '-_');
    if ($stem === '' || $stem === null) { $stem = 'image'; }
    if (function_exists('mb_substr')) { $stem = mb_substr($stem, 0, 80, 'UTF-8'); }
    else { $stem = substr($stem, 0, 80); }
    $stem = rtrim((string)$stem, '-_');
    if ($stem === '') { $stem = 'image'; }
    return $stem . (string)$extension;
}


/* Parse a php.ini size string (e.g. "8M", "512K", "2G", "1024") into bytes. */
function admin_media_parse_php_size($text) {
    $text = trim((string)$text);
    if ($text === '' || $text === '-1') { return 0; }
    $unit = strtolower(substr($text, -1));
    if ($unit >= '0' && $unit <= '9') {
        $num = (float)$text;
        if ($num <= 0) { return 0; }
        return (int)$num;
    }
    $num = (float)substr($text, 0, -1);
    if ($num <= 0) { return 0; }
    if ($unit === 'g') { $num *= 1073741824; }
    elseif ($unit === 'm') { $num *= 1048576; }
    elseif ($unit === 'k') { $num *= 1024; }
    if ($num <= 0) { return 0; }
    if ($num > 9223372036854775807) { $num = 9223372036854775807; }
    return (int)$num;
}

/* Effective per-file server limit: smaller of upload_max_filesize and post_max_size. */
function admin_media_server_limit_bytes() {
    $upload = 0;
    $post = 0;
    try {
        $upload = admin_media_parse_php_size((string)ini_get('upload_max_filesize'));
        $post = admin_media_parse_php_size((string)ini_get('post_max_size'));
    } catch (Throwable $ignored) { /* fall through to fallback */ }
    $candidates = array();
    if ($upload > 0) { $candidates[] = $upload; }
    if ($post > 0) { $candidates[] = $post; }
    if (empty($candidates)) { return 8388608; }
    return (int)min($candidates);
}

/* "9.2 MB", "8 MB", "420 KB" for messages and the drop-zone line. */
function admin_media_format_size($bytes) {
    $bytes = (int)$bytes;
    if ($bytes < 0) { $bytes = 0; }
    if ($bytes >= 1048576) {
        $mb = $bytes / 1048576;
        $rounded = round($mb, 1);
        if (abs($rounded - round($rounded)) < 0.05) {
            return (string)(int)round($rounded) . ' MB';
        }
        return rtrim(rtrim(number_format($rounded, 1, '.', ''), '0'), '.') . ' MB';
    }
    if ($bytes >= 1024) {
        $kb = $bytes / 1024;
        $rounded = round($kb, 0);
        if ($rounded < 1) { $rounded = 1; }
        return (string)(int)$rounded . ' KB';
    }
    return (string)$bytes . ' bytes';
}

/* "8", "2.5" — MB label used inside "(limit: X MB)". */
function admin_media_format_limit_mb($bytes) {
    $bytes = (int)$bytes;
    if ($bytes <= 0) { return '8'; }
    $mb = $bytes / 1048576;
    $rounded = round($mb, 1);
    if (abs($rounded - round($rounded)) < 0.05) {
        return (string)(int)round($rounded);
    }
    return rtrim(rtrim(number_format($rounded, 1, '.', ''), '0'), '.');
}

/* Clear message for a PHP upload error code. Knows the effective server limit. */
function admin_media_upload_error_message($code) {
    $code = (int)$code;
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        $limit = admin_media_server_limit_bytes();
        $label = admin_media_format_limit_mb($limit);
        return 'This picture is larger than the server accepts (limit: ' . $label . ' MB). It should be shrunk automatically before upload; if you see this, try again or choose a smaller picture.';
    }
    if ($code === UPLOAD_ERR_PARTIAL) {
        return 'The upload was interrupted. Please try again.';
    }
    if ($code === UPLOAD_ERR_NO_FILE) {
        return 'No file was chosen.';
    }
    return 'That upload failed. Please try again.';
}

/* Validate, normalise and store one picture. Returns ok/id/error. */
function admin_save_image_upload($conn, $file, $adminId, $maxWidth = 1600) {
    if (!is_array($file) || !isset($file['error'])) {
        return array('ok' => false, 'id' => 0, 'error' => 'Please choose a picture to upload.');
    }
    $code = (int)$file['error'];
    if ($code !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'id' => 0, 'error' => admin_media_upload_error_message($code));
    }
    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || $file['tmp_name'] === '') {
        return array('ok' => false, 'id' => 0, 'error' => 'That upload failed. Please try again.');
    }
    $tmp = $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return array('ok' => false, 'id' => 0, 'error' => 'That upload failed. Please try again.');
    }
    $maxWidth = (int)$maxWidth;
    if ($maxWidth < 1) { $maxWidth = 1600; }
    $clientSize = isset($file['size']) ? (int)$file['size'] : 0;
    if ($clientSize > 8388608) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture is too large (' . admin_media_format_size($clientSize) . '). Please choose a file under 8 MB.');
    }
    $realSize = $clientSize;
    try {
        $statSize = @filesize($tmp);
        if ($statSize !== false) { $realSize = (int)$statSize; }
    } catch (Throwable $ignored) { /* keep client size */ }
    if ($realSize <= 0 || $realSize > 8388608) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture is too large (' . admin_media_format_size($realSize) . '). Please choose a file under 8 MB.');
    }
    /* Real type from finfo AND getimagesize; never trust the client. */
    $finfoMime = '';
    try {
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi !== false) {
                $det = finfo_file($fi, $tmp);
                if (is_string($det)) { $finfoMime = strtolower(trim($det)); }
                finfo_close($fi);
            }
        }
    } catch (Throwable $ignored) { $finfoMime = ''; }
    $info = @getimagesize($tmp);
    if (!is_array($info) || !isset($info[0], $info[1], $info[2], $info['mime'])) {
        return array('ok' => false, 'id' => 0, 'error' => 'Only JPEG, PNG and WebP pictures are allowed.');
    }
    $allowedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP);
    $allowedMimes = array('image/jpeg', 'image/png', 'image/webp');
    $gsMime = strtolower(trim((string)$info['mime']));
    $imType = (int)$info[2];
    if (!in_array($imType, $allowedTypes, true) || !in_array($gsMime, $allowedMimes, true)) {
        return array('ok' => false, 'id' => 0, 'error' => 'Only JPEG, PNG and WebP pictures are allowed.');
    }
    if ($finfoMime !== '' && !in_array($finfoMime, $allowedMimes, true)) {
        return array('ok' => false, 'id' => 0, 'error' => 'Only JPEG, PNG and WebP pictures are allowed.');
    }
    if ($finfoMime !== '' && $finfoMime !== $gsMime) {
        return array('ok' => false, 'id' => 0, 'error' => 'Only JPEG, PNG and WebP pictures are allowed.');
    }
    $srcW = (int)$info[0];
    $srcH = (int)$info[1];
    if ($srcW > 8000 || $srcH > 8000) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture is too large. The width and height must be 8000 pixels or less.');
    }
    if ($srcW < 50 || $srcH < 50) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture is too small. Pictures must be at least 50 by 50 pixels.');
    }
    $src = false;
    try {
        if ($imType === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
            $src = @imagecreatefromjpeg($tmp);
        } elseif ($imType === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($tmp);
        } elseif ($imType === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($tmp);
        }
    } catch (Throwable $ignored) { $src = false; }
    if ($src === false) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be read. Please try another file.');
    }
    if ($imType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        try {
            $exif = @exif_read_data($tmp);
            if (is_array($exif) && isset($exif['Orientation'])) {
                $src = admin_media_fix_orientation($src, (int)$exif['Orientation']);
            }
        } catch (Throwable $ignored) { /* keep as loaded */ }
    }
    $curW = imagesx($src);
    $curH = imagesy($src);
    if ($curW === false || $curH === false || (int)$curW < 1 || (int)$curH < 1) {
        imagedestroy($src);
        return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be read. Please try another file.');
    }
    $curW = (int)$curW;
    $curH = (int)$curH;
    /* Resize so width is at most $maxWidth (never enlarge). */
    $im = $src;
    if ($curW > $maxWidth) {
        $newW = $maxWidth;
        $newH = (int)round($curH * ($maxWidth / $curW));
        if ($newH < 1) { $newH = 1; }
        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);
            return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be processed. Please try again.');
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $clear = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        if ($clear !== false) { imagefilledrectangle($dst, 0, 0, $newW, $newH, $clear); }
        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $curW, $curH)) {
            imagedestroy($src);
            imagedestroy($dst);
            return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be processed. Please try again.');
        }
        imagedestroy($src);
        $im = $dst;
    }
    $finalW = (int)imagesx($im);
    $finalH = (int)imagesy($im);
    /* Re-encode as WebP q82 (fallback JPEG q85); strips metadata. */
    $outMime = 'image/webp';
    $outExt = '.webp';
    $data = '';
    $encoded = false;
    if (function_exists('imagewebp')) {
        try {
            ob_start();
            $okW = imagewebp($im, null, 82);
            $buf = ob_get_clean();
            if ($okW && is_string($buf) && $buf !== '') { $data = $buf; $encoded = true; }
        } catch (Throwable $ignored) {
            if (ob_get_level() > 0) { ob_end_clean(); }
            $encoded = false;
        }
    }
    if (!$encoded) {
        if (!function_exists('imagejpeg')) {
            imagedestroy($im);
            return array('ok' => false, 'id' => 0, 'error' => 'Pictures cannot be processed on this server. Please try again later.');
        }
        $flat = imagecreatetruecolor($finalW, $finalH);
        if ($flat === false) {
            imagedestroy($im);
            return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be processed. Please try again.');
        }
        $white = imagecolorallocate($flat, 255, 255, 255);
        if ($white !== false) { imagefilledrectangle($flat, 0, 0, $finalW, $finalH, $white); }
        imagecopy($flat, $im, 0, 0, 0, 0, $finalW, $finalH);
        imagedestroy($im);
        $im = $flat;
        $outMime = 'image/jpeg';
        $outExt = '.jpg';
        try {
            ob_start();
            $okJ = imagejpeg($im, null, 85);
            $buf = ob_get_clean();
            if ($okJ && is_string($buf) && $buf !== '') { $data = $buf; $encoded = true; }
        } catch (Throwable $ignored) {
            if (ob_get_level() > 0) { ob_end_clean(); }
            $encoded = false;
        }
    }
    imagedestroy($im);
    if (!$encoded || $data === '') {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be processed. Please try again.');
    }
    if (strlen($data) > 2097152) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture is still larger than 2 MB after processing. Please use a smaller picture.');
    }
    $origName = (isset($file['name']) && is_string($file['name'])) ? $file['name'] : 'image';
    $filename = admin_media_clean_name($origName, $outExt);
    $sizeBytes = strlen($data);
    $by = (int)$adminId;
    try {
        if (!$conn instanceof mysqli) {
            return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be saved. Please try again.');
        }
        $stmt = $conn->prepare('INSERT INTO media (filename, mime, width, height, size_bytes, data, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($stmt === false) {
            return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be saved. Please try again.');
        }
        $blob = null;
        $stmt->bind_param('ssiiibi', $filename, $outMime, $finalW, $finalH, $sizeBytes, $blob, $by);
        $len = strlen($data);
        for ($off = 0; $off < $len; $off += 8192) {
            $stmt->send_long_data(5, substr($data, $off, 8192));
        }
        if (!$stmt->execute()) {
            $stmt->close();
            return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be saved. Please try again.');
        }
        $newId = (int)$conn->insert_id;
        $stmt->close();
    } catch (Throwable $ignored) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be saved. Please try again.');
    }
    if ($newId <= 0) {
        return array('ok' => false, 'id' => 0, 'error' => 'That picture could not be saved. Please try again.');
    }
    return array('ok' => true, 'id' => $newId, 'error' => '');
}
