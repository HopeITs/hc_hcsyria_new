<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/*
 * Serves /images/…, /files/… and /video/… from MEDIA_ROOT, outside public/.
 *
 * The legacy site kept 11GB of uploads inside its web root, next to the code
 * and with PHP allowed to run there (docs/upgrade-plan.md §2.2). Here nothing
 * a visitor requests is ever executed, and only the file types the site
 * actually publishes are served at all: a .php, .log, .tmp or anything else
 * is a 404, whatever its name or contents.
 *
 * Image, file and video content is not part of the repository.
 */
final class ServeMedia
{
    /** @var array<string, string> */
    private const TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jfif' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'pdf' => 'application/pdf',
    ];

    public function __invoke(string $area, string $path): BinaryFileResponse
    {
        $root = config('hcsyria.media_root');
        $type = self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;

        abort_if(blank($root) || $type === null, 404);

        // realpath() resolves ../ and symlinks; the result must stay inside the area.
        $base = realpath("{$root}/{$area}");
        $file = $base === false ? false : realpath("{$base}/{$path}");

        abort_unless($file !== false && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file), 404);

        $headers = ['Content-Type' => $type, 'Cache-Control' => 'public, max-age=86400'];

        // An SVG is a document that can carry script; a sandbox makes it an image only.
        if ($type === 'image/svg+xml') {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; sandbox";
        }

        return response()->file($file, $headers);
    }
}
