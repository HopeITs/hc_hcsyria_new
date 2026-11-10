<?php

use App\Actions\Admin\EditSitePage;
use App\Actions\Admin\ManageSiteContent;
use App\Enums\AdminCapability;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Queries\AdminAccessQuery;
use App\Support\Site\PageScan;
use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/*
 * /admin/site: the pictures, films and wording of the public pages, changed by staff
 * instead of in the code. A page of the public site is a "stage": its things (slots,
 * declared in config/site_content.php) are cards you drop a new file on or type into,
 * beside a live view of the real page that reloads after every change. The work is
 * ManageSiteContent; a picture is checked by SecureUpload and re-encoded by PhotoFiles
 * (ReceivesPictures), a film is checked by its bytes. Slot keys carry a dot, which a
 * Livewire property path cannot, so the form uses "__" in its place.
 *
 * Three modes. "Visual" (the default) opens the real page full size and staff click the
 * words to type over them, or a picture to replace it; the editor lives inside the page
 * (resources/views/site/editor.blade.php, switched on by ?sc-edit=1 for staff) and tells
 * this component what changed with postMessage, which saves it. "Pictures & films" is the declared slots (config/site_content.php). "Words" is
 * every line of text a visitor reads on the page, found by reading the page itself
 * (PageScan, PageText), so no copy is out of reach however it is written in the code; a
 * line is rewritten, or put back, one at a time. The menu, header and footer, shown on
 * every page, are one more "page" in this mode.
 */
