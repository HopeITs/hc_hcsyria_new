<?php

use App\Actions\SubscribeToNewsletter;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/*
 * The newsletter sign-up box on the news page. Ported from the form in
 * new/public/newsletter.blade.php and PublicController::subscribe() at
 * legacy commit 6d45ef9, which posted to /our-news/subscribe with fetch();
 * the form is a Livewire action here, so there is no separate route to
 * protect. As in legacy: ten tries a minute (its throttle:10,1; over that
 * it said the server failed), a note under the box that clears itself after
 * three seconds, and the button reading "Subscribed!" for as long.
 */
new class extends Component
{
    private const TRIES_PER_MINUTE = 10;

    public string $email = '';

    /** What the box last did: '', 'subscribed', 'exists', 'invalid' or 'error'. */
    public string $result = '';

    public function subscribe(SubscribeToNewsletter $subscribe): void
    {
        $key = 'newsletter-subscribe:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::TRIES_PER_MINUTE)) {
            $this->result = 'error';

            return;
        }

        RateLimiter::hit($key);

        $valid = ! Validator::make(['email' => $this->email], ['email' => ['required', 'email', 'max:191']])->fails();

        if (! $valid) {
            $this->result = 'invalid';

            return;
        }

        $this->result = $subscribe($this->email) ? 'subscribed' : 'exists';

        if ($this->result === 'subscribed') {
            $this->email = '';
        }
    }

    public function clear(): void
    {
        $this->result = '';
    }
};
?>

<div class="subscribe-container">
    <img src="/images/ico/letter.svg" alt="Newsletter Icon">

    <h4 class="subscribe-title">Subscribe our newsletter to receive the latest news.</h4>

    <form class="subscription" wire:submit="subscribe">
        <input class="add-email" type="email" name="email" placeholder="Enter your Email Address" aria-label="Email address" wire:model="email">
        <button class="submit-email" type="submit" wire:loading.attr="disabled" wire:target="subscribe">
            <span wire:loading.remove wire:target="subscribe">{{ $result === 'subscribed' ? 'Subscribed!' : 'Subscribe' }}</span>
            <span wire:loading wire:target="subscribe">Sending...</span>
        </button>
    </form>

    {{-- The note, and for the three seconds it stays, a poll that clears it. --}}
    <div class="subscribe-note" @if (in_array($result, ['subscribed', 'exists'], true)) wire:poll.3s="clear" @endif aria-live="polite">
        @if ($result === 'exists')
            <span class="subscribe-note__text subscribe-note__text--exists">Email already exists!</span>
        @elseif ($result === 'invalid')
            <span class="subscribe-note__text">Please enter a valid email address.</span>
        @elseif ($result === 'error')
            <span class="subscribe-note__text">Server error. Please try again later.</span>
        @endif
    </div>
</div>
