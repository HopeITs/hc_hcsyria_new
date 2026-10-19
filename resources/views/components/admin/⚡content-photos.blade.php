<?php

use App\Actions\Admin\ManageContentPhotos;
use App\Enums\ContentKind;
use App\Livewire\Concerns\ReceivesPictures;
use App\Queries\AdminPhotoQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * The photos panel of a project or an initiative: everyone who can open the item
 * sees its photos; whoever manages photos (the policy's managePhotos) can also
 * add, move, make a cover of, and remove them. Replaces legacy's add-image pages.
 * The work is ManageContentPhotos; the pictures are checked by SecureUpload and
 * re-encoded by PhotoFiles (ReceivesPictures). The item's own page is told when the
 * photos change (`photos-changed`) so its checklist follows.
 */
new class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Locked]
    public string $kind;

    #[Locked]
    public int $itemId;

    /** @var array<int, mixed> */
    public array $uploads = [];

    public function mount(string $kind, int $itemId): void
    {
        $this->kind = ContentKind::from($kind)->value;
        $this->itemId = $itemId;
        $this->authorize('view', $this->item());
    }

    protected function contentKind(): ContentKind
    {
        return ContentKind::from($this->kind);
    }

    /** Choosing files is enough: they are checked and stored at once. */
    public function updatedUploads(): void
    {
        $item = $this->managedItem();

        if (! $this->acceptPictures('uploads', many: true)) {
            return;
        }

        $result = app(ManageContentPhotos::class)->add($item, array_values($this->uploads), Auth::user());
        $this->reset('uploads');

        $this->alert($result['offline'] ? 'photos.added_offline' : 'photos.added', ['count' => $result['added'], 'number' => $this->contentKind()->number($item)]);
        $this->dispatch('photos-changed');
    }

    public function remove(int $imageId): void
    {
        $item = $this->managedItem();
        $offline = app(ManageContentPhotos::class)->remove($item, $imageId, Auth::user());

        $this->alert($offline ? 'photos.removed_offline' : 'photos.removed', ['number' => $this->contentKind()->number($item)]);
        $this->dispatch('photos-changed');
    }

    public function move(int $imageId, int $offset): void
    {
        app(ManageContentPhotos::class)->move($this->managedItem(), $imageId, $offset < 0 ? -1 : 1);
    }

    public function makeCover(int $imageId): void
    {
        app(ManageContentPhotos::class)->makeCover($this->managedItem(), $imageId);
    }

    private function item(): Model
    {
        return $this->contentKind()->modelClass()::query()->findOrFail($this->itemId);
    }

    /** Every change asks again: the buttons are only a convenience. */
    private function managedItem(): Model
    {
        $item = $this->item();
        $this->authorize('managePhotos', $item);

        return $item;
    }

    public function with(): array
    {
        $item = $this->item();

        return [
            'photos' => AdminPhotoQuery::gallery($this->contentKind(), $this->itemId),
            'canManage' => Auth::user()->can('managePhotos', $item),
            'warnOffline' => $this->contentKind()->photosChangeTakesOffline() && (string) $item->MPE_Published === '1',
            'number' => $this->contentKind()->number($item),
            'noun' => $this->contentKind()->noun(),
        ];
    }
};
?>

<x-admin.panel title="Photos" :count="$photos->count()">
    @if ($canManage)
        <x-slot:toolbar>
            <label class="btn btn-o s" style="cursor: pointer">
                <x-admin.icon name="up" /><span wire:loading.remove wire:target="uploads">Add photos</span><span wire:loading wire:target="uploads">Uploading…</span>
                <input type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp" class="sr" aria-label="Choose photos to add">
            </label>
        </x-slot:toolbar>
        @if ($warnOffline)
            <div class="note warn" style="margin: 12px 16px 0"><x-admin.icon name="warn" /><div>This {{ $noun }} is online. Changing its photos sends it back to the pending list to be published again.</div></div>
        @endif
    @endif

    @if ($photos->isEmpty())
        <x-admin.empty-state icon="img" title="No photos yet">{{ $noun === 'project' ? 'A project needs at least one before it can go online.' : 'Add the photos shown on the initiative\'s page.' }}</x-admin.empty-state>
    @else
        <div class="gal">
            @foreach ($photos as $photo)
                <div class="ph" style="background: var(--head)" wire:key="photo-{{ $photo->id }}">
                    <img src="{{ $photo->path }}" alt="Photo {{ $loop->iteration }} of {{ $noun }} #{{ $number }}" loading="lazy">
                    @if ($loop->first)<span class="tg">Cover</span>@endif
                    @if ($canManage)
                        <div class="tools">
                            <button type="button" wire:click="move({{ $photo->id }}, -1)" @disabled($loop->first) aria-label="Move earlier"><x-admin.icon name="chev" class="i16" /></button>
                            <button type="button" wire:click="move({{ $photo->id }}, 1)" @disabled($loop->last) aria-label="Move later"><x-admin.icon name="chev" class="i16" style="transform: scaleX(-1)" /></button>
                            @unless ($loop->first)<button type="button" wire:click="makeCover({{ $photo->id }})">Cover</button>@endunless
                            <button type="button" class="del" aria-label="Remove this photo"
                                x-on:click="$store.confirm.ask({ title: @js(__('admin.confirm.photo_title')), body: @js(__('admin.confirm.photo_body')), action: @js(__('admin.confirm.photo_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $photo->id }}) })"><x-admin.icon name="trash" class="i16" /></button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-admin.panel>
