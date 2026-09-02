<?php
/**
 * Comments.php — storing, moderating and listing comments.
 *
 * A "thread" is just a string: the slug of whatever the comment is attached to
 * ("page:about", "memo:2026-budget"). Any new page or widget can host comments
 * by passing its own thread id — nothing here needs changing to support it.
 */
final class Comments
{
    /** Public comments for a thread, oldest first. */
    public static function approved(string $thread): array
    {
        $stmt = Db::get()->prepare(
            "SELECT * FROM comments WHERE thread = ? AND status = 'approved' ORDER BY created_at ASC, id ASC"
        );
        $stmt->execute([$thread]);
        return $stmt->fetchAll();
    }

    public static function countApproved(string $thread): int
    {
        $stmt = Db::get()->prepare("SELECT COUNT(*) FROM comments WHERE thread = ? AND status = 'approved'");
        $stmt->execute([$thread]);
        return (int) $stmt->fetchColumn();
    }

    /** Everything in one status, for the admin queue. */
    public static function byStatus(string $status, int $limit = 200): array
    {
        $stmt = Db::get()->prepare(
            "SELECT * FROM comments WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT ?"
        );
        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll();
    }

    public static function counts(): array
    {
        $rows = Db::get()->query("SELECT status, COUNT(*) c FROM comments GROUP BY status")->fetchAll();
        $out  = ['pending' => 0, 'approved' => 0, 'spam' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Validate and store a submission.
     *
     * @return array{ok:bool, message:string, status?:string}
     */
    public static function submit(string $thread, array $input, bool $autoApprove = false): array
    {
        if (!Security::checkToken($input['csrf'] ?? null)) {
            return ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.'];
        }

        $author = trim((string) ($input['author'] ?? ''));
        $email  = trim((string) ($input['email'] ?? ''));
        $body   = trim((string) ($input['body'] ?? ''));

        if ($author === '' || $body === '') {
            return ['ok' => false, 'message' => 'Please fill in your name and your comment.'];
        }
        if (mb_strlen($author) > 80) {
            return ['ok' => false, 'message' => 'That name is a little long — 80 characters maximum.'];
        }
        if (mb_strlen($body) > 4000) {
            return ['ok' => false, 'message' => 'That comment is over the 4,000 character limit.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'That email address does not look right. Leave it blank if you prefer.'];
        }

        $ip = Security::ip();
        if (Security::rateLimited('comment', $ip)) {
            return ['ok' => false, 'message' => 'You have posted several comments just now — please wait a few minutes before posting again.'];
        }

        [$score, $why] = Security::score($input + ['author' => $author, 'body' => $body]);
        $status = $score >= Security::SPAM_THRESHOLD ? 'spam' : ($autoApprove ? 'approved' : 'pending');

        Db::get()->prepare(
            "INSERT INTO comments (thread, author, email, body, status, ip, user_agent, spam_score, spam_reason, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))"
        )->execute([
            $thread, $author, $email, $body, $status, $ip,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            $score, $why ? implode('; ', $why) : null,
        ]);

        Security::logAction('comment', $ip);

        // A spam verdict is never announced — a bot that learns it was caught
        // just tries again differently. It sees the same thank-you a human sees.
        return [
            'ok'      => true,
            'status'  => $status,
            'message' => $status === 'approved'
                ? 'Thanks — your comment is posted.'
                : 'Thanks — your comment has been sent to the moderator and will appear once approved.',
        ];
    }

    /* ------------------------------------------------------------ moderation */

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['pending', 'approved', 'spam'], true)) {
            return;
        }
        Db::get()->prepare("UPDATE comments SET status = ? WHERE id = ?")->execute([$status, $id]);
    }

    public static function reply(int $id, string $reply): void
    {
        $reply = trim($reply);
        Db::get()->prepare("UPDATE comments SET reply = ? WHERE id = ?")
            ->execute([$reply === '' ? null : $reply, $id]);
    }

    public static function delete(int $id): void
    {
        Db::get()->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
    }

    public static function emptySpam(): int
    {
        $stmt = Db::get()->prepare("DELETE FROM comments WHERE status = 'spam'");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** "3 hours ago" — friendlier than a timestamp on a comment. */
    public static function ago(string $sqlTime): string
    {
        $seconds = max(0, time() - strtotime($sqlTime . ' UTC'));
        $units   = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];

        foreach ($units as $size => $label) {
            if ($seconds >= $size) {
                $n = (int) floor($seconds / $size);
                return $n . ' ' . $label . ($n > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }
}
