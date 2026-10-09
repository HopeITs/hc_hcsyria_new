<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyzoneType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name_en',
        'name_fr',
        'image',
    ];

    /** @return HasMany<StudyzoneTypeDescription, $this> */
    public function studyzoneTypeDescriptions(): HasMany
    {
        return $this->hasMany(StudyzoneTypeDescription::class, 'type_id', 'id');
    }
}
