<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeImageRehabilitation extends Model
{
    protected $table = 'initiative_rehabilitation_images';

    protected $fillable = [
        'path_1',
        'path_2',
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
}
