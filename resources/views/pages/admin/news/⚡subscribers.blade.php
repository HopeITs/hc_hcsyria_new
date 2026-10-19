<?php

use App\Actions\Admin\DeleteSubscriber;
use App\Enums\AdminCapability;
use App\Livewire\Concerns\SendsAlerts;
use App\Queries\AdminNewsListsQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/news/subscribers: the addresses on the newsletter list. Their owners did not
 * ask to be shown to everyone: only the Admin and the Publisher see the list, and only the
 * Admin removes an address. Searchable, a page at a time, the search in the address.
 * Replaces the Subscribers tab of the legacy news page. Sending the newsletter is not part
 * of this page. The work is DeleteSubscriber.
 */
new #[Layout('layouts::admin', ['title' => 'Newsletter subscribers'])] class extends Component
{
    use SendsAlerts;
    use WithPagination;

    #[Url(history: true, except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::ViewSubscribers->value);
        $this->search = mb_substr(trim($this->search), 0, 100);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function remove(int $id): void
    {
        $this->authorize('admin.'.AdminCapability::DeleteSubscribers->value);

        app(DeleteSubscriber::class)->handle($id, Auth::user())
            ? $this->alert('subscribers.deleted')
            : $this->alert('subscribers.gone', type: 'error');
    }

    public function with(): array
    {
        return [
            'subscribers' => AdminNewsListsQuery::subscribers($this->search),
            'total' => AdminNewsListsQuery::subscriberCount(),
            'canDelete' => Auth::user()->can('admin.'.AdminCapability::DeleteSubscribers->value),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Newsletter" :count="number_format($total).' subscribers'" subtitle="The addresses that receive the newsletter." />
    <x-admin.news-nav />

    <x-admin.panel title="Subscribers" :count="number_format($subscribers->total())">
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--line)">
            <div class="field" style="max-width: 26rem">
                <label for="f-search">Search</label>
                <div class="inwrap"><x-admin.icon name="search" class="i16" /><input id="f-search" type="search" class="inp" wire:model.live.debounce.300ms="search" placeholder="Part of an address" maxlength="100"></div>
            </div>
        </div>

        @if ($subscribers->isEmpty())
            <x-admin.empty-state icon="search" title="No subscribers match">{{ $search !== '' ? 'Try another part of the address.' : 'Nobody has subscribed yet.' }}</x-admin.empty-state>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>Address</th><th>Status</th><th class="hide-m">Subscribed</th><th style="text-align: end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($subscribers as $subscriber)
                            <tr wire:key="subscriber-{{ $subscriber->id }}" style="cursor: default">
                                <td class="nm">{{ $subscriber->mail }}</td>
                                <td><x-admin.status-pill :tone="$subscriber->active ? 'good' : 'grey'" :label="$subscriber->active ? 'Active' : 'Inactive'" /></td>
                                <td class="hide-m tnum">{{ \Illuminate\Support\Carbon::parse($subscriber->created_at)->format('j M Y') }}</td>
                                <td class="acell">
                                    @if ($canDelete)
                                        <div class="acts"><button type="button" class="btn btn-g s" aria-label="Remove {{ $subscriber->mail }}"
                                            x-on:click="$store.confirm.ask({ title: 'Remove this subscriber?', body: @js($subscriber->mail.' stops receiving the newsletter.'), action: 'Remove', cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $subscriber->id }}) })"><x-admin.icon name="trash" class="i16" /></button></div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$subscribers" noun="subscribers" />
    </x-admin.panel>
</div>
