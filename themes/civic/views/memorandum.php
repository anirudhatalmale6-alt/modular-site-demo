<?php
/**
 * view: memorandum — one filed document at /memoranda/<slug>
 */
$meta   = $memo['meta'];
$thread = 'memo:' . $memo['slug'];
$file   = $meta['file'] ?? '';
?>
<section class="pagehead pagehead--doc">
    <div class="wrap">
        <p class="eyebrow"><a href="<?= e(url('/memoranda')) ?>">Memoranda</a></p>
        <h1><?= e($meta['title']) ?></h1>
        <dl class="docmeta">
            <?php if (!empty($meta['ref'])): ?>
                <div><dt>Reference</dt><dd><?= e($meta['ref']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($meta['date'])): ?>
                <div><dt>Filed</dt><dd><?= e(nice_date($meta['date'])) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($meta['author'])): ?>
                <div><dt>Issued by</dt><dd><?= e($meta['author']) ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>
</section>

<article class="wrap prose" data-reveal>
    <?= render_body($memo['body'], ['_thread' => $thread, '_flash' => $flash]) ?>

    <?php if ($file): ?>
        <p class="download">
            <a class="btn btn--small" href="<?= e(url('content/memoranda/files/' . $file)) ?>" download>Download the original (<?= e(strtoupper(pathinfo($file, PATHINFO_EXTENSION))) ?>)</a>
        </p>
    <?php endif; ?>
</article>

<div class="wrap" data-reveal>
    <?= Widgets::render('comments', [
        '_thread' => $thread,
        'thread'  => $thread,
        '_flash'  => $flash,
        'title'   => 'Comments on this memorandum',
    ]) ?>
</div>
