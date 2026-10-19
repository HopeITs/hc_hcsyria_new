<?php

use App\Enums\AdminCapability;
use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\News;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminNewsIndexQuery;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/news: the newsletter items (articles, products, short paragraphs, videos),
 * for staff. Replaces the legacy tables, one per kind. The same list as the micro
 * projects', initiatives' and successes' (shared tabs, filters, selection and
 * buttons), over newsletter items. Filters and page are in the address.
 */
new #[Layout('layouts::admin', ['title' => 'Newsletter'])] class extends Component
{
    use ManagesContent;
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: '')]
    public string $type = '';

    #[Url(history: true, except: '')]
    public string $place = '';

    #[Url(history: true, except: '')]
    public string $tag = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    protected function contentKind(): ContentKind
    {
        return ContentKind::News;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', News::class);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminNewsIndexQuery::TABS, true) ? $this->tab : 'all';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'place', 'tag', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'place', 'tag');
        $this->resetPage();
    }

    private function query(): AdminNewsIndexQuery
    {
        return AdminNewsIndexQuery::make()->search($this->search)->ofType($this->type)->inPlace($this->place)->taggedWith($this->tag)->tab($this->tab);
    }

    public function with(): array
    {
        $user = Auth::user();
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);

        return [
            'rows' => $this->query()->paginate(self::PER_PAGE),
            'counts' => $this->query()->counts(),
            'types' => collect(ContentLookups::NEWS_TYPES)->map(fn (string $name, int $id): object => (object) ['id' => $id, 'name' => $name])->values(),
            'places' => ContentLookups::places(),
            'tags' => ContentLookups::tags(),
            'moves' => array_keys(array_filter([
                'publish' => $may(AdminCapability::Publish),
                'hide' => $may(AdminCapability::Hide),
                'check-english' => $may(AdminCapability::CheckEnglish),
            ])),
            'create' => $may(AdminCapability::Create),
            'filtered' => $this->search !== '' || $this->type !== '' || $this->place !== '' || $this->tag !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Newsletter" :count="number_format($counts['all'])" subtitle="Articles, products, short paragraphs and videos shown on the news page.">
        <x-slot:actions>
            <a href="/our-news" target="_blank" rel="noopener" class="btn btn-o"><x-admin.icon name="ext" />View public page</a>
            @if ($create)
                <a href="{{ route('admin.news.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New item</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>
    <x-admin.news-nav />

    <x-admin.panel x-data="{ selected: [] }">
        <x-admin.tabs :tabs="AdminNewsIndexQuery::TABS" :counts="$counts" :current="$tab" />

        <x-admin.filter-bar placeholder="Title or number" :$filtered>
            <x-admin.select-field label="Kind" name="type" :options="$types" all="All kinds" />
            <x-admin.select-field label="Place" name="place" :options="$places" all="All places" />
            <x-admin.select-field label="Tag" name="tag" :options="$tags" all="All tags" />
        </x-admin.filter-bar>

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="search" title="No newsletter items match">
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
                                    <input type="checkbox" class="cb" aria-label="Select every item on this page"
                                        x-on:change="selected = $event.target.checked ? @js($rows->pluck('id')->all()) : []" x-bind:checked="selected.length === {{ $rows->count() }}">
                                @endif
                            </th>
                            <th>No.</th><th>Item</th><th>Kind</th><th class="hide-m">Place</th><th class="hide-m">Date</th><th>Status</th><th style="text-align: end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $item)
                            <tr wire:key="row-{{ $item->id }}" x-bind:class="{ sel: selected.includes({{ $item->id }}) }" x-on:click="Livewire.navigate(@js(route('admin.news.show', $item->id)))">
                                <td x-on:click.stop>
                                    @if ($moves !== [])
                                        <input type="checkbox" class="cb" value="{{ $item->id }}" x-model.number="selected" aria-label="Select item #{{ $item->number }}">
                                    @endif
                                </td>
                                <td class="id hide-s">{{ $item->number }}</td>
                                <td>
                                    <div class="nm">{{ $item->name }}</div>
                                    <div class="muted" style="font-size: 12px">
                                        @if ($item->priority === 1)<b style="color: var(--brand-text)">Important</b> · @elseif ($item->priority === 2)<b>Pinned</b> · @endif
                                        {{ $item->tags ?: 'No tags' }}
                                    </div>
                                </td>
                                <td class="hide-s">{{ ContentLookups::NEWS_TYPES[$item->type] ?? $item->type }}</td>
                                <td class="hide-s hide-m">{{ $item->place !== 'All' ? $item->place : '—' }}</td>
                                <td class="hide-s hide-m tnum">{{ $item->date ? \Illuminate\Support\Carbon::parse($item->date)->format('j M Y') : '—' }}</td>
                                <td><x-admin.status-pill :status="ProjectStatus::of($item->published, 1, $item->hidden)" /></td>
                                <td class="acell"><x-admin.content-actions :item="$item" kind="news" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" noun="items" />

        @if ($moves !== [])
            <x-admin.bulk-bar :$moves />
        @endif
    </x-admin.panel>
</div>
