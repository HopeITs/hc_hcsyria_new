<?php

namespace App\Support\Site;

/*
 * The words and pictures a visitor sees on a public page, found in its HTML as it is sent.
 *
 * Every page's copy lives in a different place (a view, a language file, a list in
 * a component), so staff are not made to hunt for it: the page is read the way a
 * browser reads it, and each stretch of text between two tags is one "line" they can
 * rewrite, and each picture one they can swap. Reading, rewriting and marking up for
 * the visual editor use the same walk, so what the dashboard offers is exactly what a
 * change replaces. Only visible words count: not scripts, styles, the head, form fields
 * or anything inside a tag; a line must hold at least two letters.
 *
 * A line or picture inside the menu, the header or the footer (shown on every page) is
 * "site-wide" and filed under "*"; any other belongs to the page.
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
     * The page with some lines rewritten and some pictures swapped. $changes maps a key
     * (textKey(), imageKey()) to the new words, or the new picture's address.
     *
     * @param  array<string, string>  $changes
     */
    public static function rewrite(string $html, array $changes, string $page): string
    {
        return self::walk(
            $html,
            fn (string $text, bool $wide): ?string => $changes[self::textKey($wide ? '*' : $page, $text)] ?? null,
            function (string $src, bool $wide, string $tag) use ($changes, $page): ?string {
                $new = $changes[self::imageKey(self::scope($src, $wide, $page), $src)] ?? null;

                return $new === null ? null : self::withSrc($new, $tag);
            },
        );
    }

    /**
     * The page marked up for the visual editor: each line wrapped in a span that says where it
     * belongs and what it was, each swappable picture tagged. $slots maps a picture's current
     * address to the slot (config/site_content.php) that draws it; any other static picture
     * is swapped for this page alone (imageKey()). Changes are applied, so the editor shows
     * what visitors see.
     *
     * @param  array<string, string>  $changes
     * @param  array<string, string>  $slots  address => slot key
     * @param  bool  $words  mark the text up (an account that may change the words)
     * @param  bool  $pictures  mark the pictures up (an account that may change pictures and logos)
     */
    public static function decorate(string $html, array $changes, string $page, array $slots, bool $words = true, bool $pictures = true): string
    {
        $e = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return self::walk(
            $html,
            function (string $text, bool $wide) use ($changes, $page, $e, $words): ?string {
                if (! $words) {
                    return null;
                }

                $key = self::textKey($wide ? '*' : $page, $text);
                $now = $changes[$key] ?? $text;

                return '<span data-sc-t="'.$e($key).'" data-sc-o="'.$e($text).'" data-sc-w="'.($wide ? '*' : $e($page)).'"'.(isset($changes[$key]) ? ' data-sc-c="1"' : '').'>'.$e($now).'</span>';
            },
            function (string $src, bool $wide, string $tag) use ($changes, $page, $slots, $e, $pictures): ?string {
                if (! $pictures) {
                    return null;
                }

                if (isset($slots[$src])) {
                    return self::tagged($tag, 'data-sc-slot="'.$e($slots[$src]).'"');
                }

                if (! self::swappable($src)) {
                    return null;
                }

                $key = self::imageKey(self::scope($src, $wide, $page), $src);
                $now = $changes[$key] ?? null;
                $tag = $now === null ? $tag : self::withSrc($now, $tag);

                return self::tagged($tag, 'data-sc-i="'.$e($key).'" data-sc-s="'.$e($src).'"'.($now === null ? '' : ' data-sc-c="1"'));
            },
            raw: true,
        );
    }

    /** What a rewritten line is filed under: where it applies ("*" or a path) and a fingerprint of the original words. */
    public static function textKey(string $where, string $text): string
    {
        return "text:$where:".substr(sha1($text), 0, 16);
    }

    /** What a swapped picture is filed under: where it applies and a fingerprint of the original address. */
    public static function imageKey(string $where, string $src): string
    {
        return "img:$where:".substr(sha1($src), 0, 16);
    }

    /** Kept for the older name. */
    public static function key(string $where, string $text): string
    {
        return self::textKey($where, $text);
    }

    /** True for a logo: a partner's, a funder's, the organisation's own. Swapped for the whole site at once. */
    public static function isLogo(string $src): bool
    {
        foreach ((array) config('site_content.logo_images', []) as $pattern) {
            if (preg_match($pattern, $src) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * True for a picture that is part of the page's own design and so may be swapped: a static file of
     * the site, not one of the pictures staff already manage elsewhere (projects, news, successes,
     * initiatives' galleries), not an icon or a mask. A logo may be swapped even when the original is a
     * vector; any other vector cannot be stood in for by a photo.
     */
    public static function swappable(string $src): bool
    {
        if (! str_starts_with($src, '/images/')) {
            return false;
        }

        if (self::isLogo($src)) {
            return preg_match('~\.(jpe?g|png|webp|svg)$~i', $src) === 1;
        }

        if (preg_match('~\.(jpe?g|png|webp)$~i', $src) !== 1) {
            return false;
        }

        foreach ((array) config('site_content.not_swappable', []) as $pattern) {
            if (preg_match($pattern, $src) === 1) {
                return false;
            }
        }

        return true;
    }

    /** Where a swapped picture applies: the whole site for a logo or anything in the menu, header or footer, otherwise its page. */
    private static function scope(string $src, bool $wide, string $page): string
    {
        return $wide || self::isLogo($src) ? '*' : $page;
    }

    /**
     * Walk the HTML; the line callback gets each line and says what to put there (null keeps it), the
     * picture callback gets each <img>'s address (and its whole tag) and says what tag to put there.
     *
     * @param  callable(string, bool): ?string  $line
     * @param  (callable(string, bool, string): ?string)|null  $picture
     * @param  bool  $raw  what the line callback returns is HTML, not words
     */
    private static function walk(string $html, callable $line, ?callable $picture = null, bool $raw = false): string
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

                    if ($new !== null && ($raw || $new !== $text)) {
                        preg_match('/^\s*/u', $token, $lead);
                        preg_match('/\s*$/u', $token, $trail);
                        $token = $lead[0].($raw ? $new : htmlspecialchars($new, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false)).$trail[0];
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

                if ($picture !== null && in_array($name, ['img', 'source'], true) && preg_match('~\ssrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')~i', $token, $src) === 1) {
                    $token = $picture(html_entity_decode($src[1] !== '' ? $src[1] : ($src[2] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $wide > 0, $token) ?? $token;
                }

                if (! in_array($name, self::VOID, true) && ! str_ends_with(rtrim($token, '> '), '/') && ! in_array($name, self::SKIP, true)) {
                    $open[] = $name;
                    $wide += in_array($name, self::CHROME, true) ? 1 : 0;
                }
            }

            $out .= $token;
        }

        return $out;
    }

    /** The tag with its src address changed. */
    private static function withSrc(string $new, string $tag): string
    {
        return (string) preg_replace_callback('~(\ssrc\s*=\s*)(?:"[^"]*"|\'[^\']*\')~i', fn (array $m): string => $m[1].'"'.htmlspecialchars($new, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"', $tag, 1);
    }

    /** The tag with an attribute added after its name. */
    private static function tagged(string $tag, string $attribute): string
    {
        return (string) preg_replace('~^<([a-zA-Z0-9]+)~', '<$1 '.$attribute, $tag, 1);
    }
}
