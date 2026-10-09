<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Testimony extends Model
{
    protected $fillable = [
        'image_path',
        'initiative_id',
    ];

    protected function casts(): array
    {
        return [
            'initiative_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Initiative, $this> */
    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class, 'initiative_id', 'id');
    }

    /** @return HasOne<TestimonyContent, $this> */
    public function content(): HasOne
    {
        return $this->hasOne(TestimonyContent::class, 'testimony_id', 'id');
    }
}
