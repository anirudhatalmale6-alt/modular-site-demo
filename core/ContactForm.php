<?php
/**
 * ContactForm.php — validation and storage for the Contact Us form.
 *
 * Messages always land in the admin inbox (admin → Messages). Optionally they
 * are also emailed on: set contact_form.send_email = true and an address in
 * config.php once the site is on a host with working mail.
 */
final class ContactForm
{
    public static function submit(array $input, array $config): array
    {
        if (!Security::checkToken($input['csrf'] ?? null)) {
            return ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.'];
        }

        $name    = trim((string) ($input['name'] ?? ''));
        $email   = trim((string) ($input['email'] ?? ''));
        $subject = trim((string) ($input['subject'] ?? ''));
        $body    = trim((string) ($input['message'] ?? ''));

        if ($name === '' || $email === '' || $body === '') {
            return ['ok' => false, 'message' => 'Please fill in your name, email and message.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'That email address does not look right — we need it to reply to you.'];
        }
        if (mb_strlen($body) > 5000) {
            return ['ok' => false, 'message' => 'That message is over the 5,000 character limit.'];
        }

        $ip = Security::ip();
        if (Security::rateLimited('contact', $ip)) {
            return ['ok' => false, 'message' => 'Several messages have just been sent from this connection. Please wait a few minutes.'];
        }

        // Same layered filter the comments use.
        [$score] = Security::score(['author' => $name, 'body' => $body] + $input);
        if ($score >= Security::SPAM_THRESHOLD) {
            // Accepted silently and dropped: a bot gets no signal to retune with.
            Security::logAction('contact', $ip);
            return ['ok' => true, 'message' => 'Thank you — your message has been sent. We normally reply within two working days.'];
        }

        Db::get()->prepare(
            "INSERT INTO messages (name, email, subject, body, ip, created_at)
             VALUES (?, ?, ?, ?, ?, datetime('now'))"
        )->execute([$name, $email, $subject ?: '(no subject)', $body, $ip]);

        Security::logAction('contact', $ip);

        if (!empty($config['contact_form']['send_email']) && !empty($config['contact_form']['email'])) {
            self::email($config['contact_form']['email'], $config['site_name'], $name, $email, $subject, $body);
        }

        return ['ok' => true, 'message' => 'Thank you — your message has been sent. We normally reply within two working days.'];
    }

    private static function email(string $to, string $siteName, string $name, string $from, string $subject, string $body): void
    {
        $headers = [
            'From'         => $siteName . ' <no-reply@' . (string) ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
            'Reply-To'     => $from,
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];
        $lines = '';
        foreach ($headers as $key => $value) {
            $lines .= $key . ': ' . str_replace(["\r", "\n"], '', $value) . "\r\n";
        }

        @mail($to, 'Website enquiry: ' . ($subject ?: 'no subject'), "From: {$name} <{$from}>\n\n{$body}", $lines);
    }

    /* ------------------------------------------------------------- admin side */

    public static function inbox(string $status = 'new', int $limit = 200): array
    {
        $stmt = Db::get()->prepare("SELECT * FROM messages WHERE status = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll();
    }

    public static function counts(): array
    {
        $rows = Db::get()->query("SELECT status, COUNT(*) c FROM messages GROUP BY status")->fetchAll();
        $out  = ['new' => 0, 'read' => 0, 'archived' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['c'];
        }
        return $out;
    }

    public static function setStatus(int $id, string $status): void
    {
        if (in_array($status, ['new', 'read', 'archived'], true)) {
            Db::get()->prepare("UPDATE messages SET status = ? WHERE id = ?")->execute([$status, $id]);
        }
    }

    public static function delete(int $id): void
    {
        Db::get()->prepare("DELETE FROM messages WHERE id = ?")->execute([$id]);
    }
}
