<?php
/**
 * Widget: comments
 *
 * Usage inside any page or memorandum:
 *
 *     [[comments]]                              comments for that page
 *     [[comments thread="noticeboard"]]         a named shared thread
 *     [[comments title="Have your say"]]
 *
 * Every comment is held for moderation before it is public (unless
 * comments_auto_approve is switched on in config.php).
 */
Widgets::register('comments', function (array $o): string {
    $config = require BASE_DIR . '/config.php';

    $thread = (string) ($o['thread'] ?? $o['_thread'] ?? 'general');
    $title  = (string) ($o['title'] ?? 'Comments');
    $closed = !empty($config['comments_closed']) || !empty($o['closed']);

    $items = Comments::approved($thread);
    $count = count($items);

    $flash = null;
    if (!empty($o['_flash']) && ($o['_flash']['thread'] ?? null) === $thread) {
        $flash = $o['_flash'];
    }

    $old = $_SESSION['old_comment'] ?? ['author' => '', 'email' => '', 'body' => ''];
    unset($_SESSION['old_comment']);

    ob_start(); ?>
    <section class="comments" id="comments">
        <header class="comments__head">
            <h2><?= e($title) ?></h2>
            <span class="tally"><?= $count ?> <?= $count === 1 ? 'comment' : 'comments' ?></span>
        </header>

        <?php if ($flash): ?>
            <p class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
        <?php endif; ?>

        <?php if ($count === 0): ?>
            <p class="comments__empty">No comments yet. Yours would be the first.</p>
        <?php else: ?>
            <ol class="comments__list">
                <?php foreach ($items as $c): ?>
                    <li class="comment">
                        <div class="comment__meta">
                            <span class="comment__author"><?= e($c['author']) ?></span>
                            <time datetime="<?= e($c['created_at']) ?>"><?= e(Comments::ago($c['created_at'])) ?></time>
                        </div>
                        <div class="comment__body"><?= nl2br(e($c['body'])) ?></div>
                        <?php if (!empty($c['reply'])): ?>
                            <div class="comment__reply">
                                <span class="comment__reply-label">Reply from the committee</span>
                                <?= nl2br(e($c['reply'])) ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if ($closed): ?>
            <p class="comments__empty">Comments are closed on this page.</p>
        <?php else: ?>
            <form class="comment-form" method="post" action="<?= e(url('/')) ?>">
                <h3>Leave a comment</h3>
                <p class="comment-form__note">
                    Comments are read by a moderator before they appear. Your email is
                    never published — it is only used if we need to reply to you.
                </p>

                <input type="hidden" name="form" value="comment">
                <input type="hidden" name="thread" value="<?= e($thread) ?>">
                <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? url('/')) ?>">
                <?= Security::formFields() ?>

                <div class="field-row">
                    <label class="field">
                        <span>Your name <em>required</em></span>
                        <input type="text" name="author" maxlength="80" required value="<?= e($old['author']) ?>">
                    </label>
                    <label class="field">
                        <span>Email <em>optional, never shown</em></span>
                        <input type="email" name="email" maxlength="120" value="<?= e($old['email']) ?>">
                    </label>
                </div>

                <label class="field">
                    <span>Comment <em>required</em></span>
                    <textarea name="body" rows="5" maxlength="4000" required><?= e($old['body']) ?></textarea>
                </label>

                <button type="submit" class="btn">Post comment</button>
            </form>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
});
