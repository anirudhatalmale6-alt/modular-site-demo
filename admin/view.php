<?php
/**
 * admin/view.php — the signed-in screens.
 *
 * All of the admin's HTML lives here; index.php above it does the deciding.
 */
$csrf   = Security::token();
$filter = $_GET['f'] ?? '';
$title  = ucfirst($section);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> — <?= e($config['site_short']) ?> admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Newsreader:opsz,wght@6..72,300..500&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('admin/admin.css')) ?>">
</head>
<body class="admin">

<aside class="side">
    <a class="side__brand" href="<?= e(url('/')) ?>">
        <strong><?= e($config['site_short']) ?></strong>
        <span>view the website ↗</span>
    </a>

    <nav class="side__nav">
        <?php
        $items = [
            'dashboard' => ['Dashboard', null],
            'comments'  => ['Comments', $commentCounts['pending'] ?: null],
            'messages'  => ['Messages', $messageCounts['new'] ?: null],
            'pages'     => ['Pages', null],
            'memoranda' => ['Memoranda', null],
            'gallery'   => ['Photographs', null],
            'help'      => ['How to add things', null],
        ];
        foreach ($items as $key => [$label, $badge]): ?>
            <a href="?s=<?= $key ?>" class="<?= $section === $key ? 'is-on' : '' ?>">
                <?= e($label) ?>
                <?php if ($badge): ?><span class="badge"><?= (int) $badge ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <a class="side__out" href="?logout=1">Sign out</a>
</aside>

<main class="pane">
<?php if ($notice): ?>
    <p class="a-flash"><?= e($notice) ?></p>
<?php endif; ?>

<?php
/* ============================================================ dashboard */
if ($section === 'dashboard'):
    $recent = Comments::byStatus('pending', 5);
?>
    <header class="pane__head">
        <h1>Good day</h1>
        <p>Everything that needs you is listed below.</p>
    </header>

    <div class="tiles">
        <a class="tile <?= $commentCounts['pending'] ? 'tile--attention' : '' ?>" href="?s=comments&f=pending">
            <span class="tile__n"><?= (int) $commentCounts['pending'] ?></span>
            <span class="tile__l">comments awaiting approval</span>
        </a>
        <a class="tile <?= $messageCounts['new'] ? 'tile--attention' : '' ?>" href="?s=messages&f=new">
            <span class="tile__n"><?= (int) $messageCounts['new'] ?></span>
            <span class="tile__l">unread messages</span>
        </a>
        <a class="tile" href="?s=comments&f=approved">
            <span class="tile__n"><?= (int) $commentCounts['approved'] ?></span>
            <span class="tile__l">comments published</span>
        </a>
        <a class="tile" href="?s=comments&f=spam">
            <span class="tile__n"><?= (int) $commentCounts['spam'] ?></span>
            <span class="tile__l">caught by the spam filter</span>
        </a>
    </div>

    <section class="block">
        <h2>Waiting for you</h2>
        <?php if (!$recent): ?>
            <p class="muted">Nothing in the queue. </p>
        <?php else: ?>
            <?php foreach ($recent as $c): ?>
                <article class="mod">
                    <div class="mod__meta">
                        <strong><?= e($c['author']) ?></strong>
                        <span class="muted">on <?= e($c['thread']) ?> · <?= e(Comments::ago($c['created_at'])) ?></span>
                    </div>
                    <p class="mod__body"><?= nl2br(e($c['body'])) ?></p>
                    <div class="mod__acts">
                        <?php foreach ([['approved', 'Approve', ''], ['spam', 'Mark as spam', 'a-btn--ghost']] as [$status, $label, $class]): ?>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="status" value="<?= $status ?>">
                                <input type="hidden" name="back" value="pending">
                                <button class="a-btn a-btn--small <?= $class ?>" name="do" value="comment_status"><?= $label ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <p><a class="a-link" href="?s=comments&f=pending">See the whole queue →</a></p>
        <?php endif; ?>
    </section>

    <section class="block">
        <h2>Quick actions</h2>
        <p class="rowlinks">
            <a class="a-btn a-btn--small" href="?s=pages&edit=__new__">Write a new page</a>
            <a class="a-btn a-btn--small" href="?s=memoranda&edit=__new__">File a memorandum</a>
            <a class="a-btn a-btn--small" href="?s=gallery">Upload photographs</a>
        </p>
    </section>

