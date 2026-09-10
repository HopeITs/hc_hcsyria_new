<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    protected $table = 'educationlevel';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_en',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'educationlevel_id', 'id');
    }
}
