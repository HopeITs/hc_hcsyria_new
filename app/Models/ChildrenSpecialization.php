<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildrenSpecialization extends Model
{
    protected $table = 'childrenspecializations';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_fr',
        'description',
        'description_fr',
        'logo',
    ];
}
