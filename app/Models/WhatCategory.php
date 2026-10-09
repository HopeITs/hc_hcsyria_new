<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /** @return HasMany<Initiative, $this> */
    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'what_category_id', 'id');
    }

    /** @return HasMany<SubWhatCategory, $this> */
    public function subWhatCategories(): HasMany
    {
        return $this->hasMany(SubWhatCategory::class, 'what_category_id', 'id');
    }

    /** @return HasMany<Success, $this> */
    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'what_category_id', 'id');
    }
}
