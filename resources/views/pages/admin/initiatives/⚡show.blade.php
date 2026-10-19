<?php

use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Initiative;
use App\Queries\AdminInitiativeQuery;
use App\Queries\AdminProjectQuery;
use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/initiatives/{id}: one initiative, for staff: its text, donors, logo and
 * photos, whether its English is checked, and what was done to it. Replaces
 * legacy's initiatives view. The buttons are the shared ones. Its logo, photos,
 * before-and-after pairs (rehabilitation) and testimonies (programme initiatives)
 * are panels of their own below.
 */
new #[Layout('layouts::admin', ['title' => 'Initiative'])] class extends Component
{
    use ManagesContent;

    #[Locked]
    public int $initiativeId;

    protected function contentKind(): ContentKind
    {
        return ContentKind::Initiative;
    }

    public function mount(Initiative $initiative): void
    {
        $this->authorize('view', $initiative);
        $this->initiativeId = $initiative->getKey();
    }

    protected function afterDeleted(Model $item): void
    {
        $this->flashAlert('deleted', ['number' => $this->contentKind()->number($item)]);
        $this->redirectRoute('admin.initiatives.index', navigate: true);
    }

    public function with(): array
    {
        $initiative = AdminInitiativeQuery::find($this->initiativeId);

        if ($initiative === null) {
            $this->flashAlert('gone', type: 'error');
            $this->redirectRoute('admin.initiatives.index', navigate: true);

            return ['initiative' => null];
        }

        return [
            'initiative' => $initiative,
            'status' => ProjectStatus::of($initiative->published, 1, $initiative->hidden),
            'donors' => AdminInitiativeQuery::donors($this->initiativeId),
            'history' => AdminProjectQuery::history($this->initiativeId, kind: ContentKind::Initiative),
        ];
    }
};
?>

<div class="stack">
    @if ($initiative)
        <x-admin.page-header :title="$initiative->name" :crumbs="['Dashboard' => route('admin.dashboard'), 'Initiatives' => route('admin.initiatives.index'), '#'.$initiative->number => null]">
            <x-slot:actions>
                <a href="{{ route('admin.initiatives.index') }}" wire:navigate class="btn btn-o"><x-admin.icon name="chev" class="i16" />Back</a>
                <x-admin.content-actions :item="$initiative" kind="initiative" size="" />
            </x-slot:actions>
        </x-admin.page-header>

        <div class="row-s">
            <x-admin.status-pill :status="$status" />
            @unless ($initiative->english_check)<x-admin.status-pill tone="act" label="English not checked" />@endunless
            <span class="muted">{{ $initiative->category }}@if ($initiative->kind) · {{ $initiative->kind }}@endif · serves {{ $initiative->serves }}</span>
        </div>

        <div class="cols">
            <div class="stack">
                <livewire:admin.initiative-logo :initiative-id="$initiative->id" :key="'logo-'.$initiative->id" />

                <x-admin.panel title="Details">
                    <dl class="facts" style="margin: 0">
                        @foreach ([
                            'Number' => '#'.$initiative->number,
                            'Organization' => $initiative->organization !== '0' ? $initiative->organization : '—',
                            'Date' => $initiative->date_string !== '0' ? $initiative->date_string : '—',
                            'Category' => $initiative->category,
                            'Serves' => $initiative->serves,
                            'Video' => $initiative->video !== '0' ? $initiative->video : '—',
                            'Donors' => $donors->isEmpty() ? '—' : $donors->pluck('name')->implode(', '),
                            'Testimonies' => $initiative->testimonies,
                        ] as $label => $value)
                            <div class="fact"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </x-admin.panel>

                @if ($initiative->description !== '0')
                    <x-admin.panel title="Short description"><div class="story">{{ $initiative->description }}</div></x-admin.panel>
                @endif

                <x-admin.panel title="Content"><div class="story">{!! SafeHtml::text($initiative->content) !!}</div></x-admin.panel>

                @if ($initiative->category_id === 3)
                    <livewire:admin.before-after :initiative-id="$initiative->id" :key="'pairs-'.$initiative->id" />
                @endif

                <livewire:admin.content-photos kind="initiative" :item-id="$initiative->id" :key="'photos-'.$initiative->id" />

                @if (in_array($initiative->category_id, \App\Queries\ContentLookups::TESTIMONY_CATEGORIES, true))
                    <livewire:admin.initiative-testimonies :initiative-id="$initiative->id" :key="'testimonies-'.$initiative->id" />
                @endif
            </div>

            <aside class="stack" aria-label="Status">
                <x-admin.panel title="History"><x-admin.history :entries="$history" /></x-admin.panel>
            </aside>
        </div>
    @endif
</div>