<?php
/* ============================================================= comments */
elseif ($section === 'comments'):
    $filter = in_array($filter, ['pending', 'approved', 'spam'], true) ? $filter : 'pending';
    $rows   = Comments::byStatus($filter);
?>
    <header class="pane__head">
        <h1>Comments</h1>
        <p>Nothing appears on the website until you approve it.</p>
    </header>

    <nav class="tabs">
        <?php foreach (['pending' => 'Waiting', 'approved' => 'Published', 'spam' => 'Spam bin'] as $key => $label): ?>
            <a href="?s=comments&f=<?= $key ?>" class="<?= $filter === $key ? 'is-on' : '' ?>">
                <?= $label ?> <span class="badge"><?= (int) $commentCounts[$key] ?></span>
            </a>
        <?php endforeach; ?>
        <?php if ($filter === 'spam' && $commentCounts['spam']): ?>
            <form method="post" class="tabs__right" onsubmit="return confirm('Delete every comment in the spam bin? This cannot be undone.')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button class="a-btn a-btn--small a-btn--danger" name="do" value="spam_empty">Empty the spam bin</button>
            </form>
        <?php endif; ?>
    </nav>

    <?php if (!$rows): ?>
        <p class="muted">Nothing here.</p>
    <?php endif; ?>

    <?php foreach ($rows as $c): ?>
        <article class="mod">
            <div class="mod__meta">
                <strong><?= e($c['author']) ?></strong>
                <?php if ($c['email']): ?><span class="muted"><?= e($c['email']) ?></span><?php endif; ?>
                <span class="muted">on <code><?= e($c['thread']) ?></code> · <?= e(Comments::ago($c['created_at'])) ?> · <?= e($c['ip']) ?></span>
            </div>

            <p class="mod__body"><?= nl2br(e($c['body'])) ?></p>

            <?php if ($c['spam_reason']): ?>
                <p class="mod__why">Filter score <?= (int) $c['spam_score'] ?> — <?= e($c['spam_reason']) ?></p>
            <?php endif; ?>

            <?php if ($c['reply']): ?>
                <p class="mod__reply"><span>Your reply:</span> <?= nl2br(e($c['reply'])) ?></p>
            <?php endif; ?>

            <div class="mod__acts">
                <?php foreach ([['approved', 'Approve'], ['pending', 'Send back to the queue'], ['spam', 'Mark as spam']] as [$status, $label]):
                    if ($status === $c['status']) continue; ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <input type="hidden" name="status" value="<?= $status ?>">
                        <input type="hidden" name="back" value="<?= e($filter) ?>">
                        <button class="a-btn a-btn--small <?= $status === 'approved' ? '' : 'a-btn--ghost' ?>" name="do" value="comment_status"><?= $label ?></button>
                    </form>
                <?php endforeach; ?>

                <form method="post" onsubmit="return confirm('Delete this comment permanently?')">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <input type="hidden" name="back" value="<?= e($filter) ?>">
                    <button class="a-btn a-btn--small a-btn--danger" name="do" value="comment_delete">Delete</button>
                </form>
            </div>

            <?php if ($c['status'] === 'approved'): ?>
                <details class="mod__replybox">
                    <summary>Reply publicly</summary>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <textarea name="reply" rows="3" placeholder="Your reply appears under the comment, marked as coming from the committee."><?= e((string) $c['reply']) ?></textarea>
                        <button class="a-btn a-btn--small" name="do" value="comment_reply">Save reply</button>
                    </form>
                </details>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

