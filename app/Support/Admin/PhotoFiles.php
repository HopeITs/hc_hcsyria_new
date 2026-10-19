<?php

namespace App\Support\Admin;

use GdImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/*
 * The picture files of the staff dashboard, under MEDIA_ROOT, in the layout the
 * legacy site and the public pages already use. A gallery photo is two files:
 *
 *   <directory>/<name>.jpg    the small one, the path kept in the database
 *   <directory>/<name>b.jpg   the big one, the same name plus "b"
 *
 * Legacy had staff cut and upload both sizes by hand; here one upload is cut to
 * both. A single picture (a logo, a before or after, a testimony's face) is one
 * file. Re-encoding every upload as a fresh JPEG (or PNG, for a logo that needs
 * to stay see-through) also strips whatever else the file held: camera data and
 * the GPS position of a photo taken in Syria. Needs the GD extension; without it
 * nothing is written and the caller is told why.
 */
final class PhotoFiles
{
    public static function available(): bool
    {
        return extension_loaded('gd') && filled(config('hcsyria.media_root'));
    }

    /**
     * Cut an uploaded file to a small and a big size and store both. Returns the small file's path.
     *
     * @param  array{0: int<1, max>, 1: int<1, max>}  $small  cut to exactly this box
     * @param  array{0: int<1, max>, 1: int<1, max>}  $big  scaled down to fit this box
     *
     * @throws RuntimeException when the file is not a picture GD can read, or cannot be written
     */
    public static function storePair(string $sourcePath, string $directory, array $small, array $big): string
    {
        $image = self::open($sourcePath);
        $name = self::name();

        try {
            File::ensureDirectoryExists(self::root().$directory);
            self::write(self::fit($image, ...$big), self::root()."$directory/{$name}b.jpg", 82);
            self::write(self::cover($image, ...$small), self::root()."$directory/$name.jpg", 80);
        } finally {
            imagedestroy($image);
        }

        return "$directory/$name.jpg";
    }

    /**
     * Store one picture: cut to exactly $box ($cover) or scaled down to fit it. With
     * $keepAlpha a picture that has see-through parts stays a PNG; otherwise it is a JPEG.
     * Returns the path. $suffix goes on the name ("before", "after").
     *
     * @param  array{0: int<1, max>, 1: int<1, max>}  $box
     *
     * @throws RuntimeException
     */
    public static function storeOne(string $sourcePath, string $directory, array $box, bool $cover, string $suffix = '', bool $keepAlpha = false): string
    {
        $alpha = $keepAlpha && self::hasAlpha($sourcePath);
        $image = self::open($sourcePath, $alpha);
        $name = self::name().$suffix.($alpha ? '.png' : '.jpg');

        try {
            File::ensureDirectoryExists(self::root().$directory);
            $cut = $cover ? self::cover($image, ...$box, alpha: $alpha) : self::fit($image, ...$box, alpha: $alpha);
            self::write($cut, self::root()."$directory/$name", 82, $alpha);
        } finally {
            imagedestroy($image);
        }

        return "$directory/$name";
    }

    /** Remove a stored picture, and the big file beside it if it has one. Anything not inside MEDIA_ROOT/images is left alone. Returns how many files went. */
    public static function delete(string $path): int
    {
        $removed = 0;

        foreach ([$path, self::bigPath($path)] as $candidate) {
            $file = self::absolute($candidate);

            if ($file !== null && File::delete($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /** "/images/projects/3/a.jpg" => "/images/projects/3/ab.jpg". */
    public static function bigPath(string $path): string
    {
        return substr($path, 0, -4).'b.jpg';
    }

    /** The file on disk for a stored path, or null if it is missing or would lead outside the images folder. */
    public static function absolute(string $path): ?string
    {
        $root = config('hcsyria.media_root');

        if (blank($root) || ! str_starts_with($path, '/images/')) {
            return null;
        }

        $base = realpath($root.'/images');
        $file = $base === false ? false : realpath($root.$path);

        return $file !== false && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }

    private static function name(): string
    {
        if (! self::available()) {
            throw new RuntimeException('Pictures cannot be processed here: the GD extension or MEDIA_ROOT is missing.');
        }

        return now()->format('ymdHis').Str::lower(Str::random(8));
    }

    private static function root(): string
    {
        return rtrim((string) config('hcsyria.media_root'), '/\\');
    }

    private static function hasAlpha(string $path): bool
    {
        $info = @getimagesize($path);

        return is_array($info) && in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }

    private static function open(string $path, bool $alpha = false): GdImage
    {
        $bytes = @file_get_contents($path);
        $image = $bytes === false ? false : @imagecreatefromstring($bytes);

        if ($image === false) {
            throw new RuntimeException('That file is not a picture.');
        }

        $image = self::upright($image, $path);

        if ($alpha) {
            imagealphablending($image, false);
            imagesavealpha($image, true);

            return $image;
        }

        // A PNG or WebP may have see-through parts: put them on white, as a JPEG has no transparency.
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, (int) imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        return $flat;
    }

    /** Turn a phone photo the way it was held, when the camera said so. */
    private static function upright(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $turned = imagerotate($image, $angle, 0);

        return $turned === false ? $image : $turned;
    }

    /**
     * Scaled down to fit the box, never up, keeping the shape.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    private static function fit(GdImage $image, int $width, int $height, bool $alpha = false): GdImage
    {
        $scale = min(1, $width / imagesx($image), $height / imagesy($image));
        $target = self::canvas(max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)), $alpha);
        imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), imagesx($image), imagesy($image));

        return $target;
    }

    /**
     * Exactly the box: scaled to cover it, the overflow cut evenly from both sides.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    private static function cover(GdImage $image, int $width, int $height, bool $alpha = false): GdImage
    {
        $scale = max($width / imagesx($image), $height / imagesy($image));
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);
        $target = self::canvas($width, $height, $alpha);
        imagecopyresampled(
            $target, $image, 0, 0,
            (int) floor((imagesx($image) - $cropWidth) / 2), (int) floor((imagesy($image) - $cropHeight) / 2),
            $width, $height, $cropWidth, $cropHeight,
        );

        return $target;
    }

    /**
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    private static function canvas(int $width, int $height, bool $alpha): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        if ($alpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        }

        return $canvas;
    }

    private static function write(GdImage $image, string $file, int $quality, bool $png = false): void
    {
        $temporary = $file.'.tmp';

        if ($png) {
            $written = imagepng($image, $temporary, 6);
        } else {
            imageinterlace($image, true);
            $written = imagejpeg($image, $temporary, $quality);
        }

        imagedestroy($image);

        if (! $written || ! @rename($temporary, $file)) {
            @unlink($temporary);

            throw new RuntimeException('The picture could not be saved.');
        }
    }
}
