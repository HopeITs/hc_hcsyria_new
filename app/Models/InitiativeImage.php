<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeImage extends Model
{
    protected $table = 'initiative_images';

    protected $fillable = [
        'initiative_id',
        'path',
        'image_order',
    ];

    protected function casts(): array
    {
        return [
            'initiative_id' => 'integer',
            'image_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Initiative, $this> */
    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class, 'initiative_id', 'id');
    }
}
