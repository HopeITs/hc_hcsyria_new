<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationDegree extends Model
{
    protected $table = 'educationdegree';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_en',
    ];

    /** @return HasMany<EducationField, $this> */
    public function educationFields(): HasMany
    {
        return $this->hasMany(EducationField::class, 'educationdegree_id', 'id');
    }
}
