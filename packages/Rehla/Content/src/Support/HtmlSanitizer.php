<?php

declare(strict_types=1);

namespace Rehla\Content\Support;

final class HtmlSanitizer
{
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // 1. Remove script, style, iframe, object, embed tags and their content
        $cleaned = preg_replace('/<(script|style|iframe|object|embed|applet|meta|link)[^>]*?>.*?<\/\\1>/is', '', $html) ?? '';
        $cleaned = preg_replace('/<(script|style|iframe|object|embed|applet|meta|link)[^>]*?>/is', '', $cleaned) ?? '';

        // 2. Remove inline event handlers (e.g. onclick="...", onload="...")
        $cleaned = preg_replace('/\s*on[a-zA-Z]+\s*=\s*(["\']).*?\\1/is', '', $cleaned) ?? '';
        $cleaned = preg_replace('/\s*on[a-zA-Z]+\s*=\s*[^"\'\s>]+/is', '', $cleaned) ?? '';

        // 3. Remove javascript: and vbscript: URIs
        $cleaned = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript|vbscript):.*?\\2/is', '$1="#"', $cleaned) ?? '';
        $cleaned = preg_replace('/(href|src)\s*=\s*(javascript|vbscript):[^\s>]+/is', '$1="#"', $cleaned) ?? '';

        return trim($cleaned);
    }
}
