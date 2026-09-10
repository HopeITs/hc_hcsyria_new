<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyStatisType extends Model
{
    protected $table = 'family_statis_type';
    public $timestamps = false;

    protected $fillable = [
        'type_name',
    ];

    public function familyStatistics(): HasMany
    {
        return $this->hasMany(FamilyStatistic::class, 'family_statis_type_id', 'id');
    }
}
