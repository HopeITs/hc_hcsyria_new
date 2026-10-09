<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubWhatCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'what_category_id',
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'what_category_id' => 'integer',
        ];
    }

    /** @return BelongsTo<WhatCategory, $this> */
    public function whatCategory(): BelongsTo
    {
        return $this->belongsTo(WhatCategory::class, 'what_category_id', 'id');
    }

    /** @return HasMany<Initiative, $this> */
    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'sub_what_category_id', 'id');
    }

    /** @return HasMany<Success, $this> */
    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'sub_what_category_id', 'id');
    }
}
