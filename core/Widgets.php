<?php
/**
 * Widgets.php — the plug-in point for everything you bolt on later.
 *
 * Every file in /widgets is a widget. A widget file registers itself:
 *
 *     <?php
 *     Widgets::register('hours', function (array $options): string {
 *         return '<p>Open ' . htmlspecialchars($options['days'] ?? 'Mon-Fri') . '</p>';
 *     });
 *
 * Save that as widgets/hours.php and you can now write this in any page,
 * memorandum or template:
 *
 *     [[hours days="Tue-Sat"]]
 *
 * That is the whole extension model. No core file is edited to add one,
 * no registry list to update, no cache to clear.
 */
final class Widgets
{
    /** @var array<string, callable(array):string> */
    private static array $registry = [];

    public static function register(string $name, callable $renderer): void
    {
        self::$registry[strtolower($name)] = $renderer;
    }

    public static function has(string $name): bool
    {
        return isset(self::$registry[strtolower($name)]);
    }

    public static function names(): array
    {
        $names = array_keys(self::$registry);
        sort($names);
        return $names;
    }

    /** Load every widget file once, at boot. */
    public static function loadAll(string $dir): void
    {
        foreach (glob(rtrim($dir, '/') . '/*.php') ?: [] as $file) {
            require_once $file;
        }
    }

    public static function render(string $name, array $options = []): string
    {
        $name = strtolower($name);
        if (!isset(self::$registry[$name])) {
            return '<div class="widget-missing">Unknown widget: <code>' . htmlspecialchars($name) . '</code></div>';
        }

        try {
            return (string) self::$registry[$name]($options);
        } catch (Throwable $e) {
            // A broken widget must never take the page down with it.
            error_log('Widget "' . $name . '" failed: ' . $e->getMessage());
            return '<div class="widget-missing">This section is temporarily unavailable.</div>';
        }
    }

    /**
     * Replace every [[widget ...]] tag in a block of text.
     * Attributes are key="value" pairs: [[gallery album="opening-day" columns="4"]]
     */
    public static function expand(string $text, array $context = []): string
    {
        return preg_replace_callback(
            '/\[\[\s*([a-z0-9_\-]+)((?:\s+[a-z0-9_\-]+="[^"]*")*)\s*\]\]/i',
            function (array $m) use ($context) {
                $options = $context;
                if (preg_match_all('/([a-z0-9_\-]+)="([^"]*)"/i', $m[2], $attrs, PREG_SET_ORDER)) {
                    foreach ($attrs as $attr) {
                        $options[$attr[1]] = $attr[2];
                    }
                }
                return self::render($m[1], $options);
            },
            $text
        ) ?? $text;
    }
}
