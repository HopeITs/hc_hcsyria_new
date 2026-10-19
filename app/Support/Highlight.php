<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/*
 * Puts the words a visitor searched for in a mark (<span class="highlight">),
 * as legacy's search results did. Legacy built it with preg_replace() from the
 * raw search text and printed the result unescaped, so a search could break
 * the pattern or carry markup into the page. Here the text is escaped first,
 * the term is quoted, tags are never cut into and entities (&amp;) are searched as the
 * characters they stand for, and what
 * comes back is safe to print as it is.
 */
final class Highlight
{
    /** Plain text, escaped, with every occurrence of $term marked. */
    public static function text(?string $text, ?string $term): HtmlString
    {
        return new HtmlString(self::mark(e((string) $text), $term));
    }

    /** HTML that is already safe to print (SafeHtml, a plain-text excerpt), with the occurrences of $term in its text marked. */
    public static function html(?string $html, ?string $term): HtmlString
    {
        return new HtmlString(self::mark((string) $html, $term));
    }

    private static function mark(string $html, ?string $term): string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $html;
        }

        $pattern = '/('.preg_quote($term, '/').')/iu';

        // Tags come out of the split whole, at the odd places; only the text between them is searched, as the words it says (entities decoded) and written out again escaped.
        $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return $html;
        }

        return implode('', array_map(function (string $part) use ($pattern) {
            if ($part[0] === '<') {
                return $part;
            }

            $pieces = preg_split($pattern, html_entity_decode($part, ENT_QUOTES | ENT_HTML5), -1, PREG_SPLIT_DELIM_CAPTURE);

            if ($pieces === false) {
                return $part;
            }

            // With the match captured, the pieces alternate: text, match, text, ...
            return implode('', array_map(
                fn (string $piece, int $i) => $i % 2 === 1 ? '<span class="highlight">'.e($piece).'</span>' : e($piece),
                $pieces,
                array_keys($pieces),
            ));
        }, $parts));
    }
}
