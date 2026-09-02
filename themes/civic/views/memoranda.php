<?php
/**
 * view: memoranda — the archive index at /memoranda
 */
$meta = $page['meta'] ?? ['title' => 'Memoranda'];
$tags = [];
foreach ($memoranda as $m) {
    foreach (array_filter(array_map('trim', explode(',', $m['meta']['tags'] ?? ''))) as $t) {
        $tags[$t] = ($tags[$t] ?? 0) + 1;
    }
}
ksort($tags);
?>
<section class="pagehead">
    <div class="wrap">
        <p class="eyebrow"><?= e($meta['eyebrow'] ?? 'The record') ?></p>
        <h1><?= e($meta['title']) ?></h1>
        <?php if (!empty($meta['summary'])): ?>
            <p class="pagehead__lede"><?= e($meta['summary']) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="wrap archive" data-reveal>
    <div class="archive__main">
        <?php if (!empty($page['body'])): ?>
            <div class="prose prose--tight"><?= render_body($page['body'], ['_thread' => 'page:memoranda', '_flash' => $flash]) ?></div>
        <?php endif; ?>
        <?= Widgets::render('memoranda', []) ?>
    </div>

    <aside class="archive__side">
        <div class="cardstack">
            <h4>In this archive</h4>
            <p class="figure"><?= count($memoranda) ?></p>
            <p class="stamp">documents filed</p>
        </div>
        <?php if ($tags): ?>
            <div class="cardstack">
                <h4>Subjects</h4>
                <p class="tags">
                    <?php foreach ($tags as $tag => $n): ?>
                        <span class="tag"><?= e($tag) ?> <b><?= $n ?></b></span>
                    <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>
        <div class="cardstack">
            <h4>Missing something?</h4>
            <p>Older paper records are held at the reading room. Ask the secretary and we will scan and file it here.</p>
            <a class="btn btn--small" href="<?= e(url('/contact')) ?>">Request a document</a>
        </div>
    </aside>
</div>
