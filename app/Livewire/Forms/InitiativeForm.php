<?php

namespace App\Livewire\Forms;

use App\Queries\ContentLookups;
use App\Support\Admin\LegacyValue;
use App\Support\SafeHtml;
use Illuminate\Validation\Rule;
use Livewire\Form;

/*
 * The create and edit form of an initiative: its fields, its rules and what is
 * handed to SaveInitiative. One object for both pages. The limits are the
 * columns' own (legacy let a date run to 100 characters in a column of 70).
 *
 * The category is chosen when the initiative is created and does not change
 * afterwards, as in legacy. A Family to Family initiative is either a partner or
 * a story. Fields left empty are sent as null; SaveInitiative stores legacy's "0".
 */
class InitiativeForm extends Form
{
    public string $what_category_id = '';

    public string $who_category_id = '';

    /** Family to Family only: 1 = partner, 2 = story. */
    public string $sub_what_category_id = '';

    public string $title = '';

    public string $organization = '';

    public string $date_string = '';

    public string $video = '';

    public string $description = '';

    public string $content = '';

    /** @var list<string> */
    public array $donors = [];

    /**
     * Fill from AdminInitiativeQuery::find() and ::donors(); legacy's "0" means empty.
     *
     * @param  object{category_id: int, serves_id: int, kind_id: int|null, name: string, organization: string, date_string: string, video: string, description: string, content: string}  $initiative
     * @param  iterable<object{id: int}>  $donors
     */
    public function fillFrom(object $initiative, iterable $donors): void
    {
        $this->what_category_id = (string) $initiative->category_id;
        $this->who_category_id = (string) $initiative->serves_id;
        $this->sub_what_category_id = (string) $initiative->kind_id;
        $this->title = (string) $initiative->name;
        $this->organization = LegacyValue::read($initiative->organization);
        $this->date_string = LegacyValue::read($initiative->date_string);
        $this->video = LegacyValue::read($initiative->video);
        $this->description = LegacyValue::read($initiative->description);
        $this->content = (string) $initiative->content;
        $this->donors = array_values(array_map(fn (object $donor): string => (string) $donor->id, [...$donors]));
    }

    public function isFamilyToFamily(): bool
    {
        return (int) $this->what_category_id === ContentLookups::FAMILY_TO_FAMILY;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'what_category_id' => ['required', Rule::in(ContentLookups::INITIATIVE_CATEGORIES)],
            'who_category_id' => ['required', Rule::in(ContentLookups::whoCategories()->pluck('id')->all())],
            'sub_what_category_id' => [Rule::requiredIf($this->isFamilyToFamily()), 'nullable', Rule::in(ContentLookups::familyKinds()->pluck('id')->all())],
            'title' => ['required', 'string', 'max:70'],
            'organization' => ['nullable', 'string', 'max:70'],
            'date_string' => ['nullable', 'string', 'max:70'],
            'video' => ['nullable', 'url:http,https', 'max:100'],
            'description' => ['nullable', 'string', 'max:400'],
            'content' => ['required', 'string', 'max:200000', fn (string $attribute, mixed $value, \Closure $fail) => trim(strip_tags((string) $value)) === '' ? $fail('The content is required.') : null],
            'donors' => ['required', 'array', 'min:1'],
            'donors.*' => [Rule::in(ContentLookups::allDonors()->pluck('id')->map(fn ($id): string => (string) $id)->all())],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'what_category_id' => 'category',
            'who_category_id' => 'who it serves',
            'sub_what_category_id' => 'kind',
            'date_string' => 'date',
            'donors' => 'donors',
        ];
    }

    /**
     * What SaveInitiative takes: validated, typed, the content cut to the tags the editor makes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validate();
        $empty = fn (string $value): ?string => trim($value) === '' ? null : trim($value);

        return [
            'what_category_id' => (int) $data['what_category_id'],
            'who_category_id' => (int) $data['who_category_id'],
            'sub_what_category_id' => $this->isFamilyToFamily() ? (int) $data['sub_what_category_id'] : null,
            'title' => trim($data['title']),
            'organization' => $empty($data['organization']),
            'date_string' => $empty($data['date_string']),
            'video' => $empty($data['video']),
            'description' => $empty($data['description']),
            'content' => SafeHtml::text($data['content']),
            'donors' => array_values(array_unique(array_map('intval', $data['donors']))),
        ];
    }
}
