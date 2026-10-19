<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/*
 * Checks what an uploaded picture really is, not what its name or the browser's
 * MIME type claims: the leading bytes and the file's own header must say JPEG,
 * PNG or WebP, and the picture must be a sane size (a tiny file that unpacks to
 * a billion pixels is how an image bomb works). From the Tracking app's rule of
 * the same name, cut down to the one kind of file this site takes in; the
 * extension and `image` rules trust the file name, this opens the file.
 *
 * The picture is then re-encoded (ProjectPhotoFiles), which drops anything else
 * that was inside it, including the GPS position some phones write.
 */
class SecureUpload implements ValidationRule
{
    /** What the bytes may say, by their detected type. */
    private const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** The longest side and the most pixels accepted. */
    private const MAX_SIDE = 12000;

    private const MAX_PIXELS = 60_000_000;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || $value->getRealPath() === false) {
            return;
        }

        $path = $value->getRealPath();
        $size = @getimagesize($path);
        $detected = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $detected === false ? false : finfo_file($detected, $path);

        if ($size === false || ! in_array($size['mime'], self::TYPES, true) || $mime !== $size['mime']) {
            $fail('The :attribute is not a JPEG, PNG or WebP picture.');

            return;
        }

        if (max($size[0], $size[1]) > self::MAX_SIDE || $size[0] * $size[1] > self::MAX_PIXELS) {
            $fail('The :attribute is too large in pixels. Use a picture under 12,000 px on its longest side.');
        }
    }
}
