<?php

namespace App\Support\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/*
 * A document attached to content (the PDF of a newsletter item), kept under
 * MEDIA_ROOT/files and served at /files/... by ServeMedia. Legacy kept the file
 * under the name it was uploaded with, in the web root, so a second file with the
 * same name replaced the first and any name was accepted; here it is stored under a
 * random prefix and the cleaned original name (UploadName's rules), so nothing is
 * overwritten and nothing in the name can lead out of the folder. Removing a stored
 * document is PhotoFiles::delete().
 */
final class DocumentFiles
{
    private const NAME_LENGTH = 80;

    public static function available(): bool
    {
        return filled(config('hcsyria.media_root'));
    }

    /**
     * Store an uploaded PDF in $directory (like "/files/news"). Returns its path. $stemLength cuts the
     * original name shorter where the column that keeps the path is short.
     *
     * @throws RuntimeException
     */
    public static function store(UploadedFile $file, string $directory, int $stemLength = self::NAME_LENGTH): string
    {
        $root = rtrim((string) config('hcsyria.media_root'), '/\\');

        if ($root === '' || $file->getRealPath() === false) {
            throw new RuntimeException('The document cannot be stored here: MEDIA_ROOT is missing.');
        }

        $stem = Str::limit(trim((string) preg_replace('/[^\p{L}\p{N} ._-]+/u', '', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))), $stemLength, '');
        $name = now()->format('ymdHis').'-'.Str::lower(Str::random(6)).'-'.($stem === '' ? 'file' : $stem).'.pdf';

        File::ensureDirectoryExists($root.$directory);

        if (! @copy($file->getRealPath(), $root.$directory.'/'.$name)) {
            throw new RuntimeException('The document could not be saved.');
        }

        return $directory.'/'.$name;
    }
}
