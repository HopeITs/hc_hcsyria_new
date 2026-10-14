<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/*
 * What a project card says, worked out from the record the way the legacy
 * view did inline, three times over (tile, row and details window). The
 * columns hold text where one would expect NULL: a missing partner, project
 * age or family size is the string '0'.
 */
final class ProjectPresenter
{
    private const NONE = '0';

    /** A story this short is shown whole and has no details window. */
    private const SHORT_STORY = 15;

    private const TILE_TITLE = 35;

    public function __construct(public readonly Project $project) {}

    public function name(): string
    {
        return (string) $this->project->content?->MPE_Name;
    }

    /** What stands for the project in an address: its id, then its name as a slug (3033-pick-up-truck). */
    public function linkKey(): string
    {
        $slug = Str::slug($this->name());

        return $slug === '' ? (string) $this->project->id : $this->project->id.'-'.$slug;
    }

    /** The name as it heads a row or a details window. */
    public function heading(): string
    {
        return ucfirst($this->name());
    }

    /** The name as it heads a tile: long ones are cut to fit. */
    public function tileTitle(): string
    {
        $name = $this->name();

        return strlen($name) <= self::TILE_TITLE ? $name : Str::limit($name, self::TILE_TITLE - 1, '...');
    }

    /**
     * The beneficiary and the partners, in the order legacy listed them. A
     * second partner without a first is never shown, as before.
     *
     * @return array<int, string>
     */
    public function people(): array
    {
        $project = $this->project;
        $first = $project->MPE_PartnerName != self::NONE;
        $second = $project->MPE_PartnerName2 != self::NONE;

        // A second partner without a first is never shown.
        if (! $first) {
            return $second ? [] : [ucfirst($project->MPE_BeneficiaryName)];
        }

        return $second
            ? [ucfirst($project->MPE_BeneficiaryName), ucfirst($project->MPE_PartnerName), ucfirst($project->MPE_PartnerName2)]
            : [ucfirst($project->MPE_BeneficiaryName), ucfirst($project->MPE_PartnerName)];
    }

    public function peopleLabel(): string
    {
        return __(count($this->people()) > 1 ? 'main.beneficiaries' : 'main.beneficiary');
    }

    public function city(): string
    {
        return PlaceName::of($this->project->mPECity?->name);
    }

    public function donor(): string
    {
        return (string) $this->project->mPEDonor?->name;
    }

    public function logo(): string
    {
        return (string) $this->project->mPEDonor?->logo;
    }

    /** Which size of logo the donor's mark needs (legacy had a class for each wide or tall one). */
    public function logoClass(): string
    {
        return match ($this->project->MPE_Donor) {
            12, 13 => 'missio',
            5 => 'kerk',
            3 => 'louvre',
            8 => 'hungary',
            default => 'standard',
        };
    }

    public function fundDate(): ?string
    {
        $funded = $this->project->MPE_FundedDate;

        return $funded === null ? null : Carbon::parse($funded)->format('m/Y');
    }

    /** The beneficiary's age, which the record holds as a birth year. */
    public function age(): ?int
    {
        $born = (int) $this->project->DOB;

        return $born === 0 ? null : now()->year - $born;
    }

    public function family(): ?int
    {
        $members = (int) $this->project->FamilyMembers;

        return $members === 0 ? null : $members;
    }

    public function skills(): string
    {
        return (string) $this->project->content?->MPE_ExpSkill;
    }

    /** The story, with the few tags the records use and nothing else. */
    public function story(): string
    {
        $html = strip_tags((string) $this->project->content?->MPE_Suffering, '<p><br><i><strong><h5><blockquote>');

        return (string) preg_replace('/<(p|br|i|strong|h5|blockquote)\b[^>]*>/i', '<$1>', $html);
    }

    /** True when the story is long enough to be cut short on the card. */
    public function hasDetails(): bool
    {
        return strlen((string) $this->project->content?->MPE_Suffering) > self::SHORT_STORY;
    }

    /** The first $words words of the story as plain text, on a card that cannot hold it all. */
    public function excerpt(int $words): string
    {
        if (! $this->hasDetails()) {
            return $this->story();
        }

        $parts = explode(' ', strip_tags((string) $this->project->content?->MPE_Suffering));

        return e(implode(' ', array_slice($parts, 0, $words)).'...', false);
    }

    public function video(): ?string
    {
        $video = (string) $this->project->MPE_Video;

        return $video === '' || $video === self::NONE ? null : $video;
    }

    public function hasPosition(): bool
    {
        return ! in_array($this->project->latitude, [null, '', self::NONE], true)
            && ! in_array($this->project->longtitude, [null, '', self::NONE], true);
    }

    public function mapUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.$this->project->latitude.','.$this->project->longtitude;
    }

    /**
     * Each photo with the larger copy the lightbox shows: the file's name
     * with b before the extension, as the photos are stored.
     *
     * @return array<int, array{src: string, large: string}>
     */
    public function photos(): array
    {
        return $this->project->images
            ->map(fn ($image) => ['src' => $image->MPI_Path, 'large' => substr($image->MPI_Path, 0, -4).'b.jpg'])
            ->all();
    }
}
