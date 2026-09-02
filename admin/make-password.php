<?php
/**
 * make-password.php — print a hash to paste into config.php.
 *
 * Run it from the command line:
 *     php admin/make-password.php "the new password"
 *
 * The password itself is never written anywhere. Only the hash is stored, and
 * a hash cannot be turned back into the password.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line, not from a browser.\n");
}

$password = $argv[1] ?? '';
if (strlen($password) < 8) {
    exit("Usage: php admin/make-password.php \"a password of at least 8 characters\"\n");
}

echo "Paste this into config.php as admin_password_hash:\n\n";
echo "    'admin_password_hash' => '" . password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]) . "',\n\n";
