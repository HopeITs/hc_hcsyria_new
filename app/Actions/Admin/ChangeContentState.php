<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Exceptions\AdminActionRefused;
use App\Models\News;
use App\Models\User;
use App\Support\Admin\ProjectReadiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * Publish, take offline, hide, unhide, or mark English checked: the five state
 * changes legacy had as separate controller methods for projects and again for
 * initiatives, in one place. What each changes in which table, and what it writes
 * to the activity log, is ContentKind's.
 *
 *  - publish: online, no longer hidden (a project also gets its publish date, in Damascus time;
 *    an Important news item makes the previous Important one a regular item)
 *  - unpublish: back to the pending list
 *  - hide: offline and out of the pending list
 *  - unhide: back to the pending list (it stays offline)
 *
 * Publishing is refused until the item is ready (ContentKind::blockers). The
 * caller has already checked the policy; this does not look at who asks.
 */
class ChangeContentState
{
    public function __construct(private readonly WriteActivityLog $log) {}

    /** @throws AdminActionRefused */
    public function handle(Model $item, ContentTransition $transition, User $by): void
    {
        $kind = ContentKind::of($item);

        if ($transition === ContentTransition::Publish) {
            $blockers = $kind->blockers($item);

            if ($blockers !== []) {
                throw new AdminActionRefused('not_ready', [
                    'number' => $kind->number($item),
                    'missing' => ProjectReadiness::describe($blockers),
                ]);
            }
        }

        DB::transaction(function () use ($item, $kind, $transition, $by): void {
            $kind->apply($item, $transition, Carbon::now('Asia/Damascus'));
            $item->save();
            $this->log->item($by, $kind, $item->getKey(), $kind->operation($transition));

            if ($kind === ContentKind::News && $transition === ContentTransition::Publish) {
                $this->replaceImportant($item, $by);
            }
        });
    }

    /**
     * Only one news item at a time may be the Important one: publishing an Important item
     * makes the one that was online a regular item, and says so in the log, as legacy did.
     */
    private function replaceImportant(Model $news, User $by): void
    {
        if (! $news instanceof News || (int) $news->priority_status !== 1) {
            return;
        }

        $replaced = DB::table('news')->where('online', 1)->where('priority_status', 1)->where('id', '!=', $news->getKey())->lockForUpdate()->pluck('id');

        if ($replaced->isEmpty()) {
            return;
        }

        DB::table('news')->whereIn('id', $replaced)->update(['priority_status' => 0]);

        foreach ($replaced as $id) {
            $this->log->item($by, ContentKind::News, (int) $id, 'Important status replaced by News #'.$news->getKey());
        }
    }
}
