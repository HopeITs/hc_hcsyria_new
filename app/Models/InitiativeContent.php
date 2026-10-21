<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeContent extends Model
{
    protected $table = 'initiative_contents';

    protected $fillable = [
        'initiative_id',
        'title',
        'organization',
        'date_string',
        'description',
        'content',
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
