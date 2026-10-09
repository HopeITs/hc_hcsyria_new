<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    protected $fillable = [
        'name',
        'online',
    ];

    protected function casts(): array
    {
        return [
            'online' => 'integer',
        ];
    }

    /** @return HasMany<News, $this> */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'edition_id', 'id');
    }
}
