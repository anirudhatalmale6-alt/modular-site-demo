<?php
/**
 * Content.php — reads pages and memoranda from flat files.
 *
 * A page is one file: content/pages/<slug>.md
 * A memorandum is one file: content/memoranda/<slug>.md
 *
 * Each file starts with a front-matter block between --- fences:
 *
 *     ---
 *     title: About Us
 *     nav: yes
 *     order: 20
 *     summary: Who we are and what we do.
 *     ---
 *     # Heading
 *     Body text in Markdown...
 *
 * Drop a file in, it exists. Delete the file, it is gone. There is no
 * database of pages to keep in sync and no build step to run.
 */
final class Content
{
    public function __construct(private string $root) {}

    /* ---------------------------------------------------------------- pages */

    /** All pages, ordered by their `order:` field then title. */
    public function pages(): array
    {
        return $this->loadDir($this->root . '/pages');
    }

    /** Pages that asked to appear in the main navigation. */
    public function navPages(): array
    {
        return array_values(array_filter(
            $this->pages(),
            fn($p) => $this->truthy($p['meta']['nav'] ?? 'no') && ($p['slug'] !== 'home')
        ));
    }

    public function page(string $slug): ?array
    {
        return $this->loadFile($this->root . '/pages/' . $this->safe($slug) . '.md');
    }

    /* ----------------------------------------------------------- memoranda */

    /** Memoranda, newest first by their `date:` field. */
    public function memoranda(): array
    {
        $items = $this->loadDir($this->root . '/memoranda');
        usort($items, fn($a, $b) => strcmp($b['meta']['date'] ?? '', $a['meta']['date'] ?? ''));
        return $items;
    }

    public function memorandum(string $slug): ?array
    {
        return $this->loadFile($this->root . '/memoranda/' . $this->safe($slug) . '.md');
    }

    /* ------------------------------------------------------------- gallery */

    /**
     * Albums live in content/gallery/<album>/ with an album.json holding the
     * title and per-file captions. Any image dropped in the folder shows up
     * even if it is not listed in album.json — it just gets no caption.
     */
    public function albums(): array
    {
        $dir = $this->root . '/gallery';
        if (!is_dir($dir)) {
            return [];
        }

        $albums = [];
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $path) {
            $slug = basename($path);
            $meta = [];
            if (is_file($path . '/album.json')) {
                $meta = json_decode((string) file_get_contents($path . '/album.json'), true) ?: [];
            }

            $photos = [];
            foreach (glob($path . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) as $file) {
                $name     = basename($file);
                $photos[] = [
                    'file'    => $name,
                    'url'     => 'content/gallery/' . $slug . '/' . rawurlencode($name),
                    'caption' => $meta['captions'][$name] ?? '',
                ];
            }

            $albums[] = [
                'slug'   => $slug,
                'title'  => $meta['title'] ?? ucwords(str_replace('-', ' ', $slug)),
                'date'   => $meta['date'] ?? '',
                'note'   => $meta['note'] ?? '',
                'order'  => (int) ($meta['order'] ?? 50),
                'photos' => $photos,
            ];
        }

        usort($albums, fn($a, $b) => [$a['order'], $a['title']] <=> [$b['order'], $b['title']]);
        return $albums;
    }

    public function album(string $slug): ?array
    {
        foreach ($this->albums() as $album) {
            if ($album['slug'] === $slug) {
                return $album;
            }
        }
        return null;
    }

    /* --------------------------------------------------------------- write */

    /** Save a page or memorandum back to disk (used by the admin editor). */
    public function save(string $kind, string $slug, array $meta, string $body): string
    {
        $dir = $this->root . '/' . ($kind === 'memoranda' ? 'memoranda' : 'pages');
        @mkdir($dir, 0775, true);

        $front = "---\n";
        foreach ($meta as $key => $value) {
            $front .= $key . ': ' . str_replace("\n", ' ', (string) $value) . "\n";
        }
        $front .= "---\n\n";

        $path = $dir . '/' . $this->safe($slug) . '.md';
        file_put_contents($path, $front . trim($body) . "\n");
        return $path;
    }

    public function delete(string $kind, string $slug): bool
    {
        $dir  = $this->root . '/' . ($kind === 'memoranda' ? 'memoranda' : 'pages');
        $path = $dir . '/' . $this->safe($slug) . '.md';
        return is_file($path) && unlink($path);
    }

    /* ------------------------------------------------------------ internals */

    private function loadDir(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $items = [];
        foreach (glob($dir . '/*.md') as $file) {
            $item = $this->loadFile($file);
            if ($item && !$this->truthy($item['meta']['draft'] ?? 'no')) {
                $items[] = $item;
            }
        }

        usort($items, fn($a, $b) =>
            [(int) ($a['meta']['order'] ?? 50), $a['meta']['title'] ?? '']
            <=> [(int) ($b['meta']['order'] ?? 50), $b['meta']['title'] ?? '']);

        return $items;
    }

    private function loadFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $raw  = (string) file_get_contents($path);
        $meta = [];
        $body = $raw;

        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $raw, $m)) {
            foreach (explode("\n", $m[1]) as $line) {
                if (str_contains($line, ':')) {
                    [$k, $v]           = explode(':', $line, 2);
                    $meta[trim($k)]    = trim($v);
                }
            }
            $body = $m[2];
        }

        $slug = basename($path, '.md');

        return [
            'slug' => $slug,
            'meta' => $meta + ['title' => ucwords(str_replace('-', ' ', $slug))],
            'body' => trim($body),
            'path' => $path,
        ];
    }

    private function safe(string $slug): string
    {
        return preg_replace('/[^a-z0-9\-_]/', '', strtolower($slug)) ?: 'untitled';
    }

    private function truthy(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'yes', 'true', 'on'], true);
    }
}
