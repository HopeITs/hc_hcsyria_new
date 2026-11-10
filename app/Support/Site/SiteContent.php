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
