<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RootsOfHope extends Model
{
    protected $table = 'roots_of_hope';

    protected $fillable = [
        'title',
        'image_path',
        'file_path',
        'url',
        'online',
    ];

    protected function casts(): array
    {
        return [
            'online' => 'integer',
        ];
    }
}
