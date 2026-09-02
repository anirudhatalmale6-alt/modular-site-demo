<?php
/**
 * Markdown.php — a small, dependency-free Markdown subset.
 *
 * Deliberately tiny: headings, paragraphs, bold/italic, links, images,
 * lists, blockquotes, horizontal rules, inline code and fenced code.
 * That is everything the page content needs. If you ever want the full
 * CommonMark spec, drop in league/commonmark and swap the body of
 * Markdown::render() — nothing else in the codebase touches this class.
 */
final class Markdown
{
    public static function render(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);

        // Pull fenced code blocks out first so their contents are never parsed.
        $blocks = [];
        $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) use (&$blocks) {
            $key = "\x02CODE" . count($blocks) . "\x03";
            $blocks[$key] = '<pre class="code"><code>' . htmlspecialchars($m[2], ENT_QUOTES) . '</code></pre>';
            return $key;
        }, $text);

        $out   = [];
        $list  = null; // 'ul' | 'ol' | null
        $quote = false;

        foreach (explode("\n\n", $text) as $chunk) {
            $chunk = trim($chunk, "\n");
            if ($chunk === '') {
                continue;
            }

            // Widget placeholders and extracted code blocks pass straight through.
            if (preg_match('/^\x02CODE\d+\x03$/', $chunk) || preg_match('/^\x02W\d+\x03$/', $chunk)) {
                $out[] = $chunk;
                continue;
            }

            // Horizontal rule
            if (preg_match('/^(---|\*\*\*)$/', $chunk)) {
                $out[] = '<hr>';
                continue;
            }

            // Heading
            if (preg_match('/^(#{1,4})\s+(.*)$/s', $chunk, $m) && !str_contains($m[2], "\n")) {
                $level = strlen($m[1]);
                $out[] = "<h{$level}>" . self::inline($m[2]) . "</h{$level}>";
                continue;
            }

            // Blockquote
            if (str_starts_with($chunk, '> ')) {
                $body  = preg_replace('/^> ?/m', '', $chunk);
                $out[] = '<blockquote>' . self::inline($body) . '</blockquote>';
                continue;
            }

            // Unordered / ordered list
            if (preg_match('/^\s*([-*]|\d+\.)\s+/', $chunk)) {
                $tag   = preg_match('/^\s*\d+\./', $chunk) ? 'ol' : 'ul';
                $items = '';
                foreach (explode("\n", $chunk) as $line) {
                    $line = preg_replace('/^\s*([-*]|\d+\.)\s+/', '', $line);
                    $items .= '<li>' . self::inline($line) . '</li>';
                }
                $out[] = "<{$tag}>{$items}</{$tag}>";
                continue;
            }

            $out[] = '<p>' . self::inline($chunk) . '</p>';
        }

        $html = implode("\n", $out);

        foreach ($blocks as $key => $replacement) {
            $html = str_replace($key, $replacement, $html);
        }

        return $html;
    }

    /** Inline formatting for a single run of text. */
    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Images before links — the syntax only differs by a leading "!".
        $text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1" loading="lazy">', $text);
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);

        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/', '<em>$1</em>', $text);

        // A single newline inside a paragraph is a soft break.
        return nl2br($text, false);
    }
}
