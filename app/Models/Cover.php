<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cover extends Model
{
    protected $table = 'backgrounds';

    protected $fillable = [
        'what_category_id',
        'image_path',
        'image_order',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'what_category_id' => 'integer',
            'image_order' => 'integer',
        ];
    }
}
