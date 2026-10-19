<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/*
 * Checks that an uploaded document really is a PDF, not just named like one: it
 * must start with the PDF marker and the file's own detected type must agree. From
 * the Tracking app's SecureUpload (its PDF check), for the one kind of document the
 * newsletter attaches; the extension and `mimes` rules trust the file name, this
 * opens the file. Pictures are SecureUpload's.
 */
class SecureDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || $value->getRealPath() === false) {
            return;
        }

        $path = $value->getRealPath();
        $detected = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $detected === false ? false : finfo_file($detected, $path);
        $bytes = (string) file_get_contents($path, false, null, 0, 1024);

        if (! str_contains($bytes, '%PDF-') || $mime !== 'application/pdf') {
            $fail('The :attribute is not a PDF document.');
        }
    }
}
