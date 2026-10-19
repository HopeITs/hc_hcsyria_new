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
    public function __construct(private readonly WriteActivityLog $log, private readonly SetNewsPriority $priority) {}

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

            if ($item instanceof News && $transition === ContentTransition::Publish && (int) $item->priority_status === SetNewsPriority::IMPORTANT) {
                $this->priority->replaceOthers($item, $by);
            }
        });
    }
}
