<?php

use App\Models\Donor;
use App\Models\Image;
use App\Models\Place;
use App\Models\Project;
use App\Models\ProjectContent;
use App\Support\PlaceName;
use App\Support\ProjectPresenter;

/*
 * What a project card says, from records held in memory: the columns that
 * hold '0' where NULL would be, and the cut-down text of a story.
 */

function presented(array $project = [], array $content = []): ProjectPresenter
{
    $model = new Project([
        'MPE_BeneficiaryName' => 'rami haddad',
        'MPE_PartnerName' => '0',
        'MPE_PartnerName2' => '0',
        'MPE_Video' => '0',
        'MPE_Donor' => 1,
        'DOB' => 0,
        'FamilyMembers' => 0,
        'latitude' => '36.2',
        'longtitude' => '37.1',
        ...$project,
    ]);
    $model->setRelation('content', new ProjectContent([
        'MPE_Name' => 'bakery',
        'MPE_Suffering' => 'A short story about a bakery that was lost.',
        'MPE_ExpSkill' => '',
        ...$content,
    ]));
    $model->setRelation('images', collect());
    $model->setRelation('mPECity', new Place(['name' => 'Aleppo']));
    $model->setRelation('mPEDonor', new Donor(['name' => 'Caritas Poland', 'logo' => '/images/logos/caritas.svg']));

    return new ProjectPresenter($model);
}

it('lists the beneficiary and the partners, and none of a second partner without a first', function () {
    expect(presented()->people())->toBe(['Rami haddad'])
        ->and(presented(['MPE_PartnerName' => 'maya'])->people())->toBe(['Rami haddad', 'Maya'])
        ->and(presented(['MPE_PartnerName' => 'maya', 'MPE_PartnerName2' => 'nour'])->people())->toBe(['Rami haddad', 'Maya', 'Nour'])
        ->and(presented(['MPE_PartnerName2' => 'nour'])->people())->toBe([]);
});

it('labels one beneficiary in the singular and more in the plural', function () {
    expect(presented()->peopleLabel())->toBe('Beneficiary:')
        ->and(presented(['MPE_PartnerName' => 'maya'])->peopleLabel())->toBe('Beneficiaries:');
});

it('cuts a long name for a tile and capitalises the first letter of a heading', function () {
    $long = str_repeat('a', 40);

    expect(presented([], ['MPE_Name' => $long])->tileTitle())->toBe(str_repeat('a', 34).'...')
        ->and(presented([], ['MPE_Name' => str_repeat('a', 35)])->tileTitle())->toBe(str_repeat('a', 35))
        ->and(presented()->heading())->toBe('Bakery');
});

it('reads the age from the birth year and leaves out a zero', function () {
    expect(presented(['DOB' => 1980])->age())->toBe(now()->year - 1980)
        ->and(presented()->age())->toBeNull()
        ->and(presented(['FamilyMembers' => 5])->family())->toBe(5)
        ->and(presented()->family())->toBeNull();
});

it('treats a zero or empty position as none', function () {
    expect(presented()->hasPosition())->toBeTrue()
        ->and(presented(['latitude' => '0'])->hasPosition())->toBeFalse()
        ->and(presented(['longtitude' => ''])->hasPosition())->toBeFalse()
        ->and(presented()->mapUrl())->toBe('https://www.google.com/maps/search/?api=1&query=36.2,37.1');
});

it('treats a zero as no video', function () {
    expect(presented()->video())->toBeNull()
        ->and(presented(['MPE_Video' => 'https://www.youtube.com/embed/abc'])->video())->toBe('https://www.youtube.com/embed/abc');
});

it('keeps the few tags a story uses and drops the rest, with their attributes', function () {
    $story = presented([], ['MPE_Suffering' => '<p onclick="x()">Hello <strong>there</strong><script>alert(1)</script></p><br>'])->story();

    expect($story)->toBe('<p>Hello <strong>there</strong>alert(1)</p><br>');
});

it('opens a story with its first words as plain text, and shows a short one whole', function () {
    $long = presented([], ['MPE_Suffering' => '<p>one two three four five six seven eight nine ten</p>']);

    expect($long->hasDetails())->toBeTrue()
        ->and($long->excerpt(8))->toBe('one two three four five six seven eight...')
        ->and($long->excerpt(15))->toBe('one two three four five six seven eight nine ten...')
        ->and(presented([], ['MPE_Suffering' => 'Short'])->hasDetails())->toBeFalse()
        ->and(presented([], ['MPE_Suffering' => 'Short'])->excerpt(8))->toBe('Short');
});

it('sizes the logo of the donors whose marks are wide or tall', function () {
    expect(presented(['MPE_Donor' => 13])->logoClass())->toBe('missio')
        ->and(presented(['MPE_Donor' => 12])->logoClass())->toBe('missio')
        ->and(presented(['MPE_Donor' => 5])->logoClass())->toBe('kerk')
        ->and(presented(['MPE_Donor' => 3])->logoClass())->toBe('louvre')
        ->and(presented(['MPE_Donor' => 8])->logoClass())->toBe('hungary')
        ->and(presented()->logoClass())->toBe('standard');
});

it('gives each photo the larger copy the lightbox shows', function () {
    $presenter = presented();
    $presenter->project->setRelation('images', collect([new Image(['MPI_Path' => '/images/projects/7/1e.jpg'])]));

    expect($presenter->photos())->toBe([['src' => '/images/projects/7/1e.jpg', 'large' => '/images/projects/7/1eb.jpg']]);
});

it('writes Idlib as the public site does', function () {
    expect(PlaceName::of('Idleb'))->toBe('Idlib')
        ->and(PlaceName::of('Aleppo'))->toBe('Aleppo')
        ->and(PlaceName::of(null))->toBe('');
});
