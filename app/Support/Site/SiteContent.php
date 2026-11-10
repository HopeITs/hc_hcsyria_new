<?php

namespace App\Support\Site;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The one place a public page asks for something staff may change from the
 * dashboard (Site content): a picture, a film or a paragraph.
 *
 *   SiteContent::image('home.cover_desktop')   // "/images/site/….jpg", or the original
 *   SiteContent::text('home.leap_text')
 *
 * Every slot is declared in config/site_content.php with the thing the page always
 * showed; a slot nobody changed answers with that, so a page looks exactly as it did
 * until someone edits it. The changes are read once per request (one small query).
 */
final class SiteContent
{
    /** True while the site:scan command reads a page, so it reads the original words. */
    public static bool $scanning = false;

    /** @var array<string, string>|null slot => value, loaded on first use */
    private ?array $changes = null;

    public static function image(string $slot): string
    {
        return self::get($slot);
    }

    public static function video(string $slot): string
    {
        return self::get($slot);
    }

    public static function text(string $slot): string
    {
        return self::get($slot);
    }

    /**
     * Every page with its slots, each with its current value and whether it was changed.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function pages(): array
    {
        $changes = app(self::class)->all();
        $pages = [];

        foreach ((array) config('site_content.pages') as $page => $definition) {
            foreach ($definition['slots'] as $name => $slot) {
                $key = "$page.$name";
                $definition['slots'][$name] = $slot + ['alpha' => false, 'box' => null] + [
                    'key' => $key,
                    'changed' => array_key_exists($key, $changes),
                    'value' => $changes[$key] ?? $slot['default'],
                ];
            }

            $pages[$page] = $definition;
        }

        return $pages;
    }

    /**
     * The definition of one slot, or null when the key is not declared.
     *
     * @return array<string, mixed>|null
     */
    public static function slot(string $key): ?array
    {
        [$page, $name] = array_pad(explode('.', $key, 2), 2, '');
        $slot = config("site_content.pages.$page.slots.$name");

        return is_array($slot) ? $slot + ['alpha' => false, 'box' => null, 'key' => $key] : null;
    }

    /**
     * The rewritten lines that apply to a page: its own and the site-wide ones (menu, header, footer).
     *
     * @return array<string, string> key (PageText::key) => new words
     */
    public static function textFor(string $page): array
    {
        return array_filter(
            app(self::class)->all(),
            fn (string $value, string $slot): bool => preg_match('~^(text|img):(\*|'.preg_quote($page, '~').'):~', $slot) === 1,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * The pictures and films the page draws from a slot, by the address they have now (so the editor can tell
     * which slot a picture on the page belongs to). An address two slots share cannot say which, and is left out.
     *
     * @return array<string, string> address => slot key
     */
    public static function slotAddresses(): array
    {
        $found = [];

        foreach (self::pages() as $page) {
            foreach ($page['slots'] as $slot) {
                if (in_array($slot['type'], ['image', 'video'], true)) {
                    $found[$slot['value']][] = $slot['key'];
                }
            }
        }

        return collect($found)->filter(fn (array $keys): bool => count($keys) === 1)->map(fn (array $keys): string => $keys[0])->all();
    }

    /** False until the site_contents table (with its original column) exists: the dashboard says so instead of failing. */
    public static function ready(): bool
    {
        return Schema::hasTable('site_contents') && Schema::hasColumn('site_contents', 'original');
    }

    /** Forget what was read: the next question asks the database again. */
    public function refresh(): void
    {
        $this->changes = null;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        if ($this->changes === null) {
            // Without the table (a deploy before its migration) every page simply shows its original.
            $this->changes = Schema::hasTable('site_contents')
                ? DB::table('site_contents')->pluck('value', 'slot')->all()
                : [];
        }

        return $this->changes;
    }

    private static function get(string $key): string
    {
        $changes = app(self::class)->all();

        return $changes[$key] ?? (string) (self::slot($key)['default'] ?? '');
    }
}
