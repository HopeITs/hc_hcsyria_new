<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminActionRefused;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * The tags of the newsletter: make one, rename one, delete one. Replaces
 * NewsController::tag_store(), tag_update() and tag_destroy().
 *
 * Kept from legacy: a name is at most 50 characters and no two tags share one, and the
 * activity log wording ("Tag Created: X", "Tag Updated: A -> B", "Tag Deleted: X").
 * Changed: names are compared without regard to capitals ("Relief" and "relief" are one tag, so
 * typing a tag in the news form never makes a twin); and deleting a tag takes it off the news
 * items inside one transaction (the database does not cascade the links). The caller has
 * already checked the capability.
 */
class ManageTags
{
    public function __construct(private readonly WriteActivityLog $log) {}

    /** @throws AdminActionRefused */
    public function create(string $name, User $by): int
    {
        $name = trim($name);
        $this->requireFree($name);

        $id = (int) DB::table('tags')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        $this->log->record($by, WriteActivityLog::TAGS, $id, 'Tag Created: '.$name);

        return $id;
    }

    /** @throws AdminActionRefused */
    public function rename(int $id, string $name, User $by): void
    {
        $name = trim($name);
        $old = (string) DB::table('tags')->where('id', $id)->value('name');

        if ($old === '' || $old === $name) {
            return;
        }

        $this->requireFree($name, except: $id);

        DB::table('tags')->where('id', $id)->update(['name' => $name, 'updated_at' => now()]);
        $this->log->record($by, WriteActivityLog::TAGS, $id, 'Tag Updated: '.$old.' -> '.$name, $old, $name);
    }

    public function delete(int $id, User $by): void
    {
        $name = DB::table('tags')->where('id', $id)->value('name');

        if ($name === null) {
            return;
        }

        DB::transaction(function () use ($id): void {
            DB::table('news_tags')->where('tag_id', $id)->delete();
            DB::table('tags')->where('id', $id)->delete();
        });

        $this->log->record($by, WriteActivityLog::TAGS, $id, 'Tag Deleted: '.$name);
    }

    /** @throws AdminActionRefused */
    private function requireFree(string $name, ?int $except = null): void
    {
        $taken = DB::table('tags')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except))
            ->exists();

        if ($taken) {
            throw new AdminActionRefused('tag_exists', ['name' => $name]);
        }
    }
}
