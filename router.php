<?php
/**
 * router.php — only used by PHP's built-in server, for local previewing:
 *
 *     php -S localhost:8080 router.php
 *
 * On a real host this file is ignored; Apache uses .htaccess and nginx uses
 * the try_files rule in README.md.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// Never serve the database, the raw content files or the core code.
if (preg_match('#^/(data|core)(/|$)#', $path)) {
    http_response_code(404);
    exit('Not found');
}
if (preg_match('#^/content/#', $path) && !preg_match('#\.(jpe?g|png|webp|gif|pdf|docx?|xlsx?)$#i', $path)) {
    http_response_code(404);
    exit('Not found');
}

if ($path !== '/' && is_file($file)) {
    return false;                     // let the built-in server send the file
}

if (str_starts_with($path, '/admin')) {
    require __DIR__ . '/admin/index.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
