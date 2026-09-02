<?php
/**
 * index.php — the front controller for the public site.
 *
 * Every visitor request lands here. The job of this file is small: work out
 * what was asked for, hand it to the right template, and get out of the way.
 *
 * Routes
 *   /                     home page          content/pages/home.md
 *   /<slug>               any page           content/pages/<slug>.md
 *   /photos               album index        content/gallery/*
 *   /photos/<album>       one album
 *   /memoranda            the archive        content/memoranda/*
 *   /memoranda/<slug>     one memorandum
 *
 * To add a page you do not touch this file — drop a Markdown file into
 * content/pages/ and it is routed automatically.
 */
$site    = require __DIR__ . '/core/bootstrap.php';
$config  = $site['config'];
$content = $site['content'];

/* ------------------------------------------------------------------ routing */

$path    = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$base    = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = trim(substr($path, strlen($base)), '/');
}
$segments = $path === '' ? [] : explode('/', $path);
$route    = $segments[0] ?? '';

/* --------------------------------------------------------- form submissions */
/* Handled before any output so a success can redirect (post/redirect/get,
   which stops a refresh from posting the same comment twice).               */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = (string) ($_POST['form'] ?? '');

    if ($form === 'comment') {
        $thread = (string) ($_POST['thread'] ?? '');
        $result = Comments::submit($thread, $_POST, (bool) $config['comments_auto_approve']);

        $_SESSION['flash'] = [
            'type'    => $result['ok'] ? 'ok' : 'error',
            'message' => $result['message'],
            'thread'  => $thread,
        ];
        if (!$result['ok']) {
            $_SESSION['old_comment'] = ['author' => $_POST['author'] ?? '', 'email' => $_POST['email'] ?? '', 'body' => $_POST['body'] ?? ''];
        }

        header('Location: ' . ((string) ($_POST['return'] ?? url('/'))) . '#comments');
        exit;
    }

    if ($form === 'contact') {
        require_once __DIR__ . '/core/ContactForm.php';
        $result = ContactForm::submit($_POST, $config);

        $_SESSION['flash'] = ['type' => $result['ok'] ? 'ok' : 'error', 'message' => $result['message'], 'thread' => 'contact'];
        if (!$result['ok']) {
            $_SESSION['old_contact'] = $_POST;
        }

        header('Location: ' . url('/contact') . '#contact-form');
        exit;
    }
}

/* ------------------------------------------------------------- what to show */

$view      = null;   // template file under themes/<theme>/views/
$vars      = [];     // variables handed to it
$http      = 200;

switch ($route) {
    case '':
        $page = $content->page('home');
        if (!$page) {
            $http = 404;
            break;
        }
        $view = 'page';
        $vars = ['page' => $page, 'home' => true];
        break;

    case 'photos':
        if (isset($segments[1])) {
            $album = $content->album($segments[1]);
            if (!$album) {
                $http = 404;
                break;
            }
            $view = 'album';
            $vars = ['album' => $album];
        } else {
            $view = 'photos';
            $vars = ['albums' => $content->albums(), 'page' => $content->page('photos')];
        }
        break;

    case 'memoranda':
        if (isset($segments[1])) {
            $memo = $content->memorandum($segments[1]);
            if (!$memo) {
                $http = 404;
                break;
            }
            $view = 'memorandum';
            $vars = ['memo' => $memo];
        } else {
            $view = 'memoranda';
            $vars = ['memoranda' => $content->memoranda(), 'page' => $content->page('memoranda')];
        }
        break;

    default:
        $page = $content->page($route);
        if ($page && count($segments) === 1) {
            $view = 'page';
            $vars = ['page' => $page];
        } else {
            $http = 404;
        }
}

if ($http === 404) {
    $view = '404';
    http_response_code(404);
}

/* ----------------------------------------------------------------- rendering */

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$vars += [
    'site'      => $site,
    'config'    => $config,
    'content'   => $content,
    'flash'     => $flash,
    'route'     => $route,
    'path'      => $path,
    'nav'       => $content->navPages(),
];

extract($vars, EXTR_SKIP);
$view_file = $site['theme'] . '/views/' . $view . '.php';

ob_start();
require $view_file;                       // the view fills $content_html
$content_html = ob_get_clean();

require $site['theme'] . '/layout.php';   // the layout wraps it in the chrome
