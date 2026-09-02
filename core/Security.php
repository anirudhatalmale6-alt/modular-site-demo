<?php
/**
 * Security.php — CSRF tokens, the spam filter, and rate limiting.
 *
 * The spam defence is layered and deliberately invisible to a real visitor:
 *
 *   1. Honeypot     — a field a human never sees and never fills in.
 *   2. Time trap    — a form submitted in under MIN_SECONDS was not typed.
 *   3. CSRF token   — the form must have been served by us, in this session.
 *   4. Rate limit   — N submissions per IP per window, then a polite refusal.
 *   5. Content score— links, shouting, known spam vocabulary, gibberish names.
 *   6. Moderation   — nothing appears publicly until it is approved.
 *
 * No third-party captcha, so nothing to sign up for, no cookies handed to
 * Google, and nothing for a visitor to squint at. If a real reCAPTCHA or
 * hCaptcha key is wanted later it slots in beside this, it does not replace it.
 */
final class Security
{
    /** A form filled in faster than this was filled in by a machine. */
    public const MIN_SECONDS = 3;

    /** Comments allowed from one IP inside RATE_WINDOW seconds. */
    public const RATE_LIMIT  = 5;
    public const RATE_WINDOW = 600;

    /** Score at or above which a submission is filed as spam, unseen. */
    public const SPAM_THRESHOLD = 5;

    private const SPAM_WORDS = [
        'viagra', 'cialis', 'casino', 'porn', 'crypto giveaway', 'forex signals',
        'seo services', 'buy backlinks', 'cheap loans', 'work from home',
        'binary options', 'weight loss pills', 'replica watches', 'escort',
    ];

    /* ----------------------------------------------------------------- CSRF */

    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function checkToken(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }

    /** Hidden fields every public form includes. Rendered once, checked once. */
    public static function formFields(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES);
        $time  = time();
        return <<<HTML
            <input type="hidden" name="csrf" value="{$token}">
            <input type="hidden" name="started_at" value="{$time}">
            <div class="hp" aria-hidden="true">
                <label>Leave this field empty
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </label>
            </div>
        HTML;
    }

    /* ------------------------------------------------------------ rate limit */

    public static function rateLimited(string $action, string $ip): bool
    {
        $pdo = Db::get();
        $pdo->prepare("DELETE FROM rate_log WHERE created_at < datetime('now', '-1 day')")->execute();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM rate_log
             WHERE ip = ? AND action = ? AND created_at > datetime('now', ?)"
        );
        $stmt->execute([$ip, $action, '-' . self::RATE_WINDOW . ' seconds']);

        return (int) $stmt->fetchColumn() >= self::RATE_LIMIT;
    }

    public static function logAction(string $action, string $ip): void
    {
        Db::get()->prepare("INSERT INTO rate_log (ip, action, created_at) VALUES (?, ?, datetime('now'))")
            ->execute([$ip, $action]);
    }

    /* ----------------------------------------------------------- spam score */

    /**
     * Score a submission. Returns [score, [reasons]].
     * Higher is worse; SPAM_THRESHOLD and above is filed as spam automatically.
     */
    public static function score(array $input): array
    {
        $name    = trim((string) ($input['author'] ?? ''));
        $body    = trim((string) ($input['body'] ?? ''));
        $started = (int) ($input['started_at'] ?? 0);
        $score   = 0;
        $why     = [];

        if (trim((string) ($input['website'] ?? '')) !== '') {
            $score += 10;
            $why[]  = 'honeypot field was filled in';
        }

        if ($started > 0 && (time() - $started) < self::MIN_SECONDS) {
            $score += 6;
            $why[]  = 'submitted in under ' . self::MIN_SECONDS . ' seconds';
        }

        $links = preg_match_all('~https?://|www\.~i', $body);
        if ($links >= 3) {
            $score += 6;
            $why[]  = $links . ' links in the body';
        } elseif ($links > 0) {
            $score += 2;
            $why[]  = 'contains a link';
        }

        if (preg_match('/\[url=|\[link=|<a\s+href/i', $body)) {
            $score += 6;
            $why[]  = 'BBCode or HTML link markup';
        }

        $lower = mb_strtolower($name . ' ' . $body);
        foreach (self::SPAM_WORDS as $word) {
            if (str_contains($lower, $word)) {
                $score += 5;
                $why[]  = 'spam vocabulary: "' . $word . '"';
                break;
            }
        }

        $letters = preg_replace('/[^a-z]/i', '', $body);
        if (strlen($letters) > 20 && strlen(preg_replace('/[^A-Z]/', '', $body)) / strlen($letters) > 0.6) {
            $score += 3;
            $why[]  = 'mostly capital letters';
        }

        // A "name" with no vowels is almost always generated.
        if (strlen($name) > 4 && !preg_match('/[aeiou]/i', $name)) {
            $score += 3;
            $why[]  = 'name has no vowels';
        }

        if (mb_strlen($body) < 4) {
            $score += 2;
            $why[]  = 'body too short to mean anything';
        }

        return [$score, $why];
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
