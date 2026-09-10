<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YouthProfile extends Model
{
    protected $table = 'youthprofiles';
    // This table has no primary key in the schema; Eloquent writes
    // and route-model binding will not work until one is added.
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'fullname_ar',
        'firstname',
        'fathername',
        'lastname',
        'link',
        'dob',
        'city',
        'position',
        'email',
        'mobile',
        'whatsapp',
        'facebook',
        'image_path',
        'gender',
    ];

    protected function casts(): array
    {
        return [
            'mobile' => 'integer',
            'whatsapp' => 'integer',
        ];
    }
}
