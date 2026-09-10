<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profile';
    public $timestamps = false;

    protected $fillable = [
        'image_path',
        'belong_to',
        'rank',
        'first_name',
        'last_name',
        'position',
        'facebook_link',
        'email',
        'linkedin_link',
        'phone',
        'inactive',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
        ];
    }
}
