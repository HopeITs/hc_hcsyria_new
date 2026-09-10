<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationField extends Model
{
    protected $table = 'educationfield';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_en',
        'name_en_abb',
        'educationdegree_id',
    ];

    protected function casts(): array
    {
        return [
            'educationdegree_id' => 'integer',
        ];
    }

    public function educationdegree(): BelongsTo
    {
        return $this->belongsTo(EducationDegree::class, 'educationdegree_id', 'id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'educationfield_id', 'id');
    }
}
