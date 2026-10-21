<?php

use App\Actions\Admin\ManageTestimonies;
use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Models\Initiative;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * The testimonies of an initiative (a person's picture, name and words): whoever
 * can open the initiative reads them; whoever can edit it (the policy's update, as
 * in legacy) can add, change and remove them. Replaces legacy's testimonies page.
 * Only the programmes legacy allowed them for have any (ContentLookups::TESTIMONY_CATEGORIES);
 * the work is ManageTestimonies, the picture is checked by SecureUpload.
 */
new class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    #[Locked]
    public int $initiativeId;

    /** The testimony being changed; null while adding a new one. */
    #[Locked]
    public ?int $editingId = null;

    public bool $open = false;

    public string $name = '';

    public string $text = '';

    public mixed $picture = null;

    public function mount(int $initiativeId): void
    {
        $initiative = Initiative::query()->findOrFail($initiativeId);
        abort_unless(in_array($initiative->what_category_id, ContentLookups::TESTIMONY_CATEGORIES, true), 404);

        $this->authorize('view', $initiative);
        $this->initiativeId = $initiativeId;
    }

    protected function contentKind(): ContentKind
    {
        return ContentKind::Initiative;
    }

    public function add(): void
    {
        $this->editable();
        $this->reset('editingId', 'name', 'text', 'picture');
        $this->resetErrorBag();
        $this->open = true;
    }

    public function edit(int $testimonyId): void
    {
        $this->editable();
        $row = DB::table('testimonies')->join('testimony_contents', 'testimony_contents.testimony_id', '=', 'testimonies.id')
            ->where('testimonies.initiative_id', $this->initiativeId)->where('testimonies.id', $testimonyId)
            ->first(['testimonies.id', 'testimony_contents.name', 'testimony_contents.text']);

        if ($row === null) {
            $this->alert('refusals.testimony_gone', type: 'error');

            return;
        }

        $this->resetErrorBag();
        $this->reset('picture');
        $this->fill(['editingId' => $row->id, 'name' => $row->name, 'text' => $row->text, 'open' => true]);
    }

    public function cancel(): void
    {
        $this->reset('open', 'editingId', 'name', 'text', 'picture');
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $initiative = $this->editable();

        $this->validate([
            'name' => ['required', 'string', 'max:70'],
            'text' => ['required', 'string', 'max:5000'],
        ]);

        if ($this->picture !== null && ! $this->acceptPictures('picture')) {
            return;
        }

        if ($this->editingId === null && $this->picture === null) {
            $this->addError('picture', 'Choose a picture of the person.');
            $this->alert('invalid', type: 'error');

            return;
        }

        try {
            app(ManageTestimonies::class)->save($initiative, $this->editingId, ['name' => trim($this->name), 'text' => trim($this->text)], $this->picture, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert($this->editingId === null ? 'testimonies.created' : 'testimonies.saved', ['number' => $initiative->getKey()]);
        $this->cancel();
    }

    public function remove(int $testimonyId): void
    {
        $initiative = $this->editable();
        app(ManageTestimonies::class)->delete($initiative, $testimonyId, Auth::user());

        $this->alert('testimonies.deleted', ['number' => $initiative->getKey()]);
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
        return [
            'testimonies' => DB::table('testimonies')->join('testimony_contents', 'testimony_contents.testimony_id', '=', 'testimonies.id')
                ->where('testimonies.initiative_id', $this->initiativeId)->orderBy('testimonies.id')
                ->get(['testimonies.id', 'testimonies.image_path', 'testimony_contents.name', 'testimony_contents.text']),
            'canEdit' => Auth::user()->can('update', $this->initiative()),
        ];
    }
};
?>

<x-admin.panel title="Testimonies" :count="$testimonies->count()">
    @if ($canEdit)
        <x-slot:toolbar><button type="button" class="btn btn-o s" wire:click="add"><x-admin.icon name="plus" />Add a testimony</button></x-slot:toolbar>
    @endif

    @if ($open)
        <form wire:submit="save" class="fs-f" style="padding: 16px; border-bottom: 1px solid var(--line)" novalidate>
            <x-admin.field label="Name" name="name" required><input id="f-name" class="inp" wire:model="name" maxlength="70"></x-admin.field>
            <x-admin.field label="Picture" name="picture" :required="$editingId === null" :help="$editingId === null ? null : 'Leave empty to keep the current picture.'">
                <input id="f-picture" type="file" class="inp" wire:model="picture" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 6px 8px">
            </x-admin.field>
            <x-admin.field label="Words" name="text" required full><textarea id="f-text" class="ta" wire:model="text" maxlength="5000"></textarea></x-admin.field>
            <div class="full row-s" style="justify-content: flex-end">
                <button type="button" class="btn btn-o" wire:click="cancel">Cancel</button>
                <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save"><x-admin.icon name="check" />{{ $editingId === null ? 'Add' : 'Save' }}</button>
            </div>
        </form>
    @endif

    @forelse ($testimonies as $testimony)
        <div class="q" wire:key="testimony-{{ $testimony->id }}" style="grid-template-columns: 56px minmax(0, 1fr) auto; align-items: start">
            <img src="{{ $testimony->image_path }}" alt="" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; background: var(--head)" loading="lazy">
            <div><div class="nm">{{ $testimony->name }}</div><div class="muted" style="white-space: pre-line">{{ $testimony->text }}</div></div>
            @if ($canEdit)
                <div class="acts">
                    <button type="button" class="btn btn-o s" wire:click="edit({{ $testimony->id }})"><x-admin.icon name="edit" />Edit</button>
                    <button type="button" class="btn btn-g s" aria-label="Remove this testimony"
                        x-on:click="$store.confirm.ask({ title: 'Remove this testimony?', body: 'Its picture is deleted from the server.', action: @js(__('admin.confirm.photo_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $testimony->id }}) })"><x-admin.icon name="trash" class="i16" /></button>
                </div>
            @endif
        </div>
    @empty
        <x-admin.empty-state icon="users" title="No testimonies yet">What the people it served say about the initiative.</x-admin.empty-state>
    @endforelse
</x-admin.panel>
