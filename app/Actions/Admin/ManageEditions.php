<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminActionRefused;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * The magazine editions a newsletter item can belong to: make one, put it online or
 * offline, delete one. Replaces NewsController::edition_store(), mark_online(),
 * mark_offline() and edition_destroy().
 *
 * Kept from legacy: a name of at most 50 characters. Changed: no two editions share a name;
 * the French name can be given (legacy stored an empty one); deleting an edition that news
 * items still belong to is refused with their number (the database would refuse it with a
 * raw error, after legacy had already logged the deletion); and putting an edition online
 * or offline is logged. The caller has already checked the capability.
 */
class ManageEditions
{
    public function __construct(private readonly WriteActivityLog $log) {}

    /** @throws AdminActionRefused */
    public function create(string $name, ?string $nameFrench, User $by): int
    {
        $name = trim($name);

        if (DB::table('editions')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            throw new AdminActionRefused('edition_exists', ['name' => $name]);
        }

        $id = (int) DB::table('editions')->insertGetId([
            'name' => $name,
            'name_french' => trim((string) $nameFrench),
            'online' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->log->record($by, WriteActivityLog::EDITIONS, $id, 'Edition created');

        return $id;
    }

    public function setOnline(int $id, bool $online, User $by): void
    {
        $changed = DB::table('editions')->where('id', $id)->where('online', '!=', (int) $online)->update(['online' => (int) $online, 'updated_at' => now()]);

        if ($changed > 0) {
            $this->log->record($by, WriteActivityLog::EDITIONS, $id, $online ? 'Edition made online' : 'Edition made offline');
        }
    }

    /** @throws AdminActionRefused */
    public function delete(int $id, User $by): void
    {
        $name = DB::table('editions')->where('id', $id)->value('name');

        if ($name === null) {
            return;
        }

        $used = DB::table('news')->where('edition_id', $id)->count();

        if ($used > 0) {
            throw new AdminActionRefused('edition_in_use', ['name' => $name, 'count' => $used]);
        }

        DB::table('editions')->where('id', $id)->delete();
        $this->log->record($by, WriteActivityLog::EDITIONS, $id, 'Edition deleted');
    }
}
