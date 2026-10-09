<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessContent extends Model
{
    protected $table = 'successes_english';

    protected $fillable = [
        'success_id',
        'title',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'success_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Success, $this> */
    public function success(): BelongsTo
    {
        return $this->belongsTo(Success::class, 'success_id', 'id');
    }
}
