<?php

use App\Enums\AdminCapability;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminStatisticsQuery;
use App\Queries\ContentLookups;
use App\Support\Admin\Reports;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * /admin/reports: money, people and time, by city, donor, category and age, for the
 * Admin and the Publisher. Filter by city and by the year a project was funded; each
 * table has a CSV download that uses the same filters. Only the cities the account may
 * see are offered and counted.
 */
new #[Layout('layouts::admin', ['title' => 'Reports'])] class extends Component
{
    #[Url(history: true, except: '')]
    public string $city = '';

    #[Url(history: true, except: '')]
    public string $year = '';

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::ViewReports->value);
        $this->clean();
    }

    public function updated(): void
    {
        $this->clean();
    }

    /** Keep only a city and a year this account may choose; anything else means "all". */
    private function clean(): void
    {
        $this->city = ctype_digit($this->city) && AdminAccessQuery::mayUsePlace(Auth::user(), (int) $this->city) ? $this->city : '';
        $this->year = ctype_digit($this->year) && (int) $this->year >= 2000 && (int) $this->year <= 2100 ? $this->year : '';
    }

    public function with(): array
    {
        $places = AdminAccessQuery::placeIds(Auth::user());
        $stats = new AdminStatisticsQuery($places, $this->city === '' ? null : (int) $this->city, $this->year === '' ? null : (int) $this->year);

        return [
            'cities' => ContentLookups::cities($places),
            'years' => $stats->years(),
            'tables' => collect(Reports::titles())->map(fn (string $title, string $slug): array => ['title' => $title] + Reports::build($slug, $stats)),
            'funding' => $stats->funding(),
            'beneficiaries' => $stats->beneficiaries(),
            'currency' => (string) config('hcsyria.currency'),
            'query' => array_filter(['city' => $this->city, 'year' => $this->year]),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Reports" subtitle="Funding, beneficiaries and time to publish. Cancelled projects are not counted." />

    <x-admin.panel>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; padding: 14px 16px; align-items: end">
            <label class="muted" style="display: grid; gap: 4px; font-size: 12px">City
                <select wire:model.live="city" class="sel">
                    <option value="">All cities</option>
                    @foreach ($cities as $place)<option value="{{ $place->id }}">{{ $place->name }}</option>@endforeach
                </select>
            </label>
            <label class="muted" style="display: grid; gap: 4px; font-size: 12px">Year funded
                <select wire:model.live="year" class="sel">
                    <option value="">All years</option>
                    @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                </select>
            </label>
        </div>
    </x-admin.panel>

    <div class="tiles">
        <x-admin.stat-tile label="Projects" :value="number_format($funding['projects'])" icon="folder" />
        <x-admin.stat-tile label="Donated" :value="number_format($funding['donated'])" :hint="$currency.' of '.number_format($funding['needed']).' needed'" icon="trophy" tone="good" />
        <x-admin.stat-tile label="Still missing" :value="number_format($funding['missing'])" :hint="$currency" icon="warn" :tone="$funding['missing'] > 0 ? 'warn' : 'good'" />
        <x-admin.stat-tile label="People reached" :value="number_format($beneficiaries['people'])" :hint="number_format($beneficiaries['families']).' families'" icon="users" />
    </div>

    @foreach ($tables as $slug => $table)
        <x-admin.panel :title="$table['title']" wire:key="report-{{ $slug }}">
            <x-slot:toolbar>
                <a href="{{ route('admin.reports.export', ['report' => $slug] + $query) }}" class="btn btn-o s" download><x-admin.icon name="doc" />Download CSV</a>
            </x-slot:toolbar>

            @if (count($table['rows']) === 0)
                <x-admin.empty-state icon="check" title="No data">No project matches these filters.</x-admin.empty-state>
            @else
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead><tr>@foreach ($table['headings'] as $i => $heading)<th @class(['tnum' => $i > 0])>{{ $heading }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($table['rows'] as $row)
                                <tr>@foreach ($row as $i => $cell)<td @class(['tnum' => $i > 0])>{{ is_int($cell) || is_float($cell) ? number_format($cell, is_float($cell) ? 1 : 0) : $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.panel>
    @endforeach
</div>
