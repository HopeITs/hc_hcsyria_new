<?php

namespace App\Support\Admin;

/*
 * Two habits of the legacy database every admin form has to keep, in one place:
 * a field left empty is stored as the text "0" (the public pages test for it, so
 * an empty string would show a blank where they now hide the line), and a text a
 * rich-text box re-wrote (other line endings, a trailing space) is not an edit.
 */
final class LegacyValue
{
    /** What goes into the column: the value, or "0" when there is none. */
    public static function store(mixed $value): string
    {
        return $value === null || $value === '' ? '0' : (string) $value;
    }

    /** What a form shows for a column: nothing, where the database holds "0". */
    public static function read(mixed $value): string
    {
        return in_array($value, [null, '0', 0], true) ? '' : (string) $value;
    }

    /** Line endings and surrounding space do not count. */
    public static function textChanged(mixed $old, string $new): bool
    {
        $normalise = fn (mixed $text): string => trim(str_replace(["\r\n", "\r"], "\n", (string) $text));

        return $normalise($old) !== $normalise($new);
    }
}
