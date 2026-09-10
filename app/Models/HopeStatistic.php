<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HopeStatistic extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_fr',
        'description',
        'description_fr',
        'stat1_num',
        'stat2_num',
        'stat1_text',
        'stat1_text_fr',
        'stat2_text',
        'stat2_text_fr',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'stat1_num' => 'float',
            'stat2_num' => 'float',
        ];
    }
}
