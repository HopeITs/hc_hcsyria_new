<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /** @return HasMany<UserPermission, $this> */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class, 'permission_id', 'id');
    }
}
