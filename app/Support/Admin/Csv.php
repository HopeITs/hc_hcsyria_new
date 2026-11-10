<?php

namespace App\Support\Admin;

use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * One CSV download for every export in the admin: UTF-8 with a BOM so Excel shows Arabic
 * names, and any text that starts like a formula quoted so it is shown, not run.
 */
final class Csv
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<list<string|int|float|null>>  $rows
     */
    public static function download(string $name, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings, ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ',', '"', '\\');
            }
            fclose($out);
        }, $name.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function cell(string|int|float|null $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
