<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestimonyContent extends Model
{
    protected $table = 'testimonies_english';
    public $timestamps = false;

    protected $fillable = [
        'testimony_id',
        'name',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'testimony_id' => 'integer',
        ];
    }

    public function testimony(): BelongsTo
    {
        return $this->belongsTo(Testimony::class, 'testimony_id', 'id');
    }
}
