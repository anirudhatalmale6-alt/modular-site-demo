<?php
/**
 * admin/index.php — the whole back office, in one file.
 *
 * Sections
 *   dashboard   what needs attention
 *   comments    approve / reject / reply / delete, plus the spam bin
 *   messages    contact-form inbox
 *   pages       create, edit and delete pages
 *   memoranda   the same, for filed documents
 *   gallery     create albums, upload photographs, write captions
 *   help        the widget reference, generated from what is installed
 *
 * Sign in with the password set in config.php. There is one account: it is a
 * small site and a second login is one more thing to lose.
 */
$site    = require dirname(__DIR__) . '/core/bootstrap.php';
$config  = $site['config'];
$content = $site['content'];

/* ------------------------------------------------------------------- auth */

$sessionSeconds = (int) $config['admin_session_hours'] * 3600;

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_ok'], $_SESSION['admin_since']);
    header('Location: ' . url('/admin/'));
    exit;
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify((string) $_POST['password'], $config['admin_password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_ok']    = true;
        $_SESSION['admin_since'] = time();
        header('Location: ' . url('/admin/'));
        exit;
    }
    sleep(1);                                   // slow down guessing
    $loginError = 'That password was not right.';
}

$signedIn = !empty($_SESSION['admin_ok'])
    && (time() - (int) ($_SESSION['admin_since'] ?? 0)) < $sessionSeconds;

if (!$signedIn) {
    include __DIR__ . '/login.php';
    exit;
}

/* --------------------------------------------------------------- actions */

$notice = $_SESSION['admin_notice'] ?? '';
unset($_SESSION['admin_notice']);

$section = $_GET['s'] ?? 'dashboard';

function admin_redirect(string $to, string $notice = ''): never
{
    if ($notice !== '') {
        $_SESSION['admin_notice'] = $notice;
    }
    header('Location: ' . url('/admin/') . $to);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do'])) {
    if (!Security::checkToken($_POST['csrf'] ?? null)) {
        admin_redirect('?s=' . urlencode($section), 'Session expired — nothing was changed. Please try again.');
    }

    switch ($_POST['do']) {

        /* --- comments ---------------------------------------------------- */
        case 'comment_status':
            Comments::setStatus((int) $_POST['id'], (string) $_POST['status']);
            admin_redirect('?s=comments&f=' . urlencode((string) ($_POST['back'] ?? 'pending')), 'Comment updated.');

        case 'comment_reply':
            Comments::reply((int) $_POST['id'], (string) $_POST['reply']);
            admin_redirect('?s=comments&f=approved', 'Reply saved and published under the comment.');

        case 'comment_delete':
            Comments::delete((int) $_POST['id']);
            admin_redirect('?s=comments&f=' . urlencode((string) ($_POST['back'] ?? 'pending')), 'Comment deleted for good.');

        case 'spam_empty':
            $n = Comments::emptySpam();
            admin_redirect('?s=comments&f=spam', $n . ' spam comment' . ($n === 1 ? '' : 's') . ' deleted.');

        /* --- messages ---------------------------------------------------- */
        case 'message_status':
            require_once dirname(__DIR__) . '/core/ContactForm.php';
            ContactForm::setStatus((int) $_POST['id'], (string) $_POST['status']);
            admin_redirect('?s=messages&f=' . urlencode((string) ($_POST['back'] ?? 'new')), 'Message updated.');

        case 'message_delete':
            require_once dirname(__DIR__) . '/core/ContactForm.php';
            ContactForm::delete((int) $_POST['id']);
            admin_redirect('?s=messages&f=' . urlencode((string) ($_POST['back'] ?? 'new')), 'Message deleted.');

        /* --- pages and memoranda ------------------------------------------ */
        case 'save_content':
            $kind = ($_POST['kind'] ?? 'pages') === 'memoranda' ? 'memoranda' : 'pages';
            $slug = trim((string) $_POST['slug']) ?: 'untitled';

            $meta = ['title' => trim((string) $_POST['title'])];
            foreach (['eyebrow', 'summary', 'nav', 'order', 'date', 'ref', 'author', 'tags', 'headline', 'file', 'draft'] as $key) {
                $value = trim((string) ($_POST[$key] ?? ''));
                if ($value !== '') {
                    $meta[$key] = $value;
                }
            }

            $content->save($kind, $slug, $meta, (string) $_POST['body']);
            admin_redirect('?s=' . $kind . '&edit=' . urlencode($slug), 'Saved. It is live on the site now.');

        case 'delete_content':
            $kind = ($_POST['kind'] ?? 'pages') === 'memoranda' ? 'memoranda' : 'pages';
            $content->delete($kind, (string) $_POST['slug']);
            admin_redirect('?s=' . $kind, 'Deleted.');

        /* --- gallery ------------------------------------------------------ */
        case 'album_create':
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', (string) $_POST['album_slug'])));
            if ($slug === '') {
                admin_redirect('?s=gallery', 'That album name could not be used — try letters and numbers.');
            }
            $dir = BASE_DIR . '/content/gallery/' . $slug;
            @mkdir($dir, 0775, true);
            file_put_contents($dir . '/album.json', json_encode([
                'title'    => trim((string) $_POST['album_title']) ?: ucwords(str_replace('-', ' ', $slug)),
                'date'     => trim((string) ($_POST['album_date'] ?? '')),
                'note'     => trim((string) ($_POST['album_note'] ?? '')),
                'order'    => 50,
                'captions' => (object) [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            admin_redirect('?s=gallery&album=' . urlencode($slug), 'Album created. Add photographs to it below.');

        case 'album_upload':
            require_once __DIR__ . '/uploads.php';
            $result = admin_handle_upload((string) $_POST['album'], $_FILES['photos'] ?? []);
            admin_redirect('?s=gallery&album=' . urlencode((string) $_POST['album']), $result);

        case 'album_captions':
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $_POST['album']));
            $path = BASE_DIR . '/content/gallery/' . $slug . '/album.json';
            $meta = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];

            $meta['title'] = trim((string) ($_POST['album_title'] ?? $meta['title'] ?? $slug));
            $meta['date']  = trim((string) ($_POST['album_date'] ?? ''));
            $meta['note']  = trim((string) ($_POST['album_note'] ?? ''));

            $captions = [];
            foreach ((array) ($_POST['caption'] ?? []) as $file => $caption) {
                $captions[basename((string) $file)] = trim((string) $caption);
            }
            $meta['captions'] = $captions ?: (object) [];

            file_put_contents($path, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            admin_redirect('?s=gallery&album=' . urlencode($slug), 'Album details saved.');

        case 'photo_delete':
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $_POST['album']));
            $file = basename((string) $_POST['file']);
            $path = BASE_DIR . '/content/gallery/' . $slug . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
            admin_redirect('?s=gallery&album=' . urlencode($slug), 'Photograph removed.');
    }
}

/* --------------------------------------------------------------- render */

$commentCounts = Comments::counts();
require_once dirname(__DIR__) . '/core/ContactForm.php';
$messageCounts = ContactForm::counts();

include __DIR__ . '/view.php';
