<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Age extends Model
{
    protected $table = 'age';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'Name',
        'Min',
        'Max',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'Min' => 'integer',
            'Max' => 'integer',
            'isActive' => 'integer',
        ];
    }
}
