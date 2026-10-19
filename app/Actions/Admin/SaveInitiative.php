<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Models\Initiative;
use App\Models\InitiativeContent;
use App\Models\User;
use App\Support\Admin\LegacyValue;
use Illuminate\Support\Facades\DB;

/*
 * Creates or updates an initiative, its English text and its donors. Replaces
 * InitiativesController::store() and update().
 *
 * Kept from legacy: a new initiative is offline and unchecked; an empty field is
 * stored as the text "0"; the category cannot be changed afterwards; and the
 * activity log gets one row per change, worded as legacy worded them ("Title
 * edited", "Donor X attached"), with the old and new value where there were two.
 *
 * Fixed from legacy: the donor change was compared against a column that does not
 * exist, so it was never logged; a rich-text box's re-written line endings were
 * logged as a content edit.
 *
 * $data is what InitiativeForm validated. Logo and photos are not handled here.
 */
class SaveInitiative
{
    /** data key => [initiatives_english column, log wording]; "0" stands for "none". */
    private const TEXTS = [
        'title' => ['title', 'Title edited', false],
        'organization' => ['organization', 'Organization edited', true],
        'date_string' => ['date_string', 'date edited', true],
        'description' => ['description', 'description edited', true],
        'content' => ['content', 'Content edited', false],
    ];

    /** data key => [initiatives column, log wording, table that names it]. */
    private const CHOICES = [
        'sub_what_category_id' => ['sub_what_category_id', 'Kind changed', 'sub_what_categories'],
        'place_id' => ['place_id', 'Place changed', 'places'],
        'sz_center_id' => ['sz_center_id', 'Study Zone centre changed', 'studyzonecenters'],
    ];

    public function __construct(private readonly WriteActivityLog $log) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data, User $by, ?Initiative $initiative = null): Initiative
    {
        return DB::transaction(fn (): Initiative => $initiative === null
            ? $this->create($data, $by)
            : $this->update($initiative, $data, $by));
    }

    /** @param array<string, mixed> $data */
    private function create(array $data, User $by): Initiative
    {
        $initiative = new Initiative;
        $initiative->forceFill([
            'logo' => '0',
            'video' => LegacyValue::store($data['video'] ?? null),
            'rank' => 0,
            'english_check' => 0,
            'french_translation' => '0',
            'online' => 0,
            'hidden' => false,
            'what_category_id' => $data['what_category_id'],
            'who_category_id' => $data['who_category_id'],
            'sub_what_category_id' => $data['sub_what_category_id'],
            'place_id' => $data['place_id'],
            'sz_center_id' => $data['sz_center_id'],
        ])->save();

        $content = new InitiativeContent(['initiative_id' => $initiative->getKey()]);
        foreach (self::TEXTS as $key => [$column, , $zeroWhenEmpty]) {
            $content->{$column} = $zeroWhenEmpty ? LegacyValue::store($data[$key] ?? null) : (string) $data[$key];
        }
        $content->save();

        DB::table('initiatives_donors')->insert(array_map(fn (int $donor): array => ['initiative_id' => $initiative->getKey(), 'donor_id' => $donor], $data['donors']));

        $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), 'Initiative Created');

        return $initiative;
    }

    /** @param array<string, mixed> $data */
    private function update(Initiative $initiative, array $data, User $by): Initiative
    {
        $id = $initiative->getKey();

        $initiative->video = LegacyValue::store($data['video'] ?? null);
        $this->logChange($initiative, 'video', 'Video Edited', $by);

        $initiative->who_category_id = $data['who_category_id'];
        if ($initiative->isDirty('who_category_id')) {
            $this->log->item($by, ContentKind::Initiative, $id, 'Serve category changed', $this->whoName($initiative->getOriginal('who_category_id')), $this->whoName($initiative->who_category_id));
        }

        // Where the category has them, the kind, the place and the centre can change; the category cannot.
        foreach (self::CHOICES as $key => [$column, $operation, $table]) {
            $initiative->{$column} = $data[$key];

            if ($initiative->isDirty($column)) {
                $this->log->item($by, ContentKind::Initiative, $id, $operation, $this->name($table, $initiative->getOriginal($column)), $this->name($table, $initiative->{$column}));
            }
        }

        $initiative->save();

        $content = InitiativeContent::query()->where('initiative_id', $id)->firstOrFail();
        foreach (self::TEXTS as $key => [$column, $operation, $zeroWhenEmpty]) {
            $new = $zeroWhenEmpty ? LegacyValue::store($data[$key] ?? null) : (string) $data[$key];

            if (LegacyValue::textChanged($content->{$column}, $new)) {
                $this->log->item($by, ContentKind::Initiative, $id, $operation, $content->{$column}, $new);
                $content->{$column} = $new;
            }
        }
        $content->save();

        $this->syncDonors($id, $data['donors'], $by);

        return $initiative;
    }

    /** @param list<int> $wanted */
    private function syncDonors(int $initiativeId, array $wanted, User $by): void
    {
        $current = DB::table('initiatives_donors')->where('initiative_id', $initiativeId)->pluck('donor_id')->map(fn ($id): int => (int) $id)->all();
        $names = DB::table('donors')->whereIn('id', [...$current, ...$wanted])->pluck('name', 'id');

        foreach (array_diff($wanted, $current) as $donor) {
            DB::table('initiatives_donors')->insert(['initiative_id' => $initiativeId, 'donor_id' => $donor]);
            $this->log->item($by, ContentKind::Initiative, $initiativeId, 'Donor '.$names[$donor].' attached');
        }

        foreach (array_diff($current, $wanted) as $donor) {
            DB::table('initiatives_donors')->where(['initiative_id' => $initiativeId, 'donor_id' => $donor])->delete();
            $this->log->item($by, ContentKind::Initiative, $initiativeId, 'Donor '.$names[$donor].' detached');
        }
    }

    private function logChange(Initiative $initiative, string $column, string $operation, User $by): void
    {
        if ($initiative->isDirty($column)) {
            $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), $operation, $initiative->getOriginal($column), $initiative->{$column});
        }
    }

    private function whoName(mixed $id): string
    {
        return $this->name('who_categories', $id);
    }

    /** The name behind an id, or nothing when there is none (an empty choice). */
    private function name(string $table, mixed $id): string
    {
        return (string) DB::table($table)->where('id', $id)->value('name');
    }
}
