<?php
/**
 * view: page — any file in content/pages/.
 *
 * The home page gets the tall hero; every other page gets the compact one.
 * Nothing here knows the names of your pages, so a new page needs no code.
 */
$meta   = $page['meta'];
$thread = 'page:' . $page['slug'];
$body   = render_body($page['body'], ['_thread' => $thread, '_flash' => $flash]);
?>

<?php if (!empty($home)): ?>
    <section class="hero">
        <div class="wrap hero__inner">
            <p class="eyebrow"><?= e($meta['eyebrow'] ?? 'Established 1974') ?></p>
            <h1 class="hero__title"><?= e($meta['headline'] ?? $meta['title']) ?></h1>
            <?php if (!empty($meta['summary'])): ?>
                <p class="hero__lede"><?= e($meta['summary']) ?></p>
            <?php endif; ?>
            <div class="hero__actions">
                <a class="btn" href="<?= e(url('/memoranda')) ?>">Read the memoranda</a>
                <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Contact the committee</a>
            </div>
        </div>
        <div class="hero__edge" aria-hidden="true"></div>
    </section>
<?php else: ?>
    <section class="pagehead">
        <div class="wrap">
            <p class="eyebrow"><?= e($meta['eyebrow'] ?? $config['site_short']) ?></p>
            <h1><?= e($meta['title']) ?></h1>
            <?php if (!empty($meta['summary'])): ?>
                <p class="pagehead__lede"><?= e($meta['summary']) ?></p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<article class="wrap prose" data-reveal>
    <?= $body ?>
</article>
