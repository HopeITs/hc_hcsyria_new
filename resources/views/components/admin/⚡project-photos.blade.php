<?php

use App\Actions\Admin\ManageProjectPhotos;
use App\Livewire\Concerns\SendsAlerts;
use App\Models\Project;
use App\Queries\AdminProjectQuery;
use App\Rules\SecureUpload;
use App\Support\Admin\ProjectPhotoFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * The photos panel of a project: everyone who can open the project sees its
 * photos; whoever manages photos (ProjectPolicy::managePhotos) can also add,
 * move, make a cover of, and remove them. Replaces legacy's add-image page. The
 * work is ManageProjectPhotos; the pictures are checked by SecureUpload and
 * re-encoded by ProjectPhotoFiles. The project's own page is told when the photos
 * change (`photos-changed`) so its checklist follows.
 */
new class extends Component
{
    use SendsAlerts;
    use WithFileUploads;

    private const MAX_FILES = 20;

    private const MAX_KILOBYTES = 8192;

    #[Locked]
    public int $projectId;

    /** @var array<int, mixed> */
    public array $uploads = [];

    public function mount(int $projectId): void
    {
        $this->authorize('view', Project::query()->findOrFail($projectId));
        $this->projectId = $projectId;
    }

    /** Choosing files is enough: they are checked and stored at once. */
    public function updatedUploads(): void
    {
        $project = $this->managedProject();

        if (! ProjectPhotoFiles::available()) {
            $this->reset('uploads');
            $this->alert('photos.unavailable', type: 'error');

            return;
        }

        try {
            $this->validate([
                'uploads' => ['required', 'array', 'max:'.self::MAX_FILES],
                'uploads.*' => ['file', 'max:'.self::MAX_KILOBYTES, new SecureUpload],
            ], attributes: ['uploads.*' => 'picture']);
        } catch (ValidationException $invalid) {
            $this->reset('uploads');
            $this->resetErrorBag();
            $this->alert('photos.rejected', ['reason' => collect($invalid->errors())->flatten()->first()], 'error');

            return;
        }

        $result = app(ManageProjectPhotos::class)->add($project, array_values($this->uploads), Auth::user());
        $this->reset('uploads');

        $this->alert($result['offline'] ? 'photos.added_offline' : 'photos.added', ['count' => $result['added'], 'number' => $project->MicroProject_ID]);
        $this->dispatch('photos-changed');
    }

    public function remove(int $imageId): void
    {
        $project = $this->managedProject();
        $offline = app(ManageProjectPhotos::class)->remove($project, $imageId, Auth::user());

        $this->alert($offline ? 'photos.removed_offline' : 'photos.removed', ['number' => $project->MicroProject_ID]);
        $this->dispatch('photos-changed');
    }

    public function move(int $imageId, int $offset): void
    {
        app(ManageProjectPhotos::class)->move($this->managedProject(), $imageId, $offset < 0 ? -1 : 1);
    }

    public function makeCover(int $imageId): void
    {
        app(ManageProjectPhotos::class)->makeCover($this->managedProject(), $imageId);
    }

    /** Every change asks again: the buttons are only a convenience. */
    private function managedProject(): Project
    {
        $project = Project::query()->findOrFail($this->projectId);
        $this->authorize('managePhotos', $project);

        return $project;
    }

    public function with(): array
    {
        $project = Project::query()->findOrFail($this->projectId);

        return [
            'photos' => AdminProjectQuery::photos($this->projectId),
            'canManage' => Auth::user()->can('managePhotos', $project),
            'online' => (string) $project->MPE_Published === '1',
            'number' => $project->MicroProject_ID,
        ];
    }
};
?>

<x-admin.panel title="Photos" :count="$photos->count()">
    @if ($canManage)
        <x-slot:toolbar>
            <label class="btn btn-o s" style="cursor: pointer">
                <x-admin.icon name="up" /><span wire:loading.remove wire:target="uploads">Add photos</span><span wire:loading wire:target="uploads">Uploading…</span>
                <input type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp" class="sr" aria-label="Choose photos to add">
            </label>
        </x-slot:toolbar>
        @if ($online)
            <div class="note warn" style="margin: 12px 16px 0"><x-admin.icon name="warn" /><div>This project is online. Changing its photos sends it back to the pending list to be published again.</div></div>
        @endif
    @endif

    @if ($photos->isEmpty())
        <x-admin.empty-state icon="img" title="No photos yet">A project needs at least one before it can go online.</x-admin.empty-state>
    @else
        <div class="gal">
            @foreach ($photos as $photo)
                <div class="ph" style="background: var(--head)" wire:key="photo-{{ $photo->id }}">
                    <img src="{{ $photo->path }}" alt="Photo {{ $loop->iteration }} of project #{{ $number }}" loading="lazy">
                    @if ($loop->first)<span class="tg">Cover</span>@endif
                    @if ($canManage)
                        <div class="tools">
                            <button type="button" wire:click="move({{ $photo->id }}, -1)" @disabled($loop->first) aria-label="Move earlier"><x-admin.icon name="chev" class="i16" /></button>
                            <button type="button" wire:click="move({{ $photo->id }}, 1)" @disabled($loop->last) aria-label="Move later"><x-admin.icon name="chev" class="i16" style="transform: scaleX(-1)" /></button>
                            @unless ($loop->first)<button type="button" wire:click="makeCover({{ $photo->id }})">Cover</button>@endunless
                            <button type="button" class="del" aria-label="Remove this photo"
                                x-on:click="$store.confirm.ask({ title: @js(__('admin.confirm.photo_title')), body: @js(__('admin.confirm.photo_body')), action: @js(__('admin.confirm.photo_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $photo->id }}) })"><x-admin.icon name="trash" class="i16" /></button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-admin.panel>
