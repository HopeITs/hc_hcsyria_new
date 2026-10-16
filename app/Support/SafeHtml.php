<?php

namespace App\Support;

/*
 * Text an editor wrote in the admin's rich-text box, made safe to print as
 * HTML: only the tags the box can make survive (paragraphs, line breaks,
 * bold, italics, underline and lists), and without attributes, so no
 * script, link or style can come through. Legacy printed it as it was.
 */
final class SafeHtml
{
    private const TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'];

    public static function text(?string $html): string
    {
        $allowed = '<'.implode('><', self::TAGS).'>';
        $clean = strip_tags((string) $html, $allowed);

        return preg_replace('/<('.implode('|', self::TAGS).')\b[^>]*>/i', '<$1>', $clean) ?? '';
    }
}
