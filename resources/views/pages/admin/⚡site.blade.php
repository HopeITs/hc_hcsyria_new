<?php

use App\Actions\Admin\ManageSiteContent;
use App\Enums\AdminCapability;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Queries\AdminAccessQuery;
use App\Support\Site\SiteContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * /admin/site: the pictures, films and wording of the public pages, changed by staff
 * instead of in the code. A page of the public site is a "stage": its things (slots,
 * declared in config/site_content.php) are cards you drop a new file on or type into,
 * beside a live view of the real page that reloads after every change. The work is
 * ManageSiteContent; a picture is checked by SecureUpload and re-encoded by PhotoFiles
 * (ReceivesPictures), a film is checked by its bytes. Slot keys carry a dot, which a
 * Livewire property path cannot, so the form uses "__" in its place.
 */
new #[Layout('layouts::admin', ['title' => 'Site content'])] class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Url(history: true, except: 'home')]
    public string $page = 'home';

    #[Url(except: 'wide')]
    public string $frame = 'wide';

    /** @var array<string, mixed> chosen files, by slot (dots written as "__") */
    public array $uploads = [];

    /** @var array<string, string> text being written, by slot */
    public array $texts = [];

    /** Changes with every save, so the live view of the page reloads. */
    public int $stamp = 0;

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::ManageSite->value);

        if (! array_key_exists($this->page, (array) config('site_content.pages'))) {
            $this->page = 'home';
        }

        if (! in_array($this->frame, ['wide', 'phone'], true)) {
            $this->frame = 'wide';
        }

        $this->stamp = time();
        $this->loadTexts();
    }

    public function updatedPage(): void
    {
        $this->reset('uploads');
        $this->loadTexts();
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
        $current = $pages[$this->page];
        $changed = collect($current['slots'])->where('changed', true)->count();

        return [
            'pages' => $pages,
            'current' => $current,
            'changedHere' => $changed,
            'changedBy' => collect($pages)->map(fn (array $p): int => collect($p['slots'])->where('changed', true)->count())->all(),
            'previewUrl' => $current['url'].'?studio='.$this->stamp,
        ];
    }
};
?>

<div class="stack" x-data="{ drag: null }">
    <x-admin.page-header title="Site content" subtitle="Change the pictures, films and wording of the public pages. Nothing here touches the original: reset any card to bring it back.">
        <x-slot:actions>
            <a href="{{ $current['url'] }}" target="_blank" rel="noopener" class="btn btn-o"><x-admin.icon name="ext" />Open {{ $current['label'] }}</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- The stages: one card per public page, showing how much of it has been changed. --}}
    <nav class="sc-stages" aria-label="Public pages">
        @foreach ($pages as $id => $stage)
            <button type="button" wire:key="stage-{{ $id }}" wire:click="$set('page', '{{ $id }}')" @class(['sc-stage', 'on' => $id === $page]) aria-current="{{ $id === $page ? 'page' : 'false' }}">
                <span class="ic"><x-admin.icon :name="$stage['icon']" /></span>
                <span class="tx"><b>{{ $stage['label'] }}</b><small>{{ count($stage['slots']) }} {{ str('thing')->plural(count($stage['slots'])) }}@if ($changedBy[$id] > 0) · <em>{{ $changedBy[$id] }} changed</em>@endif</small></span>
            </button>
        @endforeach
    </nav>

    <div class="sc-split">
        <div class="stack" style="min-width: 0">
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
</div>
