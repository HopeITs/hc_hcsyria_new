<?php

use App\Actions\Admin\ManageNewsPictures;
use App\Actions\Admin\SetNewsPriority;
use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Models\News;
use App\Queries\AdminNewsQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * The single pictures of a newsletter item and its status: the thumbnail of its small
 * card, the Important picture of the lead story and the Pinned picture of the wide card,
 * and whether the item is Regular, Important or Pinned. Whoever can open the item sees
 * them; whoever manages photos (the policy's managePhotos, as in legacy where all of this
 * was on the pictures page) can change them. The work is ManageNewsPictures and
 * SetNewsPriority; each picture is checked by SecureUpload and cut to its card's proportions
 * (ReceivesPictures). The gallery is the shared content-photos component.
 */
new class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Locked]
    public int $newsId;

    public mixed $thumbnail = null;

    public mixed $important = null;

    public mixed $pinned = null;

    public function mount(int $newsId): void
    {
        $this->newsId = $newsId;
        $this->authorize('view', $this->news());
    }

    protected function contentKind(): ContentKind
    {
        return ContentKind::News;
    }

    public function updatedThumbnail(): void
    {
        $this->store(ManageNewsPictures::THUMBNAIL, 'thumbnail');
    }

    public function updatedImportant(): void
    {
        $this->store(ManageNewsPictures::IMPORTANT, 'important');
    }

    public function updatedPinned(): void
    {
        $this->store(ManageNewsPictures::PINNED, 'pinned');
    }

    /** Choosing a file is enough: it is checked and stored at once. */
    private function store(int $sizetype, string $property): void
    {
        $news = $this->managed();

        if (! $this->acceptPictures($property)) {
            return;
        }

        try {
            app(ManageNewsPictures::class)->replace($news, $sizetype, $this->{$property}, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        } finally {
            $this->reset($property);
        }

        $this->alert('news_pictures.saved', ['label' => ManageNewsPictures::TYPES[$sizetype][0], 'number' => $news->getKey()]);
        $this->dispatch('photos-changed');
    }

    public function remove(int $sizetype): void
    {
        $news = $this->managed();
        app(ManageNewsPictures::class)->remove($news, $sizetype, Auth::user());

        $this->alert('news_pictures.removed', ['label' => ManageNewsPictures::TYPES[$sizetype][0] ?? 'Picture', 'number' => $news->getKey()]);
        $this->dispatch('photos-changed');
    }

    public function setPriority(int $status): void
    {
        $news = $this->managed();
        app(SetNewsPriority::class)->handle($news, $status, Auth::user());

        $this->alert('news_pictures.priority', ['priority' => SetNewsPriority::label($status), 'number' => $news->getKey()]);
    }

    private function news(): News
    {
        return News::query()->findOrFail($this->newsId);
    }

    /** Every change asks again: the buttons are only a convenience. */
    private function managed(): News
    {
        $news = $this->news();
        $this->authorize('managePhotos', $news);

        return $news;
    }

    public function with(): array
    {
        $news = $this->news();

        return [
            'singles' => AdminNewsQuery::singles($this->newsId),
            'priority' => (int) $news->priority_status,
            'canManage' => Auth::user()->can('managePhotos', $news),
            'types' => ManageNewsPictures::TYPES,
            'statuses' => [SetNewsPriority::REGULAR, SetNewsPriority::IMPORTANT, SetNewsPriority::PINNED],
        ];
    }
};
?>

<x-admin.panel title="Cover pictures and status">
    <div style="padding: 16px; display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr))">
        @foreach ($types as $sizetype => [$label, , $box])
            @php $path = $singles[$sizetype] ?? null; $property = [0 => 'thumbnail', 1 => 'important', 2 => 'pinned'][$sizetype]; @endphp
            <div wire:key="single-{{ $sizetype }}" class="stack-s">
                <b style="color: var(--s-dark)">{{ $label }}</b>
                <div class="ph" style="background: var(--head); aspect-ratio: {{ $box[0] }} / {{ $box[1] }}">
                    @if ($path)<img src="{{ $path }}" alt="{{ $label }}" loading="lazy">@else<x-admin.icon name="img" />@endif
                </div>
                @if ($canManage)
                    <div class="row-s">
                        <label class="btn btn-o s" style="cursor: pointer">
                            <x-admin.icon name="up" /><span wire:loading.remove wire:target="{{ $property }}">{{ $path ? 'Replace' : 'Add' }}</span><span wire:loading wire:target="{{ $property }}">Uploading…</span>
                            <input type="file" wire:model="{{ $property }}" accept="image/jpeg,image/png,image/webp" class="sr" aria-label="Choose the {{ strtolower($label) }}">
                        </label>
                        @if ($path)
                            <button type="button" class="btn btn-g s"
                                x-on:click="$store.confirm.ask({ title: @js('Remove the '.strtolower($label).'?'), body: 'The picture file is deleted from the server.', action: @js(__('admin.confirm.photo_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $sizetype }}) })">
                                <x-admin.icon name="trash" class="i16" />Remove
                            </button>
                        @endif
                    </div>
                @endif
                <span class="help">{{ [0 => 'Square, shown on the small cards.', 1 => 'Wide, 3 : 2, for the lead story.', 2 => 'Very wide, 2 : 1, for the pinned card.'][$sizetype] }}</span>
            </div>
        @endforeach
    </div>

    <div style="padding: 14px 16px; border-top: 1px solid var(--line)">
        <div class="lbl" style="margin-bottom: 6px">Status on the news page</div>
        <div class="seg" role="radiogroup">
            @foreach ($statuses as $status)
                <label>
                    <input type="radio" name="priority-{{ $newsId }}" value="{{ $status }}" @checked($priority === $status) @disabled(! $canManage) wire:click="setPriority({{ $status }})">
                    <span>{{ \App\Actions\Admin\SetNewsPriority::label($status) }}</span>
                </label>
            @endforeach
        </div>
        <div class="help" style="margin-top: 6px">Only one online item is the Important one: making another item Important makes this one Regular. Removing the Important or Pinned picture makes the item Regular again.</div>
    </div>
</x-admin.panel>
