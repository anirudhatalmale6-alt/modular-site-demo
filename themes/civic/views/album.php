<?php
/**
 * view: album — one album at /photos/<album>
 *
 * Each album carries its own comment thread, so residents can talk about the
 * photographs without that spilling onto other pages.
 */
$thread = 'album:' . $album['slug'];
?>
<section class="pagehead">
    <div class="wrap">
        <p class="eyebrow"><a href="<?= e(url('/photos')) ?>">Picture archive</a></p>
        <h1><?= e($album['title']) ?></h1>
        <p class="pagehead__lede">
            <?= e($album['note']) ?>
            <?php if ($album['date']): ?><span class="stamp"><?= e(nice_date($album['date'])) ?></span><?php endif; ?>
        </p>
    </div>
</section>

<div class="wrap" data-reveal>
    <?= Widgets::render('gallery', ['album' => $album['slug'], 'columns' => 3]) ?>
</div>

<div class="wrap" data-reveal>
    <?= Widgets::render('comments', [
        '_thread' => $thread,
        'thread'  => $thread,
        '_flash'  => $flash,
        'title'   => 'Comments on this album',
    ]) ?>
</div>
