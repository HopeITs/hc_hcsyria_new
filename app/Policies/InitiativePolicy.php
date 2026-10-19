<?php

namespace App\Policies;

use App\Models\Initiative;
use App\Models\User;
use App\Queries\ContentLookups;
use Illuminate\Database\Eloquent\Model;

/*
 * Initiatives: the rules of ContentPolicy. Legacy did not limit an initiative to a
 * city for the staff who manage it. Homework Room's initiatives are not part of the
 * dashboard (ContentLookups::EXCLUDED_CATEGORIES).
 */
class InitiativePolicy extends ContentPolicy
{
    protected function inScope(User $user, Model $item): bool
    {
        return $item instanceof Initiative && ! in_array((int) $item->what_category_id, ContentLookups::EXCLUDED_CATEGORIES, true);
    }
}
