<?php

namespace App\Actions;

use App\Models\Subscriber;
use Illuminate\Support\Str;

/*
 * Adds an address to the newsletter list (legacy PublicController::subscribe).
 * Returns false when the address is already on it, which the page tells the
 * visitor; the table's unique key on `mail` settles two visitors racing.
 */
final class SubscribeToNewsletter
{
    public function __invoke(string $email): bool
    {
        if (Subscriber::query()->where('mail', $email)->exists()) {
            return false;
        }

        // The token is what the unsubscribe link in each newsletter carries.
        Subscriber::query()->create(['mail' => $email, 'token' => Str::random(50)]);

        return true;
    }
}
