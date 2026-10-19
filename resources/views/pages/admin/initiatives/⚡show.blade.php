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
 * legacy's initiatives view. The buttons are the shared ones. Photos, the
 * before-and-after pairs and the testimonies are listed here; changing them
 * arrives with the images update.
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
            'photos' => AdminInitiativeQuery::photos($this->initiativeId),
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
                            'Logo' => $initiative->logo !== '0' ? basename($initiative->logo) : 'None',
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

                <x-admin.panel title="Photos" :count="$photos->count()">
                    @if ($photos->isEmpty())
                        <x-admin.empty-state icon="img" title="No photos yet">Photos are added with the images update.</x-admin.empty-state>
                    @else
                        <div class="gal">
                            @foreach ($photos as $photo)
                                <div class="ph" style="background: var(--head)">
                                    <img src="{{ $photo->path }}" alt="Photo {{ $loop->iteration }} of initiative #{{ $initiative->number }}" loading="lazy">
                                    @if ($loop->first)<span class="tg">Cover</span>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-admin.panel>
            </div>

            <aside class="stack" aria-label="Status">
                <x-admin.panel title="History"><x-admin.history :entries="$history" /></x-admin.panel>
            </aside>
        </div>
    @endif
</div>
