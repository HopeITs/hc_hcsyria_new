<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Models\News;
use App\Models\NewsContent;
use App\Models\User;
use App\Support\Admin\DocumentFiles;
use App\Support\Admin\LegacyValue;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * Creates or updates a newsletter item, its English text, its tags and its attached
 * PDF. Replaces NewsController::store() and update().
 *
 * Kept from legacy: a new item is offline and unchecked; an empty video, description
 * or attachment is the text "0"; and saving a change to an item that is online takes it
 * back to the pending list (legacy's update always set it offline, so an edited item is
 * published again after review). The activity log gets one row per changed field, worded
 * "News Edited: Title" and so on, with the old and new value.
 *
 * Changed: new tags typed into the form are created here (legacy's create form ignored
 * them, its edit form made them); tags with the same name are one tag; the attachment is
 * stored under a safe name that cannot replace another (DocumentFiles) and a replaced or
 * removed one is really deleted; the log row of an edit is one per field, not one long
 * string of every change.
 *
 * $data is what NewsForm validated. Pictures and the Important/Pinned status are not
 * handled here.
 */
class SaveNews
{
    private const FILE_DIRECTORY = '/files/news';

    /** data key => [news column, log field name, "0" when empty]. */
    private const COLUMNS = [
        'type' => ['type', 'Type', false],
        'place_id' => ['place_id', 'Place_id', false],
        'edition_id' => ['edition_id', 'Edition_id', false],
        'rank' => ['rank', 'Rank', false],
        'date' => ['date', 'Date', false],
        'video' => ['video', 'Video', true],
    ];

    /** data key => [news_contents column, log field name, "0" when empty]. */
    private const TEXTS = [
        'title' => ['title', 'Title', false],
        'description' => ['description', 'Description', true],
        'content' => ['content', 'Content', false],
    ];

    public function __construct(private readonly WriteActivityLog $log) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  bool  $removeFile  take the current attachment away (edit only)
     */
    public function handle(array $data, User $by, ?UploadedFile $file, bool $removeFile = false, ?News $news = null): News
    {
        $path = $file === null ? null : DocumentFiles::store($file, self::FILE_DIRECTORY);

        try {
            return DB::transaction(fn (): News => $news === null
                ? $this->create($data, $by, $path)
                : $this->update($news, $data, $by, $path, $removeFile));
        } catch (Throwable $failure) {
            $path === null || PhotoFiles::delete($path);

            throw $failure;
        }
    }

    /** @param array<string, mixed> $data */
    private function create(array $data, User $by, ?string $path): News
    {
        $news = new News;
        $news->forceFill([
            'file' => $path ?? '0',
            'english_check' => 0,
            'french_translation' => '0',
            'online' => 0,
            'hidden' => false,
            'priority_status' => 0,
            'view_count' => 0,
            'total_views' => 0,
        ]);
        $this->assign($news, $data);
        $news->save();

        $content = new NewsContent(['news_id' => $news->getKey()]);
        $this->fillText($content, $data);
        $content->save();

        $this->syncTags($news, $data, $by, log: false);

        $this->log->item($by, ContentKind::News, $news->getKey(), 'News Created');

        return $news;
    }

    /** @param array<string, mixed> $data */
    private function update(News $news, array $data, User $by, ?string $path, bool $removeFile): News
    {
        $id = $news->getKey();
        $old = $news->file;

        $this->assign($news, $data);

        foreach (self::COLUMNS as [$column, $field]) {
            if ($news->isDirty($column)) {
                $this->log->item($by, ContentKind::News, $id, 'News Edited: '.$field, $news->getOriginal($column), $news->{$column});
            }
        }

        if ($path !== null || ($removeFile && $old !== '0')) {
            $news->file = $path ?? '0';
            $this->log->item($by, ContentKind::News, $id, 'News Edited: File', $old, $news->file);
        }

        if ((int) $news->online === 1) {
            $news->online = 0;
            $this->log->item($by, ContentKind::News, $id, 'News Status: Offline', 'Online', 'Offline');
        }

        $news->save();

        if ($news->wasChanged('file') && $old !== '0' && $old !== '') {
            PhotoFiles::delete($old);
        }

        $content = NewsContent::query()->where('news_id', $id)->firstOrFail();
        foreach (self::TEXTS as $key => [$column, $field, $zero]) {
            $new = $zero ? LegacyValue::store($data[$key] ?? null) : (string) $data[$key];

            if (LegacyValue::textChanged($content->{$column}, $new)) {
                $this->log->item($by, ContentKind::News, $id, 'News Edited: '.$field, $content->{$column}, $new);
                $content->{$column} = $new;
            }
        }
        $content->save();

        $this->syncTags($news, $data, $by, log: true);

        return $news;
    }

    /** @param array<string, mixed> $data */
    private function assign(News $news, array $data): void
    {
        foreach (self::COLUMNS as $key => [$column, , $zero]) {
            $news->{$column} = $zero ? LegacyValue::store($data[$key] ?? null) : $data[$key];
        }
    }

    /** @param array<string, mixed> $data */
    private function fillText(NewsContent $content, array $data): void
    {
        foreach (self::TEXTS as $key => [$column, , $zero]) {
            $content->{$column} = $zero ? LegacyValue::store($data[$key] ?? null) : (string) $data[$key];
        }
    }

    /**
     * Make the tags the item should have: the ticked ones and the new ones (made if they do not exist yet, by name).
     *
     * @param  array<string, mixed>  $data
     */
    private function syncTags(News $news, array $data, User $by, bool $log): void
    {
        $wanted = new Collection(array_map('intval', (array) $data['tag_ids']));

        foreach ($data['new_tags'] as $name) {
            $existing = DB::table('tags')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');
            $wanted->push($existing ?? DB::table('tags')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]));
        }

        $wanted = $wanted->map(fn ($id): int => (int) $id)->unique()->values();
        $current = DB::table('news_tags')->where('news_id', $news->getKey())->pluck('tag_id')->map(fn ($id): int => (int) $id);
        $names = fn ($ids): string => DB::table('tags')->whereIn('id', $ids)->orderBy('name')->pluck('name')->implode(', ');

        foreach ($wanted->diff($current) as $tag) {
            DB::table('news_tags')->insert(['news_id' => $news->getKey(), 'tag_id' => $tag]);
        }

        DB::table('news_tags')->where('news_id', $news->getKey())->whereIn('tag_id', $current->diff($wanted))->delete();

        if ($log && $wanted->sort()->values()->all() !== $current->sort()->values()->all()) {
            $this->log->item($by, ContentKind::News, $news->getKey(), 'News Edited: Tags', $names($current) ?: 'none', $names($wanted));
        }
    }
}
