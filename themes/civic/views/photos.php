<?php
/**
 * view: photos — the album index at /photos
 *
 * The intro text comes from content/pages/photos.md, so it is editable in the
 * admin like any other page. The album cards below it are generated from the
 * folders in content/gallery/.
 */
$meta = $page['meta'] ?? ['title' => 'Photographs'];
?>
<section class="pagehead">
    <div class="wrap">
        <p class="eyebrow"><?= e($meta['eyebrow'] ?? 'Picture archive') ?></p>
        <h1><?= e($meta['title']) ?></h1>
        <?php if (!empty($meta['summary'])): ?>
            <p class="pagehead__lede"><?= e($meta['summary']) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($page['body'])): ?>
    <div class="wrap prose prose--tight" data-reveal>
        <?= render_body($page['body'], ['_thread' => 'page:photos', '_flash' => $flash]) ?>
    </div>
<?php endif; ?>

<div class="wrap" data-reveal>
    <?= Widgets::render('gallery', []) ?>
</div>
