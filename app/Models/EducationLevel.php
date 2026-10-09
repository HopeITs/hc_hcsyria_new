<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    protected $table = 'educationlevel';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_en',
    ];
}
