<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'abb_name',
        'logo',
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

    /** @return HasMany<InitiativeDonor, $this> */
    public function initiativeDonors(): HasMany
    {
        return $this->hasMany(InitiativeDonor::class, 'donor_id', 'id');
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'MPE_Donor', 'id');
    }
}
