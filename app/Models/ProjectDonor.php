<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDonor extends Model
{
    protected $table = 'project_donors';

    public $timestamps = false;

    protected $fillable = [
        'what_category_id',
        'donor_id',
        'isActive',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'what_category_id' => 'integer',
            'donor_id' => 'integer',
            'isActive' => 'integer',
            'rank' => 'integer',
        ];
    }
}
