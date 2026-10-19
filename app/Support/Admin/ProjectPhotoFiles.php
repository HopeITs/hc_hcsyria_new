<?php

namespace App\Support\Admin;

use GdImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/*
 * The picture files of a micro-project, in the layout the legacy site and the
 * public pages already use: every photo is two files under MEDIA_ROOT,
 *
 *   /images/projects[_city]/<project number>/<name>.jpg    630 x 420, the one in `images.MPI_Path`
 *   /images/projects[_city]/<project number>/<name>b.jpg   up to 1620 x 1080, the same name plus "b"
 *
 * Legacy had staff cut and upload both sizes by hand; here one upload is cut to
 * both. Re-encoding as a fresh JPEG also strips whatever else the file held
 * (camera data, the GPS position of a photo taken in Syria). Needs the GD
 * extension; without it nothing is written and the caller is told why.
 */
final class ProjectPhotoFiles
{
    public const SMALL = [630, 420];

    public const BIG = [1620, 1080];

    /** City => folder, as legacy's store_image() chose them. */
    private const FOLDERS = [
        'Aleppo' => 'projects',
        'Homs' => 'projects_homs',
        'Damascus' => 'projects_damascus',
        'Latakia' => 'projects_latakia',
        'Al Wadi' => 'projects_wadi',
        'Tartus' => 'projects_tartus',
        'Idleb' => 'projects_idleb',
    ];

    public static function available(): bool
    {
        return extension_loaded('gd') && filled(config('hcsyria.media_root'));
    }

    public static function folder(string $city): string
    {
        return self::FOLDERS[$city] ?? 'projects';
    }

    /**
     * Cut an uploaded file to both sizes and store it. Returns the path for `images.MPI_Path`.
     *
     * @throws RuntimeException when the file is not a picture GD can read, or cannot be written
     */
    public static function store(string $sourcePath, string $city, int $number): string
    {
        if (! self::available()) {
            throw new RuntimeException('Pictures cannot be processed here: the GD extension or MEDIA_ROOT is missing.');
        }

        $image = self::open($sourcePath);
        $directory = '/images/'.self::folder($city).'/'.$number;
        $name = now()->format('ymdHis').Str::lower(Str::random(8));

        try {
            File::ensureDirectoryExists(self::root().$directory);
            self::write(self::fit($image, ...self::BIG), self::root()."$directory/{$name}b.jpg", 82);
            self::write(self::cover($image, ...self::SMALL), self::root()."$directory/$name.jpg", 80);
        } finally {
            imagedestroy($image);
        }

        return "$directory/$name.jpg";
    }

    /** Remove both files of a photo. Anything that is not inside MEDIA_ROOT/images is left alone. Returns how many files went. */
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

    private static function root(): string
    {
        return rtrim((string) config('hcsyria.media_root'), '/\\');
    }

    private static function open(string $path): GdImage
    {
        $bytes = @file_get_contents($path);
        $image = $bytes === false ? false : @imagecreatefromstring($bytes);

        if ($image === false) {
            throw new RuntimeException('That file is not a picture.');
        }

        $image = self::upright($image, $path);

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

    /** Scaled down to fit the box, never up, keeping the shape. */
    private static function fit(GdImage $image, int $width, int $height): GdImage
    {
        $scale = min(1, $width / imagesx($image), $height / imagesy($image));
        $target = imagecreatetruecolor(max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));
        imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), imagesx($image), imagesy($image));

        return $target;
    }

    /**
     * Exactly the box: scaled to cover it, the overflow cut evenly from both sides.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    private static function cover(GdImage $image, int $width, int $height): GdImage
    {
        $scale = max($width / imagesx($image), $height / imagesy($image));
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);
        $target = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $target, $image, 0, 0,
            (int) floor((imagesx($image) - $cropWidth) / 2), (int) floor((imagesy($image) - $cropHeight) / 2),
            $width, $height, $cropWidth, $cropHeight,
        );

        return $target;
    }

    private static function write(GdImage $image, string $file, int $quality): void
    {
        imageinterlace($image, true);
        $temporary = $file.'.tmp';
        $written = imagejpeg($image, $temporary, $quality);
        imagedestroy($image);

        if (! $written || ! @rename($temporary, $file)) {
            @unlink($temporary);

            throw new RuntimeException('The picture could not be saved.');
        }
    }
}
