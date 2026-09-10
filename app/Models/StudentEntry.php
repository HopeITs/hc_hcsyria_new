<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEntry extends Model
{
    protected $fillable = [
        'user_id',
        'student_id',
        'center_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'student_id' => 'integer',
            'center_id' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(StudyzoneCenter::class, 'center_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
