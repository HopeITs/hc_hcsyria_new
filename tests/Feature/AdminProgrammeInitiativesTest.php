<?php

use App\Models\Initiative;
use App\Models\InitiativeContent;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
 * The programme initiatives (DISC, Study Zone, School to School, Children University,
 * LEAP) in the Initiatives section: legacy had a section for each over the same table;
 * here they are one list and one form that asks what the category needs (a kind, a place,
 * a centre). Like every feature test these run against hcsyria_db inside a rolled-back
 * transaction and use initiatives they create themselves.
 */

function programmeForm(array $overrides = []): array
{
    return [...initiativeForm(), ...$overrides];
}

function firstCentre(): int
{
    return (int) DB::table('study_zone_centers')->orderBy('id')->value('id');
}

function saveProgramme(array $values)
{
    return Livewire::actingAs(staff('English Editor'))->test('pages::admin.initiatives.form')->fill($values)->call('save');
}

it('lists every programme, and filters by category and by place', function () {
    $social = adminInitiative();
    $study = adminInitiative(['what_category_id' => 7, 'sub_what_category_id' => 13, 'place_id' => 2, 'sz_center_id' => firstCentre()]);
    $disc = adminInitiative(['what_category_id' => 5, 'sub_what_category_id' => 9, 'place_id' => 1]);
    $leap = adminInitiative(['what_category_id' => 19, 'place_id' => 3]);

    $page = Livewire::actingAs(staff('Viewer'))->test('pages::admin.initiatives.index')->set('search', 'zqi initiative');
    foreach ([$social, $study, $disc, $leap] as $item) {
        $page->assertSee("zqi initiative {$item->id}");
    }

    $page->set('category', '7')->assertSee("zqi initiative {$study->id}")->assertDontSee("zqi initiative {$disc->id}");
    $page->set('category', '')->set('place', '1')->assertSee("zqi initiative {$disc->id}")->assertDontSee("zqi initiative {$study->id}")->assertDontSee("zqi initiative {$leap->id}");

    expect($page->viewData('categories')->pluck('id')->all())->toBe([1, 3, 4, 5, 7, 8, 9, 17, 18, 19]);
});

it('asks each category for what it needs', function (array $values, array $errors) {
    saveProgramme(programmeForm($values))->assertHasErrors($errors);
})->with([
    'DISC needs a programme and a place' => [['form.what_category_id' => '5'], ['form.sub_what_category_id', 'form.place_id']],
    'Study Zone needs events or study, a place and a centre' => [['form.what_category_id' => '7'], ['form.sub_what_category_id', 'form.place_id', 'form.sz_center_id']],
    'School to School needs a school' => [['form.what_category_id' => '9'], ['form.sub_what_category_id']],
    'Children University needs a place' => [['form.what_category_id' => '17'], ['form.place_id']],
    'LEAP needs a place' => [['form.what_category_id' => '19'], ['form.place_id']],
    'Family to Family needs partner or story' => [['form.what_category_id' => '8'], ['form.sub_what_category_id']],
    'a kind of another category is refused' => [['form.what_category_id' => '5', 'form.sub_what_category_id' => '12', 'form.place_id' => '1'], ['form.sub_what_category_id']],
    'a centre that does not exist is refused' => [['form.what_category_id' => '7', 'form.sub_what_category_id' => '13', 'form.place_id' => '1', 'form.sz_center_id' => '999999'], ['form.sz_center_id']],
]);

it('creates a Study Zone initiative with its kind, place and centre, and keeps only what the category uses', function () {
    $centre = firstCentre();

    saveProgramme(programmeForm(['form.what_category_id' => '7', 'form.sub_what_category_id' => '12', 'form.place_id' => '2', 'form.sz_center_id' => (string) $centre]))->assertHasNoErrors()->assertRedirect();

    $initiative = Initiative::query()->findOrFail(InitiativeContent::query()->where('title', 'zqi new initiative')->value('initiative_id'));
    expect($initiative)->what_category_id->toBe(7)->sub_what_category_id->toBe(12)->place_id->toBe(2)->sz_center_id->toBe($centre)->online->toBe(0);
});

it('drops what another category would have kept: a social activity has no place, kind or centre', function () {
    saveProgramme(programmeForm(['form.what_category_id' => '1', 'form.sub_what_category_id' => '12', 'form.place_id' => '2', 'form.sz_center_id' => (string) firstCentre()]))->assertHasNoErrors();

    $initiative = Initiative::query()->findOrFail(InitiativeContent::query()->where('title', 'zqi new initiative')->value('initiative_id'));
    expect($initiative)->sub_what_category_id->toBeNull()->place_id->toBe(0)->sz_center_id->toBeNull();
});

