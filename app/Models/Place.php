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

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'place_id', 'id');
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'place_id', 'id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'MPE_City', 'id');
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class, 'place_id', 'id');
    }

    public function studyzoneCenters(): HasMany
    {
        return $this->hasMany(StudyzoneCenter::class, 'place_id', 'id');
    }

    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'place_id', 'id');
    }

    public function userPlaces(): HasMany
    {
        return $this->hasMany(UserPlace::class, 'place_id', 'id');
    }
}
