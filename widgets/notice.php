<?php
/**
 * Widget: notice — the "how small can a widget be" example.
 *
 *     [[notice]]Text[[/notice]] is deliberately NOT the syntax; keep it simple:
 *     [[notice text="Committee meeting moved to the 14th" kind="alert"]]
 *
 * kind: info (default) | alert | quiet
 *
 * Copy this file, rename it, change the two lines in the middle, and you have
 * a new widget available on every page. That is the whole process.
 */
Widgets::register('notice', function (array $o): string {
    $kinds = ['info', 'alert', 'quiet'];
    $kind  = in_array($o['kind'] ?? 'info', $kinds, true) ? $o['kind'] : 'info';
    $text  = (string) ($o['text'] ?? '');

    if ($text === '') {
        return '';
    }

    return '<div class="notice notice--' . e($kind) . '">'
        . '<span class="notice__mark" aria-hidden="true"></span>'
        . '<p>' . e($text) . '</p>'
        . '</div>';
});
