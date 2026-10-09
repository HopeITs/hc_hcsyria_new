<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyzoneTypeDescription extends Model
{
    protected $table = 'studyzone_types_description';

    public $timestamps = false;

    protected $fillable = [
        'text_en',
        'text_fr',
        'type_id',
    ];

    protected function casts(): array
    {
        return [
            'type_id' => 'integer',
        ];
    }

    /** @return BelongsTo<StudyzoneType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(StudyzoneType::class, 'type_id', 'id');
    }
}
