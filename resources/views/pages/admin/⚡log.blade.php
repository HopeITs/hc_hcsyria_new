<?php

use App\Enums\AdminCapability;
use App\Queries\AdminLogQuery;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/log: the activity log, for the Admin only. Replaces legacy's log page, which
 * fetched each category with its own request. Every change made in the dashboard is a row
 * (what, which item, who, when, and the old and new value when it has them). Tabs group the
 * categories, a search looks in the wording, the item number and the name of whoever did it.
 */
new #[Layout('layouts::admin', ['title' => 'Activity log'])] class extends Component
{
    use WithPagination;

    private const PER_PAGE = 25;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::ViewLog->value);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminLogQuery::TABS, true) ? $this->tab : 'all';
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
        return [
            'rows' => AdminLogQuery::make()->search($this->search)->tab($this->tab)->paginate(self::PER_PAGE),
            'counts' => AdminLogQuery::make()->search($this->search)->counts(),
            'filtered' => $this->search !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Activity log" :count="number_format($counts['all'])" subtitle="Everything done in the dashboard since 2022: what, to which item, by whom and when." />

    <x-admin.panel>
        <div style="overflow-x: auto"><x-admin.tabs :tabs="AdminLogQuery::TABS" :counts="$counts" :current="$tab" /></div>

        <x-admin.filter-bar placeholder="What was done, item number or name" :$filtered />

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="log" title="Nothing recorded">{{ $filtered ? 'Nothing matches that search.' : 'No activity in this tab.' }}</x-admin.empty-state>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>What</th><th class="hide-s">Item</th><th>By</th><th>When</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="log-{{ $row->id }}">
                                <td>
                                    <div class="nm">{{ $row->operation }}</div>
                                    @if ($row->previous_data !== null || $row->current_data !== null)
                                        <div class="muted" style="font-size: 12px; overflow-wrap: anywhere">
                                            {{ \Illuminate\Support\Str::limit((string) $row->previous_data, 120) }} → {{ \Illuminate\Support\Str::limit((string) $row->current_data, 120) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="hide-s tnum">{{ $row->project_id > 0 ? '#'.$row->project_id : '—' }}</td>
                                <td>{{ $row->username ?? 'Unknown' }}</td>
                                <td class="tnum">{{ Carbon::parse($row->created_at)->format('j M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" noun="entries" />
    </x-admin.panel>
</div>
