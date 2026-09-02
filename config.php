<?php
/**
 * config.php — every setting for the site, in one file.
 *
 * This is the only file you need to edit to rebrand the site. Nothing in
 * /core reads anything that is not defined here.
 */
return [
    // --- identity -----------------------------------------------------------
    'site_name'    => 'Harbour Ridge Association',
    'site_short'   => 'Harbour Ridge',
    'tagline'      => 'A residents’ association, established 1974',
    'description'  => 'News, memoranda, photographs and public comment for the Harbour Ridge community.',
    'footer_note'  => 'Harbour Ridge Association is a not-for-profit residents’ body. Views posted in the comments are those of their authors.',

    // --- contact details shown on the Contact page --------------------------
    'contact' => [
        'address' => "The Old Reading Room\n14 Ridgeway Lane\nHarbour Ridge",
        'phone'   => '+00 000 000 000',
        'hours'   => 'Committee office open Tuesday and Thursday, 10am – 2pm',
    ],

    // --- behaviour ----------------------------------------------------------
    // false  = every comment waits for approval (recommended, and the default)
    // true   = comments appear immediately, spam filter still applies
    'comments_auto_approve' => false,
    'comments_closed'       => false,

    // Where contact-form messages go. In the demo they are stored in the admin
    // inbox only; set 'email' and 'send_email' => true on a real host with mail.
    'contact_form' => [
        'send_email' => false,
        'email'      => '',
    ],

    // --- admin --------------------------------------------------------------
    // Change this by running:  php admin/make-password.php "your new password"
    // and pasting the hash it prints here. The password itself is never stored.
    'admin_password_hash' => '$2y$12$La/bGg5bmO1ndCyKglOAKOS4lUqT2HZv6FAY80srqRvND.iLF6V5y', // "demo1234"
    'admin_session_hours' => 8,

    // --- theme --------------------------------------------------------------
    'theme' => 'civic',
];
