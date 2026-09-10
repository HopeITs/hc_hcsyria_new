<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoDisc extends Model
{
    protected $table = 'info_disc';
    // This table has no primary key in the schema; Eloquent writes
    // and route-model binding will not work until one is added.
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'city',
        'facebook',
        'phone',
        'location',
        'email',
        'website',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'phone' => 'integer',
        ];
    }
}
