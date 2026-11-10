<?php

namespace App\Support\Site;

/*
 * The words a visitor reads on a public page, found in its HTML as it is sent.
 *
 * Every page's copy lives in a different place (a view, a language file, a list in
 * a component), so staff are not made to hunt for it: the page is read the way a
 * browser reads it, and each stretch of text between two tags is one "line" they can
 * rewrite. Reading and rewriting use the same walk, so what the dashboard lists is
 * exactly what a change replaces. Only visible words count: not scripts, styles,
 * the head, pictures, form fields or anything inside a tag; a line must hold at
 * least two letters.
 *
 * A line inside the menu, the header or the footer (shown on every page) is
 * "site-wide"; any other is the page's own.
 */
final class PageText
{
    /** Tags whose contents are not words a visitor reads. */
    private const SKIP = ['script', 'style', 'svg', 'textarea', 'template', 'noscript', 'head', 'select', 'option'];

    /** Tags with no closing tag. */
    private const VOID = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];

    /** Tags that make everything inside them site-wide. */
    private const CHROME = ['header', 'nav', 'footer'];

    private const TOKENS = '~<!--.*?-->|<![^>]*>|<\?[^>]*>|<(script|style|svg|textarea|template|noscript|head|select)\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*+>.*?</\1\s*>|</?[a-zA-Z][a-zA-Z0-9:_-]*(?:"[^"]*"|\'[^\']*\'|[^\'">])*+>|[^<]+|<~si';

    /**
     * Every line of text on the page, in reading order.
     *
     * @return list<array{text: string, wide: bool}> text as read (spaces collapsed); wide = on every page
     */
    public static function lines(string $html): array
    {
        $lines = [];

        self::walk($html, function (string $text, bool $wide) use (&$lines): ?string {
            $lines[] = ['text' => $text, 'wide' => $wide];

            return null;
        });

        return $lines;
    }

    /**
     * The page with some lines rewritten. $replacements maps a key (see key()) to the new words.
     *
     * @param  array<string, string>  $replacements
     */
    public static function rewrite(string $html, array $replacements, string $page): string
    {
        return self::walk($html, function (string $text, bool $wide) use ($replacements, $page): ?string {
            return $replacements[self::key($wide ? '*' : $page, $text)] ?? null;
        });
    }

    /** What an override is filed under: where it applies ("*" or a path) and a fingerprint of the original words. */
    public static function key(string $where, string $text): string
    {
        return "text:$where:".substr(sha1($text), 0, 16);
    }

    /**
     * Walk the HTML; the callback gets each line and says what to put there (null keeps it).
     *
     * @param  callable(string, bool): ?string  $line
     */
    private static function walk(string $html, callable $line): string
    {
        if (preg_match_all(self::TOKENS, $html, $found) === false) {
            return $html;
        }

        $out = '';
        $open = [];
        $wide = 0;

        foreach ($found[0] as $token) {
            if ($token[0] !== '<' || strlen($token) === 1) {
                // A run of text. Only a real one (two letters, not too long) is a line.
                $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

                if ($token[0] !== '<' && $text !== '' && mb_strlen($text) <= 1500 && preg_match('/\p{L}.*\p{L}|\p{L}{2}/u', $text) === 1) {
                    $new = $line($text, $wide > 0);

                    if ($new !== null && $new !== $text) {
                        preg_match('/^\s*/u', $token, $lead);
                        preg_match('/\s*$/u', $token, $trail);
                        $token = $lead[0].htmlspecialchars($new, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false).$trail[0];
                    }
                }
            } elseif (preg_match('~^</([a-zA-Z0-9:_-]+)~', $token, $close) === 1) {
                $name = strtolower($close[1]);
                $at = array_search($name, array_reverse($open, true), true);

                if ($at !== false) {
                    while (count($open) > $at) {
                        $wide -= in_array(array_pop($open), self::CHROME, true) ? 1 : 0;
                    }
                }
            } elseif (preg_match('~^<([a-zA-Z][a-zA-Z0-9:_-]*)~', $token, $tag) === 1) {
                $name = strtolower($tag[1]);

                if (! in_array($name, self::VOID, true) && ! str_ends_with(rtrim($token, '> '), '/') && ! in_array($name, self::SKIP, true)) {
                    $open[] = $name;
                    $wide += in_array($name, self::CHROME, true) ? 1 : 0;
                }
            }

            $out .= $token;
        }

        return $out;
    }
}
