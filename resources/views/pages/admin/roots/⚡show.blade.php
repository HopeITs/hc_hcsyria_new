<?php

use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\RootsOfHope;
use App\Queries\AdminProjectQuery;
use App\Queries\AdminRootsQuery;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/roots/{id}: one magazine edition, for staff: its cover, its links and what was
 * done to it. Replaces legacy's edition view. The buttons are the shared ones.
 */
new #[Layout('layouts::admin', ['title' => 'Magazine edition'])] class extends Component
{
    use ManagesContent;

    #[Locked]
    public int $editionId;

    protected function contentKind(): ContentKind
    {
        return ContentKind::Roots;
    }

    public function mount(RootsOfHope $edition): void
    {
        $this->authorize('view', $edition);
        $this->editionId = $edition->getKey();
    }

    protected function afterDeleted(Model $item): void
    {
        $this->flashAlert('deleted', ['number' => $this->contentKind()->number($item)]);
        $this->redirectRoute('admin.roots.index', navigate: true);
    }

    public function with(): array
    {
        $edition = AdminRootsQuery::find($this->editionId);

        if ($edition === null) {
            $this->flashAlert('gone', type: 'error');
            $this->redirectRoute('admin.roots.index', navigate: true);

            return ['edition' => null];
        }

        return [
            'edition' => $edition,
            'status' => ProjectStatus::of($edition->published, 1, 0),
            'history' => AdminProjectQuery::history($this->editionId, kind: ContentKind::Roots),
        ];
    }
};
?>

<div class="stack">
    @if ($edition)
        <x-admin.page-header :title="$edition->name" :crumbs="['Dashboard' => route('admin.dashboard'), 'Roots of Hope' => route('admin.roots.index'), '#'.$edition->number => null]">
            <x-slot:actions>
                <a href="{{ route('admin.roots.index') }}" wire:navigate class="btn btn-o"><x-admin.icon name="chev" class="i16" />Back</a>
                <x-admin.content-actions :item="$edition" kind="roots" size="" />
            </x-slot:actions>
        </x-admin.page-header>

        <div class="row-s"><x-admin.status-pill :status="$status" /></div>

        <div class="cols">
            <div class="stack">
                <x-admin.panel title="Cover">
                    <div style="padding: 16px">
                        @if ($edition->image_path !== '0')
                            <img src="{{ $edition->image_path }}" alt="Cover of {{ $edition->name }}" style="max-height: 360px; max-width: 100%; border-radius: 10px; background: var(--head)">
                        @else
                            <p class="muted" style="margin: 0">No cover yet. An edition needs one before it can go online.</p>
                        @endif
                    </div>
                </x-admin.panel>

                <x-admin.panel title="Details">
                    <dl class="facts" style="margin: 0">
                        <div class="fact"><dt>Number</dt><dd>#{{ $edition->number }}</dd></div>
                        <div class="fact"><dt>Online link</dt><dd><a href="{{ $edition->url }}" target="_blank" rel="noopener noreferrer">{{ $edition->url }}</a></dd></div>
                        <div class="fact"><dt>PDF</dt><dd>@if ($edition->file_path !== '0')<a href="{{ $edition->file_path }}" target="_blank" rel="noopener">{{ basename($edition->file_path) }}</a>@else — @endif</dd></div>
                        <div class="fact"><dt>Added</dt><dd>{{ $edition->created_at ? \Illuminate\Support\Carbon::parse($edition->created_at)->format('j M Y') : '—' }}</dd></div>
                    </dl>
                </x-admin.panel>
            </div>

            <aside class="stack" aria-label="Status">
                <x-admin.panel title="History"><x-admin.history :entries="$history" /></x-admin.panel>
            </aside>
        </div>
    @endif
</div>
