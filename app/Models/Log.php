<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $fillable = [
        'operation',
        'project_id',
        'user_id',
        'category',
        'seen',
        'previous_data',
        'current_data',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'user_id' => 'integer',
            'category' => 'integer',
            'seen' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
