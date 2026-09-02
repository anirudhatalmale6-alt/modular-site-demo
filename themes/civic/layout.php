<?php
/**
 * layout.php — the chrome that wraps every page.
 *
 * The view has already run and left its output in $content_html.
 * Anything that appears on every page (header, navigation, footer) lives here
 * and nowhere else.
 */
$navPages   = $nav ?? [];
$title      = $page_title ?? ($page['meta']['title'] ?? $config['site_name']);
$isHome     = !empty($home);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($isHome ? $config['site_name'] : $title . ' — ' . $config['site_short']) ?></title>
<meta name="description" content="<?= e($page['meta']['summary'] ?? $config['description']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..800&family=Newsreader:ital,opsz,wght@0,6..72,300..600;1,6..72,300..500&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('themes/' . $config['theme'] . '/style.css')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' fill='%23151310'/><text x='16' y='23' font-size='20' font-family='Georgia,serif' fill='%23e8dfcd' text-anchor='middle'>H</text></svg>">
</head>
<body class="<?= $isHome ? 'is-home' : 'is-inner' ?>">

<a class="skip" href="#main">Skip to content</a>

<header class="masthead">
    <div class="masthead__rule" aria-hidden="true"></div>
    <div class="wrap masthead__inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand__mark" aria-hidden="true">HR</span>
            <span class="brand__text">
                <strong><?= e($config['site_name']) ?></strong>
                <em><?= e($config['tagline']) ?></em>
            </span>
        </a>

        <input type="checkbox" id="navtoggle" class="navtoggle" hidden>
        <label for="navtoggle" class="navburger" aria-label="Menu"><span></span></label>

        <nav class="nav" aria-label="Main">
            <ul>
                <?php foreach ($navPages as $item):
                    $slug   = $item['slug'];
                    $href   = in_array($slug, ['photos', 'memoranda'], true) ? '/' . $slug : '/' . $slug;
                    $active = ($route === $slug); ?>
                    <li><a href="<?= e(url($href)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($item['meta']['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>

<main id="main">
    <?= $content_html ?>
</main>

<footer class="foot">
    <div class="wrap foot__inner">
        <div class="foot__col">
            <h4><?= e($config['site_name']) ?></h4>
            <p><?= nl2br(e($config['contact']['address'])) ?></p>
        </div>
        <div class="foot__col">
            <h4>Pages</h4>
            <ul>
                <li><a href="<?= e(url('/')) ?>">Home</a></li>
                <?php foreach ($navPages as $item): ?>
                    <li><a href="<?= e(url('/' . $item['slug'])) ?>"><?= e($item['meta']['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="foot__col">
            <h4>Office</h4>
            <p><?= e($config['contact']['hours']) ?></p>
            <p class="foot__admin"><a href="<?= e(url('/admin/')) ?>">Administrator sign-in</a></p>
        </div>
    </div>
    <div class="wrap foot__base">
        <p><?= e($config['footer_note']) ?></p>
        <p class="foot__year">© <?= date('Y') ?> <?= e($config['site_name']) ?></p>
    </div>
</footer>

<script>
/* The only JavaScript on the public site: reveal-on-scroll for long pages,
   and closing the mobile menu after a tap. Everything works without it. */
(function () {
    var reveal = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && reveal.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px' });
        reveal.forEach(function (el) { io.observe(el); });
    } else {
        reveal.forEach(function (el) { el.classList.add('is-in'); });
    }

    var toggle = document.getElementById('navtoggle');
    document.querySelectorAll('.nav a').forEach(function (a) {
        a.addEventListener('click', function () { if (toggle) toggle.checked = false; });
    });
})();
</script>
</body>
</html>
