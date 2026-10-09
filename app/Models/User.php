<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
 * Hand-written, not generated: this model must extend Authenticatable.
 * scripts/generate-models.php skips it for that reason.
 *
 * Carried over from the legacy schema, which uses an int primary key rather
 * than Laravel's bigint and adds an `active` flag. Six tables hold foreign
 * keys against users.id, so the key type is kept as-is.
 *
 * The legacy application also had a second, unused App\User model. It is not
 * ported: config/auth.php resolved to App\Models\User.
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    /** @return HasMany<Log, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'user_id', 'id');
    }

    /** @return HasMany<UserCenter, $this> */
    public function userCenters(): HasMany
    {
        return $this->hasMany(UserCenter::class, 'user_id', 'id');
    }

    /** @return HasMany<UserPermission, $this> */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class, 'user_id', 'id');
    }

    /** @return HasMany<UserPlace, $this> */
    public function userPlaces(): HasMany
    {
        return $this->hasMany(UserPlace::class, 'user_id', 'id');
    }

    /** @return HasMany<UserRole, $this> */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id', 'id');
    }
}
