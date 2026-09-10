<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContent extends Model
{
    protected $table = 'projects_english';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'MicroProject_ID',
        'MPE_SuccessStory',
        'MPE_Name',
        'MPE_Suffering',
        'MPE_ExpSkill',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'MicroProject_ID' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
