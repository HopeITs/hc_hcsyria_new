<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscCourseType extends Model
{
    protected $table = 'disc_course_type';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_fr',
        'description',
        'description_fr',
        'logo',
    ];
}
