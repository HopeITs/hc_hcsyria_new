<?php

use App\Enums\AdminCapability;
use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\RootsOfHope;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminRootsQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/roots: the editions of the Roots of Hope magazine, for staff. Replaces
 * legacy's two tables (online and pending). The same list as the other sections' (shared
 * tabs, filters, selection and buttons), over the editions. Search and page are in the address.
 */
new #[Layout('layouts::admin', ['title' => 'Roots of Hope'])] class extends Component
{
    use ManagesContent;
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    protected function contentKind(): ContentKind
    {
        return ContentKind::Roots;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', RootsOfHope::class);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminRootsQuery::TABS, true) ? $this->tab : 'all';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function with(): array
    {
        $user = Auth::user();
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);

        return [
            'rows' => AdminRootsQuery::make()->search($this->search)->tab($this->tab)->paginate(self::PER_PAGE),
            'counts' => AdminRootsQuery::make()->search($this->search)->counts(),
            'moves' => array_keys(array_filter(['publish' => $may(AdminCapability::Publish)])),
            'create' => $may(AdminCapability::Create),
            'filtered' => $this->search !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Roots of Hope" :count="number_format($counts['all'])" subtitle="The editions of the magazine shown on the Roots of Hope page.">
        <x-slot:actions>
            <a href="/rootsofhope" target="_blank" rel="noopener" class="btn btn-o"><x-admin.icon name="ext" />View public page</a>
            @if ($create)
                <a href="{{ route('admin.roots.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New edition</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.panel x-data="{ selected: [] }">
        <x-admin.tabs :tabs="AdminRootsQuery::TABS" :counts="$counts" :current="$tab" />

        <x-admin.filter-bar placeholder="Title or number" :$filtered />

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="search" title="No editions match">
                {{ $filtered ? 'Try another search.' : 'Nothing in this tab.' }}
                @if ($filtered)<div style="margin-top: 14px"><button type="button" class="btn btn-o" wire:click="clearFilters">Clear search</button></div>@endif
            </x-admin.empty-state>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width: 36px">
                                @if ($moves !== [])
                                    <input type="checkbox" class="cb" aria-label="Select every edition on this page"
                                        x-on:change="selected = $event.target.checked ? @js($rows->pluck('id')->all()) : []" x-bind:checked="selected.length === {{ $rows->count() }}">
                                @endif
                            </th>
                            <th>No.</th><th>Edition</th><th class="hide-m">Online link</th><th>Status</th><th style="text-align: end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $item)
                            <tr wire:key="row-{{ $item->id }}" x-bind:class="{ sel: selected.includes({{ $item->id }}) }" x-on:click="Livewire.navigate(@js(route('admin.roots.show', $item->id)))">
                                <td x-on:click.stop>
                                    @if ($moves !== [])
                                        <input type="checkbox" class="cb" value="{{ $item->id }}" x-model.number="selected" aria-label="Select edition #{{ $item->number }}">
                                    @endif
                                </td>
                                <td class="id hide-s">{{ $item->number }}</td>
                                <td>
                                    <div class="row-s">
                                        @if ($item->image_path !== '0')<img src="{{ $item->image_path }}" alt="" loading="lazy" style="height: 44px; width: 33px; object-fit: cover; border-radius: 4px; background: var(--head)">@endif
                                        <div class="nm">{{ $item->name }}</div>
                                    </div>
                                </td>
                                <td class="hide-s hide-m muted" style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ $item->url }}</td>
                                <td><x-admin.status-pill :status="ProjectStatus::of($item->published, 1, 0)" /></td>
                                <td class="acell"><x-admin.content-actions :item="$item" kind="roots" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" noun="editions" />

        @if ($moves !== [])
            <x-admin.bulk-bar :$moves />
        @endif
    </x-admin.panel>
</div>
