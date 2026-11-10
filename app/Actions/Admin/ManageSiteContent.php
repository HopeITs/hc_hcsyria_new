<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminActionRefused;
use App\Models\User;
use App\Support\Admin\PhotoFiles;
use App\Support\Site\SiteContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/*
 * Changing what the public pages show (Site content): replace a picture or a film,
 * write a different paragraph, or go back to the original. New to the rebuild: on
 * the legacy site these were edited in the code.
 *
 * The original is never touched. A change is a row in `site_contents` and, for a
 * picture or film, a new file under MEDIA_ROOT (/images/site, /video/site); going
 * back deletes the row and the file it pointed at. A replaced picture is re-encoded
 * like every other upload (PhotoFiles), which also drops camera data and GPS. The
 * caller has already checked the capability.
 */
class ManageSiteContent
{
    public const MAX_TEXT = 1500;

    private const MAX_VIDEO_BYTES = 60 * 1024 * 1024;

    public function __construct(private readonly WriteActivityLog $log) {}

    /** @throws AdminActionRefused */
    public function replaceImage(string $key, UploadedFile $file, User $by): void
    {
        $slot = $this->slot($key, 'image');
        $box = $slot['box'] ?? [2000, 2000];

        $path = PhotoFiles::storeOne((string) $file->getRealPath(), '/images/site', [$box[0], $box[1]], cover: false, suffix: '-'.Str::slug(Str::after($key, '.')), keepAlpha: (bool) $slot['alpha']);

        $this->store($key, $path, $by, 'Picture changed');
    }

    /** @throws AdminActionRefused */
    public function replaceVideo(string $key, UploadedFile $file, User $by): void
    {
        $this->slot($key, 'video');
        $source = (string) $file->getRealPath();
        $root = rtrim((string) config('hcsyria.media_root'), '/\\');

        // The bytes decide, not the name: an MP4 says "ftyp" at its fourth byte.
        $head = (string) @file_get_contents($source, false, null, 0, 12);
        $isMp4 = strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp';

        if ($root === '' || ! $isMp4 || (int) filesize($source) > self::MAX_VIDEO_BYTES) {
            throw new AdminActionRefused('video_rejected', ['mb' => self::MAX_VIDEO_BYTES / 1024 / 1024]);
        }

        $name = now()->format('ymdHis').Str::lower(Str::random(8)).'-'.Str::slug(Str::after($key, '.')).'.mp4';
        File::ensureDirectoryExists("$root/video/site");
        File::copy($source, "$root/video/site/$name");

        $this->store($key, "/video/site/$name", $by, 'Film changed');
    }

    /** @throws AdminActionRefused */
    public function setText(string $key, string $text, User $by): void
    {
        $slot = $this->slot($key, 'text');
        $text = trim(str_replace(["\r\n", "\r"], "\n", strip_tags($text)));

        if ($text === '' || mb_strlen($text) > self::MAX_TEXT) {
            throw new AdminActionRefused('text_rejected', ['max' => self::MAX_TEXT]);
        }

        if ($text === $slot['default']) {
            $this->reset($key, $by);

            return;
        }

        $this->store($key, $text, $by, 'Text changed');
    }

    /** Back to the original. Nothing to do when nothing was changed. */
    public function reset(string $key, User $by): void
    {
        $this->slot($key);
        $old = DB::table('site_contents')->where('slot', $key)->value('value');

        if ($old === null) {
            return;
        }

        DB::table('site_contents')->where('slot', $key)->delete();
        $this->forget((string) $old);
        app(SiteContent::class)->refresh();

        $this->log->record($by, WriteActivityLog::SITE, $this->logId($key), 'Site content reset: '.$key, $old);
    }

    private function store(string $key, string $value, User $by, string $operation): void
    {
        $old = DB::table('site_contents')->where('slot', $key)->value('value');

        DB::table('site_contents')->upsert(
            [['slot' => $key, 'value' => $value, 'updated_by' => $by->getKey(), 'created_at' => now(), 'updated_at' => now()]],
            ['slot'],
            ['value', 'updated_by', 'updated_at'],
        );

        if ($old !== null && $old !== $value) {
            $this->forget((string) $old);
        }

        app(SiteContent::class)->refresh();
        $this->log->record($by, WriteActivityLog::SITE, $this->logId($key), "$operation: $key", $old, $value);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AdminActionRefused when the slot is not declared or is another kind of thing
     */
    private function slot(string $key, ?string $kind = null): array
    {
        $slot = SiteContent::slot($key);
        $type = $slot['type'] ?? null;
        $matches = $kind === 'text' ? in_array($type, ['text', 'long'], true) : ($kind === null ? $type !== null : $type === $kind);

        if ($slot === null || ! $matches) {
            throw new AdminActionRefused('slot_unknown');
        }

        return $slot;
    }

    /** A file we stored goes with its row; only our own folders are ever touched. */
    private function forget(string $value): void
    {
        if (str_starts_with($value, '/images/site/')) {
            PhotoFiles::delete($value);
        } elseif (str_starts_with($value, '/video/site/')) {
            $root = realpath((string) config('hcsyria.media_root').'/video/site');
            $file = $root === false ? false : realpath(config('hcsyria.media_root').$value);

            if ($file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
                File::delete($file);
            }
        }
    }

    /** The log keeps a number per row; a slot's number is a stable one made from its name. */
    private function logId(string $key): int
    {
        return crc32($key) % 1_000_000;
    }
}
