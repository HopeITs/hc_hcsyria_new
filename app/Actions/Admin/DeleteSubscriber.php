<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * Takes an address off the newsletter list. Replaces NewsController::subscriber_destroy().
 * The log says that a subscriber was deleted, by whom and which row, but not the address:
 * it is personal data and the log is read by more people than the list is. The caller has
 * already checked the capability.
 */
class DeleteSubscriber
{
    public function __construct(private readonly WriteActivityLog $log) {}

    /** Returns whether there was such a subscriber. */
    public function handle(int $id, User $by): bool
    {
        if (DB::table('newsletter_subscribers')->where('id', $id)->delete() === 0) {
            return false;
        }

        $this->log->record($by, WriteActivityLog::SUBSCRIBERS, $id, 'Subscriber deleted');

        return true;
    }
}
