<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'mail',
        'active',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'integer',
        ];
    }
}