new #[Layout('layouts::admin', ['title' => 'Site content'])] class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Url(history: true, except: 'home')]
    public string $page = 'home';

    #[Url(except: 'wide')]
    public string $frame = 'wide';

    #[Url(except: 'visual')]
    public string $mode = 'visual';

    /** The picture chosen on the page, while it is being uploaded. */
    public mixed $drop = null;

    #[Url(except: '')]
    public string $search = '';

    public bool $changedOnly = false;

    /** @var array<string, string> lines being written, by fingerprint (Words) */
    public array $edits = [];

    /** @var array<string, mixed> chosen files, by slot (dots written as "__") */
    public array $uploads = [];

    /** @var array<string, string> text being written, by slot */
    public array $texts = [];

    /** Changes with every save, so the live view of the page reloads. */
    public int $stamp = 0;

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::ManageSite->value);

        if (! in_array($this->mode, ['visual', 'media', 'words'], true)) {
            $this->mode = 'visual';
        }

        if (! array_key_exists($this->page, (array) config('site_content.pages')) && ! ($this->page === 'sitewide' && $this->mode === 'words')) {
            $this->page = 'home';
        }

        $this->search = mb_substr(trim($this->search), 0, 100);

        if (! in_array($this->frame, ['wide', 'phone'], true)) {
            $this->frame = 'wide';
        }

        $this->stamp = time();
        $this->loadTexts();
    }

    public function updatedPage(): void
    {
        $this->reset('uploads', 'edits', 'search');
        $this->loadTexts();
    }

    public function updatedMode(): void
    {
        if ($this->mode !== 'words' && $this->page === 'sitewide') {
            $this->page = 'home';
        }

        $this->reset('uploads', 'edits', 'search');
    }

    /** A file was dropped on a card: it is checked and stored at once. */
    public function updatedUploads(mixed $value, string $name): void
    {
        $this->allow();
        $key = $this->key($name);
        $slot = SiteContent::slot($key);

        if ($slot === null || ! str_starts_with($key, $this->page.'.')) {
            $this->reset("uploads.$name");

            return;
        }

        $action = app(ManageSiteContent::class);

        try {
            if ($slot['type'] === 'video') {
                $this->validate(["uploads.$name" => ['required', 'file', 'max:61440']], attributes: ["uploads.$name" => 'film']);
                $action->replaceVideo($key, $this->uploads[$name], Auth::user());
                $this->done('site.video');
            } elseif ($this->acceptPictures("uploads.$name")) {
                $action->replaceImage($key, $this->uploads[$name], Auth::user());
                $this->done('site.image');
            }
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');
        } catch (\RuntimeException|ValidationException $failed) {
            $this->alert('photos.rejected', ['reason' => $failed instanceof ValidationException ? collect($failed->errors())->flatten()->first() : $failed->getMessage()], 'error');
        }

        $this->reset("uploads.$name");
    }

    public function saveText(string $name): void
    {
        $this->allow();
        $key = $this->key($name);

        try {
            app(ManageSiteContent::class)->setText($key, (string) ($this->texts[$name] ?? ''), Auth::user());
            $this->done('site.text');
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');
        }

        $this->loadTexts();
    }

    public function reset_slot(string $name): void
    {
        $this->allow();
        app(ManageSiteContent::class)->reset($this->key($name), Auth::user());
        $this->done('site.reset');
        $this->loadTexts();
    }

    /**
     * A line typed over on the page itself. The message is for the page's notice.
     *
     * @return array{ok: bool, message: string}
     */
    public function saveEdit(string $where, string $original, string $text): array
    {
        $this->allow();

        return $this->saved(app(EditSitePage::class)->text($where, $original, $text, Auth::user()), 'site.text');
    }

    /**
     * A picture or film chosen on the page (the file is in $drop): $slot for one drawn from a declared
     * slot, otherwise $key and $src for one of the page's own pictures.
     *
     * @return array{ok: bool, message: string}
     */
    public function applyImage(string $slot, string $key, string $src, bool $video = false): array
    {
        $this->allow();
        abort_if($this->drop === null, 422);

        $result = app(EditSitePage::class)->file($slot, $key, $src, $video, $this->drop, Auth::user());
        $this->reset('drop');

        return $this->saved($result, $video ? 'site.video' : 'site.image');
    }

    /** Put one thing back to its original from the page: a slot name, or a "text:" / "img:" key. */
    public function resetEdit(string $key): bool
    {
        $this->allow();

        return $this->saved(app(EditSitePage::class)->reset($key, Auth::user()), 'site.reset')['ok'];
    }

    /**
     * @param  array{ok: bool, message: string}  $result
     * @return array{ok: bool, message: string}
     */
    private function saved(array $result, string $alert): array
    {
        if ($result['ok']) {
            $this->stamp = time();
            $this->alert($alert);
        } else {
            $this->dispatch('admin-alert', type: 'error', message: $result['message'], undo: null);
        }

        return $result;
    }

    /** Save what was written for one line of the page. */
    public function saveLine(string $hash): void
    {
        $this->allow();
        $line = collect($this->scan() ?? [])->first(fn (array $line): bool => substr(sha1($line['text']), 0, 16) === $hash);

        if ($line === null) {
            $this->alert('refusals.slot_unknown', type: 'error');

            return;
        }

        try {
            app(ManageSiteContent::class)->setLine($this->where(), $line['text'], (string) ($this->edits[$hash] ?? ''), Auth::user());
            $this->done('site.text');
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');
        }

        unset($this->edits[$hash]);
    }

    public function resetLine(string $hash): void
    {
        $this->allow();
        abort_unless(preg_match('/^[a-f0-9]{16}$/', $hash) === 1, 404);

        app(ManageSiteContent::class)->reset("text:{$this->where()}:$hash", Auth::user());
        unset($this->edits[$hash]);
        $this->done('site.reset');
    }

    /** Read the page again, in case its code changed. */
    public function rescan(): void
    {
        $this->allow();
        PageScan::lines($this->scanUrl(), fresh: true);
        $this->reset('edits');
    }

    /** "*" for the menu, header and footer; otherwise the address of the page. */
    private function where(): string
    {
        return $this->page === 'sitewide' ? '*' : (string) config("site_content.pages.{$this->page}.url");
    }

    /** The menu, header and footer are on every page: any one will show them. */
    private function scanUrl(): string
    {
        return $this->page === 'sitewide' ? '/home' : $this->where();
    }

    /** @return list<array{text: string, wide: bool}>|null */
    private function scan(): ?array
    {
        $lines = PageScan::lines($this->scanUrl());

        return $lines === null ? null : array_values(array_filter($lines, fn (array $line): bool => $line['wide'] === ($this->page === 'sitewide')));
    }

    private function done(string $alert): void
    {
        $this->stamp = time();
        $this->alert($alert);
        $this->dispatch('site-changed');
    }

    private function loadTexts(): void
    {
        $this->texts = [];

        foreach (SiteContent::pages()[$this->page]['slots'] ?? [] as $slot) {
            if (in_array($slot['type'], ['text', 'long'], true)) {
                $this->texts[str_replace('.', '__', $slot['key'])] = $slot['value'];
            }
        }
    }

    /** "home__leap_text" => "home.leap_text", and only for the page being edited. */
    private function key(string $name): string
    {
        $key = str_replace('__', '.', $name);
        abort_unless(str_starts_with($key, $this->page.'.') && SiteContent::slot($key) !== null, 404);

        return $key;
    }

    /** Every change asks again: the buttons are only a convenience. */
    private function allow(): void
    {
        abort_unless(AdminAccessQuery::can(Auth::user(), AdminCapability::ManageSite), 403);
    }

    public function with(): array
    {
        $pages = SiteContent::pages();
        $ready = SiteContent::ready();
        $rewrites = $ready ? DB::table('site_contents')->where('slot', 'like', 'text:%')->get(['slot', 'value', 'original']) : collect();

        $this->page = $this->mode === 'words' || $this->page !== 'sitewide' ? $this->page : 'home';

        if ($this->page === 'sitewide') {
            $current = ['label' => 'Menu, header and footer', 'url' => '/home', 'icon' => 'menu', 'blurb' => 'The words on every page: the menu, the header and the footer.', 'slots' => []];
        } else {
            $current = $pages[$this->page];
        }

        $changed = collect($current['slots'])->where('changed', true)->count();
        $count = fn (string $where): int => $rewrites->filter(fn (object $row): bool => str_starts_with($row->slot, "text:$where:"))->count();

        return [
            'pages' => $pages,
            'current' => $current,
            'changedHere' => $changed,
            'changedBy' => collect($pages)->map(fn (array $p, string $id): int => collect($p['slots'])->where('changed', true)->count())->all(),
            'wordsBy' => collect($pages)->map(fn (array $p): int => $count($p['url']))->all() + ['sitewide' => $count('*')],
            'previewUrl' => $current['url'].'?studio='.$this->stamp,
            'editUrl' => $current['url'].'?sc-edit=1&t='.$this->stamp,
            'ready' => $ready,
            'words' => $ready && $this->mode === 'words' ? $this->words($rewrites) : null,
        ];
    }

    /**
     * The lines of the page with what was rewritten, and the rewrites whose line left the page.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rewrites
     * @return array{rows: list<array<string, mixed>>, gone: list<array<string, mixed>>, failed: bool, error: ?string, shown: int}
     */
    private function words($rewrites): array
    {
        $lines = $this->scan();
        $prefix = "text:{$this->where()}:";
        $mine = $rewrites->filter(fn (object $row): bool => str_starts_with($row->slot, $prefix))->keyBy(fn (object $row): string => substr($row->slot, strlen($prefix)));
        $rows = [];

        foreach (collect($lines ?? [])->unique('text') as $line) {
            $hash = substr(sha1($line['text']), 0, 16);
            $over = $mine->get($hash);
            $this->edits[$hash] ??= $over->value ?? $line['text'];
            $rows[] = ['hash' => $hash, 'original' => $line['text'], 'changed' => $over !== null, 'long' => mb_strlen($line['text']) > 70 || str_contains($line['text'], "\n")];
        }

        $needle = mb_strtolower($this->search);
        $shown = array_values(array_filter($rows, fn (array $row): bool => (! $this->changedOnly || $row['changed'])
            && ($needle === '' || str_contains(mb_strtolower($row['original'].' '.$this->edits[$row['hash']]), $needle))));
        $seen = array_column($rows, 'hash');
        $gone = $mine->reject(fn (object $row, string $hash): bool => in_array($hash, $seen, true))
            ->map(fn (object $row, string $hash): array => ['hash' => $hash, 'original' => (string) $row->original, 'value' => $row->value])->values()->all();

        return ['rows' => $shown, 'gone' => $gone, 'failed' => $lines === null, 'error' => PageScan::$error, 'shown' => count($rows)];
    }
};
?>