it('creates a DISC, a School to School, a Children University and a LEAP initiative', function (array $values, array $expected) {
    saveProgramme(programmeForm($values))->assertHasNoErrors();

    $initiative = Initiative::query()->findOrFail(InitiativeContent::query()->where('title', 'zqi new initiative')->value('initiative_id'));
    expect($initiative->only(array_keys($expected)))->toBe($expected);
})->with([
    'DISC' => [['form.what_category_id' => '5', 'form.sub_what_category_id' => '10', 'form.place_id' => '1'], ['what_category_id' => 5, 'sub_what_category_id' => 10, 'place_id' => 1, 'sz_center_id' => null]],
    'School to School' => [['form.what_category_id' => '9', 'form.sub_what_category_id' => '19'], ['what_category_id' => 9, 'sub_what_category_id' => 19, 'place_id' => 0, 'sz_center_id' => null]],
    'Children University' => [['form.what_category_id' => '17', 'form.place_id' => '4'], ['what_category_id' => 17, 'sub_what_category_id' => null, 'place_id' => 4, 'sz_center_id' => null]],
    'LEAP' => [['form.what_category_id' => '19', 'form.place_id' => '3'], ['what_category_id' => 19, 'sub_what_category_id' => null, 'place_id' => 3, 'sz_center_id' => null]],
]);

it('logs a change of kind, place and centre by name, and does not reset the kind (legacy set every edited Study Zone item to "events")', function () {
    $other = (int) DB::table('study_zone_centers')->where('id', '!=', firstCentre())->orderBy('id')->value('id');
    $initiative = adminInitiative(['what_category_id' => 7, 'sub_what_category_id' => 13, 'place_id' => 1, 'sz_center_id' => firstCentre()]);

    Livewire::actingAs(staff('English Editor'))->test('pages::admin.initiatives.form', ['initiative' => $initiative])
        ->assertSet('form.sub_what_category_id', '13')->assertSet('form.place_id', '1')
        ->set('form.title', 'zqi study edited')->call('save')->assertHasNoErrors();
    expect($initiative->refresh()->sub_what_category_id)->toBe(13);

    Livewire::actingAs(staff('English Editor'))->test('pages::admin.initiatives.form', ['initiative' => $initiative])
        ->set('form.sub_what_category_id', '12')->set('form.place_id', '2')->set('form.sz_center_id', (string) $other)->call('save')->assertHasNoErrors();

    $logs = initiativeLogs($initiative)->whereIn('operation', ['Kind changed', 'Place changed', 'Study Zone centre changed'])->get()->keyBy('operation');
    expect($initiative->refresh())->sub_what_category_id->toBe(12)->place_id->toBe(2)->sz_center_id->toBe($other)
        ->and($logs['Kind changed'])->previous_data->toBe('study')->current_data->toBe('events')
        ->and($logs['Place changed'])->previous_data->toBe('Homs')->current_data->toBe('Damascus')
        ->and($logs['Study Zone centre changed']->current_data)->toBe(DB::table('study_zone_centers')->where('id', $other)->value('name'));
});

it('never changes the category of an initiative, nor wipes what it needs, whatever the form says', function () {
    $initiative = adminInitiative(['what_category_id' => 7, 'sub_what_category_id' => 13, 'place_id' => 1, 'sz_center_id' => firstCentre()]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.form', ['initiative' => $initiative])->set('form.what_category_id', '1')->call('save')->assertHasErrors(['form.sub_what_category_id', 'form.place_id', 'form.sz_center_id']);

    expect($initiative->refresh())->what_category_id->toBe(7)->sub_what_category_id->toBe(13)->place_id->toBe(1)->sz_center_id->toBe(firstCentre());
});

it('shows the kind, place and centre on the initiative\'s page', function () {
    $initiative = adminInitiative(['what_category_id' => 7, 'sub_what_category_id' => 13, 'place_id' => 2, 'sz_center_id' => firstCentre()]);

    $this->actingAs(staff('Viewer'))->get("/admin/initiatives/{$initiative->id}")->assertOk()->assertSee('Study Zone centre')->assertSee('study')->assertSee('Damascus')
        ->assertSee(DB::table('study_zone_centers')->where('id', firstCentre())->value('name'));
});

it('leaves Homework Room out: not a category here, and its initiatives are neither listed nor opened', function () {
    $homework = adminInitiative(['what_category_id' => 6]);
    $admin = staff('Admin');

    expect(ContentLookups::INITIATIVE_CATEGORIES)->not->toContain(6)->and(ContentLookups::INITIATIVE_KINDS)->not->toHaveKey(6);

    Livewire::actingAs($admin)->test('pages::admin.initiatives.index')->set('search', "zqi initiative {$homework->id}")->assertSee('No initiatives match');
    test()->actingAs($admin)->get("/admin/initiatives/{$homework->id}")->assertForbidden();
    test()->actingAs($admin)->get("/admin/initiatives/{$homework->id}/edit")->assertForbidden();
});

it('keeps the DISC success kinds apart from the DISC programmes', function () {
    expect(ContentLookups::discKinds()->pluck('id')->all())->toBe([16, 17])
        ->and(ContentLookups::kindsOf(5)->pluck('id')->all())->toBe([8, 9, 10, 11])
        ->and(ContentLookups::kindsOf(7)->pluck('name')->all())->toBe(['events', 'study'])
        ->and(ContentLookups::kindsOf(1)->isEmpty())->toBeTrue();
});
