<?php
/**
 * Widget: memoranda
 *
 *     [[memoranda]]                the full archive, newest first
 *     [[memoranda limit="3"]]      the three most recent — good on a home page
 *     [[memoranda tag="minutes"]]  only items whose tags include "minutes"
 */
Widgets::register('memoranda', function (array $o): string {
    $content = new Content(BASE_DIR . '/content');
    $items   = $content->memoranda();

    if (!empty($o['tag'])) {
        $tag   = mb_strtolower((string) $o['tag']);
        $items = array_values(array_filter($items, function ($m) use ($tag) {
            $tags = array_map('trim', explode(',', mb_strtolower($m['meta']['tags'] ?? '')));
            return in_array($tag, $tags, true);
        }));
    }

    if (!empty($o['limit'])) {
        $items = array_slice($items, 0, (int) $o['limit']);
    }

    if (!$items) {
        return '<p class="comments__empty">No memoranda have been filed yet.</p>';
    }

    ob_start(); ?>
    <ul class="memo-list">
        <?php foreach ($items as $m):
            $meta = $m['meta']; ?>
            <li class="memo">
                <a class="memo__link" href="<?= e(url('/memoranda/' . $m['slug'])) ?>">
                    <span class="memo__ref"><?= e($meta['ref'] ?? '—') ?></span>
                    <span class="memo__title"><?= e($meta['title']) ?></span>
                    <span class="memo__date"><?= e(nice_date($meta['date'] ?? '')) ?></span>
                </a>
                <?php if (!empty($meta['summary'])): ?>
                    <p class="memo__summary"><?= e($meta['summary']) ?></p>
                <?php endif; ?>
                <?php if (!empty($meta['tags'])): ?>
                    <p class="tags">
                        <?php foreach (array_map('trim', explode(',', $meta['tags'])) as $tag): ?>
                            <span class="tag"><?= e($tag) ?></span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return (string) ob_get_clean();
});
