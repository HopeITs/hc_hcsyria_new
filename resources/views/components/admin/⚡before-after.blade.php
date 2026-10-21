<?php

use App\Actions\Admin\ManageInitiativeImages;
use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Models\Initiative;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * The before-and-after pairs of a rehabilitation initiative: whoever can open the
 * initiative sees them; whoever manages photos can add a pair (both pictures at
 * once) or remove one. Replaces legacy's rehabilitation images page. The work is
 * ManageInitiativeImages; the pictures are checked by SecureUpload and cut to one
 * size so the slider on the public page lines up.
 */
new class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Locked]
    public int $initiativeId;

    public mixed $before = null;

    public mixed $after = null;

    public function mount(int $initiativeId): void
    {
        $this->initiativeId = $initiativeId;
        $this->authorize('view', $this->initiative());
    }

    protected function contentKind(): ContentKind
    {
        return ContentKind::Initiative;
    }

    public function add(): void
    {
        $initiative = $this->managed();

        if (! $this->acceptPictures('before') || ! $this->acceptPictures('after')) {
            $this->reset('before', 'after');

            return;
        }

        try {
            app(ManageInitiativeImages::class)->addPair($initiative, $this->before, $this->after, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        } finally {
            $this->reset('before', 'after');
        }

        $this->alert('pairs.added', ['number' => $initiative->getKey()]);
    }

    public function remove(int $pairId): void
    {
        $initiative = $this->managed();
        app(ManageInitiativeImages::class)->removePair($initiative, $pairId, Auth::user());

        $this->alert('pairs.removed', ['number' => $initiative->getKey()]);
    }

    private function initiative(): Initiative
    {
        return Initiative::query()->findOrFail($this->initiativeId);
    }

    private function managed(): Initiative
    {
        $initiative = $this->initiative();
        $this->authorize('managePhotos', $initiative);

        return $initiative;
    }

    public function with(): array
    {
        return [
            'pairs' => DB::table('initiative_rehabilitation_images')->where('initiative_id', $this->initiativeId)->orderBy('id')->get(['id', 'path_1', 'path_2']),
            'canManage' => Auth::user()->can('managePhotos', $this->initiative()),
        ];
    }
};
?>

<x-admin.panel title="Before and after" :count="$pairs->count()">
    @if ($canManage)
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--line)">
            <form wire:submit="add" class="bar">
                <div class="field"><label for="f-before">Before</label><input id="f-before" type="file" class="inp" wire:model="before" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 6px 8px"></div>
                <div class="field"><label for="f-after">After</label><input id="f-after" type="file" class="inp" wire:model="after" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 6px 8px"></div>
                <button type="submit" class="btn btn-o" @disabled(! $before || ! $after) wire:loading.attr="disabled">
                    <x-admin.icon name="plus" /><span wire:loading.remove wire:target="add">Add the pair</span><span wire:loading wire:target="add">Saving…</span>
                </button>
            </form>
        </div>
    @endif

    @if ($pairs->isEmpty())
        <x-admin.empty-state icon="img" title="No before and after pictures">A pair shows the place before and after the rehabilitation.</x-admin.empty-state>
    @else
        <div class="gal" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
            @foreach ($pairs as $pair)
                <div wire:key="pair-{{ $pair->id }}" style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; position: relative">
                    <div class="ph" style="background: var(--head)"><img src="{{ $pair->path_1 }}" alt="Before" loading="lazy"><span class="tg">Before</span></div>
                    <div class="ph" style="background: var(--head)"><img src="{{ $pair->path_2 }}" alt="After" loading="lazy"><span class="tg">After</span></div>
                    @if ($canManage)
                        <button type="button" class="btn btn-g s" style="grid-column: 1 / -1"
                            x-on:click="$store.confirm.ask({ title: 'Remove this pair?', body: 'Both pictures are deleted from the server.', action: @js(__('admin.confirm.photo_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $pair->id }}) })">
                            <x-admin.icon name="trash" class="i16" />Remove the pair
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-admin.panel>
