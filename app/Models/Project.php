<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'MicroProject_ID',
        'MPE_City',
        'english_check',
        'MPE_Done',
        'MPE_Published',
        'hidden',
        'MPE_PublishDate',
        'MPE_FundedDate',
        'MPE_AllMoneyNeeded',
        'MPE_BeneficiaryName',
        'MPE_PartnerName',
        'MPE_PartnerName2',
        'MPE_MoneyDonated',
        'MPE_Video',
        'MPE_Donor',
        'NeedConversion',
        'DOB',
        'FamilyMembers',
        'projecttype_id',
        'WorkLoss',
        'Mobile',
        'latitude',
        'longtitude',
        'SubCategory_ID',
    ];

    protected function casts(): array
    {
        return [
            'MicroProject_ID' => 'integer',
            'MPE_City' => 'integer',
            'english_check' => 'integer',
            'hidden' => 'boolean',
            'MPE_PublishDate' => 'datetime',
            'MPE_FundedDate' => 'date',
            'MPE_AllMoneyNeeded' => 'integer',
            'MPE_MoneyDonated' => 'integer',
            'MPE_Donor' => 'integer',
            'DOB' => 'integer',
            'FamilyMembers' => 'integer',
            'projecttype_id' => 'integer',
            'WorkLoss' => 'integer',
            'SubCategory_ID' => 'integer',
        ];
    }

    /** @return BelongsTo<ProjectSubCategory, $this> */
    public function subCategoryID(): BelongsTo
    {
        return $this->belongsTo(ProjectSubCategory::class, 'SubCategory_ID', 'ID');
    }

    /** @return BelongsTo<ProjectType, $this> */
    public function projecttype(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'projecttype_id', 'id');
    }

    /** @return BelongsTo<Donor, $this> */
    public function mPEDonor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'MPE_Donor', 'id');
    }

    /** @return BelongsTo<Place, $this> */
    public function mPECity(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'MPE_City', 'id');
    }

    /** @return HasMany<Image, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class, 'project_id', 'id');
    }

    /** @return HasOne<ProjectContent, $this> */
    public function content(): HasOne
    {
        return $this->hasOne(ProjectContent::class, 'project_id', 'id');
    }

    /** @return HasMany<Success, $this> */
    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'project_id', 'id');
    }
}