<?php
/* ============================================================= messages */
elseif ($section === 'messages'):
    $filter = in_array($filter, ['new', 'read', 'archived'], true) ? $filter : 'new';
    $rows   = ContactForm::inbox($filter);
?>
    <header class="pane__head">
        <h1>Messages</h1>
        <p>Everything sent through the contact form.</p>
    </header>

    <nav class="tabs">
        <?php foreach (['new' => 'Unread', 'read' => 'Read', 'archived' => 'Archived'] as $key => $label): ?>
            <a href="?s=messages&f=<?= $key ?>" class="<?= $filter === $key ? 'is-on' : '' ?>">
                <?= $label ?> <span class="badge"><?= (int) $messageCounts[$key] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$rows): ?>
        <p class="muted">Nothing here.</p>
    <?php endif; ?>

    <?php foreach ($rows as $m): ?>
        <article class="mod">
            <div class="mod__meta">
                <strong><?= e($m['subject']) ?></strong>
                <span class="muted">from <?= e($m['name']) ?> &lt;<?= e($m['email']) ?>&gt; · <?= e(Comments::ago($m['created_at'])) ?></span>
            </div>
            <p class="mod__body"><?= nl2br(e($m['body'])) ?></p>
            <div class="mod__acts">
                <?php foreach ([['read', 'Mark read'], ['archived', 'Archive'], ['new', 'Mark unread']] as [$status, $label]):
                    if ($status === $m['status']) continue; ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                        <input type="hidden" name="status" value="<?= $status ?>">
                        <input type="hidden" name="back" value="<?= e($filter) ?>">
                        <button class="a-btn a-btn--small a-btn--ghost" name="do" value="message_status"><?= $label ?></button>
                    </form>
                <?php endforeach; ?>
                <form method="post" onsubmit="return confirm('Delete this message permanently?')">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <input type="hidden" name="back" value="<?= e($filter) ?>">
                    <button class="a-btn a-btn--small a-btn--danger" name="do" value="message_delete">Delete</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>

<?php
/* ================================================== pages and memoranda */
elseif ($section === 'pages' || $section === 'memoranda'):
    $kind    = $section;
    $editing = $_GET['edit'] ?? '';
    $isNew   = $editing === '__new__';
    $item    = (!$isNew && $editing !== '')
        ? ($kind === 'pages' ? $content->page($editing) : $content->memorandum($editing))
        : null;
    $meta    = $item['meta'] ?? [];
