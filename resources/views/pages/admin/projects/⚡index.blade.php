<?php

use App\Enums\AdminCapability;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Project;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminProjectIndexQuery;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/projects: every micro-project, for staff. Replaces the legacy tabbed
 * tables (one per city, plus pending and cancelled) with one list, tabs for the
 * state, filters for city, category and donor, a search box, and a selection to
 * publish, hide or English-check several at once. Everything shown and every
 * action follows the account's role and cities (AdminAccessQuery, ProjectPolicy).
 * The filters and the page are in the address, so a list can be shared.
 */
new #[Layout('layouts::admin', ['title' => 'Micro projects'])] class extends Component
{
    use ManagesContent;
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: '')]
    public string $city = '';

    #[Url(history: true, except: '')]
    public string $category = '';

    #[Url(history: true, except: '')]
    public string $donor = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminProjectIndexQuery::TABS, true) ? $this->tab : 'all';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'city', 'category', 'donor', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'city', 'category', 'donor');
        $this->resetPage();
    }

    private function query(): AdminProjectIndexQuery
    {
        return AdminProjectIndexQuery::make()
            ->limitedTo(AdminAccessQuery::placeIds(Auth::user()))
            ->search($this->search)
            ->inCity($this->city)
            ->inCategory($this->category)
            ->forDonor($this->donor)
            ->tab($this->tab);
    }

    public function with(): array
    {
        $user = Auth::user();
        $places = AdminAccessQuery::placeIds($user);
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);

        return [
            'rows' => $this->query()->paginate(self::PER_PAGE),
            'counts' => $this->query()->counts(),
            'cities' => ContentLookups::cities($places),
            'categories' => ContentLookups::categories(),
            'donors' => ContentLookups::donors(),
            'moves' => array_keys(array_filter([
                'publish' => $may(AdminCapability::Publish),
                'hide' => $may(AdminCapability::Hide),
                'check-english' => $may(AdminCapability::CheckEnglish),
            ])),
            'create' => $may(AdminCapability::Create),
            'filtered' => $this->search !== '' || $this->city !== '' || $this->category !== '' || $this->donor !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Micro projects" :count="number_format($counts['all'])" subtitle="Everything on the public micro-projects page, and what is waiting to go there.">
        <x-slot:actions>
            <a href="/projects/micro-projects" target="_blank" rel="noopener" class="btn btn-o"><x-admin.icon name="ext" />View public page</a>
            @if ($create)
                <a href="{{ route('admin.projects.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New project</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.panel x-data="{ selected: [] }">
        <x-admin.tabs :tabs="AdminProjectIndexQuery::TABS" :counts="$counts" :current="$tab" />

        <x-admin.filter-bar placeholder="Name, beneficiary or number" :$filtered>
            <x-admin.select-field label="City" name="city" :options="$cities" all="All cities" />
            <x-admin.select-field label="Category" name="category" :options="$categories" all="All categories" />
            <x-admin.select-field label="Donor" name="donor" :options="$donors" all="All donors" />
        </x-admin.filter-bar>

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="search" title="No projects match">
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
                                    <input type="checkbox" class="cb" aria-label="Select every project on this page"
                                        x-on:change="selected = $event.target.checked ? @js($rows->pluck('id')->all()) : []" x-bind:checked="selected.length === {{ $rows->count() }}">
                                @endif
                            </th>
                            <th>No.</th><th>Project</th><th>City</th><th class="hide-m">Category</th><th class="hide-m">Funded</th><th class="hide-m">Donor</th><th class="hide-m">Has</th><th>Status</th><th style="text-align: end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $project)
                            <tr wire:key="row-{{ $project->id }}" x-bind:class="{ sel: selected.includes({{ $project->id }}) }" x-on:click="Livewire.navigate(@js(route('admin.projects.show', $project->id)))">
                                <td x-on:click.stop>
                                    @if ($moves !== [])
                                        <input type="checkbox" class="cb" value="{{ $project->id }}" x-model.number="selected" aria-label="Select project #{{ $project->number }}">
                                    @endif
                                </td>
                                <td class="id hide-s">{{ $project->number }}</td>
                                <td>
                                    <div class="nm">{{ $project->name }}</div>
                                    <div class="muted" style="font-size: 12px">{{ $project->kind }}</div>
                                </td>
                                <td class="hide-s">{{ $project->city }}</td>
                                <td class="hide-s hide-m">{{ $project->category }}<div class="faint" style="font-size: 12px">{{ $project->sub_category }}</div></td>
                                <td class="hide-s hide-m tnum">{{ $project->funded ? \Illuminate\Support\Carbon::parse($project->funded)->format('j M Y') : '—' }}</td>
                                <td class="hide-s hide-m">{{ $project->donor ?? '—' }}</td>
                                <td class="hide-s hide-m">
                                    <div class="info">
                                        <span class="{{ $project->mobile && $project->mobile !== '0' ? 'y' : 'n' }}" title="Mobile"><x-admin.icon name="phone" class="i16" /></span>
                                        <span class="{{ (float) $project->latitude !== 0.0 ? 'y' : 'n' }}" title="Map location"><x-admin.icon name="pin" class="i16" /></span>
                                        <span class="{{ $project->video && $project->video !== '0' ? 'y' : 'n' }}" title="Video"><x-admin.icon name="video" class="i16" /></span>
                                        <span class="{{ $project->photos ? 'y' : 'n' }}" title="{{ $project->photos }} photos"><x-admin.icon name="img" class="i16" /></span>
                                    </div>
                                </td>
                                <td><x-admin.status-pill :status="ProjectStatus::of($project->published, $project->done, $project->hidden)" /></td>
                                <td class="acell"><x-admin.content-actions :item="$project" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" />

        @if ($moves !== [])
            <x-admin.bulk-bar :$moves />
        @endif
    </x-admin.panel>
</div>
