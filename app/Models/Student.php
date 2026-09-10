<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'firstname',
        'fathername',
        'lastname',
        'nationalnumber',
        'DOB',
        'gender_id',
        'religion_id',
        'email',
        'mobile',
        'whatsapp',
        'is_employed',
        'street_id',
        'educationfield_id',
        'educationlevel_id',
        'is_student',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'DOB' => 'integer',
            'gender_id' => 'integer',
            'religion_id' => 'integer',
            'is_employed' => 'integer',
            'street_id' => 'integer',
            'educationfield_id' => 'integer',
            'educationlevel_id' => 'integer',
            'is_student' => 'integer',
        ];
    }

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'street_id', 'id');
    }

    public function educationfield(): BelongsTo
    {
        return $this->belongsTo(EducationField::class, 'educationfield_id', 'id');
    }

    public function educationlevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'educationlevel_id', 'id');
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class, 'religion_id', 'id');
    }

    public function studentEntries(): HasMany
    {
        return $this->hasMany(StudentEntry::class, 'student_id', 'id');
    }
}