?>
    <header class="pane__head">
        <h1><?= $kind === 'pages' ? 'Pages' : 'Memoranda' ?></h1>
        <p>
            <?= $kind === 'pages'
                ? 'Each page is one file. Create one here and it appears on the site immediately.'
                : 'Filed documents, newest first. The reference and date are shown on the archive page.' ?>
        </p>
    </header>

    <?php if ($editing === ''): ?>
        <p class="rowlinks"><a class="a-btn a-btn--small" href="?s=<?= $kind ?>&edit=__new__">New <?= $kind === 'pages' ? 'page' : 'memorandum' ?></a></p>

        <table class="grid">
            <thead><tr>
                <th>Title</th><th>Address</th>
                <?= $kind === 'pages' ? '<th>In the menu</th>' : '<th>Reference</th><th>Filed</th>' ?>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach (($kind === 'pages' ? $content->pages() : $content->memoranda()) as $row):
                $rmeta = $row['meta'];
                $link  = $kind === 'pages' ? '/' . $row['slug'] : '/memoranda/' . $row['slug']; ?>
                <tr>
                    <td><a class="a-link" href="?s=<?= $kind ?>&edit=<?= e($row['slug']) ?>"><?= e($rmeta['title']) ?></a></td>
                    <td><code><?= e($link) ?></code></td>
                    <?php if ($kind === 'pages'): ?>
                        <td><?= in_array(strtolower($rmeta['nav'] ?? 'no'), ['yes', 'true', '1', 'on'], true) ? 'yes' : '—' ?></td>
                    <?php else: ?>
                        <td><code><?= e($rmeta['ref'] ?? '—') ?></code></td>
                        <td><?= e(nice_date($rmeta['date'] ?? '')) ?></td>
                    <?php endif; ?>
                    <td class="right"><a class="a-link" href="<?= e(url($link)) ?>" target="_blank">view ↗</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <form method="post" class="editor">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="kind" value="<?= $kind ?>">

            <div class="editor__main">
                <label class="a-field">
                    <span>Title</span>
                    <input type="text" name="title" required value="<?= e($meta['title'] ?? '') ?>">
                </label>

                <label class="a-field">
                    <span>Body <em>Markdown, plus any [[widget]] you like</em></span>
                    <textarea name="body" rows="24" spellcheck="true"><?= e($item['body'] ?? "## A heading\n\nWrite here.\n") ?></textarea>
                </label>

                <div class="editor__save">
                    <button class="a-btn" name="do" value="save_content">Save</button>
                    <a class="a-link" href="?s=<?= $kind ?>">Cancel</a>
                </div>
            </div>

            <aside class="editor__side">
                <label class="a-field">
                    <span>Web address <em>letters, numbers and hyphens</em></span>
                    <input type="text" name="slug" required pattern="[a-z0-9\-]+"
                           value="<?= e($item['slug'] ?? '') ?>"
                           <?= $item ? 'readonly' : '' ?>>
                </label>

                <label class="a-field">
                    <span>Summary <em>shown under the title</em></span>
                    <textarea name="summary" rows="3"><?= e($meta['summary'] ?? '') ?></textarea>
                </label>

                <label class="a-field">
                    <span>Eyebrow <em>the small line above the title</em></span>
                    <input type="text" name="eyebrow" value="<?= e($meta['eyebrow'] ?? '') ?>">
                </label>

                <?php if ($kind === 'pages'): ?>
                    <label class="a-field">
                        <span>Show in the menu</span>
                        <select name="nav">
                            <option value="yes" <?= ($meta['nav'] ?? '') === 'yes' ? 'selected' : '' ?>>yes</option>
                            <option value="no"  <?= ($meta['nav'] ?? 'no') !== 'yes' ? 'selected' : '' ?>>no</option>
                        </select>
                    </label>
                    <label class="a-field">
                        <span>Menu position <em>lower numbers come first</em></span>
                        <input type="number" name="order" value="<?= e($meta['order'] ?? '60') ?>">
                    </label>
                <?php else: ?>
                    <label class="a-field">
                        <span>Reference</span>
                        <input type="text" name="ref" value="<?= e($meta['ref'] ?? '') ?>" placeholder="M-2026/05">
                    </label>
                    <label class="a-field">
                        <span>Date filed</span>
                        <input type="date" name="date" value="<?= e($meta['date'] ?? date('Y-m-d')) ?>">
                    </label>
                    <label class="a-field">
                        <span>Issued by</span>
                        <input type="text" name="author" value="<?= e($meta['author'] ?? '') ?>">
                    </label>
                    <label class="a-field">
                        <span>Subjects <em>comma separated</em></span>
                        <input type="text" name="tags" value="<?= e($meta['tags'] ?? '') ?>">
                    </label>
                    <label class="a-field">
                        <span>Attached file <em>in content/memoranda/files/</em></span>
                        <input type="text" name="file" value="<?= e($meta['file'] ?? '') ?>" placeholder="minutes-2026-03.pdf">
                    </label>
                <?php endif; ?>

                <label class="a-field">
                    <span>Draft <em>hidden from the site</em></span>
                    <select name="draft">
                        <option value="" <?= ($meta['draft'] ?? '') === '' ? 'selected' : '' ?>>no, publish it</option>
                        <option value="yes" <?= ($meta['draft'] ?? '') === 'yes' ? 'selected' : '' ?>>yes, keep it hidden</option>
                    </select>
                </label>
            </aside>
        </form>

        <?php if ($item): ?>
            <form method="post" class="dangerzone" onsubmit="return confirm('Delete this permanently?')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="kind" value="<?= $kind ?>">
                <input type="hidden" name="slug" value="<?= e($item['slug']) ?>">
                <button class="a-btn a-btn--small a-btn--danger" name="do" value="delete_content">Delete this <?= $kind === 'pages' ? 'page' : 'memorandum' ?></button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

