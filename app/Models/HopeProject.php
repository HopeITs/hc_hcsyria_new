<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HopeProject extends Model
{
    protected $fillable = [
        'what_category_id',
        'name',
        'name_fr',
        'description',
        'description_fr',
        'field',
        'field_fr',
        'logo',
        'mediaType',
        'mediaURL',
    ];

    protected function casts(): array
    {
        return [
            'what_category_id' => 'integer',
        ];
    }

    public function hopeProjectMedia(): HasMany
    {
        return $this->hasMany(HopeProjectMedia::class, 'project_id', 'id');
    }
}
