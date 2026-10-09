<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Success extends Model
{
    protected $fillable = [
        'image_path',
        'inner_image_path',
        'inner_big_image_path',
        'video',
        'who_category_id',
        'what_category_id',
        'sub_what_category_id',
        'place_id',
        'sz_center_id',
        'project_id',
        'english_check',
        'online',
    ];

    protected function casts(): array
    {
        return [
            'who_category_id' => 'integer',
            'what_category_id' => 'integer',
            'sub_what_category_id' => 'integer',
            'place_id' => 'integer',
            'sz_center_id' => 'integer',
            'project_id' => 'integer',
            'english_check' => 'integer',
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

    /** @return BelongsTo<StudyzoneCenter, $this> */
    public function szCenter(): BelongsTo
    {
        return $this->belongsTo(StudyzoneCenter::class, 'sz_center_id', 'id');
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
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

    /** @return HasOne<SuccessContent, $this> */
    public function content(): HasOne
    {
        return $this->hasOne(SuccessContent::class, 'success_id', 'id');
    }
}