<?php
/* ============================================================== gallery */
elseif ($section === 'gallery'):
    $albums  = $content->albums();
    $current = isset($_GET['album']) ? $content->album((string) $_GET['album']) : null;
?>
    <header class="pane__head">
        <h1>Photographs</h1>
        <p>Albums are folders. Upload as many photographs as you like; captions are optional.</p>
    </header>

    <?php if (!$current): ?>
        <table class="grid">
            <thead><tr><th>Album</th><th>Photographs</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($albums as $a): ?>
                <tr>
                    <td><a class="a-link" href="?s=gallery&album=<?= e($a['slug']) ?>"><?= e($a['title']) ?></a></td>
                    <td><?= count($a['photos']) ?></td>
                    <td><?= e(nice_date($a['date'])) ?></td>
                    <td class="right"><a class="a-link" href="<?= e(url('/photos/' . $a['slug'])) ?>" target="_blank">view ↗</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <section class="block">
            <h2>New album</h2>
            <form method="post" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <label class="a-field"><span>Title</span><input type="text" name="album_title" required></label>
                <label class="a-field"><span>Web address</span><input type="text" name="album_slug" required pattern="[a-z0-9\- ]+" placeholder="carol-evening"></label>
                <label class="a-field"><span>Date</span><input type="date" name="album_date"></label>
                <label class="a-field"><span>Note</span><input type="text" name="album_note"></label>
                <button class="a-btn" name="do" value="album_create">Create album</button>
            </form>
        </section>
    <?php else: ?>
        <p class="rowlinks">
            <a class="a-link" href="?s=gallery">← all albums</a>
            <a class="a-link" href="<?= e(url('/photos/' . $current['slug'])) ?>" target="_blank">view on the site ↗</a>
        </p>

        <section class="block">
            <h2>Add photographs to “<?= e($current['title']) ?>”</h2>
            <form method="post" enctype="multipart/form-data" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="album" value="<?= e($current['slug']) ?>">
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" required>
                <button class="a-btn" name="do" value="album_upload">Upload</button>
            </form>
            <p class="muted">JPEG, PNG, WebP or GIF, up to 8 MB each. Anything larger than 1600px is scaled down automatically.</p>
        </section>

        <form method="post" class="block">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="album" value="<?= e($current['slug']) ?>">

            <h2>Album details</h2>
            <div class="inline-form">
                <label class="a-field"><span>Title</span><input type="text" name="album_title" value="<?= e($current['title']) ?>"></label>
                <label class="a-field"><span>Date</span><input type="date" name="album_date" value="<?= e($current['date']) ?>"></label>
                <label class="a-field"><span>Note</span><input type="text" name="album_note" value="<?= e($current['note']) ?>"></label>
            </div>

            <h2>Captions</h2>
            <div class="shots">
                <?php foreach ($current['photos'] as $p): ?>
                    <figure class="shotedit">
                        <img src="<?= e(url($p['url'])) ?>" alt="">
                        <input type="text" name="caption[<?= e($p['file']) ?>]" value="<?= e($p['caption']) ?>" placeholder="Caption (optional)">
                        <span class="shotedit__file"><?= e($p['file']) ?></span>
                    </figure>
                <?php endforeach; ?>
            </div>

            <button class="a-btn" name="do" value="album_captions">Save album</button>
        </form>

        <section class="block">
            <h2>Remove a photograph</h2>
            <div class="rowlinks">
                <?php foreach ($current['photos'] as $p): ?>
                    <form method="post" onsubmit="return confirm('Delete <?= e($p['file']) ?>?')">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="album" value="<?= e($current['slug']) ?>">
                        <input type="hidden" name="file" value="<?= e($p['file']) ?>">
                        <button class="a-btn a-btn--small a-btn--danger" name="do" value="photo_delete"><?= e($p['file']) ?> ✕</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

