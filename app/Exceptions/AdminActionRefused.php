<?php

namespace App\Exceptions;

use RuntimeException;

/*
 * An admin action that cannot go ahead for a reason the staff member can act
 * on (not ready, a duplicate number, success stories attached). Carries the
 * alert wording key under admin.refusals and its placeholders, so a page shows
 * it as an error alert without wording anything itself.
 */
class AdminActionRefused extends RuntimeException
{
    /** @param array<string, scalar> $params */
    public function __construct(public readonly string $key, public readonly array $params = [])
    {
        parent::__construct($key);
    }

    public function alertKey(): string
    {
        return 'refusals.'.$this->key;
    }
}
