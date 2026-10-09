<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'MPI_Path',
        'MicroProject_ID',
        'project_id',
        'image_order',
    ];

    protected function casts(): array
    {
        return [
            'MicroProject_ID' => 'integer',
            'project_id' => 'integer',
            'image_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
