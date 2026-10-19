<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/*
 * A newsletter item is Regular, Important (the lead story at the top of the news
 * page) or Pinned (a wide card). Legacy set this with a checkbox on the pictures page.
 *
 * Only one item at a time may be the online Important one: making an online item
 * Important, or publishing an Important item (ChangeContentState), makes the one that
 * was Important a Regular item, and the activity log says so, as legacy did.
 */
class SetNewsPriority
{
    public const REGULAR = 0;

    public const IMPORTANT = 1;

    public const PINNED = 2;

    public function __construct(private readonly WriteActivityLog $log) {}

    public static function label(int $status): string
    {
        return match ($status) {
            self::IMPORTANT => 'Important',
            self::PINNED => 'Pinned',
            default => 'Regular',
        };
    }

    public function handle(News $news, int $status, User $by): void
    {
        if (! in_array($status, [self::REGULAR, self::IMPORTANT, self::PINNED], true)) {
            throw new InvalidArgumentException("$status is not a news priority.");
        }

        $previous = (int) $news->priority_status;

        if ($previous === $status) {
            return;
        }

        DB::transaction(function () use ($news, $status, $previous, $by): void {
            if ($status === self::IMPORTANT && (int) $news->online === 1) {
                $this->replaceOthers($news, $by);
            }

            $news->forceFill(['priority_status' => $status])->save();
            $this->log->item($by, ContentKind::News, $news->getKey(), 'News Priority changed', self::label($previous), self::label($status));
        });
    }

    /** Make the other online Important items Regular, and log it on each. */
    public function replaceOthers(News $news, User $by): void
    {
        $replaced = DB::table('news')->where('online', 1)->where('priority_status', self::IMPORTANT)->where('id', '!=', $news->getKey())->lockForUpdate()->pluck('id');

        if ($replaced->isEmpty()) {
            return;
        }

        DB::table('news')->whereIn('id', $replaced)->update(['priority_status' => self::REGULAR]);

        foreach ($replaced as $id) {
            $this->log->item($by, ContentKind::News, (int) $id, 'Important status replaced by News #'.$news->getKey());
        }
    }
}
