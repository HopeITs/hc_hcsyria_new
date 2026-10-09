<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhoCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    /** @return HasMany<Initiative, $this> */
    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'who_category_id', 'id');
    }

    /** @return HasMany<Success, $this> */
    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'who_category_id', 'id');
    }
}
