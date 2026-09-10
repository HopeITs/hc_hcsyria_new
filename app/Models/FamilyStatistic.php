<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyStatistic extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_fr',
        'statis_num',
        'logo',
        'family_statis_type_id',
    ];

    protected function casts(): array
    {
        return [
            'statis_num' => 'float',
            'family_statis_type_id' => 'integer',
        ];
    }

    public function familyStatisType(): BelongsTo
    {
        return $this->belongsTo(FamilyStatisType::class, 'family_statis_type_id', 'id');
    }
}
