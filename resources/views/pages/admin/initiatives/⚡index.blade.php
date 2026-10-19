<?php

use App\Enums\AdminCapability;
use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Initiative;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminInitiativeIndexQuery;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/initiatives: the initiatives the dashboard manages (social activities,
 * rehabilitation, relief, Family to Family, earthquake response), for staff.
 * Replaces the legacy tables, one per category. The same list as the micro
 * projects' (shared tabs, filters, selection and buttons), over initiatives. The
 * programmes' own initiatives (DISC, Study Zone, School to School, Children
 * University) are managed in their sections. Filters and page are in the address.
 */
new #[Layout('layouts::admin', ['title' => 'Initiatives'])] class extends Component
{
    use ManagesContent;
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: '')]
    public string $category = '';

    #[Url(history: true, except: '')]
    public string $donor = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    protected function contentKind(): ContentKind
    {
        return ContentKind::Initiative;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Initiative::class);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminInitiativeIndexQuery::TABS, true) ? $this->tab : 'all';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'donor', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'donor');
        $this->resetPage();
    }

    private function query(): AdminInitiativeIndexQuery
    {
        return AdminInitiativeIndexQuery::make()->search($this->search)->inCategory($this->category)->forDonor($this->donor)->tab($this->tab);
    }

    public function with(): array
    {
        $user = Auth::user();
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);

        return [
            'rows' => $this->query()->paginate(self::PER_PAGE),
            'counts' => $this->query()->counts(),
            'categories' => ContentLookups::initiativeCategories(),
            'donors' => ContentLookups::allDonors(),
            'moves' => array_keys(array_filter([
                'publish' => $may(AdminCapability::Publish),
                'hide' => $may(AdminCapability::Hide),
                'check-english' => $may(AdminCapability::CheckEnglish),
            ])),
            'create' => $may(AdminCapability::Create),
            'filtered' => $this->search !== '' || $this->category !== '' || $this->donor !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Initiatives" :count="number_format($counts['all'])" subtitle="Social activities, rehabilitation, relief, Family to Family and earthquake response.">
        <x-slot:actions>
            @if ($create)
                <a href="{{ route('admin.initiatives.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New initiative</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.panel x-data="{ selected: [] }">
        <x-admin.tabs :tabs="AdminInitiativeIndexQuery::TABS" :counts="$counts" :current="$tab" />

        <x-admin.filter-bar placeholder="Title, organization or number" :$filtered>
            <x-admin.select-field label="Category" name="category" :options="$categories" all="All categories" />
            <x-admin.select-field label="Donor" name="donor" :options="$donors" all="All donors" />
        </x-admin.filter-bar>

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="search" title="No initiatives match">
                {{ $filtered ? 'Try removing a filter.' : 'Nothing in this tab.' }}
                @if ($filtered)<div style="margin-top: 14px"><button type="button" class="btn btn-o" wire:click="clearFilters">Clear filters</button></div>@endif
            </x-admin.empty-state>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width: 36px">
                                @if ($moves !== [])
                                    <input type="checkbox" class="cb" aria-label="Select every initiative on this page"
                                        x-on:change="selected = $event.target.checked ? @js($rows->pluck('id')->all()) : []" x-bind:checked="selected.length === {{ $rows->count() }}">
                                @endif
                            </th>
                            <th>No.</th><th>Initiative</th><th>Category</th><th class="hide-m">Serves</th><th class="hide-m">Donors</th><th class="hide-m">Photos</th><th>Status</th><th style="text-align: end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $initiative)
                            <tr wire:key="row-{{ $initiative->id }}" x-bind:class="{ sel: selected.includes({{ $initiative->id }}) }" x-on:click="Livewire.navigate(@js(route('admin.initiatives.show', $initiative->id)))">
                                <td x-on:click.stop>
                                    @if ($moves !== [])
                                        <input type="checkbox" class="cb" value="{{ $initiative->id }}" x-model.number="selected" aria-label="Select initiative #{{ $initiative->number }}">
                                    @endif
                                </td>
                                <td class="id hide-s">{{ $initiative->number }}</td>
                                <td>
                                    <div class="nm">{{ $initiative->name }}</div>
                                    @if ($initiative->organization !== '0')<div class="muted" style="font-size: 12px">{{ $initiative->organization }}</div>@endif
                                </td>
                                <td class="hide-s">{{ $initiative->category }}@if ($initiative->kind)<div class="faint" style="font-size: 12px">{{ $initiative->kind }}</div>@endif</td>
                                <td class="hide-s hide-m">{{ $initiative->serves }}</td>
                                <td class="hide-s hide-m" style="max-width: 16rem">{{ $initiative->donors ?? '—' }}</td>
                                <td class="hide-s hide-m"><span @class(['info', 'y' => $initiative->photos]) title="{{ $initiative->photos }} photos"><x-admin.icon name="img" class="i16" /> {{ $initiative->photos }}</span></td>
                                <td><x-admin.status-pill :status="ProjectStatus::of($initiative->published, 1, $initiative->hidden)" /></td>
                                <td class="acell"><x-admin.content-actions :item="$initiative" kind="initiative" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" noun="initiatives" />

        @if ($moves !== [])
            <x-admin.bulk-bar :$moves />
        @endif
    </x-admin.panel>
</div>
