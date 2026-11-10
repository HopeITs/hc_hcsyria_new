<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminActionRefused;
use App\Models\User;
use App\Rules\SecureUpload;
use App\Support\Admin\PhotoFiles;
use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/*
 * What happens when staff change the public page from the page itself (the visual editor): a line typed
 * over, a picture chosen, something put back. One place for it, used both by the dashboard's "Edit the
 * page" and by the editor shown on the public site to a signed-in account that may change it, so the two
 * cannot differ. Each call answers {ok, message}; the message is plain words for the editor's notice.
 * The caller has already checked the capability. A key that does not match what the page says is a 404.
 */
class EditSitePage
{
    public function __construct(private readonly ManageSiteContent $site) {}

    /** @return array{ok: bool, message: string} */
    public function text(string $where, string $original, string $text, User $by): array
    {
        return $this->attempt(fn () => $this->site->setLine($where, $original, $text, $by));
    }

    /**
     * A picture or film chosen on the page: $slot for one drawn from a declared slot, otherwise $key and
     * $src for one of the page's own pictures.
     *
     * @return array{ok: bool, message: string}
     */
    public function file(string $slot, string $key, string $src, bool $video, UploadedFile $file, User $by): array
    {
        $m = [];

        if ($video) {
            abort_if($slot === '' || (SiteContent::slot($slot)['type'] ?? null) !== 'video', 404);
        } elseif ($slot !== '') {
            abort_unless((SiteContent::slot($slot)['type'] ?? null) === 'image', 404);
        } else {
            // "img:<where>:<fingerprint>": the page the picture is on, and the address it had.
            abort_unless(preg_match('~^img:(\*|/[A-Za-z0-9/_-]*):[a-f0-9]{16}$~', $key, $m) === 1 && PageText::imageKey($m[1], $src) === $key, 404);
        }

        if ($video) {
            $check = Validator::make(['file' => $file], ['file' => ['required', 'file', 'max:61440']]);
        } elseif (! PhotoFiles::available()) {
            return ['ok' => false, 'message' => __('admin.alerts.photos.unavailable')];
        } else {
            $check = Validator::make(['file' => $file], ['file' => ['required', 'file', 'max:8192', new SecureUpload]], attributes: ['file' => 'picture']);
        }

        if ($check->fails()) {
            return ['ok' => false, 'message' => (string) $check->errors()->first()];
        }

        return $this->attempt(function () use ($video, $slot, $src, $file, $by, $m): void {
            if ($video) {
                $this->site->replaceVideo($slot, $file, $by);
            } elseif ($slot !== '') {
                $this->site->replaceImage($slot, $file, $by);
            } else {
                $this->site->replacePageImage($m[1], $src, $file, $by);
            }
        });
    }

    /** Put one thing back: a slot name, or a "text:" or "img:" key. @return array{ok: bool, message: string} */
    public function reset(string $key, User $by): array
    {
        abort_unless(preg_match('~^(text|img):(\*|/[A-Za-z0-9/_-]*):[a-f0-9]{16}$~', $key) === 1 || SiteContent::slot($key) !== null, 404);

        return $this->attempt(fn () => $this->site->reset($key, $by));
    }

    /** @return array{ok: bool, message: string} */
    private function attempt(callable $change): array
    {
        try {
            $change();
        } catch (AdminActionRefused $refused) {
            return ['ok' => false, 'message' => __('admin.'.$refused->alertKey(), $refused->params)];
        } catch (RuntimeException $failed) {
            return ['ok' => false, 'message' => $failed->getMessage()];
        }

        return ['ok' => true, 'message' => ''];
    }
}
