<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCenter extends Model
{
    protected $table = 'study_zone_center_user';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'studyzone_center_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'studyzone_center_id' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** @return BelongsTo<StudyzoneCenter, $this> */
    public function studyzoneCenter(): BelongsTo
    {
        return $this->belongsTo(StudyzoneCenter::class, 'studyzone_center_id', 'id');
    }
}
