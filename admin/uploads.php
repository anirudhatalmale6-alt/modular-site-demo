<?php
/**
 * admin/uploads.php — photograph uploads.
 *
 * Files are checked three ways before they are kept: the extension, the real
 * image dimensions (a file that is not an image has none), and the size. The
 * name is rewritten, so an uploaded "shell.php.jpg" cannot survive as anything
 * executable. Large photographs are resized down to 1600px on the long edge.
 */

function admin_handle_upload(string $albumSlug, array $files): string
{
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($albumSlug));
    $dir  = BASE_DIR . '/content/gallery/' . $slug;

    if ($slug === '' || !is_dir($dir)) {
        return 'That album does not exist.';
    }
    if (empty($files['name'][0])) {
        return 'No files were chosen.';
    }

    $allowed = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'webp' => IMAGETYPE_WEBP, 'gif' => IMAGETYPE_GIF];
    $maxBytes = 8 * 1024 * 1024;
    $kept = 0;
    $skipped = [];

    foreach ($files['name'] as $i => $originalName) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $skipped[] = $originalName . ' (upload failed)';
            continue;
        }

        $tmp = $files['tmp_name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!isset($allowed[$ext])) {
            $skipped[] = $originalName . ' (not an image type we accept)';
            continue;
        }
        if (($files['size'][$i] ?? 0) > $maxBytes) {
            $skipped[] = $originalName . ' (over 8 MB)';
            continue;
        }

        $info = @getimagesize($tmp);
        if (!$info || $info[2] !== $allowed[$ext]) {
            $skipped[] = $originalName . ' (does not look like a real image)';
            continue;
        }

        // Rewrite the filename: keep something readable, drop everything else.
        $base = preg_replace('/[^a-z0-9\-]+/', '-', strtolower(pathinfo($originalName, PATHINFO_FILENAME)));
        $base = trim((string) $base, '-') ?: 'photo';
        $name = $base . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $n = 2;
        while (file_exists($dir . '/' . $name)) {
            $name = $base . '-' . $n++ . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        }

        if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
            $skipped[] = $originalName . ' (could not be saved — check folder permissions)';
            continue;
        }

        @chmod($dir . '/' . $name, 0644);
        admin_shrink($dir . '/' . $name, 1600);
        $kept++;
    }

    $msg = $kept . ' photograph' . ($kept === 1 ? '' : 's') . ' added.';
    if ($skipped) {
        $msg .= ' Skipped: ' . implode(', ', $skipped) . '.';
    }
    return $msg;
}

/** Resize in place if the long edge is bigger than $max. Keeps pages fast. */
function admin_shrink(string $path, int $max): void
{
    if (!function_exists('imagecreatetruecolor')) {
        return;                                   // no GD on this host: leave it alone
    }

    $info = @getimagesize($path);
    if (!$info) {
        return;
    }

    [$w, $h, $type] = $info;
    if (max($w, $h) <= $max) {
        return;
    }

    $scale = $max / max($w, $h);
    $nw    = (int) round($w * $scale);
    $nh    = (int) round($h * $scale);

    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        IMAGETYPE_GIF  => @imagecreatefromgif($path),
        default        => null,
    };
    if (!$src) {
        return;
    }

    $dst = imagecreatetruecolor($nw, $nh);
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    match ($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $path, 86),
        IMAGETYPE_PNG  => imagepng($dst, $path, 6),
        IMAGETYPE_WEBP => imagewebp($dst, $path, 86),
        IMAGETYPE_GIF  => imagegif($dst, $path),
        default        => null,
    };

    imagedestroy($src);
    imagedestroy($dst);
}