<div class="stack" x-data="{ drag: null }">
    <x-admin.page-header title="Site content" subtitle="Change the pictures, films and words of the public pages. Nothing here touches the original: reset anything to bring it back.">
        <x-slot:actions>
            <div class="seg" role="radiogroup" aria-label="What to change">
                <label><input type="radio" value="visual" wire:model.live="mode"><span>Edit the page</span></label>
                <label><input type="radio" value="media" wire:model.live="mode"><span>Pictures &amp; films</span></label>
                <label><input type="radio" value="words" wire:model.live="mode"><span>Words list</span></label>
            </div>
            <a href="{{ $current['url'] }}" target="_blank" rel="noopener" class="btn btn-o"><x-admin.icon name="ext" />Open {{ $current['label'] }}</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- The stages: one card per public page, showing how much of it has been changed. --}}
    @unless ($ready)
        <x-admin.empty-state icon="warn" title="Site content is not set up on this database yet">
            Its table is missing. Run this once on the server, then reload:
            <div style="margin-top: 10px"><code>php artisan migrate --path=database/cutover/2026_11_10_000001_create_site_contents_table.php --force</code></div>
        </x-admin.empty-state>
    @else
    <nav class="sc-stages" aria-label="Public pages">
        @foreach ($pages as $id => $stage)
            <button type="button" wire:key="stage-{{ $id }}" wire:click="$set('page', '{{ $id }}')" @class(['sc-stage', 'on' => $id === $page]) aria-current="{{ $id === $page ? 'page' : 'false' }}">
                <span class="ic"><x-admin.icon :name="$stage['icon']" /></span>
                <span class="tx"><b>{{ $stage['label'] }}</b>
                    @if ($mode === 'words')<small>@if ($wordsBy[$id] > 0)<em>{{ $wordsBy[$id] }} rewritten</em>@else Words @endif</small>
                    @else<small>{{ count($stage['slots']) }} {{ str('thing')->plural(count($stage['slots'])) }}@if ($changedBy[$id] > 0) · <em>{{ $changedBy[$id] }} changed</em>@endif</small>@endif
                </span>
            </button>
        @endforeach
        @if ($mode === 'words')
            <button type="button" wire:key="stage-sitewide" wire:click="$set('page', 'sitewide')" @class(['sc-stage', 'on' => $page === 'sitewide']) aria-current="{{ $page === 'sitewide' ? 'page' : 'false' }}">
                <span class="ic"><x-admin.icon name="menu" /></span>
                <span class="tx"><b>Menu, header &amp; footer</b><small>@if ($wordsBy['sitewide'] > 0)<em>{{ $wordsBy['sitewide'] }} rewritten</em>@else On every page @endif</small></span>
            </button>
        @endif
    </nav>

    @if ($mode === 'visual')
        <section class="sc-studio" wire:key="studio-{{ $page }}"
            x-data="siteStudio($wire)" x-on:message.window="onMessage($event)" x-on:site-changed.window="reload()">
            <div class="bar">
                <p class="muted" style="margin: 0"><b>{{ $current['label'] }}</b> · click any text on the page to type over it, or any picture to replace it. Every change is saved as you make it.</p>
                <div class="row-s">
                    <div class="seg" role="radiogroup" aria-label="Screen size">
                        <label><input type="radio" value="wide" wire:model.live="frame"><span>Computer</span></label>
                        <label><input type="radio" value="phone" wire:model.live="frame"><span>Phone</span></label>
                    </div>
                    <button type="button" class="btn btn-o s" x-on:click="reload()"><x-admin.icon name="log" class="i16" />Refresh</button>
                </div>
            </div>
            <div class="canvas {{ $frame }}">
                <div class="load" x-show="loading" x-cloak><x-admin.icon name="spark" />Opening the page…</div>
                <iframe x-ref="frame" data-url="{{ $current['url'] }}" src="{{ $editUrl }}" title="Editing {{ $current['label'] }}" x-on:load="loaded()"></iframe>
            </div>
        </section>
    @else
    <div class="sc-split">
        <div class="stack" style="min-width: 0">
            @if ($mode === 'words')
                <x-admin.site-words :$words :$current :$changedOnly />
            @else
            <p class="muted" style="margin: 0">{{ $current['blurb'] }}
                @if ($changedHere > 0)<span class="pill p-warn" style="margin-inline-start: 6px"><i></i>{{ $changedHere }} changed from the original</span>@endif
            </p>

            <div class="sc-grid">
                @foreach ($current['slots'] as $slot)
                    @php($id = str_replace('.', '__', $slot['key']))
                    <article wire:key="slot-{{ $id }}" @class(['sc-card', 'is-changed' => $slot['changed'], 'is-text' => in_array($slot['type'], ['text', 'long'], true)])
                             x-on:dragover.prevent="drag = '{{ $id }}'" x-on:dragleave="drag = null" x-on:drop="drag = null"
                             x-bind:class="{ 'is-drag': drag === '{{ $id }}' }">
                        <header>
                            <div><h3>{{ $slot['label'] }}</h3><p>{{ $slot['where'] }}</p></div>
                            @if ($slot['changed'])<span class="pill p-warn"><i></i>Changed</span>@else<span class="pill p-good"><i></i>Original</span>@endif
                        </header>

                        @if ($slot['type'] === 'image')
                            <label class="sc-pic{{ $slot['alpha'] ? ' alpha' : '' }}">
                                <img src="{{ $slot['value'] }}" alt="{{ $slot['label'] }}" loading="lazy">
                                <span class="over" wire:loading.remove wire:target="uploads.{{ $id }}"><x-admin.icon name="up" />Drop a picture or click to choose</span>
                                <span class="over busy" wire:loading wire:target="uploads.{{ $id }}"><x-admin.icon name="spark" />Saving…</span>
                                <input type="file" class="sr" wire:model="uploads.{{ $id }}" accept="image/jpeg,image/png,image/webp" aria-label="Choose a new picture for {{ $slot['label'] }}">
                            </label>
                        @elseif ($slot['type'] === 'video')
                            <label class="sc-pic">
                                <video src="{{ $slot['value'] }}" muted loop playsinline preload="metadata" x-on:mouseenter="$el.play()" x-on:mouseleave="$el.pause()"></video>
                                <span class="over" wire:loading.remove wire:target="uploads.{{ $id }}"><x-admin.icon name="video" />Drop an MP4 film or click to choose</span>
                                <span class="over busy" wire:loading wire:target="uploads.{{ $id }}"><x-admin.icon name="spark" />Uploading…</span>
                                <input type="file" class="sr" wire:model="uploads.{{ $id }}" accept="video/mp4" aria-label="Choose a new film for {{ $slot['label'] }}">
                            </label>
                        @else
                            <div class="sc-text">
                                <textarea class="ta" rows="{{ $slot['type'] === 'long' ? 5 : 2 }}" wire:model="texts.{{ $id }}" maxlength="1500" aria-label="{{ $slot['label'] }}"></textarea>
                                <div class="row-s">
                                    <button type="button" class="btn btn-p s" wire:click="saveText('{{ $id }}')" wire:loading.attr="disabled" wire:target="saveText('{{ $id }}')"><x-admin.icon name="check" />Save text</button>
                                    <small class="muted" x-text="($wire.texts['{{ $id }}'] ?? '').length + ' / 1500'"></small>
                                </div>
                            </div>
                        @endif

                        @if ($slot['changed'])
                            <footer>
                                <button type="button" class="btn btn-g s"
                                    x-on:click="$store.confirm.ask({ title: 'Back to the original?', body: 'The change is removed and the original is shown again.', action: 'Reset', cancel: @js(__('admin.confirm.cancel')), run: () => $wire.reset_slot('{{ $id }}') })">
                                    <x-admin.icon name="out" class="i16" />Reset to original
                                </button>
                            </footer>
                        @endif
                    </article>
                @endforeach
            </div>
            @if ($current['slots'] === [])<x-admin.empty-state icon="img" title="No pictures or films here yet">Everything on this page can still be reworded: switch to Words.</x-admin.empty-state>@endif
            @endif
        </div>

        {{-- The real page, live: it reloads after every change. --}}
        <aside class="sc-live" x-data="{ loading: true }" x-on:site-changed.window="loading = true; $refs.frame.src = $refs.frame.dataset.base + '?studio=' + Date.now()">
            <div class="bar">
                <b>Live view</b>
                <div class="seg" role="radiogroup" aria-label="Screen size">
                    <label><input type="radio" value="wide" wire:model.live="frame"><span>Computer</span></label>
                    <label><input type="radio" value="phone" wire:model.live="frame"><span>Phone</span></label>
                </div>
            </div>
            <div class="stage {{ $frame }}" wire:key="live-{{ $page }}">
                <div class="load" x-show="loading" x-cloak><x-admin.icon name="spark" />Loading the page…</div>
                <iframe x-ref="frame" data-base="{{ $current['url'] }}" src="{{ $previewUrl }}" title="Live view of {{ $current['label'] }}" x-on:load="loading = false" loading="lazy"></iframe>
            </div>
            @if ($frame === 'phone')<p class="muted" style="margin: 8px 0 0; font-size: 12px">Narrow like a phone. Phones also get their own cover pictures, which show on real phones.</p>@endif
        </aside>
    </div>
    @endif
@endunless
</div>
