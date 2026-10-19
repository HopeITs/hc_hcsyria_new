<?php

use App\Actions\Admin\SaveProject;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\SendsAlerts;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminProjectQuery;
use App\Queries\ProjectLookups;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/projects/create and /admin/projects/{id}/edit: the one form for a
 * micro-project, in the sections of the legacy form (basics, beneficiary, story,
 * funding) with the story in a small rich-text box in place of CKEditor 12.
 * The rules are ProjectForm, the saving is SaveProject, the wording of every
 * alert is lang/en/admin.php. Replaces legacy's project-create and project-edit.
 */
new #[Layout('layouts::admin', ['title' => 'Micro project'])] class extends Component
{
    use SendsAlerts;

    public ProjectForm $form;

    #[Locked]
    public ?int $projectId = null;

    public function mount(?Project $project = null): void
    {
        if ($project === null) {
            $this->authorize('create', Project::class);
            $this->form->number = (string) ProjectLookups::nextNumber();
            $this->form->kind_id = (string) ProjectLookups::types()->first()->id;

            return;
        }

        $this->authorize('update', $project);
        $this->projectId = $project->getKey();
        $this->form->fillFrom(AdminProjectQuery::find($this->projectId));
    }

    /** Choosing another category empties the sub category, which belonged to the old one. */
    public function updatedFormCategoryId(): void
    {
        $this->form->sub_category_id = '';
    }

    public function save(): void
    {
        $project = $this->projectId === null ? null : Project::query()->findOrFail($this->projectId);

        if ($project !== null) {
            $this->authorize('update', $project);
        } else {
            $this->authorize('create', Project::class);
        }

        try {
            $data = $this->form->payload();
        } catch (ValidationException $invalid) {
            $this->alert('invalid', type: 'error');

            throw $invalid;
        }

        try {
            $saved = app(SaveProject::class)->handle($data, Auth::user(), $project);
        } catch (AdminActionRefused $refused) {
            $this->addError('form.number', __('admin.'.$refused->alertKey(), $refused->params));
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->flashAlert($project === null ? 'created' : 'saved', ['number' => $saved->MicroProject_ID]);
        $this->redirectRoute('admin.projects.show', $saved->getKey(), navigate: true);
    }

    public function with(): array
    {
        $categoryId = (int) $this->form->category_id;

        return [
            'editing' => $this->projectId !== null,
            'crumbs' => ['Dashboard' => route('admin.dashboard'), 'Micro projects' => route('admin.projects.index')]
                + ($this->projectId === null ? ['New' => null] : ['#'.$this->form->number => route('admin.projects.show', $this->projectId), 'Edit' => null]),
            'cities' => ProjectLookups::cities(AdminAccessQuery::placeIds(Auth::user()))->pluck('name', 'id')->all(),
            'kinds' => ProjectLookups::types()->pluck('name', 'id')->all(),
            'categories' => ProjectLookups::categories()->pluck('name', 'id')->all(),
            'subCategories' => ProjectLookups::subCategories()->where('category_id', $categoryId)->pluck('name', 'id')->all(),
            'donors' => ProjectLookups::donors(),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header
        :title="$editing ? 'Edit project #'.$form->number : 'New project'"
        :subtitle="$editing ? $form->name : 'Fill in what you know. Nothing goes online until someone publishes it.'"
        :crumbs="$crumbs" />

    <form wire:submit="save" class="panel" style="padding: 24px 24px 0; overflow: visible" novalidate>
        <x-admin.form-section title="Basics" hint="City, number, name and kind of project.">
            <x-admin.field label="City" name="form.city_id" required full>
                <x-admin.segmented name="form.city_id" :options="$cities" />
            </x-admin.field>
            <x-admin.field label="Project number" name="form.number" required :help="$editing ? null : 'The next free number is filled in.'">
                <input id="f-form.number" class="inp" inputmode="numeric" wire:model="form.number">
            </x-admin.field>
            <x-admin.field label="Project name" name="form.name" required>
                <input id="f-form.name" class="inp" wire:model="form.name" maxlength="200">
            </x-admin.field>
            <x-admin.field label="Project type" name="form.kind_id" required full>
                <x-admin.segmented name="form.kind_id" :options="$kinds" />
            </x-admin.field>
            <x-admin.field label="Category" name="form.category_id" full>
                <x-admin.segmented name="form.category_id" :options="$categories" live />
            </x-admin.field>
            <x-admin.field label="Sub category" name="form.sub_category_id" required full :help="$subCategories === [] ? 'Choose a category first.' : null">
                <x-admin.segmented name="form.sub_category_id" :options="$subCategories" />
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Beneficiary" hint="Who the project is for.">
            <x-admin.field label="Beneficiary name" name="form.beneficiary" required full>
                <input id="f-form.beneficiary" class="inp" wire:model="form.beneficiary" maxlength="200">
            </x-admin.field>
            <x-admin.field label="Year of birth" name="form.birth_year" required>
                <input id="f-form.birth_year" class="inp" inputmode="numeric" placeholder="1985" wire:model="form.birth_year">
            </x-admin.field>
            <x-admin.field label="Family members" name="form.family_members" required>
                <input id="f-form.family_members" class="inp" inputmode="numeric" wire:model="form.family_members">
            </x-admin.field>
            <x-admin.field label="Skills and experience" name="form.skills" full>
                <textarea id="f-form.skills" class="ta" wire:model="form.skills" maxlength="5000"></textarea>
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Story" hint="Shown to supporters on the public page.">
            <x-admin.field label="Suffering and need" name="form.story" required full>
                <x-admin.rich-text model="form.story" />
            </x-admin.field>
            <x-admin.field label="Partner" name="form.partner">
                <input id="f-form.partner" class="inp" wire:model="form.partner" maxlength="200">
            </x-admin.field>
            <x-admin.field label="Second partner" name="form.partner2">
                <input id="f-form.partner2" class="inp" wire:model="form.partner2" maxlength="200">
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Funding and contact" hint="The donor is needed before the project can go online.">
            <x-admin.field label="Donor" name="form.donor_id" required>
                <select id="f-form.donor_id" class="sel" wire:model="form.donor_id">
                    <option value="">Choose a donor</option>
                    @foreach ($donors as $donor)<option value="{{ $donor->id }}">{{ $donor->name }}</option>@endforeach
                </select>
            </x-admin.field>
            <x-admin.field label="Date of fund" name="form.funded">
                <input id="f-form.funded" type="date" class="inp" min="2000-01-01" max="{{ now()->toDateString() }}" wire:model="form.funded">
            </x-admin.field>
            <x-admin.field label="Mobile" name="form.mobile">
                <input id="f-form.mobile" class="inp" inputmode="tel" wire:model="form.mobile" maxlength="50">
            </x-admin.field>
            <x-admin.field label="Video link" name="form.video">
                <input id="f-form.video" type="url" class="inp" placeholder="https://…" wire:model="form.video" maxlength="1000">
            </x-admin.field>
            <x-admin.field label="Latitude" name="form.latitude" help="Paste from a map. Both are needed for the pin.">
                <input id="f-form.latitude" class="inp" inputmode="decimal" placeholder="36.2021" wire:model="form.latitude">
            </x-admin.field>
            <x-admin.field label="Longitude" name="form.longitude">
                <input id="f-form.longitude" class="inp" inputmode="decimal" placeholder="37.1343" wire:model="form.longitude">
            </x-admin.field>
        </x-admin.form-section>

        @if ($editing)
            <x-admin.form-section title="Status" hint="Cancelling takes the project off the public list.">
                <x-admin.field label="Project status" name="form.status" full>
                    <x-admin.segmented name="form.status" :options="['funded' => 'Funded', 'cancelled' => 'Cancelled']" />
                </x-admin.field>
            </x-admin.form-section>
        @endif

        <div class="sticky-bar">
            <span class="dirty" wire:dirty.class="on"><x-admin.icon name="warn" class="i16" />Unsaved changes</span>
            <a href="{{ $editing ? route('admin.projects.show', $projectId) : route('admin.projects.index') }}" wire:navigate class="btn btn-o">Cancel</a>
            <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save">
                <span class="spin" wire:loading wire:target="save"></span><x-admin.icon name="check" wire:loading.remove wire:target="save" />{{ $editing ? 'Save changes' : 'Create project' }}
            </button>
        </div>
    </form>
</div>
