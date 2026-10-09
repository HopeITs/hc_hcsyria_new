<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
    ];

    /** @return HasMany<NewsTag, $this> */
    public function newsTags(): HasMany
    {
        return $this->hasMany(NewsTag::class, 'tag_id', 'id');
    }
}
