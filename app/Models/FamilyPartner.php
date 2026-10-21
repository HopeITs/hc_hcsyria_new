<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyPartner extends Model
{
    protected $table = 'family_partners';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'location',
        'location_latitude',
        'location_longtitude',
    ];
}
