<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Initiative extends Model
{
    protected $fillable = [
        'logo',
        'video',
        'rank',
        'english_check',
        'online',
        'hidden',
        'what_category_id',
        'who_category_id',
        'sub_what_category_id',
        'place_id',
        'sz_center_id',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'english_check' => 'integer',
            'online' => 'integer',
            'hidden' => 'boolean',
            'what_category_id' => 'integer',
            'who_category_id' => 'integer',
            'sub_what_category_id' => 'integer',
            'place_id' => 'integer',
            'sz_center_id' => 'integer',
        ];
    }

    /** @return BelongsTo<WhoCategory, $this> */
    public function whoCategory(): BelongsTo
    {
        return $this->belongsTo(WhoCategory::class, 'who_category_id', 'id');
    }

    /** @return BelongsTo<WhatCategory, $this> */
    public function whatCategory(): BelongsTo
    {
        return $this->belongsTo(WhatCategory::class, 'what_category_id', 'id');
    }

    /** @return BelongsTo<SubWhatCategory, $this> */
    public function subWhatCategory(): BelongsTo
    {
        return $this->belongsTo(SubWhatCategory::class, 'sub_what_category_id', 'id');
    }

    /** @return BelongsTo<Place, $this> */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id', 'id');
    }

    /** @return BelongsTo<StudyzoneCenter, $this> */
    public function szCenter(): BelongsTo
    {
        return $this->belongsTo(StudyzoneCenter::class, 'sz_center_id', 'id');
    }

    /** @return HasMany<InitiativeDonor, $this> */
    public function initiativeDonors(): HasMany
    {
        return $this->hasMany(InitiativeDonor::class, 'initiative_id', 'id');
    }

    /** @return HasOne<InitiativeContent, $this> */
    public function content(): HasOne
    {
        return $this->hasOne(InitiativeContent::class, 'initiative_id', 'id');
    }

    /** @return HasMany<InitiativeImage, $this> */
    public function initiativeImages(): HasMany
    {
        return $this->hasMany(InitiativeImage::class, 'initiative_id', 'id');
    }

    /** @return HasMany<InitiativeImageRehabilitation, $this> */
    public function initiativeImageRehabilitations(): HasMany
    {
        return $this->hasMany(InitiativeImageRehabilitation::class, 'initiative_id', 'id');
    }

    /** @return HasMany<Testimony, $this> */
    public function testimonies(): HasMany
    {
        return $this->hasMany(Testimony::class, 'initiative_id', 'id');
    }
}
