<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Street extends Model
{
    protected $table = 'street';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_en',
        'place_id',
    ];

    protected function casts(): array
    {
        return [
            'place_id' => 'integer',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id', 'id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'street_id', 'id');
    }
}
