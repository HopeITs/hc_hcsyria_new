<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HopeValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'quote',
        'quote_source',
        'name_fr',
        'description_fr',
        'quote_fr',
        'quote_source_fr',
        'image_path',
    ];
}
