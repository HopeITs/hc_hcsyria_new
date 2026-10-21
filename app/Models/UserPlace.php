<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlace extends Model
{
    protected $table = 'place_user';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'place_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'place_id' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** @return BelongsTo<Place, $this> */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id', 'id');
    }
}
