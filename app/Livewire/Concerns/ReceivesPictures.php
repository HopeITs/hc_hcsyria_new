<?php

namespace App\Livewire\Concerns;

use App\Rules\SecureUpload;
use App\Support\Admin\PhotoFiles;
use Illuminate\Validation\ValidationException;

/*
 * The one way a dashboard component takes in a picture someone chose: the server
 * must be able to process it (GD), and each file must really be a JPEG, PNG or
 * WebP of a sensible size (SecureUpload). A refusal is an error alert and the
 * choice is dropped, so a component only deals with pictures it can trust. Used
 * with Livewire's WithFileUploads by every component that stores pictures.
 */
trait ReceivesPictures
{
    use SendsAlerts;

    private const MAX_KILOBYTES = 8192;

    /**
     * Check what is in the property: one file, or a list of up to $maxFiles.
     * Returns false, after alerting and clearing the property, when it must not be used.
     */
    protected function acceptPictures(string $property, bool $many = false, int $maxFiles = 20): bool
    {
        if (! PhotoFiles::available()) {
            $this->reset($property);
            $this->alert('photos.unavailable', type: 'error');

            return false;
        }

        $rules = $many
            ? [$property => ['required', 'array', 'max:'.$maxFiles], "$property.*" => ['file', 'max:'.self::MAX_KILOBYTES, new SecureUpload]]
            : [$property => ['required', 'file', 'max:'.self::MAX_KILOBYTES, new SecureUpload]];

        try {
            $this->validate($rules, attributes: [$property => 'picture', "$property.*" => 'picture']);
        } catch (ValidationException $invalid) {
            $this->reset($property);
            $this->resetErrorBag();
            $this->alert('photos.rejected', ['reason' => collect($invalid->errors())->flatten()->first()], 'error');

            return false;
        }

        return true;
    }
}
