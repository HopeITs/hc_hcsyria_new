<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_micro',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'is_micro' => 'integer',
            'rank' => 'integer',
        ];
    }

    /** @return HasMany<Initiative, $this> */
    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'place_id', 'id');
    }

    /** @return HasMany<News, $this> */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'place_id', 'id');
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'MPE_City', 'id');
    }

    /** @return HasMany<Street, $this> */
    public function streets(): HasMany
    {
        return $this->hasMany(Street::class, 'place_id', 'id');
    }

    /** @return HasMany<StudyzoneCenter, $this> */
    public function studyzoneCenters(): HasMany
    {
        return $this->hasMany(StudyzoneCenter::class, 'place_id', 'id');
    }

    /** @return HasMany<Success, $this> */
    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'place_id', 'id');
    }

    /** @return HasMany<UserPlace, $this> */
    public function userPlaces(): HasMany
    {
        return $this->hasMany(UserPlace::class, 'place_id', 'id');
    }
}
