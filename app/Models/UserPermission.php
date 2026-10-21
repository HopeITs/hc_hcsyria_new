<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $table = 'permission_user';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'permission_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'permission_id' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** @return BelongsTo<Permission, $this> */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }
}
