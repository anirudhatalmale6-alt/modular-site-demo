<?php
/**
 * view: 404 — nothing matched the address.
 */
?>
<section class="pagehead">
    <div class="wrap">
        <p class="eyebrow">Not found</p>
        <h1>That page is not in the file</h1>
        <p class="pagehead__lede">
            The address <code><?= e('/' . $path) ?></code> does not match any page, album or memorandum.
            It may have been renamed.
        </p>
        <p><a class="btn" href="<?= e(url('/')) ?>">Back to the front page</a></p>
    </div>
</section>
