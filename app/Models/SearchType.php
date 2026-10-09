<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchType extends Model
{
    protected $table = 'searchtype';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'logo',
    ];
}
