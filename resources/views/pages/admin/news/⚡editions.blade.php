<?php

use App\Actions\Admin\ManageEditions;
use App\Enums\AdminCapability;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\SendsAlerts;
use App\Queries\AdminNewsListsQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

/*
 * /admin/news/editions: the magazine editions a newsletter item can belong to.
 * Anyone who can open the newsletter sees them with how many items belong to each;
 * whoever edits can make one (the English Editor and the Admin), whoever publishes
 * can put one online or offline (the Publisher and the Admin), and only the Admin
 * deletes one. Replaces the editions page of the legacy admin. The work is ManageEditions.
 */
new #[Layout('layouts::admin', ['title' => 'Newsletter editions'])] class extends Component
{
    use SendsAlerts;

    public string $name = '';

    public string $nameFrench = '';

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::View->value);
    }

    public function add(): void
    {
        $this->authorize('admin.'.AdminCapability::Edit->value);
        $this->validate(['name' => ['required', 'string', 'max:50'], 'nameFrench' => ['nullable', 'string', 'max:50']], attributes: ['name' => 'name', 'nameFrench' => 'French name']);

        try {
            app(ManageEditions::class)->create($this->name, $this->nameFrench, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert('editions.created', ['name' => trim($this->name)]);
        $this->reset('name', 'nameFrench');
    }

    public function setOnline(int $id, bool $online): void
    {
        $this->authorize('admin.'.AdminCapability::Publish->value);
        app(ManageEditions::class)->setOnline($id, $online, Auth::user());

        $this->alert($online ? 'editions.online' : 'editions.offline', ['name' => (string) DB::table('editions')->where('id', $id)->value('name')]);
    }

    public function remove(int $id): void
    {
        $this->authorize('admin.'.AdminCapability::Delete->value);
        $name = (string) DB::table('editions')->where('id', $id)->value('name');

        try {
            app(ManageEditions::class)->delete($id, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert('editions.deleted', ['name' => $name]);
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'editions' => AdminNewsListsQuery::editions(),
            'canEdit' => $user->can('admin.'.AdminCapability::Edit->value),
            'canPublish' => $user->can('admin.'.AdminCapability::Publish->value),
            'canDelete' => $user->can('admin.'.AdminCapability::Delete->value),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Newsletter" :count="number_format($editions->count()).' editions'" subtitle="The magazine editions newsletter items belong to." />
    <x-admin.news-nav />

    <x-admin.panel title="Editions" :count="$editions->count()">
        @if ($canEdit)
            <form wire:submit="add" class="bar" style="padding: 14px 16px; border-bottom: 1px solid var(--line)">
                <x-admin.field label="Name" name="name" style="max-width: 18rem"><input id="f-name" class="inp" wire:model="name" maxlength="50" placeholder="7th-edition"></x-admin.field>
                <x-admin.field label="French name" name="nameFrench" style="max-width: 18rem"><input id="f-nameFrench" class="inp" wire:model="nameFrench" maxlength="50" placeholder="7ème-édition"></x-admin.field>
                <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="add"><x-admin.icon name="plus" />Add edition</button>
            </form>
        @endif

        @forelse ($editions as $edition)
            <div class="q" wire:key="edition-{{ $edition->id }}" style="grid-template-columns: minmax(0, 1fr) auto">
                <div>
                    <span class="nm">{{ $edition->name }}</span>
                    @if ($edition->name_french !== '')<span class="muted"> · {{ $edition->name_french }}</span>@endif
                    <div class="mt"><span>{{ $edition->news }} {{ \Illuminate\Support\Str::plural('item', $edition->news) }}</span></div>
                </div>
                <div class="acts">
                    <x-admin.status-pill :tone="$edition->online ? 'good' : 'grey'" :label="$edition->online ? 'Online' : 'Offline'" />
                    @if ($canPublish)
                        <button type="button" class="btn btn-o s" wire:click="setOnline({{ $edition->id }}, {{ $edition->online ? 'false' : 'true' }})">{{ $edition->online ? 'Take offline' : 'Put online' }}</button>
                    @endif
                    @if ($canDelete)
                        <button type="button" class="btn btn-g s" aria-label="Delete edition {{ $edition->name }}"
                            x-on:click="$store.confirm.ask({ title: @js('Delete “'.$edition->name.'”?'), body: @js($edition->news > 0 ? 'It belongs to '.$edition->news.' news items, so it cannot be deleted until they are taken off it.' : 'No news item belongs to it.'), action: @js(__('admin.confirm.delete_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $edition->id }}) })"><x-admin.icon name="trash" class="i16" /></button>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="news" title="No editions yet">An edition groups the items of one issue of the magazine.</x-admin.empty-state>
        @endforelse
    </x-admin.panel>
</div>
