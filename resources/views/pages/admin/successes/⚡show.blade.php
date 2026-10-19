<?php

use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Success;
use App\Queries\AdminProjectQuery;
use App\Queries\AdminSuccessQuery;
use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/successes/{id}: one success story, for staff: its picture, text and
 * details, whether its English is checked, and what was done to it. Replaces
 * legacy's successes view. The buttons are the shared ones.
 */
new #[Layout('layouts::admin', ['title' => 'Success'])] class extends Component
{
    use ManagesContent;

    #[Locked]
    public int $successId;

    protected function contentKind(): ContentKind
    {
        return ContentKind::Success;
    }

    public function mount(Success $success): void
    {
        $this->authorize('view', $success);
        $this->successId = $success->getKey();
    }

    protected function afterDeleted(Model $item): void
    {
        $this->flashAlert('deleted', ['number' => $this->contentKind()->number($item)]);
        $this->redirectRoute('admin.successes.index', navigate: true);
    }

    public function with(): array
    {
        $success = AdminSuccessQuery::find($this->successId);

        if ($success === null) {
            $this->flashAlert('gone', type: 'error');
            $this->redirectRoute('admin.successes.index', navigate: true);

            return ['success' => null];
        }

        return [
            'success' => $success,
            'status' => ProjectStatus::of($success->published, 1, 0),
            'history' => AdminProjectQuery::history($this->successId, kind: ContentKind::Success),
        ];
    }
};
?>

<div class="stack">
    @if ($success)
        <x-admin.page-header :title="$success->name" :crumbs="['Dashboard' => route('admin.dashboard'), 'Successes' => route('admin.successes.index'), '#'.$success->number => null]">
            <x-slot:actions>
                <a href="{{ route('admin.successes.index') }}" wire:navigate class="btn btn-o"><x-admin.icon name="chev" class="i16" />Back</a>
                <x-admin.content-actions :item="$success" kind="success" size="" />
            </x-slot:actions>
        </x-admin.page-header>

        <div class="row-s">
            <x-admin.status-pill :status="$status" />
            @unless ($success->english_check)<x-admin.status-pill tone="act" label="English not checked" />@endunless
            <span class="muted">{{ $success->category }}@if ($success->kind) · {{ $success->kind }}@endif · serves {{ $success->serves }}</span>
        </div>

        <div class="cols">
            <div class="stack">
                <x-admin.panel title="Picture">
                    <div style="padding: 16px"><img src="{{ $success->image_path }}" alt="{{ $success->name }}" style="max-height: 240px; max-width: 100%; border-radius: 10px; background: var(--head)"></div>
                </x-admin.panel>

                <x-admin.panel title="Details">
                    <dl class="facts" style="margin: 0">
                        @foreach ([
                            'Number' => '#'.$success->number,
                            'Category' => $success->category,
                            'Serves' => $success->serves,
                            'Kind' => $success->kind ?? '—',
                            'Place' => $success->place !== 'All' ? $success->place : '—',
                            'Study Zone centre' => $success->centre ?? '—',
                            'Video' => $success->video !== '0' ? $success->video : '—',
                            'Linked micro project' => $success->project_number ? '#'.$success->project_number.' '.$success->project_name : '—',
                        ] as $label => $value)
                            <div class="fact"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </x-admin.panel>

                <x-admin.panel title="Story"><div class="story">{!! SafeHtml::text($success->text) !!}</div></x-admin.panel>
            </div>

            <aside class="stack" aria-label="Status">
                <x-admin.panel title="History"><x-admin.history :entries="$history" /></x-admin.panel>
            </aside>
        </div>
    @endif
</div>
