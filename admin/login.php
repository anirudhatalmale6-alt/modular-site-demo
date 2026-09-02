<?php
/** admin/login.php — the sign-in screen. */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — <?= e($config['site_short']) ?> admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Newsreader:opsz,wght@6..72,300..500&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('admin/admin.css')) ?>">
</head>
<body class="signin">
<form class="signin__box" method="post">
    <p class="signin__eyebrow">Administrator</p>
    <h1><?= e($config['site_name']) ?></h1>
    <?php if ($loginError): ?>
        <p class="a-flash a-flash--bad"><?= e($loginError) ?></p>
    <?php endif; ?>
    <label class="a-field">
        <span>Password</span>
        <input type="password" name="password" autofocus required>
    </label>
    <button class="a-btn" type="submit">Sign in</button>
    <p class="signin__foot"><a href="<?= e(url('/')) ?>">← back to the website</a></p>
</form>
</body>
</html>
