<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HopeProjectMedia extends Model
{
    protected $table = 'hope_project_media';

    protected $fillable = [
        'project_id',
        'mediaType',
        'mediaURL',
        'mediaOrder',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'mediaOrder' => 'integer',
        ];
    }

    /** @return BelongsTo<HopeProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(HopeProject::class, 'project_id', 'id');
    }
}
