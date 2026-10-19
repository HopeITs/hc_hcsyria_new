<?php

namespace App\Policies;

use App\Models\Success;
use App\Models\User;
use App\Queries\ContentLookups;
use Illuminate\Database\Eloquent\Model;

/*
 * Success stories: the rules of ContentPolicy, for the categories the Successes
 * section manages (ContentLookups::SUCCESS_CATEGORIES). A story of any other
 * category, Homework Room's included, is not the dashboard's to open or change.
 */
class SuccessPolicy extends ContentPolicy
{
    protected function inScope(User $user, Model $item): bool
    {
        return $item instanceof Success && in_array((int) $item->what_category_id, ContentLookups::SUCCESS_CATEGORIES, true);
    }
}
