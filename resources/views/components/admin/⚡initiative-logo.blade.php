<?php

use App\Actions\Admin\ManageInitiativeImages;
use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Models\Initiative;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * The logo of an initiative: whoever can open the initiative sees it; whoever can
 * edit it (the policy's update, as in legacy where the logo was a field of the edit
 * form) can add, replace or remove it. The work is ManageInitiativeImages; the
 * picture is checked by SecureUpload and re-encoded by PhotoFiles (ReceivesPictures),
 * keeping its see-through parts.
 */
new class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Locked]
    public int $initiativeId;

    public mixed $logo = null;

    public function mount(int $initiativeId): void
    {
        $this->initiativeId = $initiativeId;
        $this->authorize('view', $this->initiative());
    }

    protected function contentKind(): ContentKind
    {
        return ContentKind::Initiative;
    }

    /** Choosing a file is enough: it is checked and stored at once. */
    public function updatedLogo(): void
    {
        $initiative = $this->editable();

        if (! $this->acceptPictures('logo')) {
            return;
        }

        try {
            app(ManageInitiativeImages::class)->replaceLogo($initiative, $this->logo, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->reset('logo');
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->reset('logo');
        $this->alert('logo.saved', ['number' => $initiative->getKey()]);
    }

    public function remove(): void
    {
        $initiative = $this->editable();
        app(ManageInitiativeImages::class)->removeLogo($initiative, Auth::user());

        $this->alert('logo.removed', ['number' => $initiative->getKey()]);
    }

    private function initiative(): Initiative
    {
        return Initiative::query()->findOrFail($this->initiativeId);
    }

    /** Every change asks again: the buttons are only a convenience. */
    private function editable(): Initiative
    {
        $initiative = $this->initiative();
        $this->authorize('update', $initiative);

        return $initiative;
    }

    public function with(): array
    {
        $initiative = $this->initiative();

        return [
            'path' => $initiative->logo !== '0' ? $initiative->logo : null,
            'canEdit' => Auth::user()->can('update', $initiative),
        ];
    }
};
?>

<x-admin.panel title="Logo">
    @if ($canEdit)
        <x-slot:toolbar>
            <div class="row-s">
                <label class="btn btn-o s" style="cursor: pointer">
                    <x-admin.icon name="up" /><span wire:loading.remove wire:target="logo">{{ $path ? 'Replace' : 'Add logo' }}</span><span wire:loading wire:target="logo">Uploading…</span>
                    <input type="file" wire:model="logo" accept="image/jpeg,image/png,image/webp" class="sr" aria-label="Choose the logo">
                </label>
                @if ($path)
                    <button type="button" class="btn btn-g s"
                        x-on:click="$store.confirm.ask({ title: 'Remove the logo?', body: 'The logo file is deleted from the server.', action: @js(__('admin.confirm.photo_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove() })">
                        <x-admin.icon name="trash" class="i16" />Remove
                    </button>
                @endif
            </div>
        </x-slot:toolbar>
    @endif

    @if ($path)
        <div style="padding: 16px"><img src="{{ $path }}" alt="Logo" style="max-height: 120px; max-width: 100%; object-fit: contain"></div>
    @else
        <x-admin.empty-state icon="img" title="No logo">The logo is shown on the initiative's card.</x-admin.empty-state>
    @endif
</x-admin.panel>
