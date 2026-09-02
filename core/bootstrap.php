<?php
/**
 * bootstrap.php — loads everything and hands back a $site array.
 *
 * Included by index.php (the public site) and by admin/index.php. Nothing
 * else needs to include it.
 */
declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));

require_once BASE_DIR . '/core/Markdown.php';
require_once BASE_DIR . '/core/Content.php';
require_once BASE_DIR . '/core/Db.php';
require_once BASE_DIR . '/core/Security.php';
require_once BASE_DIR . '/core/Comments.php';
require_once BASE_DIR . '/core/Widgets.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$config  = require BASE_DIR . '/config.php';
$content = new Content(BASE_DIR . '/content');

Db::get();                                   // opens the file and applies the schema
Widgets::loadAll(BASE_DIR . '/widgets');     // every file in /widgets registers itself

$site = [
    'config'  => $config,
    'content' => $content,
    'theme'   => BASE_DIR . '/themes/' . $config['theme'],
];

/* -------------------------------------------------------------- helpers */

/** Escape for HTML. Short name because templates use it constantly. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Build a site URL. One place to change if the site ever moves into a subfolder. */
function url(string $path = ''): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    if (str_ends_with($base, '/admin')) {
        $base = substr($base, 0, -6);
    }
    return ($base ?: '') . '/' . ltrim($path, '/');
}

/**
 * Turn stored content into page HTML: widgets first (so Markdown never sees
 * their output), then Markdown, then the widgets dropped back in.
 */
function render_body(string $text, array $context = []): string
{
    $slots = [];

    $text = preg_replace_callback(
        '/\[\[\s*([a-z0-9_\-]+)((?:\s+[a-z0-9_\-]+="[^"]*")*)\s*\]\]/i',
        function (array $m) use (&$slots, $context) {
            $key         = "\x02W" . count($slots) . "\x03";
            $slots[$key] = Widgets::expand($m[0], $context);
            return $key;
        },
        $text
    ) ?? $text;

    $html = Markdown::render($text);

    foreach ($slots as $key => $rendered) {
        // Markdown may have wrapped a lone placeholder in <p> tags; unwrap it.
        $html = str_replace(['<p>' . $key . '</p>', $key], [$rendered, $rendered], $html);
    }

    return $html;
}

/** Pretty date for display: 2026-03-14 -> 14 March 2026 */
function nice_date(?string $iso): string
{
    if (!$iso || !($ts = strtotime($iso))) {
        return '';
    }
    return date('j F Y', $ts);
}

return $site;