<?php
/* ================================================================= help */
else: ?>
    <header class="pane__head">
        <h1>How to add things</h1>
        <p>Everything below works without touching a line of code.</p>
    </header>

    <section class="block">
        <h2>Add a page</h2>
        <ol class="steps">
            <li>Pages → New page.</li>
            <li>Give it a title and a web address (<code>history</code> becomes <code>/history</code>).</li>
            <li>Set “Show in the menu” to yes if it belongs in the navigation.</li>
            <li>Save. It is live.</li>
        </ol>
        <p class="muted">The equivalent by hand: drop a file into <code>content/pages/</code>. Same result.</p>
    </section>

    <section class="block">
        <h2>Drop a widget into any page</h2>
        <p>Type the tag anywhere in the body of a page or memorandum. These are installed now:</p>
        <table class="grid">
            <thead><tr><th>Tag</th><th>What it puts on the page</th></tr></thead>
            <tbody>
                <tr><td><code>[[comments]]</code></td><td>A moderated comments section for that page</td></tr>
                <tr><td><code>[[comments thread="noticeboard"]]</code></td><td>A shared thread that several pages can use</td></tr>
                <tr><td><code>[[gallery]]</code></td><td>Every album, as cards</td></tr>
                <tr><td><code>[[gallery album="summer-fete" columns="3"]]</code></td><td>The photographs from one album</td></tr>
                <tr><td><code>[[memoranda limit="3"]]</code></td><td>The most recent filed documents</td></tr>
                <tr><td><code>[[memoranda tag="minutes"]]</code></td><td>Only documents on one subject</td></tr>
                <tr><td><code>[[contact-form]]</code></td><td>The contact form</td></tr>
                <tr><td><code>[[notice kind="alert" text="..."]]</code></td><td>A highlighted notice box</td></tr>
            </tbody>
        </table>
        <p class="muted">Registered right now: <?= e(implode(', ', array_map(fn($n) => '[[' . $n . ']]', Widgets::names()))) ?></p>
    </section>

    <section class="block">
        <h2>Formatting in the body</h2>
        <table class="grid">
            <thead><tr><th>You type</th><th>You get</th></tr></thead>
            <tbody>
                <tr><td><code>## Heading</code></td><td>A section heading</td></tr>
                <tr><td><code>**important**</code></td><td>Bold</td></tr>
                <tr><td><code>*quietly*</code></td><td>Italic</td></tr>
                <tr><td><code>- item</code></td><td>A bulleted list</td></tr>
                <tr><td><code>1. item</code></td><td>A numbered list</td></tr>
                <tr><td><code>&gt; quoted</code></td><td>A pull quote</td></tr>
                <tr><td><code>[text](/about)</code></td><td>A link</td></tr>
                <tr><td><code>![caption](url)</code></td><td>An image</td></tr>
            </tbody>
        </table>
    </section>

    <section class="block">
        <h2>Build a new widget</h2>
        <p>Copy <code>widgets/notice.php</code>, rename it, and change the middle. A file in that folder is a widget — there is no list to register it in and no core file to edit.</p>
        <pre class="a-code">&lt;?php
Widgets::register('hours', function (array $options): string {
    return '&lt;p&gt;Office open ' . htmlspecialchars($options['days'] ?? 'Tue &amp; Thu') . '&lt;/p&gt;';
});</pre>
        <p>Save as <code>widgets/hours.php</code> and <code>[[hours days="Mon-Fri"]]</code> works everywhere, immediately.</p>
    </section>

    <section class="block">
        <h2>Change the site name, or the spam settings</h2>
        <p><code>config.php</code> holds the site name, tagline, address, opening hours and whether comments appear straight away or wait for approval. It is the only file you need for a rebrand.</p>
    </section>
<?php endif; ?>
</main>
</body>
</html>
