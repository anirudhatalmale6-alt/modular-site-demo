<?php
/**
 * Widget: gallery
 *
 *     [[gallery]]                         every album, as cards
 *     [[gallery album="opening-day"]]     the photos in one album
 *     [[gallery album="opening-day" columns="4" limit="8"]]
 *
 * Photos are files. Drop a JPG into content/gallery/<album>/ and it appears —
 * through the admin uploader, over FTP, or by copying it in.
 */
Widgets::register('gallery', function (array $o): string {
    $content = new Content(BASE_DIR . '/content');
    $columns = max(2, min(5, (int) ($o['columns'] ?? 3)));
    $limit   = (int) ($o['limit'] ?? 0);

    ob_start();

    if (!empty($o['album'])) {
        $album = $content->album((string) $o['album']);
        if (!$album) {
            return '<div class="widget-missing">No album called <code>' . e((string) $o['album']) . '</code>.</div>';
        }

        $photos = $limit > 0 ? array_slice($album['photos'], 0, $limit) : $album['photos'];
        ?>
        <div class="gallery-grid" style="--cols: <?= $columns ?>">
            <?php foreach ($photos as $i => $photo): ?>
                <figure class="shot">
                    <a href="<?= e(url($photo['url'])) ?>" target="_blank" rel="noopener">
                        <img src="<?= e(url($photo['url'])) ?>" alt="<?= e($photo['caption'] ?: $album['title']) ?>" loading="lazy">
                    </a>
                    <?php if ($photo['caption']): ?>
                        <figcaption><?= e($photo['caption']) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    $albums = $content->albums();
    if (!$albums) {
        return '<p class="comments__empty">No photo albums yet.</p>';
    }
    ?>
    <div class="album-grid">
        <?php foreach ($albums as $album):
            $cover = $album['photos'][0]['url'] ?? null; ?>
            <a class="album-card" href="<?= e(url('/photos/' . $album['slug'])) ?>">
                <div class="album-card__frame">
                    <?php if ($cover): ?>
                        <img src="<?= e(url($cover)) ?>" alt="<?= e($album['title']) ?>" loading="lazy">
                    <?php endif; ?>
                    <span class="album-card__count"><?= count($album['photos']) ?> photos</span>
                </div>
                <h3><?= e($album['title']) ?></h3>
                <?php if ($album['date']): ?><p class="stamp"><?= e(nice_date($album['date'])) ?></p><?php endif; ?>
                <?php if ($album['note']): ?><p class="album-card__note"><?= e($album['note']) ?></p><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    return (string) ob_get_clean();
});
