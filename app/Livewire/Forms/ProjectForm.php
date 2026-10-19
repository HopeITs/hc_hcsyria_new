<?php

namespace App\Livewire\Forms;

use App\Queries\AdminAccessQuery;
use App\Queries\ProjectLookups;
use App\Support\SafeHtml;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

/*
 * The create and edit form of a micro-project: its fields, its rules and what
 * is handed to SaveProject. One object for both pages, so the two can never
 * differ in what they ask or accept.
 *
 * Fields left empty are sent as null; SaveProject turns them into legacy's "0".
 * The category is only there to narrow the sub-category list and is not stored.
 */
class ProjectForm extends Form
{
    public string $number = '';

    public string $city_id = '';

    public string $kind_id = '';

    public string $category_id = '';

    public string $sub_category_id = '';

    public string $name = '';

    public string $beneficiary = '';

    public string $birth_year = '';

    public string $family_members = '';

    public string $skills = '';

    public string $story = '';

    public string $partner = '';

    public string $partner2 = '';

    public string $funded = '';

    public string $donor_id = '';

    public string $mobile = '';

    public string $video = '';

    public string $latitude = '';

    public string $longitude = '';

    /** 'funded' or 'cancelled' (edit only). */
    public string $status = 'funded';

    /** Fill from AdminProjectQuery::find(); legacy's "0" means empty. */
    public function fillFrom(object $project): void
    {
        $text = fn (mixed $value): string => in_array($value, [null, '0', 0], true) ? '' : (string) $value;

        $this->number = (string) $project->number;
        $this->city_id = (string) $project->city_id;
        $this->kind_id = (string) $project->kind_id;
        $this->category_id = (string) $project->category_id;
        $this->sub_category_id = (string) $project->sub_category_id;
        $this->name = (string) $project->name;
        $this->beneficiary = (string) $project->beneficiary;
        $this->birth_year = (string) $project->birth_year;
        $this->family_members = (string) $project->family_members;
        $this->skills = (string) $project->skills;
        $this->story = (string) $project->story;
        $this->partner = $text($project->partner);
        $this->partner2 = $text($project->partner2);
        $this->funded = (string) $project->funded;
        $this->donor_id = (string) $project->donor_id;
        $this->mobile = $text($project->mobile);
        $this->video = $text($project->video);
        $this->latitude = $text($project->latitude);
        $this->longitude = $text($project->longitude);
        $this->status = (int) $project->done === 2 ? 'cancelled' : 'funded';
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $user = Auth::user();
        $cities = ProjectLookups::cities(AdminAccessQuery::placeIds($user))->pluck('id')->all();

        return [
            'number' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'city_id' => ['required', Rule::in($cities)],
            'kind_id' => ['required', Rule::in(ProjectLookups::types()->pluck('id')->all())],
            'sub_category_id' => ['required', Rule::in(ProjectLookups::subCategories()->pluck('id')->all())],
            'name' => ['required', 'string', 'max:200'],
            'beneficiary' => ['required', 'string', 'max:200'],
            'birth_year' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'family_members' => ['required', 'integer', 'min:0', 'max:50'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'story' => ['required', 'string', 'max:20000', fn (string $attribute, mixed $value, \Closure $fail) => trim(strip_tags((string) $value)) === '' ? $fail('The story is required.') : null],
            'partner' => ['nullable', 'string', 'max:200'],
            'partner2' => ['nullable', 'string', 'max:200'],
            'funded' => ['nullable', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'donor_id' => ['required', Rule::in(ProjectLookups::donors()->pluck('id')->all())],
            'mobile' => ['nullable', 'string', 'max:50'],
            'video' => ['nullable', 'url:http,https', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in(['funded', 'cancelled'])],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'number' => 'project number',
            'city_id' => 'city',
            'kind_id' => 'project type',
            'sub_category_id' => 'sub category',
            'birth_year' => 'year of birth',
            'family_members' => 'family members',
            'donor_id' => 'donor',
            'funded' => 'fund date',
        ];
    }

    /** What SaveProject takes: validated, typed, the story cut to the tags the editor makes. @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->validate();
        $empty = fn (string $value): ?string => trim($value) === '' ? null : trim($value);

        return [
            'number' => (int) $data['number'],
            'city_id' => (int) $data['city_id'],
            'kind_id' => (int) $data['kind_id'],
            'sub_category_id' => (int) $data['sub_category_id'],
            'name' => trim($data['name']),
            'beneficiary' => trim($data['beneficiary']),
            'birth_year' => (int) $data['birth_year'],
            'family_members' => (int) $data['family_members'],
            'skills' => (string) $data['skills'],
            'story' => SafeHtml::text($data['story']),
            'partner' => $empty($data['partner']),
            'partner2' => $empty($data['partner2']),
            'funded' => $empty($data['funded']),
            'donor_id' => (int) $data['donor_id'],
            'mobile' => $empty($data['mobile']),
            'video' => $empty($data['video']),
            'latitude' => $empty($data['latitude']),
            'longitude' => $empty($data['longitude']),
            'status' => $data['status'],
        ];
    }
}
