<?php
/**
 * Db.php — SQLite connection + schema.
 *
 * Only the interactive parts of the site need a database: comments and
 * contact-form messages. Pages, memoranda and photos stay as files, so a
 * backup is "copy the folder" and the schema below is the whole of it.
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function get(string $path = ''): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $path = $path ?: dirname(__DIR__) . '/data/site.db';
        @mkdir(dirname($path), 0775, true);

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');

        self::migrate($pdo);
        return self::$pdo = $pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                thread      TEXT NOT NULL,              -- which page/memo the comment belongs to
                author      TEXT NOT NULL,
                email       TEXT,                       -- never shown publicly
                body        TEXT NOT NULL,
                status      TEXT NOT NULL DEFAULT 'pending', -- pending | approved | spam
                reply       TEXT,                       -- optional public reply from the site owner
                ip          TEXT,
                user_agent  TEXT,
                spam_score  INTEGER DEFAULT 0,
                spam_reason TEXT,
                created_at  TEXT NOT NULL
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_thread ON comments (thread, status, created_at)");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL,
                email      TEXT NOT NULL,
                subject    TEXT,
                body       TEXT NOT NULL,
                status     TEXT NOT NULL DEFAULT 'new',  -- new | read | archived
                ip         TEXT,
                created_at TEXT NOT NULL
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_log (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                ip         TEXT NOT NULL,
                action     TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rate_log ON rate_log (ip, action, created_at)");
    }
}
