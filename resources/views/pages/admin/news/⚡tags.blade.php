<?php

use App\Actions\Admin\ManageTags;
use App\Enums\AdminCapability;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\SendsAlerts;
use App\Queries\AdminNewsListsQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/news/tags: the tags newsletter items carry. Anyone who can open the
 * newsletter sees them with how many items use each; whoever edits can make and
 * rename them (the English Editor and the Admin); only the Admin deletes one.
 * Replaces the Tags tab of the legacy news page. The work is ManageTags.
 */
new #[Layout('layouts::admin', ['title' => 'Newsletter tags'])] class extends Component
{
    use SendsAlerts;

    public string $newName = '';

    #[Locked]
    public ?int $editingId = null;

    public string $editName = '';

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::View->value);
    }

    public function add(): void
    {
        $this->authorize('admin.'.AdminCapability::Edit->value);
        $this->validate(['newName' => ['required', 'string', 'max:50']], attributes: ['newName' => 'tag']);

        try {
            app(ManageTags::class)->create($this->newName, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert('tags.created', ['name' => trim($this->newName)]);
        $this->reset('newName');
    }

    public function startEdit(int $id, string $name): void
    {
        $this->authorize('admin.'.AdminCapability::Edit->value);
        $this->fill(['editingId' => $id, 'editName' => $name]);
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'editName');
        $this->resetErrorBag();
    }

    public function saveEdit(): void
    {
        $this->authorize('admin.'.AdminCapability::Edit->value);
        $this->validate(['editName' => ['required', 'string', 'max:50']], attributes: ['editName' => 'tag']);

        try {
            app(ManageTags::class)->rename((int) $this->editingId, $this->editName, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert('tags.renamed', ['name' => trim($this->editName)]);
        $this->cancel();
    }

    public function remove(int $id, string $name): void
    {
        $this->authorize('admin.'.AdminCapability::Delete->value);
        app(ManageTags::class)->delete($id, Auth::user());

        $this->alert('tags.deleted', ['name' => $name]);
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'tags' => AdminNewsListsQuery::tags(),
            'canEdit' => $user->can('admin.'.AdminCapability::Edit->value),
            'canDelete' => $user->can('admin.'.AdminCapability::Delete->value),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Newsletter" :count="number_format($tags->count()).' tags'" subtitle="The tags readers use to find newsletter items." />
    <x-admin.news-nav />

    <x-admin.panel title="Tags" :count="$tags->count()">
        @if ($canEdit)
            <form wire:submit="add" class="bar" style="padding: 14px 16px; border-bottom: 1px solid var(--line)">
                <x-admin.field label="New tag" name="newName" style="max-width: 24rem"><input id="f-newName" class="inp" wire:model="newName" maxlength="50" placeholder="A tag people will look for"></x-admin.field>
                <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="add"><x-admin.icon name="plus" />Add tag</button>
            </form>
        @endif

        @forelse ($tags as $tag)
            <div class="q" wire:key="tag-{{ $tag->id }}" style="grid-template-columns: minmax(0, 1fr) auto">
                @if ($editingId === $tag->id)
                    <form wire:submit="saveEdit" class="row-s">
                        <input class="inp" style="max-width: 22rem" wire:model="editName" maxlength="50" aria-label="Tag name" autofocus>
                        <button type="submit" class="btn btn-p s"><x-admin.icon name="check" />Save</button>
                        <button type="button" class="btn btn-g s" wire:click="cancel">Cancel</button>
                        @error('editName')<span class="err" style="display: inline">{{ $message }}</span>@enderror
                    </form>
                @else
                    <div><span class="nm">{{ $tag->name }}</span> <span class="muted">· {{ $tag->news }} {{ \Illuminate\Support\Str::plural('item', $tag->news) }}</span></div>
                @endif
                @if ($editingId !== $tag->id)
                    <div class="acts">
                        @if ($canEdit)<button type="button" class="btn btn-o s" wire:click="startEdit({{ $tag->id }}, @js($tag->name))"><x-admin.icon name="edit" />Rename</button>@endif
                        @if ($canDelete)
                            <button type="button" class="btn btn-g s" aria-label="Delete tag {{ $tag->name }}"
                                x-on:click="$store.confirm.ask({ title: @js('Delete the tag “'.$tag->name.'”?'), body: @js($tag->news > 0 ? 'It is taken off its '.$tag->news.' news '.\Illuminate\Support\Str::plural('item', $tag->news).'.' : 'No news item uses it.'), action: @js(__('admin.confirm.delete_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $tag->id }}, @js($tag->name)) })"><x-admin.icon name="trash" class="i16" /></button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <x-admin.empty-state icon="news" title="No tags yet">Tags are made here or typed into a newsletter item.</x-admin.empty-state>
        @endforelse
    </x-admin.panel>
</div>
