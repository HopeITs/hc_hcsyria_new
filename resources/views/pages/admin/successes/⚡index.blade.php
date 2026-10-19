<?php

use App\Enums\AdminCapability;
use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Success;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminSuccessIndexQuery;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/successes: the success stories the dashboard manages (social activities,
 * DISC, Study Zone, Family to Family, micro projects, Children University), for
 * staff. Replaces the legacy tables, one per programme. The same list as the
 * micro projects' and initiatives' (shared tabs, filters, selection and buttons),
 * over success stories; a story is online or not, it has no hidden state.
 * Filters and page are in the address.
 */
new #[Layout('layouts::admin', ['title' => 'Successes'])] class extends Component
{
    use ManagesContent;
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: '')]
    public string $category = '';

    #[Url(history: true, except: '')]
    public string $serves = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    protected function contentKind(): ContentKind
    {
        return ContentKind::Success;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Success::class);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminSuccessIndexQuery::TABS, true) ? $this->tab : 'all';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'serves', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'serves');
        $this->resetPage();
    }

    private function query(): AdminSuccessIndexQuery
    {
        return AdminSuccessIndexQuery::make()->search($this->search)->inCategory($this->category)->serving($this->serves)->tab($this->tab);
    }

    public function with(): array
    {
        $user = Auth::user();
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);

        return [
            'rows' => $this->query()->paginate(self::PER_PAGE),
            'counts' => $this->query()->counts(),
            'categories' => ContentLookups::successCategories(),
            'serves' => ContentLookups::whoCategories(),
            'moves' => array_keys(array_filter([
                ContentTransition::Publish->value => $may(AdminCapability::Publish),
                ContentTransition::CheckEnglish->value => $may(AdminCapability::CheckEnglish),
            ])),
            'create' => $may(AdminCapability::Create),
            'filtered' => $this->search !== '' || $this->category !== '' || $this->serves !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Successes" :count="number_format($counts['all'])" subtitle="Success stories of DISC, Study Zone, Children University, micro projects and the other programmes.">
        <x-slot:actions>
            @if ($create)
                <a href="{{ route('admin.successes.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New success</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.panel x-data="{ selected: [] }">
        <x-admin.tabs :tabs="AdminSuccessIndexQuery::TABS" :counts="$counts" :current="$tab" />

        <x-admin.filter-bar placeholder="Title or number" :$filtered>
            <x-admin.select-field label="Category" name="category" :options="$categories" all="All categories" />
            <x-admin.select-field label="Serves" name="serves" :options="$serves" all="Everyone" />
        </x-admin.filter-bar>

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="search" title="No successes match">
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
                                    <input type="checkbox" class="cb" aria-label="Select every success on this page"
                                        x-on:change="selected = $event.target.checked ? @js($rows->pluck('id')->all()) : []" x-bind:checked="selected.length === {{ $rows->count() }}">
                                @endif
                            </th>
                            <th>No.</th><th>Success</th><th>Category</th><th class="hide-m">Serves</th><th class="hide-m">Where</th><th>Status</th><th style="text-align: end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $success)
                            <tr wire:key="row-{{ $success->id }}" x-bind:class="{ sel: selected.includes({{ $success->id }}) }" x-on:click="Livewire.navigate(@js(route('admin.successes.show', $success->id)))">
                                <td x-on:click.stop>
                                    @if ($moves !== [])
                                        <input type="checkbox" class="cb" value="{{ $success->id }}" x-model.number="selected" aria-label="Select success #{{ $success->number }}">
                                    @endif
                                </td>
                                <td class="id hide-s">{{ $success->number }}</td>
                                <td>
                                    <div style="display: flex; gap: 10px; align-items: center">
                                        <img src="{{ $success->image_path }}" alt="" width="40" height="40" loading="lazy" style="border-radius: 8px; object-fit: cover; background: var(--head)">
                                        <div class="nm">{{ $success->name }}</div>
                                    </div>
                                </td>
                                <td class="hide-s">{{ $success->category }}@if ($success->kind)<div class="faint" style="font-size: 12px">{{ $success->kind }}</div>@endif</td>
                                <td class="hide-s hide-m">{{ $success->serves }}</td>
                                <td class="hide-s hide-m">{{ $success->centre ?: ($success->place !== 'All' ? $success->place : '—') }}</td>
                                <td><x-admin.status-pill :status="ProjectStatus::of($success->published, 1, 0)" /></td>
                                <td class="acell"><x-admin.content-actions :item="$success" kind="success" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" noun="successes" />

        @if ($moves !== [])
            <x-admin.bulk-bar :$moves />
        @endif
    </x-admin.panel>
</div>
