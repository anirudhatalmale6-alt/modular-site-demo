<?php
/**
 * Widget: contact-form
 *
 *     [[contact-form]]
 *     [[contact-form title="Write to the committee"]]
 *
 * Messages land in admin → Messages. Turn on emailing in config.php once the
 * site is on a host with working mail.
 */
Widgets::register('contact-form', function (array $o): string {
    $flash = (!empty($o['_flash']) && ($o['_flash']['thread'] ?? null) === 'contact') ? $o['_flash'] : null;

    $old = $_SESSION['old_contact'] ?? [];
    unset($_SESSION['old_contact']);
    $val = fn(string $k) => e((string) ($old[$k] ?? ''));

    ob_start(); ?>
    <section class="contact-form" id="contact-form">
        <h2><?= e((string) ($o['title'] ?? 'Send us a message')) ?></h2>

        <?php if ($flash): ?>
            <p class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/')) ?>">
            <input type="hidden" name="form" value="contact">
            <?= Security::formFields() ?>

            <div class="field-row">
                <label class="field">
                    <span>Your name <em>required</em></span>
                    <input type="text" name="name" maxlength="80" required value="<?= $val('name') ?>">
                </label>
                <label class="field">
                    <span>Email <em>required</em></span>
                    <input type="email" name="email" maxlength="120" required value="<?= $val('email') ?>">
                </label>
            </div>

            <label class="field">
                <span>Subject</span>
                <input type="text" name="subject" maxlength="120" value="<?= $val('subject') ?>">
            </label>

            <label class="field">
                <span>Message <em>required</em></span>
                <textarea name="message" rows="6" maxlength="5000" required><?= $val('message') ?></textarea>
            </label>

            <button type="submit" class="btn">Send message</button>
            <p class="comment-form__note">
                We use your address to reply and nothing else. It is not added to any list.
            </p>
        </form>
    </section>
    <?php
    return (string) ob_get_clean();
});
